@extends('layouts.app')
@section('title', 'Fee Plan - ' . $feeAssignment->student->name)
@section('page_title', 'Fee Plan')
@section('content')

<div style="display:flex;align-items:center;gap:12px;margin-bottom:20px;flex-wrap:wrap">
  <a href="{{ route('students.show', $feeAssignment->student) }}" class="btn btn-outline btn-sm"><i class="fas fa-arrow-left"></i> Back to Profile</a>
  <a href="{{ route('fee-assignments.plan', $feeAssignment) }}" target="_blank" class="btn btn-primary btn-sm"><i class="fas fa-download"></i> Download /Print Vouchers</a>
  <a href="{{ route('fee-assignments.summary', $feeAssignment) }}" target="_blank" class="btn btn-outline btn-sm"><i class="fas fa-file-invoice"></i> Installment Plan</a>
  @if($feeAssignment->feeTemplate)
  <a href="{{ route('fee-assignments.renew', $feeAssignment) }}" class="btn btn-outline btn-sm"><i class="fas fa-rotate"></i> Renew for Next Semester/Year</a>
  @endif
</div>

@if($feeAssignment->parentAssignment)
<div class="alert alert-info" style="margin-bottom:14px;font-size:13px">
  <i class="fas fa-circle-info"></i> This plan continues from
  <a href="{{ route('fee-assignments.show', $feeAssignment->parentAssignment) }}">{{ $feeAssignment->parentAssignment->cycleRangeLabel() }}</a>.
</div>
@endif
@if($feeAssignment->renewals->isNotEmpty())
<div class="alert alert-info" style="margin-bottom:14px;font-size:13px">
  <i class="fas fa-circle-info"></i> Renewed into:
  @foreach($feeAssignment->renewals as $r)
    <a href="{{ route('fee-assignments.show', $r) }}">{{ $r->cycleRangeLabel() }}</a>@if(!$loop->last), @endif
  @endforeach
</div>
@endif

@if($errors->any())
<div style="background:#fee2e2;border:1px solid #ef4444;padding:12px 16px;border-radius:8px;margin-bottom:14px">
  @foreach($errors->all() as $error)<div style="color:#dc2626;font-size:13px">{{ $error }}</div>@endforeach
</div>
@endif

@if($feeAssignment->carried_forward_amount > 0)
<div class="alert alert-info" style="margin-bottom:14px;font-size:13px">
  <i class="fas fa-hand-holding-dollar"></i> Includes <strong>Rs. {{ number_format($feeAssignment->carried_forward_amount, 0) }}</strong> carried forward from prior outstanding balance.
</div>
@endif

