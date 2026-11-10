@extends('layouts.app')
@section('title', 'Medical Lab Inventory')
@section('page_title', 'Medical Lab Inventory')
@section('content')
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;flex-wrap:wrap;gap:12px">
  <div style="font-size:22px;font-weight:800">Medical Lab Inventory</div>
  <div style="display:flex;gap:8px;flex-wrap:wrap">
    <a href="{{ route('admin.medical-lab-inventory.export-pdf', array_filter(['lab' => $selectedLab, 'item' => $itemSearch])) }}" class="btn btn-outline">
      <i class="fas fa-file-pdf"></i> List Report PDF
    </a>
    <a href="{{ route('admin.medical-lab-inventory.create') }}" class="btn btn-primary"><i class="fas fa-plus"></i> Add Item</a>
  </div>
</div>

<div class="card" style="margin-bottom:16px">
  <div class="card-body" style="padding:14px 18px">
    <form method="GET" action="{{ route('admin.medical-lab-inventory.index') }}" style="display:flex;align-items:flex-end;gap:10px;flex-wrap:wrap" data-ajax-filter>
      <div class="form-group" style="margin-bottom:0;min-width:240px;max-width:380px;flex:1">
        <label class="form-label">Filter by Lab</label>
        <select name="lab" class="form-control">
          <option value="">All Labs</option>
          @foreach($labs as $lab)
            <option value="{{ $lab }}" {{ $selectedLab === $lab ? 'selected' : '' }}>{{ $lab }}</option>
          @endforeach
        </select>
      </div>

      <div class="form-group" style="margin-bottom:0;min-width:260px;max-width:420px;flex:1">
        <label class="form-label">Search Item Only</label>
        <div style="position:relative">
          <i class="fas fa-search" style="position:absolute;left:13px;top:50%;transform:translateY(-50%);color:var(--text-muted);pointer-events:none"></i>
          <input
            type="search"
            name="item"
            value="{{ $itemSearch }}"
            class="form-control"
            placeholder="Type item name..."
            autocomplete="off"
            style="padding-left:38px"
          >
        </div>
      </div>

      <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Apply</button>
      @if($selectedLab !== '' || $itemSearch !== '')
        <a href="{{ route('admin.medical-lab-inventory.index') }}" class="btn btn-outline" data-ajax-filter-link><i class="fas fa-xmark"></i> Clear</a>
      @endif
    </form>

    @if($selectedLab !== '' || $itemSearch !== '')
      <div style="margin-top:10px;font-size:13px;color:var(--text-muted)">
        Showing
        @if($itemSearch !== '') items matching <strong>“{{ $itemSearch }}”</strong>@else all items @endif
        @if($selectedLab !== '') in <strong>{{ $selectedLab }}</strong>@else across all labs @endif.
        The List Report PDF uses these same filters.
      </div>
    @endif
  </div>
</div>

<div class="card">
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>#</th>
          <th>Item Name</th>
          <th>Lab / Category</th>
          <th>Quantity</th>
          <th>Unit</th>
          <th>Unit Price</th>
          <th>Expiry Date</th>
          <th>Status</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        @forelse($items as $item)
        <tr>
          <td>{{ $items->firstItem() + $loop->index }}</td>
          <td><strong>{{ $item->name }}</strong></td>
          <td>{{ $item->category ?? '—' }}</td>
          <td>
            <span class="{{ $item->quantity <= $item->min_quantity ? 'badge danger' : 'badge success' }}">
              {{ $item->quantity }} {{ $item->unit ?? '' }}
            </span>
          </td>
          <td>{{ $item->unit ?? '—' }}</td>
          <td>{{ $item->price ? 'Rs. ' . number_format($item->price, 0) : '—' }}</td>
          <td>
            @if($item->expiry_date)
              {{ $item->expiry_date->format('d-m-Y') }}
              @if($item->isExpired())<span class="badge danger" style="margin-left:4px">Expired</span>
              @elseif($item->isExpiringSoon())<span class="badge" style="margin-left:4px;background:#fef3c7;color:#92400e">Expiring Soon</span>@endif
            @else — @endif
          </td>
          <td><span class="{{ $item->quantity <= $item->min_quantity ? 'badge danger' : 'badge success' }}">{{ $item->quantity <= $item->min_quantity ? 'Low Stock' : 'Active' }}</span></td>
          <td>
            <a href="{{ route('admin.medical-lab-inventory.show', $item) }}" class="btn btn-outline btn-sm" title="View">
              <i class="fas fa-eye"></i>
            </a>
            <a href="{{ route('admin.medical-lab-inventory.edit', $item) }}" class="btn btn-outline btn-sm" title="Edit">
              <i class="fas fa-edit"></i>
            </a>
            <form action="{{ route('admin.medical-lab-inventory.destroy', $item) }}" method="POST" style="display:inline" onsubmit="return confirm('Delete?')">
              @csrf @method('DELETE')
              <button class="btn btn-outline btn-sm" style="color:var(--danger)" title="Delete">
                <i class="fas fa-trash"></i>
              </button>
            </form>
          </td>
        </tr>
        @empty
        <tr>
          <td colspan="9" style="text-align:center;padding:40px;color:var(--text-muted)">
            No inventory items found for the selected filters.
          </td>
        </tr>
        @endforelse
      </tbody>
    </table>
  </div>
  <div style="padding:16px 20px">{{ $items->links() }}</div>
</div>
@endsection
