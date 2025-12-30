@extends('lfl::layout')

@section('title', 'Admin Dashboard')

@section('content')
<div class="card">
    <h1>Admin Dashboard</h1>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px;">
        <div>
            <h3 style="color: #999; font-size: 14px; margin-bottom: 5px;">Total Profiles</h3>
            <div style="font-size: 32px; font-weight: bold; color: #007bff;">
                {{ number_format($stats['total_profiles']) }}
            </div>
        </div>
        <div>
            <h3 style="color: #999; font-size: 14px; margin-bottom: 5px;">Achievements</h3>
            <div style="font-size: 32px; font-weight: bold; color: #28a745;">
                {{ number_format($stats['total_achievements']) }}
            </div>
        </div>
        <div>
            <h3 style="color: #999; font-size: 14px; margin-bottom: 5px;">Prizes</h3>
            <div style="font-size: 32px; font-weight: bold; color: #ffc107;">
                {{ number_format($stats['total_prizes']) }}
            </div>
        </div>
        <div>
            <h3 style="color: #999; font-size: 14px; margin-bottom: 5px;">Total Awards</h3>
            <div style="font-size: 32px; font-weight: bold; color: #dc3545;">
                {{ number_format($stats['total_awards']) }}
            </div>
        </div>
        <div>
            <h3 style="color: #999; font-size: 14px; margin-bottom: 5px;">Points Awarded</h3>
            <div style="font-size: 32px; font-weight: bold; color: #6f42c1;">
                {{ number_format((float) $stats['total_points_awarded'], 0) }}
            </div>
        </div>
    </div>

    <nav style="margin-top: 20px;">
        <a href="{{ route('lfl.admin.achievements') }}" class="{{ request()->routeIs('lfl.admin.achievements') ? 'active' : '' }}">Achievements</a>
        <a href="{{ route('lfl.admin.prizes') }}" class="{{ request()->routeIs('lfl.admin.prizes') ? 'active' : '' }}">Prizes</a>
        <a href="{{ route('lfl.admin.analytics') }}" class="{{ request()->routeIs('lfl.admin.analytics') ? 'active' : '' }}">Analytics</a>
        <a href="{{ route('lfl.admin.gamed-metrics') }}" class="{{ request()->routeIs('lfl.admin.gamed-metrics') ? 'active' : '' }}">GamedMetrics</a>
        <a href="{{ route('lfl.admin.metric-levels') }}" class="{{ request()->routeIs('lfl.admin.metric-levels') ? 'active' : '' }}">MetricLevels</a>
        <a href="{{ route('lfl.admin.metric-level-groups') }}" class="{{ request()->routeIs('lfl.admin.metric-level-groups') ? 'active' : '' }}">MetricLevelGroups</a>
    </nav>
</div>
@endsection

