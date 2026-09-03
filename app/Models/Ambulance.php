<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ambulance extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'plate',
        'brand',
        'model',
        'km_per_gallon',
        'soat_expires_at',
        'tech_review_expires_at',
        'status',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'km_per_gallon' => 'decimal:2',
            'soat_expires_at' => 'date',
            'tech_review_expires_at' => 'date',
        ];
    }

    /**
     * Remissions assigned to this ambulance.
     */
    public function remissions(): HasMany
    {
        return $this->hasMany(Remission::class);
    }

    /**
     * Telemetry locations recorded for this ambulance.
     */
    public function locations(): HasMany
    {
        return $this->hasMany(Location::class);
    }

    /**
     * Scope a query to only include available ambulances.
     */
    public function scopeAvailable(Builder $query): Builder
    {
        return $query->where('status', 'available');
    }

    /**
     * Scope a query to only include ambulances with documentation expiring within $days days.
     */
    public function scopeExpiringDocs(Builder $query, int $days = 5): Builder
    {
        $targetDate = now()->addDays($days)->toDateString();

        return $query->where(function (Builder $q) use ($targetDate) {
            $q->whereDate('soat_expires_at', '<=', $targetDate)
              ->orWhereDate('tech_review_expires_at', '<=', $targetDate);
        });
    }
}
