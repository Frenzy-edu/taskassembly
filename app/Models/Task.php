<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Task extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_COMPLETED = 'completed';

    protected static function booted(): void
    {
        static::creating(function (Task $task): void {
            $task->position = (static::query()->min('position') ?? 0) - 1;
        });

        static::saving(function (Task $task): void {
            if ($task->exists && ! $task->isDirty('title')) {
                return;
            }

            $baseSlug = substr(Str::slug($task->title) ?: 'task', 0, 240);
            $slug = $baseSlug;
            $suffix = 2;

            while (static::query()
                ->where('slug', $slug)
                ->when($task->exists, fn ($query) => $query->where('id', '!=', $task->getKey()))
                ->exists()) {
                $suffixText = '-'.$suffix++;
                $slug = substr($baseSlug, 0, 255 - strlen($suffixText)).$suffixText;
            }

            $task->slug = $slug;
        });
    }

    protected $fillable = [
        'title',
        'description',
        'status',
        'start_date',
        'due_date',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'due_date' => 'date',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function resolveRouteBinding($value, $field = null)
    {
        $field ??= $this->getRouteKeyName();
        $task = $this->resolveRouteBindingQuery($this, $value, $field)->first();

        if ($task !== null || $field !== 'slug' || ! ctype_digit((string) $value)) {
            return $task;
        }

        return static::query()->whereKey($value)->first();
    }
}
