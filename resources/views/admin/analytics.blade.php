@extends('lfl::layout')

@section('title', 'Analytics')

@section('content')
<div class="card">
    <h1>Analytics</h1>

    <div style="margin-bottom: 20px;">
        <form method="GET" style="display: flex; gap: 10px; align-items: center;">
            <label>Period:</label>
            <select name="period" style="padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                <option value="7" {{ $period === '7' ? 'selected' : '' }}>Last 7 days</option>
                <option value="30" {{ $period === '30' ? 'selected' : '' }}>Last 30 days</option>
                <option value="90" {{ $period === '90' ? 'selected' : '' }}>Last 90 days</option>
            </select>
            <button type="submit" style="padding: 8px 16px; background: #007bff; color: #fff; border: none; border-radius: 4px; cursor: pointer;">Update</button>
        </form>
    </div>

    <h2>Awards Over Time</h2>
    <div class="card" style="margin-bottom: 30px;">
        <canvas id="awardsChart" style="max-height: 300px;"></canvas>
    </div>

    <h2>Top Earners</h2>
    <table>
        <thead>
            <tr>
                <th>Rank</th>
                <th>User</th>
                <th>Total Points</th>
                <th>Achievements</th>
            </tr>
        </thead>
        <tbody>
            @forelse($topEarners as $index => $profile)
                <tr>
                    <td><strong>#{{ $index + 1 }}</strong></td>
                    <td>{{ $profile->awardable->name ?? 'User #' . $profile->awardable_id }}</td>
                    <td>{{ number_format((float) $profile->total_points, 0) }}</td>
                    <td>{{ $profile->achievement_count }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" style="text-align: center; padding: 40px; color: #999;">
                        No data available
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const ctx = document.getElementById('awardsChart');
    if (ctx) {
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: {!! json_encode($awardsOverTime->pluck('date')) !!},
                datasets: [{
                    label: 'Awards',
                    data: {!! json_encode($awardsOverTime->pluck('count')) !!},
                    borderColor: '#007bff',
                    backgroundColor: 'rgba(0, 123, 255, 0.1)',
                    tension: 0.4
                }, {
                    label: 'Points Awarded',
                    data: {!! json_encode($awardsOverTime->pluck('total_points')) !!},
                    borderColor: '#28a745',
                    backgroundColor: 'rgba(40, 167, 69, 0.1)',
                    tension: 0.4,
                    yAxisID: 'y1'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                scales: {
                    y: {
                        beginAtZero: true
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        beginAtZero: true
                    }
                }
            }
        });
    }
</script>
@endpush
@endsection

