@extends('layouts.app')
@section('title', 'Daily Ledger')
@section('page_title', 'Daily Ledger')
@section('content')

@php
  $isAccountant = auth()->user()->isAccountant();
  $adminRoute   = auth()->user()->isAdmin();
  $prefix       = $adminRoute ? 'admin.' : '';
@endphp

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;flex-wrap:wrap;gap:12px">
  <div>
    <div style="font-size:22px;font-weight:800">Daily Ledger</div>
    <div style="font-size:12.5px;color:var(--text-muted)">Cash book — In, Out &amp; Expense entries. Entry details can be edited only on the same calendar date; afterward only remarks can be added.</div>
  </div>
  <div style="display:flex;gap:10px;flex-wrap:wrap">
    <form method="GET" style="display:flex;gap:8px" data-ajax-filter>
      <input type="date" name="date" class="form-control" value="{{ $date->format('Y-m-d') }}" onchange="this.form.requestSubmit()">
    </form>
    <a href="{{ route($prefix.'daily-ledger.weekly', ['date' => $date->format('Y-m-d')]) }}" class="btn btn-outline"><i class="fas fa-calendar-week"></i> Weekly</a>
    <a href="{{ route($prefix.'daily-ledger.yearly', ['year' => $date->format('Y')]) }}" class="btn btn-outline"><i class="fas fa-calendar"></i> Yearly</a>
    <a href="{{ route($prefix.'daily-ledger.print.daily', ['date' => $date->format('Y-m-d')]) }}" target="_blank" class="btn btn-outline"><i class="fas fa-print"></i> Export PDF</a>
  </div>
</div>

@if(session('success'))<div class="alert alert-success" style="margin-bottom:16px">{{ session('success') }}</div>@endif
@if(session('error'))<div class="alert alert-danger" style="margin-bottom:16px">{{ session('error') }}</div>@endif
@if($errors->any())
  <div class="alert alert-danger" style="margin-bottom:16px">
    <strong>Please fix the following:</strong>
    <ul style="margin:6px 0 0 18px">
      @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
    </ul>
  </div>
@endif

<div style="margin-bottom:16px">
  @if($isOpen && $date->isToday())
    <div class="alert" style="background:#f0fdf4;border:1px solid #bbf7d0;color:#166534">
      <i class="fas fa-pen-to-square"></i> Open for entries. Entries dated today can be edited until the calendar date changes. Entries can never be deleted.
    </div>
  @elseif($isOpen)
    <div class="alert" style="background:#fff7ed;border:1px solid #fed7aa;color:#9a3412">
      <i class="fas fa-clock"></i> This past date is still open for adding new entries until <strong>{{ $lockAt->format('M d, Y g:i A') }}</strong>. Existing entry details are already locked because the entry date has passed; only remarks can be added.
    </div>
  @else
    <div class="alert" style="background:#fef2f2;border:1px solid #fecaca;color:#991b1b">
      <i class="fas fa-lock"></i> This day's ledger is locked (since {{ $lockAt->format('M d, Y g:i A') }}). No new entries can be added, and existing financial details cannot be changed — only remarks can be added.
    </div>
  @endif
</div>

{{-- Day summary --}}
<div class="grid grid-3" style="margin-bottom:20px">
  <div class="card"><div class="card-body">
    <div style="font-size:20px;font-weight:800;color:var(--success,#16a34a)">Rs. {{ number_format($totals['in'], 0) }}</div>
    <div style="font-size:12px;color:var(--text-muted)">Total In</div>
  </div></div>
  <div class="card"><div class="card-body">
    <div style="font-size:20px;font-weight:800;color:var(--danger)">Rs. {{ number_format($totals['out'] + $totals['expense'], 0) }}</div>
    <div style="font-size:12px;color:var(--text-muted)">Total Out + Expense</div>
  </div></div>
  <div class="card"><div class="card-body">
    <div style="font-size:20px;font-weight:800">Rs. {{ number_format($closingBalance, 0) }}</div>
    <div style="font-size:12px;color:var(--text-muted)">Closing Balance</div>
  </div></div>
</div>

