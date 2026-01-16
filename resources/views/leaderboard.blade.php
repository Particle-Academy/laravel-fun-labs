@extends('lfl::layout')

@section('title', 'Leaderboard')

@section('content')
<div class="card">
    <h1>Leaderboard</h1>

    <div style="margin-bottom: 20px;">
        <form method="GET" style="display: flex; gap: 10px; align-items: center;">
            <select name="by" style="padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                <option value="xp" {{ $sortBy === 'xp' ? 'selected' : '' }}>XP</option>
                <option value="achievements" {{ $sortBy === 'achievements' ? 'selected' : '' }}>Achievements</option>
                <option value="prizes" {{ $sortBy === 'prizes' ? 'selected' : '' }}>Prizes</option>
            </select>
            <select name="period" style="padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                <option value="all-time" {{ $period === 'all-time' ? 'selected' : '' }}>All Time</option>
                <option value="daily" {{ $period === 'daily' ? 'selected' : '' }}>Daily</option>
                <option value="weekly" {{ $period === 'weekly' ? 'selected' : '' }}>Weekly</option>
                <option value="monthly" {{ $period === 'monthly' ? 'selected' : '' }}>Monthly</option>
            </select>
            <button type="submit" style="padding: 8px 16px; background: #007bff; color: #fff; border: none; border-radius: 4px; cursor: pointer;">Filter</button>
        </form>
    </div>

    <table>
        <thead>
            <tr>
                <th>Rank</th>
                <th>User</th>
                <th>Total XP</th>
                <th>Achievements</th>
                <th>Prizes</th>
            </tr>
        </thead>
        <tbody>
            @forelse($leaderboard as $profile)
                <tr>
                    <td><strong>#{{ $profile->rank }}</strong></td>
                    <td>
                        <a href="{{ route('lfl.profiles.show', ['type' => $type, 'id' => $profile->awardable_id]) }}">
                            {{ $profile->awardable->name ?? 'User #' . $profile->awardable_id }}
                        </a>
                    </td>
                    <td>{{ number_format((int) $profile->total_xp, 0) }}</td>
                    <td>{{ $profile->achievement_count }}</td>
                    <td>{{ $profile->prize_count }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" style="text-align: center; padding: 40px; color: #999;">
                        No entries found
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @if($leaderboard->hasPages())
        <div class="pagination">
            @if($leaderboard->onFirstPage())
                <span style="padding: 8px 12px; color: #999;">Previous</span>
            @else
                <a href="{{ $leaderboard->previousPageUrl() }}">Previous</a>
            @endif

            @foreach($leaderboard->getUrlRange(1, $leaderboard->lastPage()) as $page => $url)
                @if($page == $leaderboard->currentPage())
                    <span class="active">{{ $page }}</span>
                @else
                    <a href="{{ $url }}">{{ $page }}</a>
                @endif
            @endforeach

            @if($leaderboard->hasMorePages())
                <a href="{{ $leaderboard->nextPageUrl() }}">Next</a>
            @else
                <span style="padding: 8px 12px; color: #999;">Next</span>
            @endif
        </div>
    @endif
</div>
@endsection

