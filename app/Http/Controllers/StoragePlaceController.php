<?php

namespace App\Http\Controllers;

use App\Models\StoragePlace;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

class StoragePlaceController extends Controller
{
    public function getAllStoragePlaces(Request $request)
    {
        try {
            $query = StoragePlace::with('cupboard:id,name')->withCount('items');

            if ($request->has('cupboard_id')) {
                $query->where('cupboard_id', $request->cupboard_id);
            }

            $storagePlaces = $query->orderBy('name')->get();

            return response()->json([
                'success' => true,
                'message' => 'Storage places retrieved successfully.',
                'storage_places' => $storagePlaces,
            ], 200);
        } catch (\Throwable $th) {
            Log::error('Fetching storage places failed: ' . $th->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch storage places, please try again later.'
            ], 500);
        }
    }

    public function createStoragePlace(Request $request)
    {
        try {
            $data = $request->validate([
                'cupboard_id' => 'required|exists:cupboards,id',
                'name'        => 'required|string',
                'description' => 'nullable|string',
            ], [
                'cupboard_id.required' => 'The cupboard is required.',
                'cupboard_id.exists' => 'The selected cupboard does not exist.',
                'name.required' => 'The storage place name is required.',
                'name.string' => 'The storage place name must be text.',
                'description.string' => 'The description must be text.',
            ]);

            $exists = StoragePlace::where('cupboard_id', $data['cupboard_id'])
                ->where('name', $data['name'])
                ->exists();

            if ($exists) {
                return response()->json([
                    'success' => false,
                    'message' => 'A storage place with this name already exists in this cupboard.',
                ], 422);
            }

            $storagePlace = StoragePlace::create($data)->load('cupboard:id,name');

            return response()->json([
                'success' => true,
                'message' => 'Storage place created successfully.',
                'storage_place' => $storagePlace,
            ], 201);
        } catch (\Throwable $th) {
            Log::error('Creating storage place failed: ' . $th->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to create storage place, please try again later.'
            ], 500);
        }
    }

    public function getStoragePlace(Request $request)
    {
        try {
            $id = $request->id;

            $storagePlace = StoragePlace::with(['cupboard:id,name', 'items'])->find($id);

            if (!$storagePlace) {
                return response()->json([
                    'success' => false,
                    'message' => 'Storage place not found.'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Storage place retrieved successfully.',
                'storage_place' => $storagePlace,
            ], 200);
        } catch (\Throwable $th) {
            Log::error('Fetching storage place failed: ' . $th->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch storage place, please try again later.'
            ], 500);
        }
    }

    public function updateStoragePlace(Request $request)
    {
        try {
            $id = $request->id;

            $storagePlace = StoragePlace::find($id);

            if (!$storagePlace) {
                return response()->json([
                    'success' => false,
                    'message' => 'Storage place not found.'
                ], 404);
            }

            $data = $request->validate([
                'cupboard_id' => 'sometimes|exists:cupboards,id',
                'name'        => 'sometimes|string',
                'description' => 'nullable|string',
            ], [
                'cupboard_id.exists' => 'The selected cupboard does not exist.',
                'name.string' => 'The storage place name must be text.',
                'description.string' => 'The description must be text.',
            ]);

            $storagePlace->update($data);

            return response()->json([
                'success' => true,
                'message' => 'Storage place updated successfully.',
                'storage_place' => $storagePlace->load('cupboard:id,name'),
            ], 200);
        } catch (\Throwable $th) {
            Log::error('Updating storage place failed: ' . $th->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to update storage place, please try again later.'
            ], 500);
        }
    }

    public function deleteStoragePlace(Request $request)
    {
        try {
            $id = $request->id;

            $storagePlace = StoragePlace::find($id);

            if (!$storagePlace) {
                return response()->json([
                    'success' => false,
                    'message' => 'Storage place not found.'
                ], 404);
            }

            if ($storagePlace->items()->count() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete storage place. Please move all items from this place first.',
                ], 422);
            }

            $storagePlace->delete();

            return response()->json([
                'success' => true,
                'message' => 'Storage place deleted successfully.',
            ], 200);
        } catch (\Throwable $th) {
            Log::error('Deleting storage place failed: ' . $th->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete storage place, please try again later.'
            ], 500);
        }
    }
}
