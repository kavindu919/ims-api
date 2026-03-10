<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Model as EloquentModel;

class ActivityLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'action',
        'subject_type',
        'subject_id',
        'old_value',
        'new_value',
    ];

    protected $casts = [
        'old_value'  => 'array',
        'new_value'  => 'array',
        'created_at' => 'datetime',
    ];

    public static function log(
        string $action,
        Model  $subject,
        ?array $oldValue,
        ?array $newValue
    ): void {
        static::create([
            'user_id'      => auth()->id(),
            'action'       => $action,
            'subject_type' => get_class($subject),
            'subject_id'   => $subject->getKey(),
            'old_value'    => $oldValue,
            'new_value'    => $newValue,
        ]);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
