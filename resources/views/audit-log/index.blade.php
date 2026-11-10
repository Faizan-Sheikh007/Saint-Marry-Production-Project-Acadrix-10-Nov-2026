@extends('layouts.app')
@section('title', 'Audit Log')
@section('page_title', 'Audit Log')
@section('content')
<div style="font-size:22px;font-weight:800;margin-bottom:20px">Audit Log</div>
<div class="card">
  <div class="table-wrap">
    <table>
      <thead><tr><th>Date</th><th>User</th><th>Action</th><th>Subject</th><th>Description</th></tr></thead>
      <tbody>
        @forelse($logs as $log)
        <tr>
          <td>{{ $log->created_at->format('M d, Y h:i A') }}</td>
          <td>{{ $log->user->name ?? 'System' }}</td>
          <td><span class="badge primary">{{ ucfirst($log->action) }}</span></td>
          <td>{{ $log->subject_type }}</td>
          <td>{{ $log->description }}</td>
        </tr>
        @empty
        <tr><td colspan="5" style="text-align:center;padding:40px;color:var(--text-muted)">No activity recorded yet.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
  <div style="padding:16px 20px">{{ $logs->links() }}</div>
</div>
@endsection
