<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\BorrowRecord;
use App\Models\InventoryItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class BorrowController extends Controller
{
    public function getAllBorrowRecords(Request $request)
    {
        try {
            $query = BorrowRecord::with(['item:id,name,code', 'createdBy:id,name'])
                ->orderBy('created_at', 'desc');

            if ($request->status) {
                $query->where('status', $request->status);
            }

            $records = $query->paginate(20);

            return response()->json([
                'success' => true,
                'message' => 'Borrow records retrieved successfully.',
                'records' => $records,
            ], 200);
        } catch (\Throwable $th) {
            Log::error('Fetching borrow records failed: ' . $th->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch borrow records, please try again later.'
            ], 500);
        }
    }

    public function createBorrowRecord(Request $request)
    {
        try {
            $data = $request->validate([
                'item_id'              => 'required|exists:inventory_items,id',
                'borrower_name'        => 'required|string|max:255',
                'contact'              => 'required|string|max:255',
                'borrow_date'          => 'required|date',
                'expected_return_date' => 'required|date|after_or_equal:borrow_date',
                'quantity_borrowed'    => 'required|integer|min:1',
                'notes'                => 'nullable|string',
            ], [
                'item_id.required' => 'The item is required.',
                'item_id.exists' => 'The selected item does not exist.',
                'borrower_name.required' => 'The borrower name is required.',
                'borrower_name.string' => 'The borrower name must be text.',
                'borrower_name.max' => 'The borrower name must not exceed 255 characters.',
                'contact.required' => 'The contact information is required.',
                'contact.string' => 'The contact must be text.',
                'contact.max' => 'The contact must not exceed 255 characters.',
                'borrow_date.required' => 'The borrow date is required.',
                'borrow_date.date' => 'The borrow date must be a valid date.',
                'expected_return_date.required' => 'The expected return date is required.',
                'expected_return_date.date' => 'The expected return date must be a valid date.',
                'expected_return_date.after_or_equal' => 'The expected return date must be after or equal to borrow date.',
                'quantity_borrowed.required' => 'The quantity is required.',
                'quantity_borrowed.integer' => 'The quantity must be a number.',
                'quantity_borrowed.min' => 'The quantity must be at least 1.',
                'notes.string' => 'The notes must be text.',
            ]);

            $borrow = DB::transaction(function () use ($data) {
                $item = InventoryItem::lockForUpdate()->findOrFail($data['item_id']);

                if ($item->quantity < $data['quantity_borrowed']) {
                    throw new \Exception(
                        "Insufficient stock. Available: {$item->quantity}, Requested: {$data['quantity_borrowed']}"
                    );
                }

                $item->decrement('quantity', $data['quantity_borrowed']);

                if ($item->fresh()->quantity === 0) {
                    $item->update(['status' => 'borrowed']);
                }

                $borrow = BorrowRecord::create([
                    ...$data,
                    'status'     => 'borrowed',
                    'created_by' => auth()->id(),
                ]);

                ActivityLog::log('borrowed', $borrow, null, [
                    'item_name'         => $item->name,
                    'borrower_name'     => $borrow->borrower_name,
                    'quantity_borrowed' => $borrow->quantity_borrowed,
                ]);

                return $borrow;
            });

            return response()->json([
                'success' => true,
                'message' => 'Borrow record created successfully.',
                'borrow' => $borrow->load(['item:id,name,code', 'createdBy:id,name']),
            ], 201);
        } catch (\Exception $e) {
            Log::error('Creating borrow record failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => $e->getMessage() ?: 'Failed to create borrow record, please try again later.'
            ], 500);
        }
    }

    public function getBorrowRecord(Request $request)
    {
        try {
            $id = $request->id;

            $borrowRecord = BorrowRecord::with(['item.storagePlace.cupboard', 'createdBy:id,name'])->find($id);

            if (!$borrowRecord) {
                return response()->json([
                    'success' => false,
                    'message' => 'Borrow record not found.'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Borrow record retrieved successfully.',
                'borrow' => $borrowRecord,
            ], 200);
        } catch (\Throwable $th) {
            Log::error('Fetching borrow record failed: ' . $th->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch borrow record, please try again later.'
            ], 500);
        }
    }

    public function returnBorrowedItem(Request $request)
    {
        try {
            $id = $request->id;

            $borrowRecord = BorrowRecord::find($id);

            if (!$borrowRecord) {
                return response()->json([
                    'success' => false,
                    'message' => 'Borrow record not found.'
                ], 404);
            }

            if ($borrowRecord->status === 'returned') {
                return response()->json([
                    'success' => false,
                    'message' => 'This item has already been returned.'
                ], 422);
            }

            $request->validate([
                'notes' => 'nullable|string'
            ], [
                'notes.string' => 'The notes must be text.'
            ]);

            DB::transaction(function () use ($request, $borrowRecord) {
                $item = InventoryItem::lockForUpdate()->findOrFail($borrowRecord->item_id);

                $oldBorrow = $borrowRecord->toArray();

                $item->increment('quantity', $borrowRecord->quantity_borrowed);

                if ($item->fresh()->status === 'borrowed') {
                    $item->update(['status' => 'in_store']);
                }

                $borrowRecord->update([
                    'status'      => 'returned',
                    'return_date' => now()->toDateString(),
                    'notes'       => $request->notes ?? $borrowRecord->notes,
                ]);

                ActivityLog::log(
                    'returned',
                    $borrowRecord,
                    $oldBorrow,
                    $borrowRecord->fresh()->toArray()
                );
            });

            return response()->json([
                'success' => true,
                'message' => 'Item returned successfully.',
                'borrow'  => $borrowRecord->fresh()->load(['item:id,name,code', 'createdBy:id,name']),
            ], 200);
        } catch (\Throwable $th) {
            Log::error('Returning item failed: ' . $th->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to return item, please try again later.'
            ], 500);
        }
    }
}
