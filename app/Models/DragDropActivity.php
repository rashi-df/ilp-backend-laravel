<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DragDropActivity extends Model
{
    use HasUuids;

    protected $table = 'drag_drop_activities';

    protected $fillable = ['title', 'lesson_id', 'type', 'items', 'instructions', 'status'];

    protected $casts = [
        'items' => 'array',
    ];

    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }
}