<div class="card" style="margin-bottom:20px">
  <div class="card-body" style="display:flex;flex-wrap:wrap;gap:24px;align-items:center">
    <div>
      <div style="font-weight:800;font-size:16px">{{ $feeAssignment->student->name }} <span style="color:var(--text-muted);font-weight:500">({{ $feeAssignment->student->student_id }})</span></div>
      <div style="font-size:12px;color:var(--text-muted)">Father Name: {{ $feeAssignment->student->father_name ?: ($feeAssignment->student->guardian_name ?: '—') }}</div>
      <div style="font-size:12px;color:var(--text-muted)">{{ $feeAssignment->student->schoolClass->name ?? '—' }} &middot; {{ $feeAssignment->feeTemplate->name ?? '—' }}
        @if($feeAssignment->feeTemplate) &middot; <strong style="color:var(--primary)">{{ $feeAssignment->cycleRangeLabel() }}</strong> @endif
      </div>
    </div>
    <div style="flex:1;min-width:120px;text-align:center;background:var(--bg);border-radius:10px;padding:10px">
      <div style="font-size:18px;font-weight:800;color:var(--primary)">{{ $feeAssignment->installments }}</div>
      <div style="font-size:11px;color:var(--text-muted)">Installments</div>
    </div>
    @if($feeAssignment->increase_amount > 0)
    <div style="flex:1;min-width:120px;text-align:center;background:var(--bg);border-radius:10px;padding:10px">
      <div style="font-size:18px;font-weight:800;color:var(--primary)">Rs. {{ number_format($feeAssignment->increase_amount, 0) }}</div>
      <div style="font-size:11px;color:var(--text-muted)">Fee Increase (Legacy)</div>
    </div>
    @endif
    @if(($feeAssignment->scholarship_amount ?? 0) > 0)
    <div style="flex:1;min-width:120px;text-align:center;background:rgba(16,185,129,0.08);border-radius:10px;padding:10px">
      <div style="font-size:18px;font-weight:800;color:var(--success)">Rs. {{ number_format($feeAssignment->scholarship_amount, 0) }}</div>
      <div style="font-size:11px;color:var(--text-muted)">Scholarship Applied</div>
    </div>
    @endif
    @if($feeAssignment->discount_amount > 0)
    <div style="flex:1;min-width:120px;text-align:center;background:var(--bg);border-radius:10px;padding:10px">
      <div style="font-size:18px;font-weight:800;color:var(--text-muted)">Rs. {{ number_format($feeAssignment->discount_amount, 0) }}</div>
      <div style="font-size:11px;color:var(--text-muted)">Legacy Discount</div>
    </div>
    @endif
    @if($feeAssignment->financial_aid_amount > 0)
    <div style="flex:1;min-width:120px;text-align:center;background:rgba(245,158,11,0.08);border-radius:10px;padding:10px">
      <div style="font-size:18px;font-weight:800;color:var(--warning)">Rs. {{ number_format($feeAssignment->financial_aid_amount, 0) }}</div>
      <div style="font-size:11px;color:var(--text-muted)">Financial Aid Applied</div>
    </div>
    @endif
    <div style="flex:1;min-width:140px;text-align:center;background:#fff7ed;border-radius:10px;padding:10px">
      <div style="font-size:18px;font-weight:800;color:#ea580c">Rs. {{ number_format($feeAssignment->total_amount, 0) }}</div>
      <div style="font-size:11px;color:var(--text-muted)">Total Payable (Plan)</div>
    </div>
  </div>
</div>

<div class="card">
  <div class="card-header"><div class="card-title">Installment Breakdown</div></div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>#</th><th>Voucher #</th><th>Month</th><th>Due Date</th><th>Items</th><th>Payable</th><th>Balance</th></tr></thead>
      <tbody>
        @php
          // Running plan balance: after each installment's payable amount is
          // applied, show how much of the fee plan still remains.
          $runningPlanBalance = (float) $feeAssignment->total_amount;

          // $groups is keyed by installment_no, in order; regroup those into
          // one block per semester/year (cycle_no) so the plan reads
          // semester-by-semester instead of one long flat list. Older plans
          // predating cycle_no fall back to the original flat list.
          $hasCycles = $groups->isNotEmpty() && $groups->every(fn ($vouchers) => !is_null($vouchers->first()->cycle_no));
          $bySemester = $hasCycles ? $groups->groupBy(fn ($vouchers) => $vouchers->first()->cycle_no) : collect([null => $groups]);
        @endphp
        @php $installmentDisplayNo = 0; @endphp
        @foreach($bySemester as $cycleNo => $cycleGroups)
        @if($hasCycles)
        @php
          $cycleAmount   = $cycleGroups->flatten()->sum('amount');
          $cycleDiscount = $cycleGroups->flatten()->sum('discount_amount');
        @endphp
        <tr style="background:var(--bg)">
          <td colspan="5" style="font-weight:800;color:var(--primary);padding-top:14px">
            {{ $feeAssignment->termLabelFor((int) $cycleNo) }}
            <a href="{{ route('fee-assignments.cycle-summary', [$feeAssignment, (int) $cycleNo]) }}" target="_blank" style="font-size:11px;font-weight:600;margin-left:10px;color:var(--primary)"><i class="fas fa-file-invoice-dollar"></i> Download Summary</a>
          </td>
          <td colspan="2" style="text-align:right;font-weight:700;color:var(--text-muted);padding-top:14px">Rs. {{ number_format($cycleAmount - $cycleDiscount, 0) }} total</td>
        </tr>
        @endif
        @foreach($cycleGroups as $no => $vouchers)
        @php
          $amount = (float) $vouchers->sum('amount');
          $discount = (float) $vouchers->sum('discount_amount');
          $payable = round($amount - $discount, 2);
          $runningPlanBalance = max(0, round($runningPlanBalance - $payable, 2));
        @endphp
        @php $installmentDisplayNo++; @endphp
        <tr>
          <td><strong>{{ $installmentDisplayNo }}</strong></td>
          <td>
            @foreach($vouchers->groupBy('voucher_no') as $voucherNo => $challanRows)
              <div style="white-space:nowrap">
                <a href="{{ route('vouchers.show', $challanRows->first()) }}" style="font-weight:700">{{ $voucherNo }}</a>
                @if($challanRows->first()->split_sequence)
                  <span style="font-size:9px;color:var(--primary);font-weight:700"> · Split {{ $challanRows->first()->split_sequence }}</span>
                @endif
              </div>
            @endforeach
          </td>
          <td>{{ $vouchers->first()->month }}</td>
          <td>{{ $vouchers->first()->due_date->format('M d, Y') }}</td>
          <td style="font-size:12px;color:var(--text-muted)">{{ $vouchers->pluck('feeHead.name')->filter()->implode(', ') }}</td>
          <td><strong>Rs. {{ number_format($payable, 0) }}</strong></td>
          <td><strong style="color:var(--primary)">Rs. {{ number_format($runningPlanBalance, 0) }}</strong></td>
        </tr>
        @endforeach
        @endforeach
      </tbody>
    </table>
  </div>
