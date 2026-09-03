<?php

namespace App\Services;

use App\Models\Ambulance;
use App\Models\Remission;
use Illuminate\Support\Facades\DB;

class RemissionLifecycleService
{
    /**
     * Generate a unique correlative remission code formatted as REM-YYYYMMDD-XXXX.
     *
     * @return string
     */
    public function generateUniqueCode(): string
    {
        $prefix = 'REM-' . date('Ymd') . '-';

        $latestCode = Remission::where('code', 'like', "{$prefix}%")
            ->orderByDesc('id')
            ->value('code');

        if ($latestCode) {
            $sequence = (int) substr($latestCode, strlen($prefix)) + 1;
        } else {
            $sequence = 1;
        }

        return sprintf('%s%04d', $prefix, $sequence);
    }

    /**
     * Create a new remission, allocate the ambulance and register any occupants.
     *
     * @param array<string, mixed> $data
     * @param array<int, array<string, mixed>> $occupants
     * @return Remission
     */
    public function createRemission(array $data, array $occupants = []): Remission
    {
        return DB::transaction(function () use ($data, $occupants) {
            $ambulance = Ambulance::findOrFail($data['ambulance_id']);
            $ambulance->update(['status' => 'in_service']);

            $code = $data['code'] ?? $this->generateUniqueCode();

            /** @var Remission $remission */
            $remission = Remission::create([
                'code' => $code,
                'ambulance_id' => $ambulance->id,
                'driver_id' => $data['driver_id'],
                'patient_id' => $data['patient_id'],
                'origin_address' => $data['origin_address'],
                'destination_address' => $data['destination_address'],
                'status' => 'en_camino',
                'is_out_of_city' => $data['is_out_of_city'] ?? false,
                'started_at' => now(),
                'total_kilometers' => 0.000,
                'fuel_consumed_gallons' => 0.000,
                'notes' => $data['notes'] ?? null,
            ]);

            if (!empty($occupants)) {
                foreach ($occupants as $occupant) {
                    $remission->occupants()->create([
                        'name' => $occupant['name'],
                        'identification' => $occupant['identification'] ?? null,
                        'role' => $occupant['role'] ?? 'Tripulante',
                    ]);
                }
            }

            return $remission->load(['ambulance', 'driver', 'patient', 'occupants']);
        });
    }

    /**
     * Transition a remission to in-transfer status ('trasladando').
     *
     * @param Remission $remission
     * @return Remission
     */
    public function startTransfer(Remission $remission): Remission
    {
        $remission->update([
            'status' => 'trasladando',
            'transfer_started_at' => now(),
        ]);

        return $remission->fresh(['ambulance', 'driver', 'patient', 'occupants']);
    }

    /**
     * Finish a remission, calculate fuel consumed and release the ambulance to available.
     *
     * @param Remission $remission
     * @param string|null $notes
     * @return Remission
     */
    public function finishRemission(Remission $remission, ?string $notes = null): Remission
    {
        return DB::transaction(function () use ($remission, $notes) {
            $remission->loadMissing('ambulance');
            $ambulance = $remission->ambulance;

            $kmPerGallon = (float) ($ambulance?->km_per_gallon ?? 0);
            $totalKm = (float) $remission->total_kilometers;
            $fuelConsumed = $kmPerGallon > 0 ? round($totalKm / $kmPerGallon, 3) : 0.000;

            $updateData = [
                'status' => 'finalizado',
                'finished_at' => now(),
                'fuel_consumed_gallons' => $fuelConsumed,
            ];

            if ($notes !== null && trim($notes) !== '') {
                $updateData['notes'] = $remission->notes ? $remission->notes . "\n" . $notes : $notes;
            }

            $remission->update($updateData);

            if ($ambulance) {
                $ambulance->update(['status' => 'available']);
            }

            return $remission->fresh(['ambulance', 'driver', 'patient', 'occupants']);
        });
    }

    /**
     * Cancel an active remission, register cancellation reason and release the ambulance.
     *
     * @param Remission $remission
     * @param string $reason
     * @return Remission
     */
    public function cancelRemission(Remission $remission, string $reason): Remission
    {
        return DB::transaction(function () use ($remission, $reason) {
            $remission->loadMissing('ambulance');
            $ambulance = $remission->ambulance;

            $cancelNote = '[Cancelado]: ' . $reason;
            $newNotes = $remission->notes ? $remission->notes . "\n" . $cancelNote : $cancelNote;

            $remission->update([
                'status' => 'cancelado',
                'notes' => $newNotes,
            ]);

            if ($ambulance) {
                $ambulance->update(['status' => 'available']);
            }

            return $remission->fresh(['ambulance', 'driver', 'patient', 'occupants']);
        });
    }
}
