<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubscriptionPlan extends Model
{
    use HasUuids;

    protected $fillable = [
        'name', 'price', 'interval', 'features', 'max_courses', 'status',
    ];

    protected $casts = [
        'features' => 'array',
        'price' => 'float',
        'max_courses' => 'integer',
    ];

    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    public function userSubscriptions(): HasMany
    {
        return $this->hasMany(UserSubscription::class, 'plan_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'plan_id');
    }
}
