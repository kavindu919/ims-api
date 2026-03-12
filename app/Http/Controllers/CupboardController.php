<?php

namespace App\Http\Controllers;

use App\Models\Cupboard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CupboardController extends Controller
{
    public function getAllCupboards(Request $request)
    {
        try {
            $request->validate([
                'search'    => 'nullable|string|max:255',
                'sortBy'    => 'nullable|in:name,location,created_at',
                'sortOrder' => 'nullable|in:asc,desc',
                'page'      => 'nullable|integer|min:1',
                'limit'     => 'nullable|integer|min:1|max:100',
            ]);

            $search    = $request->search ?? '';
            $sortBy    = $request->sortBy ?? 'name';
            $sortOrder = $request->sortOrder ?? 'asc';
            $page      = $request->page ?? 1;
            $limit     = $request->limit ?? 20;

            $cupboards = Cupboard::withCount('storagePlaces')
                ->where('name', 'ilike', "%{$search}%")
                ->orderBy($sortBy, $sortOrder)
                ->paginate($limit, ['*'], 'page', $page);

            return response()->json([
                'success'   => true,
                'message'   => 'Cupboards retrieved successfully.',
                'cupboards' => $cupboards->items(),
                'meta'      => [
                    'total' => $cupboards->total(),
                    'page'  => $cupboards->currentPage(),
                    'limit' => $cupboards->perPage(),
                ],
            ], 200);
        } catch (\Throwable $th) {
            Log::error('Fetching cupboards failed: ' . $th->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch cupboards, please try again later.'
            ], 500);
        }
    }


    public function createCupboards(Request $request)
    {
        try {
            $data = $request->validate([
                'name'        => 'required|string|unique:cupboards,name',
                'location'    => 'nullable|string',
                'description' => 'nullable|string',
            ], [
                'name.required' => 'The cupboard name is required.',
                'name.string'   => 'The cupboard name must be text.',
                'name.unique'   => 'This cupboard name already exists. Please choose a different name.',
                'location.string' => 'The location must be text.',
                'description.string' => 'The description must be text.',
            ]);

            $cupboard = Cupboard::create($data);

            return response()->json([
                'success'   => true,
                'message'   => 'Cupboard created successfully.',
                'cupboard' => $cupboard,
            ], 201);
        } catch (\Throwable $th) {
            Log::error('Creating cupboard failed: ' . $th->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to create cupboard, please try again later.'
            ], 500);
        }
    }

    public function getCupboardWithPlaces(Request $request)
    {
        try {
            $id = $request->id;

            $cupboard = Cupboard::find($id);

            if (!$cupboard) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cupboard not found.'
                ], 404);
            }

            $cupboard->load('storagePlaces');

            return response()->json([
                'success'   => true,
                'message'   => 'Cupboard retrieved successfully.',
                'cupboard' => $cupboard,
            ], 200);
        } catch (\Throwable $th) {
            Log::error('Fetching cupboard failed: ' . $th->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch cupboard, please try again later.'
            ], 500);
        }
    }

    public function updateCupboard(Request $request)
    {
        try {
            $id = $request->id;

            $cupboard = Cupboard::find($id);

            if (!$cupboard) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cupboard not found.'
                ], 404);
            }

            $data = $request->validate([
                'name'        => 'sometimes|string|unique:cupboards,name,' . $id,
                'location'    => 'nullable|string',
                'description' => 'nullable|string',
            ], [
                'name.string' => 'The cupboard name must be text.',
                'name.unique' => 'This cupboard name already exists. Please choose a different name.',
                'location.string' => 'The location must be text.',
                'description.string' => 'The description must be text.',
            ]);

            $cupboard->update($data);

            return response()->json([
                'success' => true,
                'message' => 'Cupboard updated successfully.',
                'cupboard' => $cupboard,
            ], 200);
        } catch (\Throwable $th) {
            Log::error('Updating cupboard failed: ' . $th->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to update cupboard, please try again later.'
            ], 500);
        }
    }

    public function deleteCupboard(Request $request)
    {
        try {
            $id = $request->id;

            $cupboard = Cupboard::find($id);

            if (!$cupboard) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cupboard not found.'
                ], 404);
            }

            if ($cupboard->storagePlaces()->count() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete cupboard. Please remove all storage places first.',
                ], 422);
            }

            $cupboard->delete();

            return response()->json([
                'success' => true,
                'message' => 'Cupboard deleted successfully.',
            ], 200);
        } catch (\Throwable $th) {
            Log::error('Deleting cupboard failed: ' . $th->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete cupboard, please try again later.'
            ], 500);
        }
    }
}
