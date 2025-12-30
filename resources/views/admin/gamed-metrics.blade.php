@extends('lfl::layout')

@section('title', 'GamedMetrics Management')

@section('content')
<div class="card">
    <h1>GamedMetrics</h1>

    @if(session('success'))
        <div style="background: #d4edda; color: #155724; padding: 12px; border-radius: 4px; margin-bottom: 20px;">
            {{ session('success') }}
        </div>
    @endif

    <div style="background: #f8f9fa; padding: 20px; border-radius: 4px; margin-bottom: 30px;">
        <h2 style="margin-top: 0; margin-bottom: 15px;">Create New GamedMetric</h2>
        <form method="POST" action="{{ route('lfl.admin.gamed-metrics.store') }}" style="display: grid; gap: 15px;">
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
            <div>
                <label for="icon" style="display: block; margin-bottom: 5px; font-weight: bold;">Icon</label>
                <input type="text" id="icon" name="icon" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;" placeholder="e.g., ⚔️, 🛠️, 💬">
            </div>
            <div>
                <label style="display: flex; align-items: center; gap: 8px;">
                    <input type="checkbox" name="active" value="1" checked>
                    <span>Active</span>
                </label>
            </div>
            <button type="submit" style="background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; font-weight: bold;">Create GamedMetric</button>
        </form>
    </div>

    <table>
        <thead>
            <tr>
                <th>Name</th>
                <th>Slug</th>
                <th>Description</th>
                <th>Icon</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($metrics as $metric)
                <tr>
                    <td><strong>{{ $metric->name }}</strong></td>
                    <td><code>{{ $metric->slug }}</code></td>
                    <td>{{ $metric->description ?? '-' }}</td>
                    <td>{{ $metric->icon ?? '-' }}</td>
                    <td>
                        @if($metric->active)
                            <span class="badge badge-success">Active</span>
                        @else
                            <span class="badge badge-warning">Inactive</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" style="text-align: center; padding: 40px; color: #999;">
                        No GamedMetrics found
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @if($metrics->hasPages())
        <div class="pagination">
            {{ $metrics->links() }}
        </div>
    @endif
</div>
@endsection

