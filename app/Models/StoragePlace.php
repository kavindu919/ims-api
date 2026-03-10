<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class StoragePlace extends Model
{
    use HasFactory;

    protected $fillable = ['cupboard_id', 'name', 'description'];

    public function cupboard()
    {
        return $this->belongsTo(Cupboard::class);
    }

    public function items()
    {
        return $this->hasMany(InventoryItem::class);
    }
}
