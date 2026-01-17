@extends('lfl::layout')

@section('title', 'Edit ' . $metric->name)

@section('content')
<div class="card">
    <div style="margin-bottom: 20px;">
        <a href="{{ route('lfl.admin.gamed-metrics') }}" style="color: #007bff; text-decoration: none;">← Back to GamedMetrics</a>
    </div>

    <h1>Edit GamedMetric</h1>

    @if($errors->any())
        <div style="background: #f8d7da; color: #721c24; padding: 12px; border-radius: 4px; margin-bottom: 20px;">
            <strong>Please fix the following errors:</strong>
            <ul style="margin: 10px 0 0 20px;">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('lfl.admin.gamed-metrics.update', $metric) }}" style="display: grid; gap: 20px; max-width: 800px;">
        @csrf
        @method('PUT')

        <div>
            <label for="name" style="display: block; margin-bottom: 5px; font-weight: bold;">Name *</label>
            <input type="text" id="name" name="name" value="{{ old('name', $metric->name) }}" required style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
            @error('name')
                <div style="color: #dc3545; font-size: 14px; margin-top: 5px;">{{ $message }}</div>
            @enderror
        </div>

        <div>
            <label for="slug" style="display: block; margin-bottom: 5px; font-weight: bold;">Slug *</label>
            <input type="text" id="slug" name="slug" value="{{ old('slug', $metric->slug) }}" required style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; font-family: monospace;">
            @error('slug')
                <div style="color: #dc3545; font-size: 14px; margin-top: 5px;">{{ $message }}</div>
            @enderror
            <p style="color: #666; font-size: 12px; margin-top: 5px;">Used in code to reference this metric. Must be unique.</p>
        </div>

        <div>
            <label for="description" style="display: block; margin-bottom: 5px; font-weight: bold;">Description</label>
            <textarea id="description" name="description" rows="4" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">{{ old('description', $metric->description) }}</textarea>
            @error('description')
                <div style="color: #dc3545; font-size: 14px; margin-top: 5px;">{{ $message }}</div>
            @enderror
        </div>

        <div>
            <label for="icon" style="display: block; margin-bottom: 5px; font-weight: bold;">Icon</label>
            <input type="text" id="icon" name="icon" value="{{ old('icon', $metric->icon) }}" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;" placeholder="e.g., ⚔️, 🛠️, 💬">
            @error('icon')
                <div style="color: #dc3545; font-size: 14px; margin-top: 5px;">{{ $message }}</div>
            @enderror
            <p style="color: #666; font-size: 12px; margin-top: 5px;">Emoji or icon to represent this metric</p>
        </div>

        <div>
            <label style="display: flex; align-items: center; gap: 8px;">
                <input type="checkbox" name="active" value="1" {{ old('active', $metric->active) ? 'checked' : '' }}>
                <span style="font-weight: bold;">Active</span>
            </label>
            <p style="color: #666; font-size: 12px; margin-top: 5px; margin-left: 28px;">Only active metrics can receive XP awards</p>
            @error('active')
                <div style="color: #dc3545; font-size: 14px; margin-top: 5px;">{{ $message }}</div>
            @enderror
        </div>

        <div style="display: flex; gap: 10px; margin-top: 20px;">
            <button type="submit" style="background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; font-weight: bold;">Update Metric</button>
            <a href="{{ route('lfl.admin.gamed-metrics') }}" style="background: #6c757d; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; text-decoration: none; display: inline-block;">Cancel</a>
        </div>
    </form>
</div>
@endsection
