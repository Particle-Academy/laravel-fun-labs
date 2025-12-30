@extends('lfl::layout')

@section('title', 'MetricLevels Management')

@section('content')
<div class="card">
    <h1>MetricLevels</h1>

    @if(session('success'))
        <div style="background: #d4edda; color: #155724; padding: 12px; border-radius: 4px; margin-bottom: 20px;">
            {{ session('success') }}
        </div>
    @endif

    <div style="background: #f8f9fa; padding: 20px; border-radius: 4px; margin-bottom: 30px;">
        <h2 style="margin-top: 0; margin-bottom: 15px;">Create New MetricLevel</h2>
        <form method="POST" action="{{ route('lfl.admin.metric-levels.store') }}" style="display: grid; gap: 15px;">
            @csrf
            <div>
                <label for="gamed_metric_id" style="display: block; margin-bottom: 5px; font-weight: bold;">GamedMetric *</label>
                <select id="gamed_metric_id" name="gamed_metric_id" required style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                    <option value="">Select a GamedMetric</option>
                    @foreach($metrics as $metric)
                        <option value="{{ $metric->id }}">{{ $metric->name }}</option>
                    @endforeach
                </select>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <div>
                    <label for="level" style="display: block; margin-bottom: 5px; font-weight: bold;">Level *</label>
                    <input type="number" id="level" name="level" min="1" required style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                </div>
                <div>
                    <label for="xp_threshold" style="display: block; margin-bottom: 5px; font-weight: bold;">XP Threshold *</label>
                    <input type="number" id="xp_threshold" name="xp_threshold" min="0" required style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                </div>
            </div>
            <div>
                <label for="name" style="display: block; margin-bottom: 5px; font-weight: bold;">Name *</label>
                <input type="text" id="name" name="name" required style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
            </div>
            <div>
                <label for="description" style="display: block; margin-bottom: 5px; font-weight: bold;">Description</label>
                <textarea id="description" name="description" rows="3" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;"></textarea>
            </div>
            <div style="border-top: 1px solid #ddd; padding-top: 15px;">
                <label style="display: block; margin-bottom: 10px; font-weight: bold;">Attach Achievements</label>
                <p style="color: #666; font-size: 12px; margin-bottom: 10px;">Select achievements that should be automatically awarded when users reach this level:</p>
                <div style="max-height: 150px; overflow-y: auto; border: 1px solid #ddd; border-radius: 4px; padding: 10px;">
                    @php
                        $achievements = \LaravelFunLab\Models\Achievement::active()->orderBy('name')->get();
                    @endphp
                    @foreach($achievements as $achievement)
                        <label style="display: flex; align-items: center; gap: 8px; padding: 5px 0;">
                            <input type="checkbox" name="achievement_ids[]" value="{{ $achievement->id }}">
                            <span>{{ $achievement->icon ?? '' }} {{ $achievement->name }}</span>
                        </label>
                    @endforeach
                    @if($achievements->isEmpty())
                        <div style="color: #999; padding: 10px; text-align: center; font-size: 12px;">No achievements available. <a href="{{ route('lfl.admin.achievements.create') }}">Create an achievement</a> first.</div>
                    @endif
                </div>
            </div>
            <button type="submit" style="background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; font-weight: bold;">Create MetricLevel</button>
        </form>
    </div>

    <table>
        <thead>
            <tr>
                <th>GamedMetric</th>
                <th>Level</th>
                <th>XP Threshold</th>
                <th>Name</th>
                <th>Description</th>
                <th>Achievements</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($levels as $level)
                <tr>
                    <td><strong>{{ $level->gamedMetric->name }}</strong></td>
                    <td>{{ $level->level }}</td>
                    <td>{{ number_format($level->xp_threshold) }}</td>
                    <td>{{ $level->name }}</td>
                    <td>{{ $level->description ?? '-' }}</td>
                    <td>
                        @php
                            $achievements = $level->achievements;
                        @endphp
                        @if($achievements->isNotEmpty())
                            <div style="font-size: 12px;">
                                @foreach($achievements as $achievement)
                                    <div>{{ $achievement->icon ?? '' }} {{ $achievement->name }}</div>
                                @endforeach
                            </div>
                        @else
                            <span style="color: #999;">-</span>
                        @endif
                    </td>
                    <td>
                        <a href="{{ route('lfl.admin.metric-levels.edit', $level) }}" style="color: #007bff; text-decoration: none;">Edit</a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" style="text-align: center; padding: 40px; color: #999;">
                        No MetricLevels found
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @if($levels->hasPages())
        <div class="pagination">
            {{ $levels->links() }}
        </div>
    @endif
</div>
@endsection

