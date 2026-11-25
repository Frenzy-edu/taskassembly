<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_COMPLETED = 'completed';

    protected static function booted(): void
    {
        static::creating(function (Task $task): void {
            $task->position = (static::query()->min('position') ?? 0) - 1;
        });
    }

    protected $fillable = [
        'title',
        'description',
        'status',
    ];
}