@if($isAccountant)
{{-- Add entry — Accounts only --}}
<div class="card" style="margin-bottom:20px">
  <div class="card-header"><div class="card-title"><i class="fas fa-plus" style="color:var(--primary);margin-right:6px"></i>Add Entry</div></div>
  <div class="card-body">
    @if($isOpen)
    <form method="POST" action="{{ route('daily-ledger.store') }}" style="display:grid;grid-template-columns:repeat(5,1fr);gap:12px;align-items:end">
      @csrf
      <input type="hidden" name="entry_date" value="{{ $date->format('Y-m-d') }}">
      <div class="form-group" style="margin:0">
        <label>Type</label>
        <select name="type" class="form-control" required>
          <option value="in">In</option>
          <option value="out">Out</option>
          <option value="expense">Expense</option>
        </select>
      </div>
      <div class="form-group" style="margin:0;grid-column:span 2">
        <label>Particulars</label>
        <input type="text" name="particulars" class="form-control" placeholder="e.g. Cash received from student, Office supplies..." required maxlength="255">
      </div>
      <div class="form-group" style="margin:0">
        <label>Category <small style="color:var(--text-muted)">(optional)</small></label>
        <input type="text" name="category" class="form-control" placeholder="e.g. Stationery" maxlength="100">
      </div>
      <div class="form-group" style="margin:0">
        <label>Amount</label>
        <input type="number" name="amount" class="form-control" step="1" min="0" required>
      </div>
      <div style="grid-column:1/-1">
        <button type="submit" class="btn btn-primary" data-confirm="This entry can be edited only while its entry date is today. After the date changes, only a remark can be added. Entries can never be deleted. Continue?" data-confirm-title="Save this entry?" data-confirm-variant="warning" data-confirm-ok-text="Yes, save it">
          <i class="fas fa-save"></i> Save Entry
        </button>
      </div>
    </form>
    @else
    <div style="color:var(--text-muted);font-size:13px"><i class="fas fa-lock"></i> This day is locked — new entries can no longer be added.</div>
    @endif
  </div>
</div>
@endif

{{-- Entries table --}}
<div class="card" style="margin-bottom:20px">
  <div class="card-header"><div class="card-title">Entries for {{ $date->format('M d, Y') }}</div></div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Time</th><th>Type</th><th>Particulars</th><th>Category</th><th>Amount</th><th>Entered By</th><th>Remarks</th>
          @if($isAccountant)<th>Action</th>@endif
        </tr>
      </thead>
      <tbody>
        @forelse($entries as $entry)
        <tr>
          <td>{{ $entry->created_at->format('g:i A') }}</td>
          <td>
            <span class="badge {{ $entry->type === 'in' ? 'success' : ($entry->type === 'expense' ? 'danger' : 'warning') }}">{{ ucfirst($entry->type) }}</span>
          </td>
          <td>{{ $entry->particulars }}</td>
          <td>{{ $entry->category ?? '—' }}</td>
          <td>Rs. {{ number_format($entry->amount, 0) }}</td>
          <td>{{ $entry->createdBy->name ?? '—' }}</td>
          <td style="max-width:220px">
            @if($entry->remarks)
              <div style="font-size:12.5px;color:#991b1b"><i class="fas fa-flag"></i> {{ $entry->remarks }}</div>
              <div style="font-size:11px;color:var(--text-muted)">— {{ $entry->remarksBy->name ?? '' }}, {{ optional($entry->remarks_at)->format('M d, g:i A') }}</div>
            @else
              <span style="color:var(--text-muted)">—</span>
            @endif
          </td>
          @if($isAccountant)
          <td>
            <div style="display:flex;gap:6px;flex-wrap:wrap">
              @if($entry->canEditDetails())
                <button type="button" class="btn btn-outline btn-sm" onclick="document.getElementById('editModal{{ $entry->id }}').style.display='flex'"><i class="fas fa-edit"></i> Edit</button>
              @endif
              <button type="button" class="btn btn-outline btn-sm" onclick="document.getElementById('remarkModal{{ $entry->id }}').style.display='flex'"><i class="fas fa-flag"></i> Remark</button>
            </div>

            @if($entry->canEditDetails())
            <div id="editModal{{ $entry->id }}" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);align-items:center;justify-content:center;z-index:500">
              <div class="card" style="max-width:520px;width:92%">
                <div class="card-header" style="display:flex;justify-content:space-between;align-items:center">
                  <div class="card-title">Edit Today's Ledger Entry</div>
                  <button type="button" onclick="document.getElementById('editModal{{ $entry->id }}').style.display='none'" class="btn btn-outline btn-sm"><i class="fas fa-times"></i></button>
                </div>
                <form method="POST" action="{{ route('daily-ledger.update', $entry) }}" class="card-body">
                  @csrf
                  @method('PUT')
                  <p style="font-size:12.5px;color:var(--text-muted);margin-bottom:12px">You can correct Type, Particulars, Category and Amount only while the entry date is today. The entry date and creator cannot be changed.</p>
                  <div class="form-group">
                    <label>Type</label>
                    <select name="type" class="form-control" required>
                      <option value="in" {{ $entry->type === 'in' ? 'selected' : '' }}>In</option>
                      <option value="out" {{ $entry->type === 'out' ? 'selected' : '' }}>Out</option>
                      <option value="expense" {{ $entry->type === 'expense' ? 'selected' : '' }}>Expense</option>
                    </select>
                  </div>
                  <div class="form-group">
                    <label>Particulars</label>
                    <input type="text" name="particulars" class="form-control" value="{{ $entry->particulars }}" required maxlength="255">
                  </div>
                  <div class="form-group">
                    <label>Category <small style="color:var(--text-muted)">(optional)</small></label>
                    <input type="text" name="category" class="form-control" value="{{ $entry->category }}" maxlength="100">
                  </div>
                  <div class="form-group">
                    <label>Amount</label>
                    <input type="number" name="amount" class="form-control" step="1" min="0" value="{{ (int) $entry->amount }}" required>
                  </div>
                  <button type="submit" class="btn btn-primary" style="width:100%"><i class="fas fa-save"></i> Save Changes</button>
                </form>
              </div>
            </div>
            @endif

            <div id="remarkModal{{ $entry->id }}" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);align-items:center;justify-content:center;z-index:500">
              <div class="card" style="max-width:420px;width:92%">
                <div class="card-header" style="display:flex;justify-content:space-between;align-items:center">
                  <div class="card-title">{{ $entry->remarks ? 'Edit Remark' : 'Add Remark' }}</div>
                  <button type="button" onclick="document.getElementById('remarkModal{{ $entry->id }}').style.display='none'" class="btn btn-outline btn-sm"><i class="fas fa-times"></i></button>
                </div>
                <form method="POST" action="{{ route('daily-ledger.remark', $entry) }}" class="card-body">
                  @csrf
                  <p style="font-size:12.5px;color:var(--text-muted);margin-bottom:10px">Remarks remain available after the entry date has passed. They document corrections without changing or deleting the original audit history.</p>
                  <div class="form-group">
                    <label>Remark</label>
                    <textarea name="remarks" class="form-control" rows="3" required maxlength="1000">{{ $entry->remarks }}</textarea>
                  </div>
                  <button type="submit" class="btn btn-primary" style="width:100%">Save Remark</button>
                </form>
              </div>
            </div>
          </td>
          @endif
        </tr>
        @empty
        <tr><td colspan="{{ $isAccountant ? 8 : 7 }}" style="text-align:center;color:var(--text-muted);padding:24px">No entries for this day.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>

