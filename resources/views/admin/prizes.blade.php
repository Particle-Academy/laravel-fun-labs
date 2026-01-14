@extends('lfl::layout')

@section('title', 'Prizes Management')

@section('content')
<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h1 style="margin: 0;">Prizes</h1>
        <a href="{{ route('lfl.admin.prizes.create') }}" style="background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; text-decoration: none; display: inline-block;">
            Create Prize
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
                <th>Type</th>
                <th>Cost</th>
                <th>Inventory</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($prizes as $prize)
                <tr>
                    <td><strong>{{ $prize->name }}</strong></td>
                    <td><code>{{ $prize->slug }}</code></td>
                    <td>{{ $prize->type->label() }}</td>
                    <td>{{ $prize->cost_in_points ? number_format($prize->cost_in_points) . ' points' : 'Free' }}</td>
                    <td>
                        @if($prize->inventory_quantity !== null)
                            @php
                                $remaining = $prize->getRemainingInventory();
                            @endphp
                            {{ $remaining !== null ? $remaining : 0 }} / {{ $prize->inventory_quantity }}
                        @else
                            Unlimited
                        @endif
                    </td>
                    <td>
                        @if($prize->isAvailable())
                            <span class="badge badge-success">Available</span>
                        @else
                            <span class="badge badge-warning">Unavailable</span>
                        @endif
                    </td>
                    <td>
                        <a href="{{ route('lfl.admin.prizes.edit', $prize) }}" style="color: #007bff; text-decoration: none; margin-right: 10px;">Edit</a>
                        <form method="POST" action="{{ route('lfl.admin.prizes.delete', $prize) }}" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this prize?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" style="color: #dc3545; text-decoration: none; background: none; border: none; cursor: pointer; padding: 0;">Delete</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" style="text-align: center; padding: 40px; color: #999;">
                        No prizes found
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @if($prizes->hasPages())
        <div class="pagination">
            {{ $prizes->links() }}
        </div>
    @endif
</div>
@endsection

