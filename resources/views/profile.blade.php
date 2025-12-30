@extends('lfl::layout')

@section('title', 'Profile')

@section('content')
<div class="card">
    <h1>Profile</h1>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px;">
        <div>
            <h3 style="color: #999; font-size: 14px; margin-bottom: 5px;">Total Points</h3>
            <div style="font-size: 32px; font-weight: bold; color: #007bff;">
                {{ number_format((float) $profile->total_points, 0) }}
            </div>
        </div>
        <div>
            <h3 style="color: #999; font-size: 14px; margin-bottom: 5px;">Achievements</h3>
            <div style="font-size: 32px; font-weight: bold; color: #28a745;">
                {{ $profile->achievement_count }}
            </div>
        </div>
        <div>
            <h3 style="color: #999; font-size: 14px; margin-bottom: 5px;">Prizes</h3>
            <div style="font-size: 32px; font-weight: bold; color: #ffc107;">
                {{ $profile->prize_count }}
            </div>
        </div>
    </div>

    <h2>Achievements</h2>
    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 15px; margin-bottom: 30px;">
        @forelse($achievements as $grant)
            <div style="padding: 15px; border: 1px solid #eee; border-radius: 8px;">
                <div style="font-weight: 600; margin-bottom: 5px;">{{ $grant->achievement->name }}</div>
                @if($grant->achievement->description)
                    <div style="font-size: 14px; color: #666;">{{ $grant->achievement->description }}</div>
                @endif
                <div style="font-size: 12px; color: #999; margin-top: 10px;">
                    {{ $grant->created_at->format('M d, Y') }}
                </div>
            </div>
        @empty
            <div style="grid-column: 1 / -1; text-align: center; padding: 40px; color: #999;">
                No achievements yet
            </div>
        @endforelse
    </div>

    <h2>Recent Awards</h2>
    <table>
        <thead>
            <tr>
                <th>Type</th>
                <th>Amount</th>
                <th>Reason</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody>
            @forelse($recentAwards as $award)
                <tr>
                    <td>
                        <span class="badge badge-primary">{{ ucfirst($award->type) }}</span>
                    </td>
                    <td>{{ number_format((float) $award->amount, 0) }}</td>
                    <td>{{ $award->reason ?? '-' }}</td>
                    <td>{{ $award->created_at->format('M d, Y H:i') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" style="text-align: center; padding: 40px; color: #999;">
                        No awards yet
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection

