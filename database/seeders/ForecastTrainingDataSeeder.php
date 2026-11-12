<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Seeds one year of demo sales history for SARIMAX training only.
 * Run after its migration with `php artisan db:seed --class=ForecastTrainingDataSeeder`.
 */
class ForecastTrainingDataSeeder extends Seeder
{
    private const DAYS = 365;

    public function run(): void
    {
        $items = DB::table('inventory_items')
            ->where('status', 'active')
            ->where('category', 'Fruit')
            ->orderBy('id')
            ->get(['id', 'name']);

        if ($items->isEmpty()) {
            throw new RuntimeException('No active fruit inventory items found for forecast demo data.');
        }

        $timezone = config('services.openweather.timezone');
        $firstDay = Carbon::now($timezone)->startOfDay()->subDays(self::DAYS);
        $now = now();
        $rows = [];

        foreach ($items as $index => $item) {
            $baseDemand = 8 + (($index * 7) % 17);

            for ($day = 0; $day < self::DAYS; $day++) {
                $date = $firstDay->copy()->addDays($day);
                $weekdayFactor = [1.0, 0.96, 0.98, 1.02, 1.08, 1.2, 1.15][$date->dayOfWeek];
                $seasonalFactor = 1 + 0.12 * sin(2 * M_PI * $date->month / 12);
                $weeklyVariation = 1 + 0.08 * sin(2 * M_PI * $day / 7 + $index);
                $quantity = max(0.5, round($baseDemand * $weekdayFactor * $seasonalFactor * $weeklyVariation, 2));

                $rows[] = [
                    'inventory_item_id' => $item->id,
                    'sale_date' => $date->toDateString(),
                    'quantity' => $quantity,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                if (count($rows) >= 500) {
                    $this->upsert($rows);
                    $rows = [];
                }
            }
        }

        if ($rows !== []) {
            $this->upsert($rows);
        }

        $this->command?->info(sprintf(
            'Added or refreshed %d forecast-only demo sales samples for %d fruit(s).',
            $items->count() * self::DAYS,
            $items->count()
        ));
    }

    private function upsert(array $rows): void
    {
        DB::table('forecast_training_samples')->upsert(
            $rows,
            ['inventory_item_id', 'sale_date'],
            ['quantity', 'updated_at']
        );
    }
}
