<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BorrowRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'item_id',
        'borrower_name',
        'contact',
        'borrow_date',
        'expected_return_date',
        'return_date',
        'quantity_borrowed',
        'status',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'borrow_date'          => 'date',
        'expected_return_date' => 'date',
        'return_date'          => 'date',
    ];

    public function item()
    {
        return $this->belongsTo(InventoryItem::class, 'item_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
