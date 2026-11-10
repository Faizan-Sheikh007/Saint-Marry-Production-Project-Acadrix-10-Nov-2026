@extends('layouts.auth')
@section('content')
<div class="card" style="max-width:420px;text-align:center;padding:40px">
  <div style="font-size:60px;margin-bottom:16px">🔒</div>
  <h2 style="font-size:22px;font-weight:800;margin-bottom:8px">Access Denied</h2>
  <p style="color:var(--text-muted);margin-bottom:24px">You don't have permission to view this page.</p>
  <a href="{{ url('/') }}" class="btn btn-primary"><i class="fas fa-home"></i> Go to Dashboard</a>
</div>
@endsection