{{-- Currency calculator (closing) --}}
<div class="card">
  <div class="card-header"><div class="card-title"><i class="fas fa-calculator" style="color:var(--primary);margin-right:6px"></i>Currency Calculator — Day Closing</div></div>
  <div class="card-body">
    <p style="font-size:12.5px;color:var(--text-muted);margin-bottom:14px">Count the physical cash on hand by denomination to verify against the system's closing balance.</p>
    @if($isAccountant)
    <form method="POST" action="{{ route('daily-ledger.closing.store') }}" id="closingForm">
      @csrf
      <input type="hidden" name="entry_date" value="{{ $date->format('Y-m-d') }}">
      <div style="display:grid;grid-template-columns:repeat(5,1fr);gap:12px;margin-bottom:14px">
        @foreach(\App\Models\DailyLedgerClosing::denominationUnits() as $note)
        <div class="form-group" style="margin:0">
          <label>Rs. {{ $note }} &times;</label>
          <input type="number" min="0" step="1" name="denominations[{{ $note }}]" class="form-control denom-input" data-note="{{ $note }}"
                 value="{{ $closing->denominations[$note] ?? '' }}" placeholder="0" oninput="recalcClosing()">
        </div>
        @endforeach
      </div>
      <div style="font-size:18px;font-weight:800;margin-bottom:12px">Counted Total: Rs. <span id="closingTotal">{{ number_format($closing->counted_total ?? 0, 0) }}</span></div>
      <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Closing Count</button>
    </form>
    <script>
      function recalcClosing() {
        let total = 0;
        document.querySelectorAll('.denom-input').forEach(el => {
          const note = parseFloat(el.dataset.note) || 0;
          const qty  = parseFloat(el.value) || 0;
          total += note * qty;
        });
        document.getElementById('closingTotal').textContent = total.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
      }
    </script>
    @endif

    @if($closing)
    <div style="margin-top:16px;padding-top:16px;border-top:1px solid #e2e8f0;display:grid;grid-template-columns:repeat(3,1fr);gap:16px">
      <div><div style="font-size:12px;color:var(--text-muted)">Counted (physical cash)</div><div style="font-weight:700">Rs. {{ number_format($closing->counted_total, 0) }}</div></div>
      <div><div style="font-size:12px;color:var(--text-muted)">System Closing Balance</div><div style="font-weight:700">Rs. {{ number_format($closing->expected_total, 0) }}</div></div>
      <div><div style="font-size:12px;color:var(--text-muted)">Difference</div><div style="font-weight:700;color:{{ (float) $closing->difference == 0 ? 'var(--success,#16a34a)' : 'var(--danger)' }}">Rs. {{ number_format($closing->difference, 0) }}</div></div>
    </div>
    <div style="font-size:11px;color:var(--text-muted);margin-top:8px">Last saved by {{ $closing->closedBy->name ?? '—' }} on {{ $closing->updated_at->format('M d, Y g:i A') }}</div>
    @endif
  </div>
</div>

@endsection
