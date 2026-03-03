<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FlashcardSet extends Model
{
    use HasUuids;

    protected $fillable = ['title', 'lesson_id', 'cards', 'status'];

    protected $casts = [
        'cards' => 'array',
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
