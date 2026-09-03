<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Remission extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'code',
        'ambulance_id',
        'driver_id',
        'patient_id',
        'origin_address',
        'destination_address',
        'status',
        'is_out_of_city',
        'started_at',
        'transfer_started_at',
        'finished_at',
        'total_kilometers',
        'fuel_consumed_gallons',
        'notes',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_out_of_city' => 'boolean',
            'started_at' => 'datetime',
            'transfer_started_at' => 'datetime',
            'finished_at' => 'datetime',
            'total_kilometers' => 'decimal:3',
            'fuel_consumed_gallons' => 'decimal:3',
        ];
    }

    /**
     * The ambulance assigned to the remission.
     */
    public function ambulance(): BelongsTo
    {
        return $this->belongsTo(Ambulance::class);
    }

    /**
     * The driver assigned to the remission.
     */
    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    /**
     * The patient being transferred.
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    /**
     * Additional occupants (medical crew, companions) on the remission.
     */
    public function occupants(): HasMany
    {
        return $this->hasMany(RemissionOccupant::class);
    }

    /**
     * Telemetry locations registered during the remission.
     */
    public function locations(): HasMany
    {
        return $this->hasMany(Location::class);
    }

    /**
     * Scope a query to only include active remissions (in progress).
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', ['en_camino', 'trasladando']);
    }

    /**
     * Scope a query to only include remissions assigned to a specific driver.
     */
    public function scopeForDriver(Builder $query, int $driverId): Builder
    {
        return $query->where('driver_id', $driverId);
    }
}
