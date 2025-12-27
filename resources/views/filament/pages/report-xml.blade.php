<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <title>Event Report</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 1cm 1.5cm;
        }
        
        body {
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Arial, sans-serif;
            font-size: 7pt;
            line-height: 1.0;
            color: #000;
        }
        
        .header {
            text-align: center;
            margin-bottom: 4px;
            padding: 4px 0;
            border-bottom: 1px solid #000;
        }
        
        .header h1 {
            margin: 0 0 2px 0;
            font-size: 11pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            line-height: 1.1;
        }
        
        .header-info {
            font-size: 7pt;
            line-height: 1.0;
            margin: 1px 0;
        }
        
        .section-title {
            font-size: 9pt;
            font-weight: bold;
            margin: 4px 0 2px 0;
            padding-bottom: 1px;
            border-bottom: 1px solid #000;
            line-height: 1.1;
        }
        
        table {
            width: 100%;
            margin: 2px 0;
            border-collapse: collapse;
            border: 1px solid #000;
            font-size: 6.5pt;
        }
        
        th {
            padding: 2px 4px;
            border: 1px solid #000;
            background: #f0f0f0;
            font-weight: bold;
            text-align: left;
            font-size: 6.5pt;
            line-height: 1.0;
        }
        
        th.text-center {
            text-align: center;
        }
        
        td {
            padding: 1px 4px;
            border: 1px solid #000;
            font-size: 6.5pt;
            line-height: 1.0;
        }
        
        td.text-center {
            text-align: center;
        }
        
        tbody tr:last-child {
            background: #f5f5f5;
            font-weight: bold;
        }
        
        .stats-grid {
            display: table;
            width: 100%;
            margin: 2px 0;
        }
        
        .stats-grid > div {
            display: table-cell;
            padding: 2px 8px;
            border: 1px solid #000;
            width: 50%;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>EVENT REPORT DASHBOARD</h1>
        <div class="header-info">
            <div>Event: {{ $event->name }}</div>
            <div>Report Date / Time: {{ $reportGeneratedAt }}</div>
        </div>
    </div>

    {{-- Global Stats --}}
    @if(!empty($globalStats))
        <div class="stats-grid">
            <div>
                <strong>Total Participants:</strong> {{ number_format($globalStats['total_participants'] ?? 0) }}
            </div>
            <div>
                <strong>Total Revenue:</strong> Rp {{ number_format($globalStats['total_revenue'] ?? 0, 0, ',', '.') }}
            </div>
        </div>
    @endif

    {{-- Revenue Table --}}
    @if(!empty($chartData['labels']))
        <div class="section-title">Registrations & Revenue by Category & Ticket Type</div>
        <table>
            <thead>
                <tr>
                    <th>Category & Ticket Type</th>
                    <th class="text-center">Registrations</th>
                    <th class="text-center">Revenue</th>
                </tr>
            </thead>
            <tbody>
                @foreach($chartData['labels'] as $index => $label)
                    <tr>
                        <td>{{ $label }}</td>
                        <td class="text-center">{{ number_format($chartData['values'][$index] ?? 0) }}</td>
                        <td class="text-center">Rp {{ number_format($chartData['revenues'][$index] ?? 0, 0, ',', '.') }}</td>
                    </tr>
                @endforeach
                <tr>
                    <td><strong>Total</strong></td>
                    <td class="text-center"><strong>{{ number_format(array_sum($chartData['values'] ?? [])) }}</strong></td>
                    <td class="text-center"><strong>Rp {{ number_format(array_sum($chartData['revenues'] ?? []), 0, ',', '.') }}</strong></td>
                </tr>
            </tbody>
        </table>
    @endif

    {{-- Gender & Nationality Table --}}
    @if(!empty($genderNationalityTable))
        <div class="section-title">Gender & Nationality by Category & Ticket Type</div>
        <table>
            <thead>
                <tr>
                    <th>Category & Ticket Type</th>
                    <th class="text-center">Laki-laki</th>
                    <th class="text-center">Perempuan</th>
                    <th class="text-center">Peserta Luar Negeri</th>
                </tr>
            </thead>
            <tbody>
                @foreach($genderNationalityTable as $row)
                    <tr>
                        <td>{{ $row['ticketType'] }}</td>
                        <td class="text-center">{{ number_format($row['male'] ?? 0) }}</td>
                        <td class="text-center">{{ number_format($row['female'] ?? 0) }}</td>
                        <td class="text-center">{{ number_format($row['foreigner'] ?? 0) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    {{-- Jersey Table --}}
    @if(!empty($jerseyTable['data']))
        <div class="section-title">Jersey Distribution by Category & Ticket Type</div>
        <table>
            <thead>
                <tr>
                    <th>Size</th>
                    @foreach($jerseyTable['ticketTypes'] as $ticketType)
                        <th class="text-center">{{ $ticketType }}</th>
                    @endforeach
                    <th class="text-center">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($jerseyTable['data'] as $row)
                    <tr>
                        <td>{{ $row['size'] }}</td>
                        @foreach($jerseyTable['ticketTypes'] as $ticketType)
                            <td class="text-center">{{ $row[$ticketType] ?? 0 }}</td>
                        @endforeach
                        <td class="text-center"><strong>{{ $row['totals'] }}</strong></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    {{-- Community & City Ranks --}}
    @if(!empty($communityRanks) || !empty($cityRanks))
        <div style="display: table; width: 100%; margin: 2px 0;">
            @if(!empty($communityRanks))
                <div style="display: table-cell; width: 50%; padding-right: 4px; vertical-align: top;">
                    <div class="section-title">Community Rank</div>
                    <ol style="margin: 2px 0; padding-left: 20px; font-size: 7pt;">
                        @foreach(array_slice($communityRanks, 0, 50) as $index => $community)
                            <li style="margin: 0; line-height: 1.0;">
                                {{ $index + 1 }}. {{ $community['name'] }} ({{ $community['count'] }})
                            </li>
                        @endforeach
                    </ol>
                </div>
            @endif
            @if(!empty($cityRanks))
                <div style="display: table-cell; width: 50%; padding-left: 4px; vertical-align: top;">
                    <div class="section-title">City / Regency Rank</div>
                    <ol style="margin: 2px 0; padding-left: 20px; font-size: 7pt;">
                        @foreach($cityRanks as $index => $city)
                            <li style="margin: 0; line-height: 1.0;">
                                {{ $index + 1 }}. {{ $city['location'] }} ({{ $city['count'] }})
                            </li>
                        @endforeach
                    </ol>
                </div>
            @endif
        </div>
    @endif
</body>
</html>

