@extends('lfl::layout')

@section('title', 'Achievements Management')

@section('content')
<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h1 style="margin: 0;">Achievements</h1>
        <a href="{{ route('lfl.admin.achievements.create') }}" style="background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; text-decoration: none; display: inline-block;">
            Create Achievement
        </a>
    </div>

    @if(session('success'))
        <div style="background: #d4edda; color: #155724; padding: 12px; border-radius: 4px; margin-bottom: 20px;">
            {{ session('success') }}
        </div>
    @endif

    <table>
        <thead>
            <tr>
                <th>Name</th>
                <th>Slug</th>
                <th>Description</th>
                <th>Type</th>
                <th>Status</th>
                <th>Sort Order</th>
                <th>Attached Prizes</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($achievements as $achievement)
                <tr>
                    <td><strong>{{ $achievement->name }}</strong> @if($achievement->icon) {{ $achievement->icon }} @endif</td>
                    <td><code>{{ $achievement->slug }}</code></td>
                    <td>{{ $achievement->description ?? '-' }}</td>
                    <td>{{ $achievement->awardable_type ?? 'Universal' }}</td>
                    <td>
                        @if($achievement->is_active)
                            <span class="badge badge-success">Active</span>
                        @else
                            <span class="badge badge-warning">Inactive</span>
                        @endif
                    </td>
                    <td>{{ $achievement->sort_order }}</td>
                    <td>
                        @php
                            $prizes = $achievement->prizes;
                        @endphp
                        @if($prizes->isNotEmpty())
                            <div style="font-size: 12px;">
                                @foreach($prizes as $prize)
                                    <div>{{ $prize->name }}</div>
                                @endforeach
                            </div>
                        @else
                            <span style="color: #999;">-</span>
                        @endif
                    </td>
                    <td>
                        <a href="{{ route('lfl.admin.achievements.edit', $achievement) }}" style="color: #007bff; text-decoration: none; margin-right: 10px;">Edit</a>
                        <form method="POST" action="{{ route('lfl.admin.achievements.delete', $achievement) }}" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this achievement?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" style="color: #dc3545; text-decoration: none; background: none; border: none; cursor: pointer; padding: 0;">Delete</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" style="text-align: center; padding: 40px; color: #999;">
                        No achievements found
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @if($achievements->hasPages())
        <div class="pagination">
            {{ $achievements->links() }}
        </div>
    @endif
</div>
@endsection

