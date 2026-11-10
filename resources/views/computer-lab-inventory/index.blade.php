@extends('layouts.app')
@section('title', 'Computer Lab Inventory')
@section('page_title', 'Computer Lab Inventory')
@section('content')
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;flex-wrap:wrap;gap:12px">
  <div style="font-size:22px;font-weight:800">Computer Lab Inventory</div>
  <a href="{{ route('admin.computer-lab-inventory.create') }}" class="btn btn-primary"><i class="fas fa-plus"></i> Add Item</a>
</div>
<div class="card">
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>#</th>
          <th>Item Name</th>
          <th>Category</th>
          <th>Type</th>
          <th>Quantity</th>
          <th>Unit</th>
          <th>Unit Price</th>
          <th>Status</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        @forelse($items as $item)
        <tr>
          <td>{{ $loop->iteration }}</td>
          <td><strong>{{ $item->name }}</strong></td>
          <td>{{ $item->category ?? '—' }}</td>
          <td>
            @if($item->isServer())
              <span class="badge" style="background:#dbeafe;color:#1e40af"><i class="fas fa-server"></i> Server</span>
            @else
              {{ \App\Models\ComputerLabInventory::itemTypes()[$item->item_type] ?? ucfirst($item->item_type) }}
            @endif
          </td>
          <td>
            <span class="{{ $item->quantity <= $item->min_quantity ? 'badge danger' : 'badge success' }}">
              {{ $item->quantity }} {{ $item->unit ?? '' }}
            </span>
          </td>
          <td>{{ $item->unit ?? '—' }}</td>
          <td>{{ $item->price ? 'Rs. ' . number_format($item->price, 0) : '—' }}</td>
          <td><span class="badge success">Active</span></td>
          <td>
            <a href="{{ route('admin.computer-lab-inventory.show', $item) }}" class="btn btn-outline btn-sm" title="View">
              <i class="fas fa-eye"></i>
            </a>
            <a href="{{ route('admin.computer-lab-inventory.edit', $item) }}" class="btn btn-outline btn-sm" title="Edit">
              <i class="fas fa-edit"></i>
            </a>
            <form action="{{ route('admin.computer-lab-inventory.destroy', $item) }}" method="POST" style="display:inline" onsubmit="return confirm('Delete?')">
              @csrf @method('DELETE')
              <button class="btn btn-outline btn-sm" style="color:var(--danger)" title="Delete">
                <i class="fas fa-trash"></i>
              </button>
            </form>
          </td>
        </tr>
        @empty
        <tr>
          <td colspan="9" style="text-align:center;padding:40px;color:var(--text-muted)">No records found.</td>
        </tr>
        @endforelse
      </tbody>
    </table>
  </div>
  <div style="padding:16px 20px">{{ $items->links() }}</div>
</div>
@endsection
