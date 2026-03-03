<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Lesson extends Model
{
    use HasUuids;

    protected $fillable = ['title', 'module_id', 'order', 'type', 'duration', 'content'];

    protected $casts = [
        'order' => 'integer',
        'duration' => 'integer',
        'content' => 'array',
    ];

    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }

    public function video(): HasOne
    {
        return $this->hasOne(Video::class);
    }

    public function quiz(): HasOne
    {
        return $this->hasOne(Quiz::class);
    }

    public function flashcardSet(): HasOne
    {
        return $this->hasOne(FlashcardSet::class);
    }

    public function dragDropActivity(): HasOne
    {
        return $this->hasOne(DragDropActivity::class);
    }

    public function homework(): HasMany
    {
        return $this->hasMany(Homework::class);
    }
}
