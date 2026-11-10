@extends('layouts.app')
@section('title', 'Fee Heads')
@section('page_title', 'Fee Heads')
@section('content')
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px">
  <div style="font-size:22px;font-weight:800">Fee Heads <span style="color:var(--text-muted);font-size:16px">({{ $items->total() }})</span></div>
  <a href="{{ route('fee-heads.create') }}" class="btn btn-primary"><i class="fas fa-plus"></i> Add Fee Head</a>
</div>
<div class="card">
  <div class="table-wrap">
    <table>
      <thead><tr><th>Name</th><th>Category</th><th>Type</th><th>Default Amount</th><th>Status</th><th>Actions</th></tr></thead>
      <tbody>
        @forelse($items as $item)
        <tr>
          <td style="font-weight:600"><a href="{{ route('fee-heads.show', $item) }}">{{ $item->name }}</a></td>
          <td>{{ \App\Models\FeeHead::categories()[$item->category] ?? ucfirst($item->category) }}</td>
          <td><span class="badge {{ $item->type == 'recurring' ? 'primary' : 'info' }}">{{ ucfirst($item->type) }}</span></td>
          <td>Rs. {{ number_format($item->default_amount, 0) }}</td>
          <td><span class="badge {{ $item->is_active ? 'success' : 'gray' }}">{{ $item->is_active ? 'Active' : 'Inactive' }}</span></td>
          <td>
            <div style="display:flex;gap:6px">
              <a href="{{ route('fee-heads.edit', $item) }}" class="btn btn-outline btn-sm"><i class="fas fa-edit"></i></a>
              <form action="{{ route('fee-heads.destroy', $item) }}" method="POST" onsubmit="return confirm('Delete this fee head?')">
                @csrf @method('DELETE')
                <button type="submit" class="btn btn-outline btn-sm" style="color:var(--danger)"><i class="fas fa-trash"></i></button>
              </form>
            </div>
          </td>
        </tr>
        @empty
        <tr><td colspan="6" style="text-align:center;padding:40px;color:var(--text-muted)">No fee heads yet. <a href="{{ route('fee-heads.create') }}">Create one</a></td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
  <div style="padding:16px 20px">{{ $items->links() }}</div>
</div>
@endsection
