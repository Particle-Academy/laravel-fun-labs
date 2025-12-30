@extends('lfl::layout')

@section('title', 'Edit Achievement')

@section('content')
<div class="card">
    <h1>Edit Achievement</h1>

    <form method="POST" action="{{ route('lfl.admin.achievements.update', $achievement) }}" style="display: grid; gap: 20px; max-width: 800px;">
        @csrf
        @method('PUT')

        <div>
            <label for="name" style="display: block; margin-bottom: 5px; font-weight: bold;">Name *</label>
            <input type="text" id="name" name="name" value="{{ old('name', $achievement->name) }}" required style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
            @error('name')
                <div style="color: #dc3545; font-size: 14px; margin-top: 5px;">{{ $message }}</div>
            @enderror
        </div>

        <div>
            <label for="slug" style="display: block; margin-bottom: 5px; font-weight: bold;">Slug</label>
            <input type="text" id="slug" name="slug" value="{{ old('slug', $achievement->slug) }}" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
            @error('slug')
                <div style="color: #dc3545; font-size: 14px; margin-top: 5px;">{{ $message }}</div>
            @enderror
        </div>

        <div>
            <label for="description" style="display: block; margin-bottom: 5px; font-weight: bold;">Description</label>
            <textarea id="description" name="description" rows="3" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">{{ old('description', $achievement->description) }}</textarea>
            @error('description')
                <div style="color: #dc3545; font-size: 14px; margin-top: 5px;">{{ $message }}</div>
            @enderror
        </div>

        <div>
            <label for="icon" style="display: block; margin-bottom: 5px; font-weight: bold;">Icon</label>
            <input type="text" id="icon" name="icon" value="{{ old('icon', $achievement->icon) }}" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;" placeholder="e.g., 🏆, ⭐, 🎖️">
            @error('icon')
                <div style="color: #dc3545; font-size: 14px; margin-top: 5px;">{{ $message }}</div>
            @enderror
        </div>

        <div>
            <label for="awardable_type" style="display: block; margin-bottom: 5px; font-weight: bold;">Awardable Type</label>
            <input type="text" id="awardable_type" name="awardable_type" value="{{ old('awardable_type', $achievement->awardable_type) }}" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;" placeholder="Leave empty for universal (e.g., App\Models\User)">
            <small style="color: #666; font-size: 12px;">Leave empty to make this achievement available to all awardable types</small>
            @error('awardable_type')
                <div style="color: #dc3545; font-size: 14px; margin-top: 5px;">{{ $message }}</div>
            @enderror
        </div>

        <div>
            <label style="display: flex; align-items: center; gap: 8px;">
                <input type="checkbox" name="is_active" value="1" {{ old('is_active', $achievement->is_active) ? 'checked' : '' }}>
                <span>Active</span>
            </label>
        </div>

        <div>
            <label for="sort_order" style="display: block; margin-bottom: 5px; font-weight: bold;">Sort Order</label>
            <input type="number" id="sort_order" name="sort_order" value="{{ old('sort_order', $achievement->sort_order) }}" min="0" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
            @error('sort_order')
                <div style="color: #dc3545; font-size: 14px; margin-top: 5px;">{{ $message }}</div>
            @enderror
        </div>

        <div style="border-top: 1px solid #ddd; padding-top: 20px;">
            <h2 style="margin-top: 0; margin-bottom: 15px;">Attach Prizes</h2>
            <p style="color: #666; font-size: 14px; margin-bottom: 15px;">Select prizes that should be awarded when this achievement is unlocked:</p>
            <div style="max-height: 200px; overflow-y: auto; border: 1px solid #ddd; border-radius: 4px; padding: 10px;">
                @php
                    $attachedPrizeIds = old('prize_ids', $achievement->prizes->pluck('id')->toArray());
                @endphp
                @foreach($prizes as $prize)
                    <label style="display: flex; align-items: center; gap: 8px; padding: 5px 0;">
                        <input type="checkbox" name="prize_ids[]" value="{{ $prize->id }}" {{ in_array($prize->id, $attachedPrizeIds) ? 'checked' : '' }}>
                        <span>{{ $prize->name }} ({{ $prize->type->label() }}) - {{ $prize->cost_in_points > 0 ? number_format($prize->cost_in_points) . ' points' : 'Free' }}</span>
                    </label>
                @endforeach
                @if($prizes->isEmpty())
                    <div style="color: #999; padding: 20px; text-align: center;">No prizes available. <a href="{{ route('lfl.admin.prizes.create') }}">Create a prize</a> first.</div>
                @endif
            </div>
        </div>

        <div style="display: flex; gap: 10px; margin-top: 20px;">
            <button type="submit" style="background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; font-weight: bold;">Update Achievement</button>
            <a href="{{ route('lfl.admin.achievements') }}" style="background: #6c757d; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; text-decoration: none; display: inline-block;">Cancel</a>
        </div>
    </form>
</div>
@endsection