</div>

<div class="card" style="margin-top:20px">
  <div class="card-header"><div class="card-title"><i class="fas fa-layer-group" style="color:var(--primary);margin-right:8px"></i>Change Installment Plan / Increase Fee for a Semester</div></div>
  <div class="card-body">
    <p style="font-size:13px;color:var(--text-muted);margin-bottom:14px">
      Re-split one semester/year of this plan into more (or fewer) installments, and/or increase or decrease its fee — available any time nothing has been paid against it yet and the one before it is fully paid.
    </p>
    @php $cycleFeeEditorsForJs = []; @endphp
    @for($cycle = $feeAssignment->cycle_start; $cycle <= $feeAssignment->cycle_end; $cycle++)
      @php
        $cycleVouchers = $feeAssignment->vouchersForCycle($cycle);
        $canSplit = $cycleVouchers->isNotEmpty() && $feeAssignment->canSplitCycle($cycle);
      @endphp
      @if($cycleVouchers->isNotEmpty())
      <div style="border:1px solid var(--border);border-radius:8px;padding:10px 14px;margin-bottom:10px;display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap">
        <div style="font-size:13px">
          <strong>{{ $feeAssignment->feeTemplate?->cycleLabel() ?? 'Term' }} {{ $cycle }}</strong>
          &middot; currently {{ $cycleVouchers->pluck('part_no')->unique()->count() }} installment(s)
          &middot; Rs. {{ number_format($cycleVouchers->sum(fn ($v) => (float) $v->amount - (float) $v->discount_amount), 0) }} payable
          @if(!$canSplit)
            <span style="color:var(--warning)"> — {{ $feeAssignment->splitLockReason($cycle) }}</span>
          @elseif($feeAssignment->isCycleManuallyUnlocked($cycle))
            <span style="color:var(--primary)"> — manually force-unlocked</span>
          @endif
        </div>
        @if(!$canSplit)
          <form action="{{ route('fee-assignments.toggle-cycle-lock', [$feeAssignment, $cycle]) }}" method="POST">
            @csrf
            <input type="hidden" name="action" value="unlock">
            <button type="submit" class="btn btn-outline btn-sm" title="Force-unlock this semester if the automatic rule doesn't match reality"><i class="fas fa-lock-open"></i> Force Unlock</button>
          </form>
        @endif
        @if($canSplit && $feeAssignment->isCycleManuallyUnlocked($cycle))
          <form action="{{ route('fee-assignments.toggle-cycle-lock', [$feeAssignment, $cycle]) }}" method="POST">
            @csrf
            <input type="hidden" name="action" value="lock">
            <button type="submit" class="btn btn-outline btn-sm" title="Restore the automatic lock rule"><i class="fas fa-lock"></i> Restore Auto-Lock</button>
          </form>
        @endif
        @if($canSplit)
        @php $cycleByHead = $cycleVouchers->groupBy('fee_head_id'); @endphp
        <form action="{{ route('fee-assignments.split-cycle', [$feeAssignment, $cycle]) }}" method="POST" style="display:flex;flex-direction:column;gap:10px;flex:1 1 760px;min-width:280px">
          @csrf
          <input type="hidden" name="manage_installment_fee_heads" value="1">
          <input type="hidden" name="installment_amount_basis" value="net">
          @php
            $headStateForJs = [];
            $customStateForJs = [];
            foreach ($cycleVouchers->groupBy('part_no') as $partNo => $partRows) {
                foreach ($partRows->groupBy('fee_head_id') as $feeHeadId => $headRows) {
                    if ($feeHeadId === '' || is_null($feeHeadId)) {
                        foreach ($headRows as $row) {
                            $customStateForJs[(int) $partNo][] = [
                                'fee_head_id' => null,
                                'label' => $row->title,
                                'amount' => max(0, (float) $row->amount - (float) $row->discount_amount),
                            ];
                        }
                    } else {
                        $sampleHead = $headRows->first();
                        $headStateForJs[(int) $partNo][(string) $feeHeadId] = [
                            'amount' => max(0, (float) $headRows->sum('amount') - (float) $headRows->sum('discount_amount')),
                            'name' => $sampleHead->feeHead->name ?? $sampleHead->title,
                        ];
                    }
                }
            }
            $cycleFeeEditorsForJs[(string) $cycle] = [
                'heads' => $headStateForJs,
                'custom' => $customStateForJs,
            ];
          @endphp
          <div style="display:flex;gap:8px;align-items:flex-end;flex-wrap:wrap">
            <label style="display:flex;flex-direction:column;font-size:10px;color:var(--text-muted)">Installments
              <input type="number" name="installments" min="1" max="12" value="{{ $cycleVouchers->pluck('part_no')->unique()->count() }}" class="form-control" style="width:70px" title="New number of installments">
            </label>
            <label style="display:flex;flex-direction:column;font-size:10px;color:var(--text-muted)">First Due Date
              <input type="date" name="first_due_date" value="{{ optional($cycleVouchers->sortBy('due_date')->first())->due_date?->toDateString() }}" class="form-control" style="width:135px" title="New due date for installment 1 of this semester">
            </label>
            <input type="hidden" name="frequency_days" value="30">
            <label style="display:flex;flex-direction:column;font-size:10px;color:var(--text-muted)">Increase / Decrease Fee By (Rs.)
              <input type="number" name="fee_increase_amount" step="1" value="0" class="form-control" style="width:110px" title="Positive increases and negative decreases this semester's fee by this flat amount.">
            </label>
            <label style="display:flex;flex-direction:column;font-size:10px;color:var(--text-muted)">Month Labels <span style="font-weight:400">(optional)</span>
              <input type="text" name="month_labels" class="form-control" style="width:200px" placeholder="e.g. Jan 2027, Feb 2027" title="Comma-separated, one per installment above — leave blank to auto-label as 'Semester N - Part N'">
            </label>
          </div>

          <div style="border-top:1px dashed var(--border);padding-top:9px">
            <div style="font-size:11px;font-weight:800;margin-bottom:4px"><i class="fas fa-list-check"></i> Fee heads by installment</div>
            <div style="font-size:10px;color:var(--text-muted);margin-bottom:8px;display:flex;align-items:flex-start;gap:7px;flex-wrap:wrap"><span style="display:inline-flex;align-items:center;gap:4px;background:rgba(16,185,129,.10);color:#047857;border:1px solid rgba(16,185,129,.22);border-radius:999px;padding:2px 7px;font-weight:800;white-space:nowrap"><i class="fas fa-arrow-right"></i> Smart re-balance</span><span style="flex:1;min-width:220px">Amounts in this editor are the current <strong>net payable after scholarship / discount / aid</strong>. Each installment can have its own fee-head selection. Unchecking a fee head carries that amount to the next payable installment. If you manually increase an amount in one installment, the same fee head is reduced from the coming installments in order; if you decrease it, the difference is added to the next installment. This lets a student pay a full fee head at once without changing the semester's total fee. Custom lines remain independent.</span></div>
            <div id="cycle-fee-editor-{{ $cycle }}" class="cycle-fee-editor" data-cycle="{{ $cycle }}"></div>
          </div>

          <button type="submit" class="btn btn-primary btn-sm" style="align-self:flex-start"><i class="fas fa-wand-magic-sparkles"></i> Apply Changes</button>
        </form>
        @endif
      </div>
      @endif
    @endfor
  </div>
