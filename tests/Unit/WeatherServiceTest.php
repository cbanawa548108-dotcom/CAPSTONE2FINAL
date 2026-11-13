<?php

namespace Tests\Unit;

use App\Services\WeatherService;
use Carbon\Carbon;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WeatherServiceTest extends TestCase
{
    public function test_current_conditions_are_mapped_from_the_configured_davao_location(): void
    {
        config([
            'services.openweather.key' => 'test-api-key',
            'services.openweather.city' => 'Davao City',
            'services.openweather.lat' => 7.0731,
            'services.openweather.lon' => 125.6128,
            'services.openweather.timezone' => 'Asia/Manila',
        ]);

        $timestamp = 1794550000;
        Http::fake([
            'api.openweathermap.org/data/2.5/weather*' => Http::response([
                'name' => 'Davao City',
                'dt' => $timestamp,
                'main' => [
                    'temp' => 26.6,
                    'feels_like' => 29.7,
                    'humidity' => 83,
                ],
                'rain' => ['1h' => 0.04],
                'weather' => [[
                    'main' => 'Clouds',
                    'description' => 'overcast clouds',
                    'icon' => '04n',
                ]],
            ]),
        ]);

        $current = app(WeatherService::class)->current();

        $this->assertSame('Davao City', $current['city']);
        $this->assertSame('OpenWeather', $current['source']);
        $this->assertSame(26.6, $current['temperature']);
        $this->assertSame(29.7, $current['feels_like']);
        $this->assertSame(83, $current['humidity']);
        $this->assertSame(0.04, $current['rainfall']);
        $this->assertSame(
            Carbon::createFromTimestamp($timestamp)->setTimezone('Asia/Manila')->toDateTimeString(),
            $current['observed_at'],
        );

        Http::assertSent(fn (Request $request) => str_contains($request->url(), '/data/2.5/weather')
            && (float) $request['lat'] === 7.0731
            && (float) $request['lon'] === 125.6128
            && $request['units'] === 'metric');
    }
}
