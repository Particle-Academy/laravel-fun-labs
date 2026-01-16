@extends('lfl::layout')

@section('title', 'Profile')

@section('content')
<div class="card">
    <h1>Profile</h1>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px;">
        <div>
            <h3 style="color: #999; font-size: 14px; margin-bottom: 5px;">Total XP</h3>
            <div style="font-size: 32px; font-weight: bold; color: #007bff;">
                {{ number_format((int) $profile->total_xp, 0) }}
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

    @if($profileMetrics->isNotEmpty())
    <h2>XP by Category</h2>
    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 15px; margin-bottom: 30px;">
        @foreach($profileMetrics as $metric)
            <div style="padding: 15px; border: 1px solid #eee; border-radius: 8px;">
                <div style="font-weight: 600; margin-bottom: 5px;">{{ $metric->gamedMetric->name }}</div>
                <div style="font-size: 24px; font-weight: bold; color: #007bff;">
                    {{ number_format($metric->total_xp, 0) }} XP
                </div>
                <div style="font-size: 14px; color: #666;">
                    Level {{ $metric->current_level }}
                </div>
            </div>
        @endforeach
    </div>
    @endif

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
</div>
@endsection

