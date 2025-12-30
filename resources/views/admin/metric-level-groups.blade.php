@extends('lfl::layout')

@section('title', 'MetricLevelGroups Management')

@section('content')
<div class="card">
    <h1>MetricLevelGroups</h1>

    @if(session('success'))
        <div style="background: #d4edda; color: #155724; padding: 12px; border-radius: 4px; margin-bottom: 20px;">
            {{ session('success') }}
        </div>
    @endif

    <div style="background: #f8f9fa; padding: 20px; border-radius: 4px; margin-bottom: 30px;">
        <h2 style="margin-top: 0; margin-bottom: 15px;">Create New MetricLevelGroup</h2>
        <form method="POST" action="{{ route('lfl.admin.metric-level-groups.store') }}" style="display: grid; gap: 15px;">
            @csrf
            <div>
                <label for="name" style="display: block; margin-bottom: 5px; font-weight: bold;">Name *</label>
                <input type="text" id="name" name="name" required style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
            </div>
            <div>
                <label for="slug" style="display: block; margin-bottom: 5px; font-weight: bold;">Slug</label>
                <input type="text" id="slug" name="slug" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;" placeholder="Auto-generated from name if empty">
            </div>
            <div>
                <label for="description" style="display: block; margin-bottom: 5px; font-weight: bold;">Description</label>
                <textarea id="description" name="description" rows="3" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;"></textarea>
            </div>
            <button type="submit" style="background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; font-weight: bold;">Create MetricLevelGroup</button>
        </form>
        <p style="margin-top: 15px; color: #666; font-size: 14px;">
            <strong>Note:</strong> After creating a MetricLevelGroup, you'll need to associate GamedMetrics and define levels separately.
        </p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Name</th>
                <th>Slug</th>
                <th>Description</th>
                <th>Metrics</th>
                <th>Levels</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($groups as $group)
                <tr>
                    <td><strong>{{ $group->name }}</strong></td>
                    <td><code>{{ $group->slug }}</code></td>
                    <td>{{ $group->description ?? '-' }}</td>
                    <td>
                        @foreach($group->metrics as $metric)
                            <span class="badge">{{ $metric->gamedMetric->name }} ({{ $metric->weight }}x)</span>
                        @endforeach
                    </td>
                    <td>
                        @if($group->levels->isNotEmpty())
                            <div style="font-size: 12px;">
                                @foreach($group->levels as $level)
                                    <div>
                                        <a href="{{ route('lfl.admin.metric-level-group-levels.edit', $level) }}" style="color: #007bff; text-decoration: none;">
                                            Level {{ $level->level }}: {{ $level->name }}
                                        </a>
                                        @if($level->achievements->isNotEmpty())
                                            <span style="color: #666; margin-left: 5px;">
                                                ({{ $level->achievements->count() }} achievement{{ $level->achievements->count() !== 1 ? 's' : '' }})
                                            </span>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <span style="color: #999;">No levels</span>
                        @endif
                    </td>
                    <td>
                        <a href="{{ route('lfl.admin.metric-level-groups') }}#group-{{ $group->id }}" style="color: #007bff; text-decoration: none;">View</a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" style="text-align: center; padding: 40px; color: #999;">
                        No MetricLevelGroups found
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @if($groups->hasPages())
        <div class="pagination">
            {{ $groups->links() }}
        </div>
    @endif
</div>
@endsection

