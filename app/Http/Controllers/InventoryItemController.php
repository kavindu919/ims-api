<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\InventoryItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Services\StatusTransitionService;


class InventoryItemController extends Controller
{



    public function getAllInventoryItems(Request $request)
    {
        try {
            $request->validate([
                'search'           => 'nullable|string|max:255',
                'status'           => 'nullable|in:in_store,borrowed,damaged,missing',
                'storage_place_id' => 'nullable|integer|exists:storage_places,id',
                'sortBy'           => 'nullable|in:name,code,quantity,created_at',
                'sortOrder'        => 'nullable|in:asc,desc',
                'page'             => 'nullable|integer|min:1',
                'limit'            => 'nullable|integer|min:1|max:100',
            ]);

            $search          = $request->search ?? '';
            $sortBy          = $request->sortBy ?? 'name';
            $sortOrder       = $request->sortOrder ?? 'asc';
            $page            = $request->page ?? 1;
            $limit           = $request->limit ?? 20;

            $items = InventoryItem::with(['storagePlace:id,name,cupboard_id', 'storagePlace.cupboard:id,name'])
                ->select('id', 'name', 'code', 'quantity', 'serial_number', 'image_path', 'description', 'storage_place_id', 'status', 'created_at')
                ->where(function ($q) use ($search) {
                    $q->where('name', 'ilike', "%{$search}%")
                        ->orWhere('code', 'ilike', "%{$search}%");
                })
                ->when($request->status, fn($q) => $q->where('status', $request->status))
                ->when($request->storage_place_id, fn($q) => $q->where('storage_place_id', $request->storage_place_id))
                ->orderBy($sortBy, $sortOrder)
                ->paginate($limit, ['*'], 'page', $page);

            return response()->json([
                'success' => true,
                'message' => 'Inventory items retrieved successfully.',
                'items'   => $items->items(),
                'meta'    => [
                    'total' => $items->total(),
                    'page'  => $items->currentPage(),
                    'limit' => $items->perPage(),
                ],
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['success' => false, 'message' => $e->errors()], 422);
        } catch (\Throwable $th) {
            Log::error('Fetching inventory items failed: ' . $th->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch inventory items, please try again later.'
            ], 500);
        }
    }

