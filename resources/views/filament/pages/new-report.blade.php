<x-filament::page>
    <div class="space-y-6">
        {{-- Header: Event & Report Time + Print --}}
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white">
                    Event Report Dashboard
                </h2>
                @if($this->selectedEvent)
                    <p class="text-sm text-gray-600 dark:text-gray-300">
                        Event:
                        <span class="font-medium">
                            {{ \App\Models\Event::find($this->selectedEvent)?->name }}
                        </span>
                    </p>
                @endif
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                    Report Date / Time : {{ $this->reportGeneratedAt }}
                </p>
            </div>

            <div class="flex items-center gap-3">
                {{-- Event filter form --}}
                <div>
                    {{ $this->form }}
                </div>

                {{-- Print button --}}
                <button
                    type="button"
                    onclick="window.print()"
                    class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-sm font-medium text-gray-700 dark:text-gray-200 shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 print:hidden"
                >
                    <x-heroicon-o-printer class="w-4 h-4 mr-2" />
                    Print
                </button>
            </div>
        </div>

        {{-- Global Stats --}}
        @if(! empty($this->globalStats))
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="p-4 bg-white dark:bg-gray-800 shadow rounded-lg">
                    <h3 class="text-gray-500 dark:text-gray-400 text-xs font-semibold uppercase tracking-wide">
                        Total Participants
                    </h3>
                    <p class="text-2xl font-bold mt-2 text-gray-900 dark:text-white">
                        {{ number_format($this->globalStats['total_participants'] ?? 0) }}
                    </p>
                </div>

                <div class="p-4 bg-white dark:bg-gray-800 shadow rounded-lg">
                    <h3 class="text-gray-500 dark:text-gray-400 text-xs font-semibold uppercase tracking-wide">
                        Total Revenue
                    </h3>
                    <p class="text-2xl font-bold mt-2 text-gray-900 dark:text-white">
                        Rp {{ number_format($this->globalStats['total_revenue'] ?? 0, 0, ',', '.') }}
                    </p>
                </div>

                <div class="p-4 bg-white dark:bg-gray-800 shadow rounded-lg">
                    <h3 class="text-gray-500 dark:text-gray-400 text-xs font-semibold uppercase tracking-wide">
                        Gender (Total)
                    </h3>
                    <p class="mt-2 text-sm text-gray-900 dark:text-gray-100">
                        Male:
                        <span class="font-semibold">
                            {{ $this->globalStats['gender']['male'] ?? 0 }}
                        </span>
                    </p>
                    <p class="text-sm text-gray-900 dark:text-gray-100">
                        Female:
                        <span class="font-semibold">
                            {{ $this->globalStats['gender']['female'] ?? 0 }}
                        </span>
                    </p>
                </div>

                <div class="p-4 bg-white dark:bg-gray-800 shadow rounded-lg">
                    <h3 class="text-gray-500 dark:text-gray-400 text-xs font-semibold uppercase tracking-wide">
                        Nationality (Total)
                    </h3>
                    <p class="mt-2 text-sm text-gray-900 dark:text-gray-100">
                        Indonesian:
                        <span class="font-semibold">
                            {{ $this->globalStats['nationality']['indonesian'] ?? 0 }}
                        </span>
                    </p>
                    <p class="text-sm text-gray-900 dark:text-gray-100">
                        Foreigner:
                        <span class="font-semibold">
                            {{ $this->globalStats['nationality']['foreigner'] ?? 0 }}
                        </span>
                    </p>
                </div>
            </div>
        @endif

        {{-- Chart --}}
        <div class="p-6 bg-white dark:bg-gray-800 shadow rounded-2xl">
            <h3 class="text-gray-700 dark:text-gray-300 text-sm font-semibold mb-4">
                Registrations & Revenue by Category & Ticket Type
            </h3>

            <div class="w-full overflow-x-auto">
                <div
                    class="relative"
                    style="height: 300px; min-width: calc(80px * {{ count($this->chartData['labels'] ?? []) }});"
                >
                    <canvas id="registrationChart" wire:ignore></canvas>
                </div>
            </div>
        </div>

        {{-- Per Ticket Type Summary --}}
        @if(! empty($this->perTicketStats))
            <div class="space-y-4">
                <h3 class="text-gray-700 dark:text-gray-300 text-sm font-semibold">
                    Ticket Type Summary
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($this->perTicketStats as $ticket)
                        <div class="p-4 bg-white dark:bg-gray-800 shadow rounded-lg break-inside-avoid">
                            <h4 class="text-sm font-semibold text-gray-900 dark:text-white">
                                {{ $ticket['label'] }}
                            </h4>

                            <p class="mt-2 text-sm text-gray-900 dark:text-gray-100">
                                Revenue:
                                <span class="font-semibold">
                                    Rp {{ number_format($ticket['revenue'] ?? 0, 0, ',', '.') }}
                                </span>
                            </p>

                            <p class="text-sm text-gray-900 dark:text-gray-100">
                                Participants:
                                <span class="font-semibold">
                                    {{ $ticket['participants'] ?? 0 }}
                                </span>
                            </p>

                            <p class="mt-2 text-xs text-gray-500 dark:text-gray-400 font-semibold uppercase tracking-wide">
                                Gender
                            </p>
                            <p class="text-sm text-gray-900 dark:text-gray-100">
                                Male:
                                <span class="font-semibold">
                                    {{ $ticket['gender']['male'] ?? 0 }}
                                </span>
                            </p>
                            <p class="text-sm text-gray-900 dark:text-gray-100">
                                Female:
                                <span class="font-semibold">
                                    {{ $ticket['gender']['female'] ?? 0 }}
                                </span>
                            </p>

                            @if(! empty($ticket['jersey_sizes']))
                                <p class="mt-2 text-xs text-gray-500 dark:text-gray-400 font-semibold uppercase tracking-wide">
                                    Jersey Sizes
                                </p>
                                <dl class="mt-1 grid grid-cols-2 gap-x-3 gap-y-1 text-sm">
                                    @foreach($ticket['jersey_sizes'] as $size => $count)
                                        <div class="flex justify-between">
                                            <dt class="text-gray-600 dark:text-gray-300">{{ $size }}</dt>
                                            <dd class="font-semibold text-gray-900 dark:text-gray-100">{{ $count }}</dd>
                                        </div>
                                    @endforeach
                                </dl>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Community & City / Regency Rank + Jersey Global --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Community Rank --}}
            <div class="p-4 bg-white dark:bg-gray-800 shadow rounded-lg">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-2">
                    Community Rank
                </h3>
                @if(! empty($this->communityRanks))
                    <ol class="mt-2 space-y-1 text-sm text-gray-900 dark:text-gray-100">
                        @foreach($this->communityRanks as $index => $community)
                            <li>
                                {{ $index + 1 }}.
                                <span class="font-semibold">{{ $community['name'] }}</span>
                                <span class="text-xs text-gray-500 dark:text-gray-400">
                                    ({{ $community['count'] }} participants)
                                </span>
                            </li>
                        @endforeach
                    </ol>
                @else
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        No community data available.
                    </p>
                @endif
            </div>

            {{-- City / Regency Rank --}}
            <div class="p-4 bg-white dark:bg-gray-800 shadow rounded-lg">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-2">
                    City / Regency Rank
                </h3>
                @if(! empty($this->cityRanks))
                    <ol class="mt-2 space-y-1 text-sm text-gray-900 dark:text-gray-100">
                        @foreach($this->cityRanks as $index => $city)
                            <li>
                                {{ $index + 1 }}.
                                <span class="font-semibold">{{ $city['location'] }}</span>
                                <span class="text-xs text-gray-500 dark:text-gray-400">
                                    ({{ $city['count'] }} participants)
                                </span>
                            </li>
                        @endforeach
                    </ol>
                @else
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        No city / regency data available.
                    </p>
                @endif
            </div>

            {{-- Jersey Global Statistics --}}
            <div class="p-4 bg-white dark:bg-gray-800 shadow rounded-lg">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-2">
                    Jersey Statistics
                </h3>
                @if(! empty($this->jerseyStats))
                    <div class="space-y-3 text-sm text-gray-900 dark:text-gray-100">
                        @foreach($this->jerseyStats as $jersey)
                            <div>
                                <p class="font-semibold">
                                    {{ $jersey['category'] }}
                                </p>
                                <dl class="mt-1 grid grid-cols-2 gap-x-3 gap-y-1">
                                    @foreach($jersey['sizes'] as $size => $count)
                                        <div class="flex justify-between">
                                            <dt class="text-gray-600 dark:text-gray-300">{{ $size }}</dt>
                                            <dd class="font-semibold text-gray-900 dark:text-gray-100">{{ $count }}</dd>
                                        </div>
                                    @endforeach
                                </dl>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        No jersey data available.
                    </p>
                @endif
            </div>
        </div>
    </div>

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script>
            let chartInstance = null;

            function renderChart(chartData) {
                if (!chartData) return;

                const labels = chartData.labels || [];
                const values = chartData.values || [];
                const revenues = chartData.revenues || [];

                const ctx = document.getElementById('registrationChart');
                if (!ctx) return;

                if (chartInstance) {
                    chartInstance.destroy();
                }

                chartInstance = new Chart(ctx, {
                    data: {
                        labels: labels,
                        datasets: [
                            {
                                type: 'line',
                                label: 'Revenue (Rp)',
                                data: revenues,
                                borderColor: '#f97316',
                                backgroundColor: '#f97316',
                                tension: 0.3,
                                borderWidth: 3,
                                pointRadius: 4,
                                pointBackgroundColor: '#f97316',
                                yAxisID: 'y1',
                            },
                            {
                                type: 'bar',
                                label: 'Registrations',
                                data: values,
                                backgroundColor: '#3b82f6',
                                borderRadius: 6,
                                yAxisID: 'y',
                            },
                        ],
                    },
                    options: {
                        responsive: true,
                        interaction: { mode: 'index', intersect: false },
                        scales: {
                            y: {
                                beginAtZero: true,
                                title: { display: true, text: 'Registrations' },
                                ticks: { precision: 0 },
                            },
                            y1: {
                                beginAtZero: true,
                                position: 'right',
                                title: { display: true, text: 'Revenue (Rp)' },
                                grid: { drawOnChartArea: false },
                            },
                        },
                    },
                });
            }

            window.addEventListener('chartUpdated', event => {
                const chartData = event.detail[0].chartData;
                renderChart(chartData);
            });
        </script>
    @endpush
</x-filament::page>











