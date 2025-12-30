@extends('lfl::layout')

@section('title', 'Edit Prize')

@section('content')
<div class="card">
    <h1>Edit Prize</h1>

    <form method="POST" action="{{ route('lfl.admin.prizes.update', $prize) }}" style="display: grid; gap: 20px; max-width: 800px;">
        @csrf
        @method('PUT')

        <div>
            <label for="name" style="display: block; margin-bottom: 5px; font-weight: bold;">Name *</label>
            <input type="text" id="name" name="name" value="{{ old('name', $prize->name) }}" required style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
            @error('name')
                <div style="color: #dc3545; font-size: 14px; margin-top: 5px;">{{ $message }}</div>
            @enderror
        </div>

        <div>
            <label for="slug" style="display: block; margin-bottom: 5px; font-weight: bold;">Slug</label>
            <input type="text" id="slug" name="slug" value="{{ old('slug', $prize->slug) }}" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
            @error('slug')
                <div style="color: #dc3545; font-size: 14px; margin-top: 5px;">{{ $message }}</div>
            @enderror
        </div>

        <div>
            <label for="description" style="display: block; margin-bottom: 5px; font-weight: bold;">Description</label>
            <textarea id="description" name="description" rows="3" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">{{ old('description', $prize->description) }}</textarea>
            @error('description')
                <div style="color: #dc3545; font-size: 14px; margin-top: 5px;">{{ $message }}</div>
            @enderror
        </div>

        <div>
            <label for="type" style="display: block; margin-bottom: 5px; font-weight: bold;">Type *</label>
            <select id="type" name="type" required style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                <option value="">Select a type</option>
                <option value="virtual" {{ old('type', $prize->type->value) === 'virtual' ? 'selected' : '' }}>Virtual</option>
                <option value="physical" {{ old('type', $prize->type->value) === 'physical' ? 'selected' : '' }}>Physical</option>
                <option value="feature_unlock" {{ old('type', $prize->type->value) === 'feature_unlock' ? 'selected' : '' }}>Feature Unlock</option>
                <option value="custom" {{ old('type', $prize->type->value) === 'custom' ? 'selected' : '' }}>Custom</option>
            </select>
            @error('type')
                <div style="color: #dc3545; font-size: 14px; margin-top: 5px;">{{ $message }}</div>
            @enderror
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
            <div>
                <label for="cost_in_points" style="display: block; margin-bottom: 5px; font-weight: bold;">Cost in Points</label>
                <input type="number" id="cost_in_points" name="cost_in_points" value="{{ old('cost_in_points', $prize->cost_in_points) }}" min="0" step="0.01" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                @error('cost_in_points')
                    <div style="color: #dc3545; font-size: 14px; margin-top: 5px;">{{ $message }}</div>
                @enderror
            </div>
            <div>
                <label for="inventory_quantity" style="display: block; margin-bottom: 5px; font-weight: bold;">Inventory Quantity</label>
                <input type="number" id="inventory_quantity" name="inventory_quantity" value="{{ old('inventory_quantity', $prize->inventory_quantity) }}" min="0" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;" placeholder="Leave empty for unlimited">
                <small style="color: #666; font-size: 12px;">Leave empty for unlimited inventory</small>
                @error('inventory_quantity')
                    <div style="color: #dc3545; font-size: 14px; margin-top: 5px;">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <div>
            <label for="sort_order" style="display: block; margin-bottom: 5px; font-weight: bold;">Sort Order</label>
            <input type="number" id="sort_order" name="sort_order" value="{{ old('sort_order', $prize->sort_order) }}" min="0" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
            @error('sort_order')
                <div style="color: #dc3545; font-size: 14px; margin-top: 5px;">{{ $message }}</div>
            @enderror
        </div>

        <div>
            <label style="display: flex; align-items: center; gap: 8px;">
                <input type="checkbox" name="is_active" value="1" {{ old('is_active', $prize->is_active) ? 'checked' : '' }}>
                <span>Active</span>
            </label>
        </div>

        <div style="display: flex; gap: 10px; margin-top: 20px;">
            <button type="submit" style="background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; font-weight: bold;">Update Prize</button>
            <a href="{{ route('lfl.admin.prizes') }}" style="background: #6c757d; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; text-decoration: none; display: inline-block;">Cancel</a>
        </div>
    </form>
</div>
@endsection