    public function createInventoryItem(Request $request)
    {
        try {
            $data = $request->validate([
                'name'             => 'required|string|max:255',
                'code'             => 'required|string|unique:inventory_items,code',
                'quantity'         => 'required|integer|min:0',
                'serial_number'    => 'nullable|string',
                'description'      => 'nullable|string',
                'storage_place_id' => 'required|exists:storage_places,id',
                'status'           => 'required|in:in_store,borrowed,damaged,missing',
                'image'            => 'nullable|file|image|max:2048',
            ], [
                'name.required' => 'The item name is required.',
                'name.string' => 'The item name must be text.',
                'name.max' => 'The item name must not exceed 255 characters.',
                'code.required' => 'The item code is required.',
                'code.unique' => 'This item code already exists.',
                'quantity.required' => 'The quantity is required.',
                'quantity.integer' => 'The quantity must be a number.',
                'quantity.min' => 'The quantity cannot be negative.',
                'storage_place_id.required' => 'The storage place is required.',
                'storage_place_id.exists' => 'The selected storage place does not exist.',
                'status.required' => 'The status is required.',
                'status.in' => 'The status must be in_store, borrowed, damaged, or missing.',
                'image.image' => 'The file must be an image.',
                'image.max' => 'The image must not exceed 2MB.',
            ]);

            if ($request->hasFile('image')) {
                $data['image_path'] = $request->file('image')->store('items', 'public');
            }
            unset($data['image']);

            $item = InventoryItem::create($data);

            ActivityLog::log('item_created', $item, null, $item->toArray());

            return response()->json([
                'success' => true,
                'message' => 'Inventory item created successfully.',
            ], 201);
        } catch (\Throwable $th) {
            Log::error('Creating inventory item failed: ' . $th->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to create inventory item, please try again later.'
            ], 500);
        }
    }


    public function getInventoryItem(Request $request)
    {
        try {
            $id = $request->id;

            $item = InventoryItem::with(['storagePlace.cupboard', 'borrowRecords'])->find($id);

            if (!$item) {
                return response()->json([
                    'success' => false,
                    'message' => 'Inventory item not found.'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Inventory item retrieved successfully.',
                'item' => $item,
            ], 200);
        } catch (\Throwable $th) {
            Log::error('Fetching inventory item failed: ' . $th->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch inventory item, please try again later.'
            ], 500);
        }
    }

    public function updateInventoryItem(Request $request)
    {
        try {
            $id = $request->id;

            $item = InventoryItem::find($id);

            if (!$item) {
                return response()->json([
                    'success' => false,
                    'message' => 'Inventory item not found.'
                ], 404);
            }

            $data = $request->validate([
                'name'             => 'sometimes|string|max:255',
                'code'             => 'sometimes|string|unique:inventory_items,code,' . $id,
                'serial_number'    => 'nullable|string',
                'description'      => 'nullable|string',
                'storage_place_id' => 'sometimes|exists:storage_places,id',
                'image'            => 'nullable|file|image|max:2048',
            ], [
                'name.string' => 'The item name must be text.',
                'name.max' => 'The item name must not exceed 255 characters.',
                'code.unique' => 'This item code already exists.',
                'storage_place_id.exists' => 'The selected storage place does not exist.',
                'image.image' => 'The file must be an image.',
                'image.max' => 'The image must not exceed 2MB.',
            ]);

            $oldValues = $item->only(['name', 'code', 'serial_number', 'description', 'storage_place_id']);

            if ($request->hasFile('image')) {
                if ($item->image_path) {
                    Storage::disk('public')->delete($item->image_path);
                }
                $data['image_path'] = $request->file('image')->store('items', 'public');
            }
            unset($data['image']);

            $item->update($data);

            ActivityLog::log(
                'item_updated',
                $item,
                $oldValues,
                $item->fresh()->only(array_keys($oldValues))
            );

            return response()->json([
                'success' => true,
                'message' => 'Inventory item updated successfully.',
            ], 200);
        } catch (\Throwable $th) {
            Log::error('Updating inventory item failed: ' . $th->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to update inventory item, please try again later.'
            ], 500);
        }
    }

    public function deleteInventoryItem(Request $request)
    {
        try {
            $id = $request->id;

            $item = InventoryItem::find($id);

            if (!$item) {
                return response()->json([
                    'success' => false,
                    'message' => 'Inventory item not found.'
                ], 404);
            }

            if ($item->activeBorrows()->count() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete item. It has active borrow records.',
                ], 422);
            }

            if ($item->image_path) {
                Storage::disk('public')->delete($item->image_path);
            }

            $item->delete();

            return response()->json([
                'success' => true,
                'message' => 'Inventory item deleted successfully.',
            ], 200);
        } catch (\Throwable $th) {
            Log::error('Deleting inventory item failed: ' . $th->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete inventory item, please try again later.'
            ], 500);
        }
    }

    public function adjustItemQuantity(Request $request)
    {
        try {
            $id = $request->id;

            $item = InventoryItem::find($id);

            if (!$item) {
                return response()->json([
                    'success' => false,
                    'message' => 'Inventory item not found.'
                ], 404);
            }

            $data = $request->validate([
                'type'   => 'required|in:increment,decrement',
                'amount' => 'required|integer|min:1',
                'reason' => 'nullable|string|max:255',
            ], [
                'type.required' => 'The adjustment type is required.',
                'type.in' => 'The type must be increment or decrement.',
                'amount.required' => 'The amount is required.',
                'amount.integer' => 'The amount must be a number.',
                'amount.min' => 'The amount must be at least 1.',
                'reason.string' => 'The reason must be text.',
                'reason.max' => 'The reason must not exceed 255 characters.',
            ]);

            DB::transaction(function () use ($data, $item) {
                $locked = InventoryItem::lockForUpdate()->findOrFail($item->id);
                $oldQty = $locked->quantity;

                if ($data['type'] === 'decrement') {
                    if ($locked->quantity < $data['amount']) {
                        throw new \Exception(
                            "Cannot decrement by {$data['amount']}. Only {$locked->quantity} in stock."
                        );
                    }
                    $locked->quantity -= $data['amount'];
                } else {
                    $locked->quantity += $data['amount'];
                }

                $locked->save();

                ActivityLog::log(
                    'quantity_changed',
                    $locked,
                    ['quantity' => $oldQty],
                    ['quantity' => $locked->quantity, 'reason' => $data['reason'] ?? null]
                );
            });

            return response()->json([
                'success' => true,
                'message' => 'Quantity updated successfully.',
            ], 200);
        } catch (\Exception $e) {
            Log::error('Adjusting quantity failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => $e->getMessage() ?: 'Failed to adjust quantity, please try again later.'
            ], 500);
        }
    }

    public function changeItemStatus(Request $request)
    {
        try {
            $id = $request->id;
            $item = InventoryItem::find($id);

            if (!$item) {
                return response()->json([
                    'success' => false,
                    'message' => 'Inventory item not found.'
                ], 404);
            }

            $request->validate([
                'status' => 'required|in:in_store,borrowed,damaged,missing',
            ]);

            $allowedTransitions = [
                'in_store' => ['borrowed', 'damaged', 'missing'],
                'borrowed' => ['in_store', 'damaged', 'missing'],
                'damaged'  => ['in_store', 'missing'],
                'missing'  => ['in_store'],
            ];

            $currentStatus = $item->status;
            $newStatus = $request->status;

            if (!in_array($newStatus, $allowedTransitions[$currentStatus])) {
                return response()->json([
                    'success' => false,
                    'message' => "Cannot transition from '{$currentStatus}' to '{$newStatus}'. Allowed: " . implode(', ', $allowedTransitions[$currentStatus])
                ], 422);
            }

            $oldValue = ['status' => $currentStatus];
            $item->update(['status' => $newStatus]);

            ActivityLog::log('status_changed', $item, $oldValue, ['status' => $newStatus]);

            return response()->json([
                'success' => true,
                'message' => 'Status updated successfully.',
            ], 200);
        } catch (\Throwable $th) {
            Log::error('Changing status failed: ' . $th->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to change status, please try again later.'
            ], 500);
        }
    }
}
