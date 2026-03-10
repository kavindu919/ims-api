<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class InventoryItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'quantity',
        'serial_number',
        'image_path',
        'description',
        'storage_place_id',
        'status',
    ];

    protected $casts = [
        'quantity' => 'integer',
    ];

    public function storagePlace()
    {
        return $this->belongsTo(StoragePlace::class);
    }

    public function borrowRecords()
    {
        return $this->hasMany(BorrowRecord::class, 'item_id');
    }

    public function activeBorrows()
    {
        return $this->hasMany(BorrowRecord::class, 'item_id')
                    ->where('status', 'borrowed');
    }
}
