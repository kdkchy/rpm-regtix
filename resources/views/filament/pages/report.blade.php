<x-filament::page>
    <div class="space-y-6">
        {{-- Form event --}}
        {{ $this->form }}

        {{-- Statistik --}}
        @if($this->totalRegistrations > 0 && $this->totalRevenue > 0)
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
            <div class="p-4 bg-white dark:bg-gray-800 shadow rounded-lg">
                <h3 class="text-gray-300 dark:text-gray-300 text-sm font-semibold">Total Registrations</h3>
                <p class="text-3xl font-bold mt-2 text-gray-900 dark:text-white">
                    {{ number_format($this->totalRegistrations) }}
                </p>
            </div>

           <div class="p-4 bg-white dark:bg-gray-800 shadow rounded-lg">
                <h3 class="text-gray-300 dark:text-gray-300 text-sm font-semibold">Total Revenue</h3>
                <p class="text-3xl font-bold mt-2 text-gray-900 dark:text-white">
                    Rp {{ number_format($this->totalRevenue, 0, ',', '.') }}
                </p>
            </div>
        </div>
        @endif


        @if(!empty($this->jerseyByCategory))
        <!-- Title -->
        <h2 class="text-2xl font-bold mb-4 text-gray-800 dark:text-gray-100">
            Jersey Size Summary
        </h2>
        <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-3 gap-6 mt-6">
            @foreach($this->jerseyByCategory as $category => $sizes)
                <div class="p-4 bg-white dark:bg-gray-800 shadow rounded-lg">
                    <!-- Header kategori -->
                    <h3 class="text-lg font-bold mb-3">{{ $category }}</h3>
                    <hr class="p-2">
                    <!-- List size -->
                    <ul class="space-y-2">
                        @foreach($sizes as $size => $quantity)
                            <li class="flex justify-between items-center p-1">
                                <span class="font-semibold">{{ $size }}</span>
                                <span class="font-bold">{{ $quantity }}</span>
                            </li>
                        @endforeach
                    </ul>

                    <!-- Total per kategori -->
                    <div class="mt-3 text-right font-semibold text-gray-800 dark:text-gray-200">
                        Total: {{ array_sum($sizes) }}
                    </div>
                </div>
            @endforeach
        </div>
        @endif

        {{-- Chart --}}
        <div class="p-6 bg-white dark:bg-gray-800 shadow rounded-2xl mt-8">
            <h3 class="text-gray-700 dark:text-gray-300 text-sm font-semibold mb-4">
                Registrations & Revenue by Category & Ticket Type
            </h3>

            {{-- Scrollable horizontal container --}}
            <div class="w-full overflow-x-auto">
                <div class="relative" style="min-width: calc(80px * {{ count($this->chartData['labels'] ?? []) }});">
                    <canvas id="registrationChart" wire:ignore></canvas>
                </div>
            </div>
        </div>
    </div>

   @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script>
            let chartInstance = null;

           function renderChart(chartData) {

                if (!chartData) return; // safety
                const labels = chartData.labels || [];
                const values = chartData.values || [];
                const revenues = chartData.revenues || [];

                const ctx = document.getElementById('registrationChart');
                if (!ctx) return;

                if (chartInstance) chartInstance.destroy();

                chartInstance = new Chart(ctx, {
                    data: {
                        labels: labels,
                        datasets: [                           
                            { type: 'line', label: 'Revenue (Rp)', data: revenues, borderColor: '#f97316', backgroundColor: '#f97316', tension: 0.3, borderWidth: 3, pointRadius: 4, pointBackgroundColor: '#f97316', yAxisID: 'y1' },
                            { type: 'bar', label: 'Registrations', data: values, backgroundColor: '#3b82f6', borderRadius: 6, yAxisID: 'y' },
                        ]
                    },
                    options: {
                        responsive: true,
                        interaction: { mode: 'index', intersect: false },
                        scales: {
                            y: { beginAtZero: true, title: { display: true, text: 'Registrations' }, ticks: { precision: 0 } },
                            y1: { beginAtZero: true, position: 'right', title: { display: true, text: 'Revenue (Rp)' }, grid: { drawOnChartArea: false } }
                        }
                    }
                });
            }


            // Listen Livewire event
            window.addEventListener('chartUpdated', event => {                
                const chartData = event.detail[0].chartData
                renderChart(chartData);
            });


        </script>
    @endpush
    
</x-filament::page>
