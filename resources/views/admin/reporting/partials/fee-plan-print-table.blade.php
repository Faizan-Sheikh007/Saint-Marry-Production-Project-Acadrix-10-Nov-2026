<div class="section-title">Fee Plan Detail</div>
<div class="rpt-table-shell">
<table class="rpt" style="font-size:8px">
  <thead>
    <tr>
      <th>#</th><th>ID / Roll No.</th><th>Name</th><th>Father Name</th><th>Program</th><th>Semester</th>
      <th>Total Fee</th><th>Total Inst.</th>
      <?php for ($i = 1; $i <= $maxInstallments; $i++): ?>
        <th>Inst. {{ $i }} Due</th>
      <?php endfor; ?>
      <th>Total Paid</th><th>Total Pending</th><th>Outstandings</th><th>Fee Status</th><th>Remarks</th>
    </tr>
  </thead>
  <tbody>
    <?php if (count($rows) === 0): ?>
      <tr><td colspan="{{ 13 + $maxInstallments }}" class="ctr">No fee records found for the selected filters.</td></tr>
    <?php else: ?>
      <?php foreach ($rows as $idx => $row): ?>
        <tr>
          <td class="ctr">{{ $idx + 1 }}</td>
          <td>{{ $row['student']->student_id ?? '—' }}</td>
          <td>{{ $row['student']->name ?? '—' }}<br>@include('partials.report-student-status', ['statusStudent' => $row['student']])</td>
          <td>{{ $row['student']->father_name ?? '—' }}</td>
          <td>{{ $row['student']->program->name ?? '—' }}</td>
          <td>{{ $row['student']->schoolClass->section_label ?? '—' }}</td>
          <td class="num">{{ number_format($row['total_fee'], 0) }}</td>
          <td class="ctr">{{ $row['total_installments'] }}</td>
          <?php for ($i = 1; $i <= $maxInstallments; $i++): ?>
            <?php $inst = $row['installments']->get($i); ?>
            <td class="ctr">
              <?php if ($inst): ?>
                {{ number_format($inst['pending'] > 0 ? $inst['pending'] : $inst['total'], 0) }}<br>
                {{ $inst['due_date']?->format('d M y') ?? 'No date' }}<br>{{ $inst['status'] }}
              <?php else: ?>
                —
              <?php endif; ?>
            </td>
          <?php endfor; ?>
          <td class="num">{{ number_format($row['total_paid'], 0) }}</td>
          <td class="num">{{ number_format($row['total_pending'], 0) }}</td>
          <td class="num">{{ number_format($row['outstanding'], 0) }}</td>
          <td class="ctr">{{ ucfirst($row['fee_status']) }}</td>
          <td>{{ $row['remarks'] ?: '' }}</td>
        </tr>
      <?php endforeach; ?>
    <?php endif; ?>
  </tbody>
</table>
</div>
