<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RemissionOccupant extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'remission_id',
        'name',
        'identification',
        'role',
    ];

    /**
     * The remission this occupant belongs to.
     */
    public function remission(): BelongsTo
    {
        return $this->belongsTo(Remission::class);
    }
}
