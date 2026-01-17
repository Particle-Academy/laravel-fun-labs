@extends('lfl::layout')

@section('title', $group->name . ' - MetricLevelGroup Details')

@section('content')
<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
        <div>
            <h1 style="margin: 0;">{{ $group->name }}</h1>
            <p style="color: #666; margin: 5px 0 0 0; font-size: 14px;">
                <code>{{ $group->slug }}</code>
            </p>
        </div>
        <div style="display: flex; gap: 10px; align-items: center;">
            <a href="{{ route('lfl.admin.metric-level-groups.edit', $group) }}" style="background: #007bff; color: white; padding: 8px 16px; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; text-decoration: none; display: inline-block;">
                Edit Group
            </a>
            <a href="{{ route('lfl.admin.metric-level-groups') }}" style="color: #007bff; text-decoration: none;">← Back to Groups</a>
        </div>
    </div>

    @if(session('success'))
        <div style="background: #d4edda; color: #155724; padding: 12px; border-radius: 4px; margin-bottom: 20px;">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div style="background: #f8d7da; color: #721c24; padding: 12px; border-radius: 4px; margin-bottom: 20px;">
            {{ session('error') }}
        </div>
    @endif

    @if($group->description)
        <div style="background: #f8f9fa; padding: 15px; border-radius: 4px; margin-bottom: 30px;">
            <p style="margin: 0; color: #666;">{{ $group->description }}</p>
        </div>
    @endif

    <!-- Metrics Section -->
    <div style="margin-bottom: 40px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
            <h2 style="margin: 0;">Metrics in Group</h2>
            <span style="color: #666; font-size: 14px;">{{ $group->metrics->count() }} metric{{ $group->metrics->count() !== 1 ? 's' : '' }}</span>
        </div>
        
        @if($group->metrics->isNotEmpty())
            <div style="display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 20px;">
                @foreach($group->metrics as $groupMetric)
                    <div style="background: #e9ecef; padding: 10px 15px; border-radius: 6px; display: flex; align-items: center; gap: 8px; position: relative;">
                        <span style="font-size: 18px;">{{ $groupMetric->gamedMetric->icon ?? '📊' }}</span>
                        <div>
                            <div style="font-weight: bold; font-size: 14px;">{{ $groupMetric->gamedMetric->name }}</div>
                            <div style="color: #666; font-size: 12px;">
                                <form method="POST" action="{{ route('lfl.admin.metric-level-group-metrics.update', $groupMetric) }}" style="display: inline;">
                                    @csrf
                                    @method('PUT')
                                    Weight: 
                                    <input type="number" name="weight" value="{{ $groupMetric->weight }}" step="0.01" min="0.01" max="100" style="width: 60px; padding: 2px 5px; border: 1px solid #ccc; border-radius: 3px; font-size: 12px;">
                                    <button type="submit" style="background: #007bff; color: white; border: none; padding: 2px 8px; border-radius: 3px; cursor: pointer; font-size: 11px; margin-left: 5px;">Update</button>
                                </form>
                            </div>
                        </div>
                        <form method="POST" action="{{ route('lfl.admin.metric-level-group-metrics.delete', $groupMetric) }}" style="margin-left: 10px;" onsubmit="return confirm('Are you sure you want to remove this metric from the group?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" style="background: #dc3545; color: white; border: none; padding: 4px 8px; border-radius: 3px; cursor: pointer; font-size: 11px;" title="Remove metric">×</button>
                        </form>
                    </div>
                @endforeach
            </div>
        @else
            <div style="background: #f8f9fa; padding: 20px; border-radius: 4px; text-align: center; color: #999; margin-bottom: 20px;">
                No metrics added to this group yet.
            </div>
        @endif

        @if($availableMetrics->isNotEmpty())
            <div style="background: #f8f9fa; padding: 20px; border-radius: 4px; border: 1px solid #dee2e6;">
                <h3 style="margin: 0 0 15px 0; font-size: 16px;">Add Metric to Group</h3>
                <form method="POST" action="{{ route('lfl.admin.metric-level-group-metrics.store', $group) }}" style="display: grid; grid-template-columns: 2fr 1fr auto; gap: 10px; align-items: end;">
                    @csrf
                    <div>
                        <label for="gamed_metric_id" style="display: block; margin-bottom: 5px; font-weight: bold; font-size: 14px;">Metric *</label>
                        <select id="gamed_metric_id" name="gamed_metric_id" required style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                            <option value="">Select a metric...</option>
                            @foreach($availableMetrics as $metric)
                                <option value="{{ $metric->id }}">{{ $metric->icon ?? '📊' }} {{ $metric->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="weight" style="display: block; margin-bottom: 5px; font-weight: bold; font-size: 14px;">Weight *</label>
                        <input type="number" id="weight" name="weight" value="1.0" step="0.01" min="0.01" max="100" required style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                        <p style="color: #666; font-size: 11px; margin: 5px 0 0 0;">Multiplier for XP</p>
                    </div>
                    <div>
                        <button type="submit" style="background: #28a745; color: white; padding: 8px 16px; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; white-space: nowrap;">
                            Add Metric
                        </button>
                    </div>
                </form>
            </div>
        @else
            <div style="background: #fff3cd; color: #856404; padding: 15px; border-radius: 4px; border: 1px solid #ffeaa7;">
                <p style="margin: 0; font-size: 14px;">All available metrics are already in this group. <a href="{{ route('lfl.admin.gamed-metrics') }}" style="color: #856404; text-decoration: underline;">Create a new metric</a> to add more.</p>
            </div>
        @endif
    </div>

    <!-- Levels Section -->
    <div>
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
            <h2 style="margin: 0;">Level Thresholds</h2>
            <a href="{{ route('lfl.admin.metric-level-group-levels.create', $group) }}" style="background: #28a745; color: white; padding: 8px 16px; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; text-decoration: none; display: inline-block;">
                + Add Level
            </a>
        </div>

        @if($group->levels->isNotEmpty())
            <table>
                <thead>
                    <tr>
                        <th>Level</th>
                        <th>Name</th>
                        <th>XP Threshold</th>
                        <th>Description</th>
                        <th>Achievements</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($group->levels->sortBy('level') as $level)
                        <tr>
                            <td><strong>{{ $level->level }}</strong></td>
                            <td>{{ $level->name }}</td>
                            <td><code>{{ number_format($level->xp_threshold) }}</code></td>
                            <td style="color: #666; font-size: 14px;">{{ $level->description ?? '-' }}</td>
                            <td>
                                @if($level->achievements->isNotEmpty())
                                    <div style="display: flex; flex-wrap: wrap; gap: 5px;">
                                        @foreach($level->achievements as $achievement)
                                            <span style="background: #e7f3ff; color: #0066cc; padding: 3px 8px; border-radius: 3px; font-size: 12px;">
                                                {{ $achievement->icon ?? '' }} {{ $achievement->name }}
                                            </span>
                                        @endforeach
                                    </div>
                                @else
                                    <span style="color: #999; font-size: 12px;">None</span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('lfl.admin.metric-level-group-levels.edit', $level) }}" style="color: #007bff; text-decoration: none; font-weight: bold;">Edit</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <div style="background: #f8f9fa; padding: 40px; border-radius: 4px; text-align: center; color: #999;">
                <p style="margin: 0 0 15px 0;">No levels defined yet.</p>
                <a href="{{ route('lfl.admin.metric-level-group-levels.create', $group) }}" style="background: #28a745; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; text-decoration: none; display: inline-block;">
                    Create First Level
                </a>
            </div>
        @endif
    </div>
</div>
@endsection
