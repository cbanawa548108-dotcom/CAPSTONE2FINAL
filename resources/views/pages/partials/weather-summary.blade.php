<section class="card overflow-hidden mb-6" x-data="weatherSummary()" x-init="load()" aria-label="Weather summary">
    <div class="bg-gradient-to-r from-[#102B21] via-[#153B2C] to-[#1F4D37] px-5 py-5 sm:px-6">
        <div class="flex flex-col gap-5 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-4">
                <div class="flex h-14 w-14 flex-shrink-0 items-center justify-center rounded-2xl bg-white/10 text-[#C9F27A]">
                    <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M20 15.5A4.5 4.5 0 0015.5 11H15a7 7 0 10-13.8 1.6A4 4 0 004 20h11a5 5 0 005-4.5zM8 3v1m8-1v1M3 8h1m16 0h1"/>
                    </svg>
                </div>
                <div>
                    <p class="text-[11px] font-bold uppercase tracking-[.14em] text-white/60">Local weather</p>
                    <h2 class="mt-1 text-lg font-bold text-white" x-text="city || 'Davao City'"></h2>
                    <p class="mt-1 text-[12px] text-white/65" x-show="loading">Loading latest weather…</p>
                    <p class="mt-1 text-[12px] text-white/65" x-show="error && !loading" x-text="weather.current ? 'Live conditions unavailable · Showing stored forecast' : 'Live conditions unavailable'"></p>
                </div>
            </div>

            <template x-if="weather.current">
                <div class="flex items-center gap-5 sm:justify-end">
                    <div class="text-right">
                        <p class="text-4xl font-black leading-none text-white"><span x-text="Math.round(weather.current.temperature)"></span><span class="text-2xl">°C</span></p>
                        <p class="mt-2 text-sm capitalize text-[#C9F27A]" x-text="weather.current.description || weather.current.condition || 'Current conditions'"></p>
                    </div>
                    <div class="hidden h-12 w-px bg-white/20 sm:block"></div>
                    <div class="space-y-1.5 text-[11px] text-white/75">
                        <p><span class="font-semibold text-white" x-text="weather.current.feels_like + '°C'"></span> feels like</p>
                        <p><span class="font-semibold text-white" x-text="weather.current.humidity + '%'"></span> humidity</p>
                        <p><span class="font-semibold text-white" x-text="Number(weather.current.rainfall || 0).toFixed(1) + ' mm'"></span> rain / hr</p>
                    </div>
                </div>
            </template>
            <p class="text-sm text-white/70" x-show="!loading && !weather.current && !error && !forecast.length">No weather data available yet.</p>
        </div>
    </div>

    <div class="flex flex-col gap-3 bg-white px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
        <div class="flex items-center gap-2">
            <span class="h-2 w-2 rounded-full bg-[#84CC16]"></span>
            <p class="text-[12px] font-semibold text-gray-700">Upcoming forecast</p>
            <p class="text-[11px] text-gray-400" x-show="forecast.length" x-text="'· ' + forecast.length + ' days'"></p>
        </div>
        <div class="grid grid-cols-2 gap-2 sm:flex sm:flex-wrap sm:justify-end">
            <template x-for="day in forecast" :key="day.date">
                <div class="min-w-[92px] rounded-xl border border-gray-100 bg-gray-50 px-3 py-2 text-center">
                    <p class="text-[10px] font-bold uppercase text-gray-500" x-text="day.label || day.date"></p>
                    <p class="mt-0.5 text-sm font-bold text-gray-900"><span x-text="day.temperature != null ? Math.round(day.temperature) : '—'"></span>°</p>
                    <p class="text-[10px] text-gray-500"><span x-text="day.humidity != null ? Math.round(day.humidity) : '—'"></span>% RH · <span x-text="day.rainfall != null ? Number(day.rainfall).toFixed(1) : '—'"></span> mm</p>
                </div>
            </template>
            <p class="self-center text-[11px] text-gray-400" x-show="!loading && !forecast.length">Forecast data will appear after a weather sync.</p>
        </div>
    </div>
</section>

<script>
function weatherSummary() {
    return {
        loading: true,
        error: null,
        city: 'Davao City',
        weather: { current: null },
        forecast: [],

        async load() {
            try {
                const response = await fetch('/api/weather', { headers: { Accept: 'application/json' } });
                if (!response.ok) throw new Error(`Weather request failed (HTTP ${response.status}).`);

                const data = await response.json();
                if (!data.success) throw new Error(data.message || 'Weather data could not be loaded.');

                this.weather = data;
                this.city = data.city || 'Davao City';
                this.forecast = data.forecast || [];
                this.error = data.error || null;
            } catch (error) {
                this.error = error.message;
                console.error('Unable to load weather summary:', error);
            } finally {
                this.loading = false;
            }
        }
    };
}
</script>
