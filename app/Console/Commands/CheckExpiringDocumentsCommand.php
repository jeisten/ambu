<?php

namespace App\Console\Commands;

use App\Models\Ambulance;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CheckExpiringDocumentsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ambulances:check-expiring-docs {--days=5 : Días de anticipación para verificar vencimientos}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verifica ambulancias con SOAT o Tecnomecánica próximas a vencer y emite alertas.';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $days = (int) $this->option('days');
        $this->info("🔍 Verificando documentación de ambulancias con vencimiento en $\le {$days}$ días...");

        $ambulances = Ambulance::expiringDocs($days)->get();

        if ($ambulances->isEmpty()) {
            $this->info("✅ Todas las ambulancias tienen SOAT y Tecnomecánica al día ($\ge {$days}$ días restantes).");
            return Command::SUCCESS;
        }

        $this->warn("⚠️  Se encontraron {$ambulances->count()} ambulancias con documentación próxima a vencer o vencida:");

        $tableRows = [];
        $today = Carbon::today();

        foreach ($ambulances as $ambulance) {
            $soatDays = $ambulance->soat_expires_at ? $today->diffInDays($ambulance->soat_expires_at, false) : null;
            $techDays = $ambulance->tech_review_expires_at ? $today->diffInDays($ambulance->tech_review_expires_at, false) : null;

            $alerts = [];
            if ($soatDays !== null && $soatDays <= $days) {
                $alerts[] = $soatDays < 0 ? "SOAT VENCIDO hace " . abs($soatDays) . " días" : "SOAT vence en {$soatDays} días";
            }
            if ($techDays !== null && $techDays <= $days) {
                $alerts[] = $techDays < 0 ? "Tecno VENCIDA hace " . abs($techDays) . " días" : "Tecno vence en {$techDays} días";
            }

            $alertText = implode(' | ', $alerts);

            // Log warning
            Log::warning("[Flota-Alerta] Ambulancia {$ambulance->plate} (ID: {$ambulance->id}) - {$alertText}", [
                'ambulance_id' => $ambulance->id,
                'plate' => $ambulance->plate,
                'soat_expires_at' => $ambulance->soat_expires_at?->toDateString(),
                'tech_review_expires_at' => $ambulance->tech_review_expires_at?->toDateString(),
                'status' => $ambulance->status,
            ]);

            $tableRows[] = [
                $ambulance->id,
                $ambulance->plate,
                trim("{$ambulance->brand} {$ambulance->model}"),
                $ambulance->soat_expires_at?->toDateString() ?? 'N/A',
                $ambulance->tech_review_expires_at?->toDateString() ?? 'N/A',
                $ambulance->status,
                $alertText,
            ];
        }

        $this->table(
            ['ID', 'Placa', 'Marca / Modelo', 'Vence SOAT', 'Vence Tecnomecánica', 'Estado', 'Alertas'],
            $tableRows
        );

        return Command::SUCCESS;
    }
}
