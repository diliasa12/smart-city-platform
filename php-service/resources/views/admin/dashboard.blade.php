<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - SmartCity</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background-color: #f4f6f9;
            color: #1f2937;
        }
        .navbar {
            background-color: #1e293b;
            color: white;
            padding: 16px 32px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .navbar h1 { font-size: 20px; font-weight: 600; }
        .container { padding: 32px; max-width: 1200px; margin: 0 auto; }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin-bottom: 32px;
        }
        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            border-left: 4px solid #3b82f6;
        }
        .stat-card .label { font-size: 13px; color: #6b7280; margin-bottom: 6px; }
        .stat-card .value { font-size: 28px; font-weight: 700; color: #111827; }

        .section { background: white; border-radius: 10px; padding: 24px; margin-bottom: 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .section h2 { font-size: 16px; margin-bottom: 16px; color: #374151; }

        table { width: 100%; border-collapse: collapse; }
        th, td { text-align: left; padding: 10px 12px; border-bottom: 1px solid #e5e7eb; font-size: 14px; }
        th { color: #6b7280; font-weight: 600; font-size: 12px; text-transform: uppercase; }

        .badge { padding: 4px 10px; border-radius: 999px; font-size: 12px; font-weight: 600; }
        .badge-nyaman { background: #dcfce7; color: #166534; }
        .badge-cukup_nyaman { background: #fef9c3; color: #854d0e; }
        .badge-tidak_nyaman { background: #fee2e2; color: #991b1b; }
        .badge-belum_ada_data { background: #f3f4f6; color: #6b7280; }

        .badge-status-pending { background: #fef9c3; color: #854d0e; }
        .badge-status-approved { background: #dcfce7; color: #166534; }
        .badge-status-rejected { background: #fee2e2; color: #991b1b; }
        .badge-status-cancelled { background: #f3f4f6; color: #6b7280; }

        .empty { color: #9ca3af; font-size: 14px; padding: 16px 0; text-align: center; }
    </style>
</head>
<body>

    <div class="navbar">
        <h1>🏙️ SmartCity Admin Dashboard</h1>
        <span>{{ now()->format('d M Y, H:i') }}</span>
    </div>

    <div class="container">

        {{-- Statistik Ringkas --}}
        <div class="stats-grid">
            <div class="stat-card">
                <div class="label">Total User</div>
                <div class="value">{{ $stats['total_users'] }}</div>
            </div>
            <div class="stat-card">
                <div class="label">Total Ruangan</div>
                <div class="value">{{ $stats['total_rooms'] }}</div>
            </div>
            <div class="stat-card">
                <div class="label">Ruangan Aktif</div>
                <div class="value">{{ $stats['total_active_rooms'] }}</div>
            </div>
            <div class="stat-card">
                <div class="label">Booking Hari Ini</div>
                <div class="value">{{ $stats['total_bookings_today'] }}</div>
            </div>
        </div>

        {{-- Status Kenyamanan Ruangan --}}
        <div class="section">
            <h2>Status Kenyamanan Ruangan</h2>
            @if(count($roomStatus) > 0)
            <table>
                <thead>
                    <tr>
                        <th>Ruangan</th>
                        <th>Zona</th>
                        <th>Status</th>
                        <th>Suhu</th>
                        <th>Kelembaban</th>
                        <th>Kebisingan</th>
                        <th>Prediksi Jam Sibuk</th>
                        <th>Update Terakhir</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($roomStatus as $room)
                    <tr>
                        <td>{{ $room['room_name'] }}</td>
                        <td>{{ $room['zone'] ?? '-' }}</td>
                        <td><span class="badge badge-{{ $room['comfort'] }}">{{ $room['comfort'] }}</span></td>
                        <td>{{ $room['temperature'] !== null ? $room['temperature'].'°C' : '-' }}</td>
                        <td>{{ $room['humidity'] !== null ? $room['humidity'].'%' : '-' }}</td>
                        <td>{{ $room['decibel'] !== null ? $room['decibel'].' dB' : '-' }}</td>
                        <td>{{ $room['predicted_busy_hour'] !== null ? $room['predicted_busy_hour'].':00' : '-' }}</td>
                        <td>{{ $room['last_updated'] ?? '-' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @else
            <div class="empty">Belum ada data ruangan.</div>
            @endif
        </div>

        {{-- Booking Terbaru --}}
        <div class="section">
            <h2>Booking Terbaru</h2>
            @if(count($recentBookings) > 0)
            <table>
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Ruangan</th>
                        <th>Bangku</th>
                        <th>Tanggal</th>
                        <th>Jam</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($recentBookings as $booking)
                    <tr>
                        <td>{{ $booking['user_name'] }}</td>
                        <td>{{ $booking['room_name'] }}</td>
                        <td>{{ $booking['seat_number'] }}</td>
                        <td>{{ $booking['booking_date'] }}</td>
                        <td>{{ $booking['start_time'] }} - {{ $booking['end_time'] }}</td>
                        <td><span class="badge badge-status-{{ $booking['status'] }}">{{ $booking['status'] }}</span></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @else
            <div class="empty">Belum ada booking.</div>
            @endif
        </div>

    </div>

</body>
</html>