<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use HasUuids;

    protected $fillable = ['name', 'icon'];

    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    public function courses(): HasMany
    {
        return $this->hasMany(Course::class);
    }
}
