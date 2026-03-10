<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Cupboard extends Model
{
    use HasFactory;
    
    protected $fillable = ['name', 'location', 'description'];

    public function storagePlaces()
    {
        return $this->hasMany(StoragePlace::class);
    }
}
