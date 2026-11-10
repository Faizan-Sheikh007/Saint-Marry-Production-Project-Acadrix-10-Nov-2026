@extends('layouts.app')
@section('title', 'Fee Templates')
@section('page_title', 'Fee Templates')
@section('content')
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px">
  <div style="font-size:22px;font-weight:800">Fee Templates <span style="color:var(--text-muted);font-size:16px">({{ $items->total() }})</span></div>
  <a href="{{ route('fee-templates.create') }}" class="btn btn-primary"><i class="fas fa-plus"></i> Add Fee Template</a>
</div>
<div class="card">
  <div class="table-wrap">
    <table>
      <thead><tr><th>Name</th><th>Class/Program</th><th>Billing Cycle</th><th>Items</th><th>Total</th><th>Status</th><th>Actions</th></tr></thead>
      <tbody>
        @forelse($items as $item)
        <tr>
          <td style="font-weight:600"><a href="{{ route('fee-templates.show', $item) }}">{{ $item->name }}</a></td>
          <td>{{ $item->schoolClass->name ?? 'All Classes' }}</td>
          <td>{{ ucfirst($item->billing_cycle) }}</td>
          <td>{{ $item->items->count() }}</td>
          <td>Rs. {{ number_format($item->items->sum('amount'), 2) }}</td>
          <td><span class="badge {{ $item->is_active ? 'success' : 'gray' }}">{{ $item->is_active ? 'Active' : 'Inactive' }}</span></td>
          <td>
            <div style="display:flex;gap:6px">
              <a href="{{ route('fee-templates.edit', $item) }}" class="btn btn-outline btn-sm"><i class="fas fa-edit"></i></a>
              <form action="{{ route('fee-templates.destroy', $item) }}" method="POST" onsubmit="return confirm('Delete this template?')">
                @csrf @method('DELETE')
                <button type="submit" class="btn btn-outline btn-sm" style="color:var(--danger)"><i class="fas fa-trash"></i></button>
              </form>
            </div>
          </td>
        </tr>
        @empty
        <tr><td colspan="7" style="text-align:center;padding:40px;color:var(--text-muted)">No fee templates yet. <a href="{{ route('fee-templates.create') }}">Create one</a></td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
  <div style="padding:16px 20px">{{ $items->links() }}</div>
</div>
@endsection
