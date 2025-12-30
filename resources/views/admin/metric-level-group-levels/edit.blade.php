@extends('lfl::layout')

@section('title', 'Edit MetricLevelGroupLevel')

@section('content')
<div class="card">
    <h1>Edit MetricLevelGroupLevel</h1>
    <p style="color: #666; margin-bottom: 20px;">
        Group: <strong>{{ $level->metricLevelGroup->name }}</strong>
    </p>

    <form method="POST" action="{{ route('lfl.admin.metric-level-group-levels.update', $level) }}" style="display: grid; gap: 20px; max-width: 800px;">
        @csrf
        @method('PUT')

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
            <div>
                <label for="level" style="display: block; margin-bottom: 5px; font-weight: bold;">Level *</label>
                <input type="number" id="level" name="level" min="1" value="{{ old('level', $level->level) }}" required style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                @error('level')
                    <div style="color: #dc3545; font-size: 14px; margin-top: 5px;">{{ $message }}</div>
                @enderror
            </div>
            <div>
                <label for="xp_threshold" style="display: block; margin-bottom: 5px; font-weight: bold;">XP Threshold *</label>
                <input type="number" id="xp_threshold" name="xp_threshold" min="0" value="{{ old('xp_threshold', $level->xp_threshold) }}" required style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                @error('xp_threshold')
                    <div style="color: #dc3545; font-size: 14px; margin-top: 5px;">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <div>
            <label for="name" style="display: block; margin-bottom: 5px; font-weight: bold;">Name *</label>
            <input type="text" id="name" name="name" value="{{ old('name', $level->name) }}" required style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
            @error('name')
                <div style="color: #dc3545; font-size: 14px; margin-top: 5px;">{{ $message }}</div>
            @enderror
        </div>

        <div>
            <label for="description" style="display: block; margin-bottom: 5px; font-weight: bold;">Description</label>
            <textarea id="description" name="description" rows="3" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">{{ old('description', $level->description) }}</textarea>
            @error('description')
                <div style="color: #dc3545; font-size: 14px; margin-top: 5px;">{{ $message }}</div>
            @enderror
        </div>

        <div style="border-top: 1px solid #ddd; padding-top: 15px;">
            <label style="display: block; margin-bottom: 10px; font-weight: bold;">Attach Achievements</label>
            <p style="color: #666; font-size: 12px; margin-bottom: 10px;">Select achievements that should be automatically awarded when users reach this group level:</p>
            <div style="max-height: 200px; overflow-y: auto; border: 1px solid #ddd; border-radius: 4px; padding: 10px;">
                @php
                    $attachedAchievementIds = old('achievement_ids', $level->achievements->pluck('id')->toArray());
                @endphp
                @foreach($achievements as $achievement)
                    <label style="display: flex; align-items: center; gap: 8px; padding: 5px 0;">
                        <input type="checkbox" name="achievement_ids[]" value="{{ $achievement->id }}" {{ in_array($achievement->id, $attachedAchievementIds) ? 'checked' : '' }}>
                        <span>{{ $achievement->icon ?? '' }} {{ $achievement->name }}</span>
                    </label>
                @endforeach
                @if($achievements->isEmpty())
                    <div style="color: #999; padding: 10px; text-align: center; font-size: 12px;">No achievements available. <a href="{{ route('lfl.admin.achievements.create') }}">Create an achievement</a> first.</div>
                @endif
            </div>
        </div>

        <div style="display: flex; gap: 10px; margin-top: 20px;">
            <button type="submit" style="background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; font-weight: bold;">Update Level</button>
            <a href="{{ route('lfl.admin.metric-level-groups') }}" style="background: #6c757d; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; text-decoration: none; display: inline-block;">Cancel</a>
        </div>
    </form>
</div>
@endsection

