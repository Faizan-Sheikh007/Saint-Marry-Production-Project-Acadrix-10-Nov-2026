@extends('layouts.app')
@section('title', 'Student Profile Disabled')
@section('content')
<div class="card"><div class="card-body">
  <h2><i class="fas fa-lock"></i> Student Profile Disabled</h2>
  <p>{{ $message }}</p>
  <p>No changes from this request have been saved. Existing records, receipts and balances have been retained.</p>
  @if(auth('web')->user()?->isAdmin())
    <a class="btn btn-outline" href="{{ route('students.show', $student) }}">View Read-only Profile</a>
  @elseif(auth('accounts')->user()?->isAccountant())
    <a class="btn btn-outline" href="{{ route('accounts.students.show', $student) }}">View Read-only Account</a>
  @else
    <a class="btn btn-outline" href="{{ url('/home') }}">Back to Dashboard</a>
  @endif
</div></div>
@endsection
