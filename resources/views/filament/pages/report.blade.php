<x-filament::page>
    <div class="space-y-6">
        {{-- Header Card --}}
        <div class="p-6 bg-white dark:bg-gray-800 shadow rounded-lg">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                {{-- Title & Info Section --}}
                <div class="flex-1">
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-3">
                        Event Report Dashboard
                    </h2>
                    
                    <div class="space-y-2">
                        @if($this->selectedEvent)
                            <div class="flex items-center gap-2">
                                <span class="text-sm font-medium text-gray-500 dark:text-gray-400">
                                    Event:
                                </span>
                                <span class="text-sm font-semibold text-gray-900 dark:text-white">
                                    {{ \App\Models\Event::find($this->selectedEvent)?->name }}
                                </span>
                            </div>
                        @endif
                        
                        <div class="flex items-center gap-2">
                            <span class="text-xs text-gray-500 dark:text-gray-400">
                                Report Date / Time:
                            </span>
                            <span class="text-xs font-medium text-gray-700 dark:text-gray-300">
                                {{ $this->reportGeneratedAt }}
                            </span>
                        </div>
                    </div>
                </div>

                {{-- Actions Section --}}
                <div class="flex flex-col sm:flex-row items-stretch sm:items-end gap-3">
                    {{-- Event filter form --}}
                    <div class="min-w-[200px] sm:flex-1">
                        {{ $this->form }}
                    </div>

                    {{-- Print button --}}
                    <a
                        href="{{ route('report.print-xml', $this->selectedEvent) }}"
                        target="_blank"
                        class="inline-flex items-center justify-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-sm font-medium text-gray-700 dark:text-gray-200 shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition-colors whitespace-nowrap"
                    >
                        <x-heroicon-o-printer class="w-4 h-4 mr-2" />
                        Print
                    </a>
                </div>
            </div>
        </div>

        {{-- Global Stats --}}
        @if(! empty($this->globalStats))
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
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
            </div>
        @endif

        {{-- Jersey Table --}}
        @if(! empty($this->jerseyTable['data']))
            <div class="p-6 bg-white dark:bg-gray-800 shadow rounded-lg">
                <h3 class="text-gray-700 dark:text-white text-lg font-semibold mb-4">
                    Jersey Distribution by Category & Ticket Type
                </h3>
                
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-900">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 dark:text-white uppercase tracking-wider border-r border-gray-200 dark:border-gray-600">
                                    Size
                                </th>
                                @foreach($this->jerseyTable['ticketTypes'] as $ticketType)
                                    <th class="px-4 py-3 text-center text-xs font-semibold text-gray-700 dark:text-white uppercase tracking-wider border-r border-gray-200 dark:border-gray-600">
                                        {{ $ticketType }}
                                    </th>
                                @endforeach
                                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-700 dark:text-white uppercase tracking-wider bg-gray-100 dark:bg-gray-700">
                                    Total
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($this->jerseyTable['data'] as $row)
                                <tr class="{{ $row['size'] === 'Total' ? 'bg-gray-50 dark:bg-gray-900 font-semibold' : 'hover:bg-gray-50 dark:hover:bg-gray-700' }}">
                                    <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-white border-r border-gray-200 dark:border-gray-600">
                                        {{ $row['size'] }}
                                    </td>
                                    @foreach($this->jerseyTable['ticketTypes'] as $ticketType)
                                        <td class="px-4 py-3 text-sm text-center text-gray-900 dark:text-gray-100 border-r border-gray-200 dark:border-gray-600">
                                            {{ $row[$ticketType] ?? 0 }}
                                        </td>
                                    @endforeach
                                    <td class="px-4 py-3 text-sm text-center font-semibold text-gray-900 dark:text-white bg-gray-100 dark:bg-gray-700">
                                        {{ $row['totals'] }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        {{-- Registrations & Revenue Table and Gender & Nationality Table --}}
        @if(! empty($this->chartData['labels']))
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                {{-- Revenue Table --}}
                <div class="p-6 bg-white dark:bg-gray-800 shadow rounded-lg">
                    <h3 class="text-gray-700 dark:text-white text-lg font-semibold mb-4">
                        Registrations & Revenue by Category & Ticket Type
                    </h3>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-900">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 dark:text-white uppercase tracking-wider border-r border-gray-200 dark:border-gray-600">
                                        Category & Ticket Type
                                    </th>
                                    <th class="px-4 py-3 text-center text-xs font-semibold text-gray-700 dark:text-white uppercase tracking-wider border-r border-gray-200 dark:border-gray-600">
                                        Registrations
                                    </th>
                                    <th class="px-4 py-3 text-center text-xs font-semibold text-gray-700 dark:text-white uppercase tracking-wider bg-gray-100 dark:bg-gray-700">
                                        Revenue
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach($this->chartData['labels'] as $index => $label)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                        <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-white border-r border-gray-200 dark:border-gray-600">
                                            {{ $label }}
                                        </td>
                                        <td class="px-4 py-3 text-sm text-center text-gray-900 dark:text-gray-100 border-r border-gray-200 dark:border-gray-600">
                                            {{ number_format($this->chartData['values'][$index] ?? 0) }}
                                        </td>
                                        <td class="px-4 py-3 text-sm text-center font-semibold text-gray-900 dark:text-white bg-gray-100 dark:bg-gray-700">
                                            Rp {{ number_format($this->chartData['revenues'][$index] ?? 0, 0, ',', '.') }}
                                        </td>
                                    </tr>
                                @endforeach
                                <tr class="bg-gray-50 dark:bg-gray-900 font-semibold">
                                    <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-white border-r border-gray-200 dark:border-gray-600">
                                        Total
                                    </td>
                                    <td class="px-4 py-3 text-sm text-center text-gray-900 dark:text-gray-100 border-r border-gray-200 dark:border-gray-600">
                                        {{ number_format(array_sum($this->chartData['values'] ?? [])) }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-center font-semibold text-gray-900 dark:text-white bg-gray-100 dark:bg-gray-700">
                                        Rp {{ number_format(array_sum($this->chartData['revenues'] ?? []), 0, ',', '.') }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Gender & Nationality Table --}}
                @if(! empty($this->genderNationalityTable))
                    <div class="p-6 bg-white dark:bg-gray-800 shadow rounded-lg">
                        <h3 class="text-gray-700 dark:text-white text-lg font-semibold mb-4">
                            Gender & Nationality by Category & Ticket Type
                        </h3>

                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-900">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 dark:text-white uppercase tracking-wider border-r border-gray-200 dark:border-gray-600">
                                            Category & Ticket Type
                                        </th>
                                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-700 dark:text-white uppercase tracking-wider border-r border-gray-200 dark:border-gray-600">
                                            Laki-laki
                                        </th>
                                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-700 dark:text-white uppercase tracking-wider border-r border-gray-200 dark:border-gray-600">
                                            Perempuan
                                        </th>
                                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-700 dark:text-white uppercase tracking-wider bg-gray-100 dark:bg-gray-700">
                                            Peserta Luar Negeri
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                    @foreach($this->genderNationalityTable as $row)
                                        <tr class="{{ $row['ticketType'] === 'Total' ? 'bg-gray-50 dark:bg-gray-900 font-semibold' : 'hover:bg-gray-50 dark:hover:bg-gray-700' }}">
                                            <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-white border-r border-gray-200 dark:border-gray-600">
                                                {{ $row['ticketType'] }}
                                            </td>
                                            <td class="px-4 py-3 text-sm text-center text-gray-900 dark:text-gray-100 border-r border-gray-200 dark:border-gray-600">
                                                {{ number_format($row['male'] ?? 0) }}
                                            </td>
                                            <td class="px-4 py-3 text-sm text-center text-gray-900 dark:text-gray-100 border-r border-gray-200 dark:border-gray-600">
                                                {{ number_format($row['female'] ?? 0) }}
                                            </td>
                                            <td class="px-4 py-3 text-sm text-center font-semibold text-gray-900 dark:text-white bg-gray-100 dark:bg-gray-700">
                                                {{ number_format($row['foreigner'] ?? 0) }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif
            </div>
        @endif

        {{-- Community & City / Regency Rank --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
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
        </div>
    </div>

    {{-- Print Stylesheet - Very Simple Version --}}
    <style>
        @media print {
            @page {
                size: A4 portrait;
                margin: 1cm 1.5cm;
            }

            /* Hide non-essential elements */
            .print\\:hidden,
            button,
            [onclick],
            .filament-sidebar,
            .filament-header,
            nav,
            .filament-topbar,
            .filament-page-header,
            .filament-page-header-heading,
            .filament-page-header-title,
            h1,
            .filament-page-title,
            .filament-page-actions,
            .filament-breadcrumbs,
            .filament-main-content > header,
            header,
            .fi-page-header,
            .fi-page-header-heading,
            .fi-page-header-title {
                display: none !important;
                visibility: hidden !important;
                height: 0 !important;
                margin: 0 !important;
                padding: 0 !important;
            }

            /* Hide empty pages and ensure content starts at top */
            .filament-main-content {
                padding: 0 !important;
                margin: 0 !important;
            }

            /* Ensure page content starts immediately */
            .filament-page {
                padding-top: 0 !important;
                margin-top: 0 !important;
            }

            /* Make sure first element starts at top of page */
            .space-y-6 {
                margin-top: 0 !important;
                padding-top: 0 !important;
            }

            /* Base styles - Ultra Compact */
            body,
            html {
                margin: 0 !important;
                padding: 0 !important;
                font-family: 'Segoe UI', Arial, sans-serif;
                font-size: 7pt;
                line-height: 1.0;
                color: #000 !important;
                background: white !important;
            }

            /* Ensure content is visible */
            .space-y-6,
            .filament-page,
            [wire\\:id] {
                display: block !important;
                visibility: visible !important;
                opacity: 1 !important;
            }

            /* Remove shadows and rounded corners */
            .shadow,
            .shadow-sm,
            .shadow-lg,
            .rounded-lg,
            .rounded-xl,
            .rounded-2xl {
                box-shadow: none !important;
                border-radius: 0 !important;
            }

            /* Header Card - Ultra Compact header */
            .p-6:first-child {
                text-align: center;
                margin-bottom: 4px !important;
                padding: 4px 0 !important;
                border-bottom: 1px solid #000 !important;
                background: white !important;
            }

            .p-6:first-child h2 {
                margin: 0 0 2px 0 !important;
                font-size: 11pt;
                font-weight: bold;
                color: #000 !important;
                text-transform: uppercase;
                letter-spacing: 0.3px;
                line-height: 1.1;
            }

            .p-6:first-child .space-y-2 {
                font-size: 7pt;
                color: #000 !important;
                line-height: 1.0;
            }

            .p-6:first-child .space-y-2 > * {
                margin: 1px 0 !important;
            }

            /* All cards become simple sections - Ultra Compact */
            .p-6,
            .p-4 {
                background: white !important;
                border: none !important;
                padding: 2px 0 !important;
                margin-bottom: 4px !important;
                box-shadow: none !important;
            }

            /* Ensure content containers are visible */
            .space-y-6 {
                display: block !important;
                visibility: visible !important;
            }

            .space-y-6 > * {
                display: block !important;
                visibility: visible !important;
            }

            /* Section Titles - Ultra Compact */
            h3 {
                font-size: 9pt;
                font-weight: bold;
                margin: 4px 0 2px 0 !important;
                padding-bottom: 1px;
                border-bottom: 1px solid #000 !important;
                color: #000 !important;
                page-break-after: avoid;
                line-height: 1.1;
            }

            /* Tables - Ultra compact */
            table {
                width: 100% !important;
                margin: 2px 0 !important;
                border-collapse: collapse !important;
                border: 1px solid #000 !important;
                font-size: 6.5pt;
                page-break-inside: auto;
            }

            thead {
                display: table-header-group !important;
            }

            tbody {
                display: table-row-group !important;
            }

            th {
                padding: 2px 4px !important;
                border: 1px solid #000 !important;
                background: #f0f0f0 !important;
                font-weight: bold !important;
                text-align: left !important;
                color: #000 !important;
                font-size: 6.5pt !important;
                line-height: 1.0;
            }

            th.text-center {
                text-align: center !important;
            }

            td {
                padding: 1px 4px !important;
                border: 1px solid #000 !important;
                color: #000 !important;
                font-size: 6.5pt !important;
                background: white !important;
                line-height: 1.0;
            }

            td.text-center {
                text-align: center !important;
            }

            tbody tr:last-child {
                background: #f5f5f5 !important;
                font-weight: bold !important;
            }

            /* All text colors to black */
            * {
                color: #000 !important;
            }

            /* Grid layouts - Ultra Compact */
            .grid {
                display: block !important;
            }

            .grid > div {
                display: block !important;
                margin-bottom: 2px !important;
                page-break-inside: avoid;
            }

            /* Lists - Ultra Compact */
            ol, ul {
                margin: 2px 0 !important;
                padding-left: 15px;
            }

            li {
                margin: 0 !important;
                font-size: 7pt;
                color: #000 !important;
                line-height: 1.0;
            }

            /* Remove gaps and spacing */
            .gap-4,
            .gap-6 {
                gap: 0 !important;
            }

            .space-y-6 > * + * {
                margin-top: 0 !important;
            }

            /* Overflow */
            .overflow-x-auto {
                overflow: visible !important;
            }

            /* Ensure tables are visible */
            table {
                display: table !important;
                visibility: visible !important;
            }

            thead {
                display: table-header-group !important;
            }

            tbody {
                display: table-row-group !important;
            }

            tr {
                display: table-row !important;
            }

            td,
            th {
                display: table-cell !important;
                visibility: visible !important;
            }

            /* Page breaks - Ultra Compact */
            .space-y-6 > div {
                page-break-inside: avoid;
                margin-bottom: 2px !important;
            }

            /* Reduce all spacing */
            p {
                margin: 1px 0 !important;
                line-height: 1.0;
            }

            div {
                line-height: 1.0;
            }

            /* Prevent unnecessary page breaks */
            .space-y-6 {
                margin: 0 !important;
                padding: 0 !important;
            }

            /* Make tables fit better */
            table {
                page-break-inside: auto;
            }

            tr {
                page-break-inside: avoid;
            }

            /* Footer */
            @page {
                @bottom-right {
                    content: "Halaman " counter(page) " dari " counter(pages);
                    font-size: 8pt;
                    color: #666;
                }
            }
        }
    </style>
</x-filament::page>