</div>


@push('scripts')
<script>
(() => {
  const feeHeadCatalog = @json($feeHeads->map(fn ($h) => ['id' => $h->id, 'name' => $h->name])->values());
  const cycleData = @json($cycleFeeEditorsForJs ?? []);
  function esc(value) { return String(value ?? '').replace(/[&<>'"]/g, ch => ({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#039;','"':'&quot;'}[ch])); }

  document.querySelectorAll('.cycle-fee-editor').forEach(editor => {
    const form = editor.closest('form');
    const cycle = String(editor.dataset.cycle);
    const countInput = form.querySelector('input[name="installments"]');
    let headState = JSON.parse(JSON.stringify(cycleData[cycle]?.heads || {}));
    let customState = JSON.parse(JSON.stringify(cycleData[cycle]?.custom || {}));

    function capture() {
      if (!editor.querySelector('.cycle-head-row, .cycle-custom-row')) return;
      const nextHeads = {};
      editor.querySelectorAll('.cycle-head-row').forEach(row => {
        const box = row.querySelector('.cycle-head-check');
        const amount = row.querySelector('.cycle-head-amount');
        if (!box?.checked || !(parseFloat(amount?.value) > 0)) return;
        const part = String(row.dataset.part), head = String(row.dataset.head);
        if (!nextHeads[part]) nextHeads[part] = {};
        nextHeads[part][head] = {amount: amount.value, name: row.dataset.name || ''};
      });
      headState = nextHeads;
      const nextCustom = {};
      editor.querySelectorAll('.cycle-custom-row').forEach(row => {
        const amount = row.querySelector('.cycle-custom-amount');
        if (!(parseFloat(amount?.value) > 0)) return;
        const part = String(row.dataset.part);
        if (!nextCustom[part]) nextCustom[part] = [];
        nextCustom[part].push({fee_head_id: row.querySelector('.cycle-custom-head')?.value || '', label: row.querySelector('.cycle-custom-label')?.value || '', amount: amount.value});
      });
      customState = nextCustom;
    }

    function customRow(part, idx, values={}) {
      const selected = values.fee_head_id == null ? '' : String(values.fee_head_id);
      const opts = feeHeadCatalog.map(h => `<option value="${h.id}" ${String(h.id)===selected?'selected':''}>${esc(h.name)}</option>`).join('');
      return `<div class="cycle-custom-row" data-part="${part}" style="display:grid;grid-template-columns:minmax(140px,1.1fr) minmax(140px,1.2fr) minmax(95px,.7fr) auto;gap:6px;margin-top:6px">
        <select name="installment_custom_items[${cycle}][${part}][${idx}][fee_head_id]" class="form-control cycle-custom-head"><option value="">Custom label only</option>${opts}</select>
        <input type="text" name="installment_custom_items[${cycle}][${part}][${idx}][label]" class="form-control cycle-custom-label" value="${esc(values.label)}" placeholder="Label">
        <input type="number" min="0" step="1" name="installment_custom_items[${cycle}][${part}][${idx}][amount]" class="form-control cycle-custom-amount" value="${esc(values.amount)}" placeholder="Amount">
        <button type="button" class="btn btn-outline btn-sm remove-cycle-custom"><i class="fas fa-trash"></i></button>
      </div>`;
    }

    function render() {
      capture();
      const parts = Math.max(1, Math.min(parseInt(countInput.max || '12',10), parseInt(countInput.value || '1',10) || 1));
      countInput.value = parts;
      editor.innerHTML = '';
      for (let part=1; part<=parts; part++) {
        const saved = headState[String(part)] || {};
        const catalogMap = new Map(feeHeadCatalog.map(h => [String(h.id), h]));
        Object.entries(saved).forEach(([id,v]) => { if (!catalogMap.has(String(id))) catalogMap.set(String(id), {id:parseInt(id,10), name:v.name || `Fee Head #${id}`}); });
        const rows = Array.from(catalogMap.values()).map(head => {
          const value = saved[String(head.id)]?.amount ?? '';
          const selected = parseFloat(value) > 0;
          return `<div class="cycle-head-row" data-part="${part}" data-head="${head.id}" data-name="${esc(head.name)}" style="display:grid;grid-template-columns:minmax(150px,1fr) minmax(100px,130px);gap:7px;align-items:center;padding:5px 0;border-top:1px dashed var(--border)">
            <label style="display:flex;align-items:center;gap:6px;font-size:11px;margin:0;cursor:pointer"><input type="checkbox" class="cycle-head-check" ${selected?'checked':''}><span>${esc(head.name)}</span></label>
            <input type="number" min="0" step="1" name="installment_fee_heads[${cycle}][${part}][${head.id}]" class="form-control cycle-head-amount" value="${esc(value)}" ${selected?'':'disabled'} placeholder="Amount">
          </div>`;
        }).join('');
        const customs = Array.isArray(customState[String(part)]) ? customState[String(part)].map((v,i)=>customRow(part,i,v)).join('') : '';
        const card = document.createElement('div');
        card.style.cssText = 'border:1px solid var(--border);border-radius:9px;padding:9px;margin-bottom:8px;background:var(--bg)';
        card.innerHTML = `<div style="font-size:11.5px;font-weight:800;margin-bottom:5px">Installment ${part}</div>${rows}<div class="cycle-custom-wrap">${customs}</div><button type="button" class="btn btn-outline btn-sm add-cycle-custom" style="margin-top:7px"><i class="fas fa-plus"></i> Add fee head / custom</button>`;
        editor.appendChild(card);
        // Keep a lightweight in-page ledger so an immediate re-check can undo
        // a carry-forward before the form is submitted. The posted values are
        // still the source of truth: unchecked rows are disabled and the next
        // payable installment receives the combined amount.
        const transferLedger = editor._feeHeadTransferLedger || (editor._feeHeadTransferLedger = {});

        function sameHeadRows(headId) {
          return Array.from(editor.querySelectorAll('.cycle-head-row'))
            .filter(row => String(row.dataset.head || '') === String(headId))
            .sort((a, b) => Number(a.dataset.part || 0) - Number(b.dataset.part || 0));
        }

        function firstNextCheckedRow(headId, afterPart) {
          return sameHeadRows(headId).find(row => {
            const partNo = Number(row.dataset.part || 0);
            return partNo > Number(afterPart) && row.querySelector('.cycle-head-check')?.checked;
          }) || null;
        }

        function smallestCheckedAmount(headId, excludeRow = null) {
          const values = sameHeadRows(headId)
            .filter(row => row !== excludeRow && row.querySelector('.cycle-head-check')?.checked)
            .map(row => parseFloat(row.querySelector('.cycle-head-amount')?.value || ''))
            .filter(value => Number.isFinite(value) && value > 0);
          return values.length ? Math.min(...values) : 0;
        }

        card.querySelectorAll('.cycle-head-amount').forEach(input => {
          const initial = parseFloat(input.value || '');
          input.dataset.lastValidAmount = Number.isFinite(initial) && initial > 0 ? String(initial) : '';
        });

        card.querySelectorAll('.cycle-head-check').forEach(box => box.addEventListener('change', () => {
          const sourceRow = box.closest('.cycle-head-row');
          if (!sourceRow) return;

          const headId = String(sourceRow.dataset.head || '');
          const sourcePart = Number(sourceRow.dataset.part || 0);
          const sourceAmount = sourceRow.querySelector('.cycle-head-amount');
          const ledgerKey = `${headId}:${sourcePart}`;

          if (!box.checked) {
            const amountToMove = parseFloat(sourceAmount?.value || '');
            if (!(amountToMove > 0)) {
              if (sourceAmount) {
                sourceAmount.disabled = true;
                sourceAmount.value = '';
                sourceAmount.dataset.lastValidAmount = '';
              }
              return;
            }

            // Carry to the next installment that is still payable for this
            // head. If the immediately following installment was already
            // unchecked, the amount continues forward to the next checked one.
            const targetRow = firstNextCheckedRow(headId, sourcePart);
            if (!targetRow) {
              box.checked = true;
              if (sourceAmount) sourceAmount.disabled = false;
              window.alert('This fee head cannot be removed from the final payable installment because there is no later installment to carry its amount into.');
              return;
            }

            const targetBox = targetRow.querySelector('.cycle-head-check');
            const targetAmount = targetRow.querySelector('.cycle-head-amount');
            const targetValue = parseFloat(targetAmount?.value || '') || 0;
            const combined = Math.round((targetValue + amountToMove) * 100) / 100;

            if (sourceAmount) {
              sourceAmount.disabled = true;
              sourceAmount.value = '';
              sourceAmount.dataset.lastValidAmount = '';
            }
            if (targetBox) targetBox.checked = true;
            if (targetAmount) {
              targetAmount.disabled = false;
              targetAmount.value = String(combined);
              targetAmount.dataset.lastValidAmount = String(combined);
            }

            transferLedger[ledgerKey] = { amount: amountToMove };
            return;
          }

          // Re-checking a row that was just carried forward restores that
          // amount and removes the same amount from the next payable row.
          // This makes checkbox toggling reversible before Apply Changes.
          const previousTransfer = transferLedger[ledgerKey];
          if (previousTransfer?.amount > 0) {
            const downstreamRow = firstNextCheckedRow(headId, sourcePart);
            if (downstreamRow) {
              const downstreamBox = downstreamRow.querySelector('.cycle-head-check');
              const downstreamAmount = downstreamRow.querySelector('.cycle-head-amount');
              const downstreamValue = parseFloat(downstreamAmount?.value || '') || 0;
              const remaining = Math.round(Math.max(0, downstreamValue - Number(previousTransfer.amount)) * 100) / 100;

              if (downstreamAmount) {
                if (remaining > 0) {
                  downstreamAmount.value = String(remaining);
                  downstreamAmount.dataset.lastValidAmount = String(remaining);
                } else {
                  downstreamAmount.value = '';
                  downstreamAmount.dataset.lastValidAmount = '';
                  downstreamAmount.disabled = true;
                  if (downstreamBox) downstreamBox.checked = false;
                }
              }
            }

            if (sourceAmount) {
              sourceAmount.disabled = false;
              sourceAmount.value = String(previousTransfer.amount);
              sourceAmount.dataset.lastValidAmount = String(previousTransfer.amount);
            }
            delete transferLedger[ledgerKey];
            return;
          }

          // A fee head that was originally absent can still be enabled for
          // this one installment. Seed it with the smallest current amount for
          // that head, which avoids accidentally copying a carried total.
          if (sourceAmount) {
            sourceAmount.disabled = false;
            if (!(parseFloat(sourceAmount.value) > 0)) {
              const seed = smallestCheckedAmount(headId, sourceRow);
              sourceAmount.value = seed > 0 ? String(seed) : '';
              sourceAmount.dataset.lastValidAmount = seed > 0 ? String(seed) : '';
            }
          }
        }));

        card.querySelectorAll('.cycle-head-amount').forEach(input => input.addEventListener('input', () => {
          const sourceRow = input.closest('.cycle-head-row');
          if (!sourceRow) return;

          const headId = String(sourceRow.dataset.head || '');
          const sourcePart = Number(sourceRow.dataset.part || 0);
          const currentRaw = input.value.trim();
          if (currentRaw === '') return; // Do not rebalance while the user is clearing/typing.

          let currentValue = parseFloat(currentRaw);
          if (!Number.isFinite(currentValue) || currentValue < 0) return;
          currentValue = Math.round(currentValue * 100) / 100;

          let previousValue = parseFloat(input.dataset.lastValidAmount || '');
          if (!Number.isFinite(previousValue) || previousValue < 0) previousValue = 0;

          let delta = Math.round((currentValue - previousValue) * 100) / 100;
          if (Math.abs(delta) < 0.000001) return;

          const futureRows = sameHeadRows(headId).filter(row => Number(row.dataset.part || 0) > sourcePart);

          // Any manual edit must keep the total for this fee head unchanged.
          // Increasing the current installment consumes the same amount from
          // the coming installments, nearest first. Decreasing it pushes the
          // difference into the immediately following installment.
          if (delta > 0) {
            const availableLater = Math.round(futureRows.reduce((sum, row) => {
              const amount = parseFloat(row.querySelector('.cycle-head-amount')?.value || '');
              return sum + (Number.isFinite(amount) && amount > 0 ? amount : 0);
            }, 0) * 100) / 100;

            // A student cannot move more into this installment than remains in
            // the later installments. Cap the edit instead of changing the
            // semester/head total.
            if (delta > availableLater) {
              delta = availableLater;
              currentValue = Math.round((previousValue + availableLater) * 100) / 100;
              input.value = String(currentValue);
              window.alert('The full remaining amount for this fee head is already in this installment. There is nothing left in later installments to move here.');
            }

            let toTake = delta;
            for (const row of futureRows) {
              if (toTake <= 0.000001) break;
              const box = row.querySelector('.cycle-head-check');
              const amountInput = row.querySelector('.cycle-head-amount');
              if (!amountInput) continue;

              const oldAmount = parseFloat(amountInput.value || '');
              if (!(oldAmount > 0)) continue;

              const taken = Math.min(oldAmount, toTake);
              const remaining = Math.round((oldAmount - taken) * 100) / 100;
              toTake = Math.round((toTake - taken) * 100) / 100;

              if (remaining > 0) {
                amountInput.value = String(remaining);
                amountInput.disabled = false;
                amountInput.dataset.lastValidAmount = String(remaining);
                if (box) box.checked = true;
              } else {
                amountInput.value = '';
                amountInput.disabled = true;
                amountInput.dataset.lastValidAmount = '';
                if (box) box.checked = false;
              }
            }
          } else {
            const amountToMoveLater = Math.round(Math.abs(delta) * 100) / 100;
            const targetRow = futureRows[0] || null;

            if (!targetRow) {
              input.value = previousValue > 0 ? String(previousValue) : '';
              window.alert('The final installment cannot be reduced because there is no later installment to receive the difference.');
              return;
            }

            const targetBox = targetRow.querySelector('.cycle-head-check');
            const targetAmount = targetRow.querySelector('.cycle-head-amount');
            const oldTarget = parseFloat(targetAmount?.value || '');
            const nextTarget = Math.round(((Number.isFinite(oldTarget) && oldTarget > 0 ? oldTarget : 0) + amountToMoveLater) * 100) / 100;

            if (targetAmount) {
              targetAmount.disabled = false;
              targetAmount.value = String(nextTarget);
              targetAmount.dataset.lastValidAmount = String(nextTarget);
            }
            if (targetBox) targetBox.checked = true;
          }

          input.dataset.lastValidAmount = currentValue > 0 ? String(currentValue) : '0';
        }));

        // If the user deliberately finishes an edit at zero, treat that row as
        // no longer payable. The amount has already been moved forward above.
        card.querySelectorAll('.cycle-head-amount').forEach(input => input.addEventListener('change', () => {
          const row = input.closest('.cycle-head-row');
          const box = row?.querySelector('.cycle-head-check');
          const value = parseFloat(input.value || '');
          if (!row || !box || (Number.isFinite(value) && value > 0)) return;

          // A blank field can happen when the user clears the amount and tabs
          // away before typing a replacement. Preserve the fee-head total by
          // moving the last amount forward rather than silently dropping it.
          const previousValue = parseFloat(input.dataset.lastValidAmount || '');
          if (Number.isFinite(previousValue) && previousValue > 0) {
            const headId = String(row.dataset.head || '');
            const sourcePart = Number(row.dataset.part || 0);
            const targetRow = sameHeadRows(headId).find(r => Number(r.dataset.part || 0) > sourcePart) || null;

            if (!targetRow) {
              input.value = String(previousValue);
              input.disabled = false;
              box.checked = true;
              window.alert('The final installment cannot be cleared because there is no later installment to receive the amount.');
              return;
            }

            const targetBox = targetRow.querySelector('.cycle-head-check');
            const targetAmount = targetRow.querySelector('.cycle-head-amount');
            const oldTarget = parseFloat(targetAmount?.value || '');
            const nextTarget = Math.round(((Number.isFinite(oldTarget) && oldTarget > 0 ? oldTarget : 0) + previousValue) * 100) / 100;
            if (targetAmount) {
              targetAmount.disabled = false;
              targetAmount.value = String(nextTarget);
              targetAmount.dataset.lastValidAmount = String(nextTarget);
            }
            if (targetBox) targetBox.checked = true;
          }

          box.checked = false;
          input.value = '';
          input.disabled = true;
          input.dataset.lastValidAmount = '';
        }));

        card.querySelectorAll('.remove-cycle-custom').forEach(btn => btn.addEventListener('click', () => btn.closest('.cycle-custom-row')?.remove()));
        card.querySelector('.add-cycle-custom')?.addEventListener('click', () => {
          const wrap=card.querySelector('.cycle-custom-wrap'); const idx=Date.now() + Math.floor(Math.random() * 1000); wrap.insertAdjacentHTML('beforeend', customRow(part,idx,{}));
          wrap.lastElementChild?.querySelector('.remove-cycle-custom')?.addEventListener('click', e => e.currentTarget.closest('.cycle-custom-row')?.remove());
        });
      }
    }
    countInput.addEventListener('input', render);
    render();
  });
})();
</script>
@endpush

@endsection