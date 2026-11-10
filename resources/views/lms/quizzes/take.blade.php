@extends('layouts.app')
@section('title', 'Taking: ' . $quiz->title)
@section('page_title', $quiz->title)
@section('content')

<div class="page-header">
  <div>
    <div class="page-title">{{ $quiz->title }}</div>
    <div class="page-subtitle">{{ $course->title }} &middot; {{ $quiz->questions->count() }} questions &middot; {{ $quiz->total_marks }} marks</div>
  </div>
  <div class="badge warning" id="timer" style="font-size:14px;padding:8px 16px"><i class="fas fa-clock"></i> <span id="timerText">{{ $quiz->time_limit }}:00</span></div>
</div>

<form method="POST" action="{{ route('lms.quizzes.submit', [$course, $quiz]) }}" id="quizTakeForm">
@csrf
<input type="hidden" name="attempt_id" value="{{ $attempt->id }}">

@foreach($quiz->questions as $q)
<div class="quiz-question">
  <div class="quiz-qnum">Question {{ $loop->iteration }} &middot; {{ $q->marks }} mark{{ $q->marks == 1 ? '' : 's' }}</div>
  <div class="quiz-qtext">{{ $q->question }}</div>

  @if($q->type === 'mcq')
    <div class="quiz-options">
      @foreach(($q->options ?? []) as $opt)
        <label>
          <input type="radio" name="answers[{{ $q->id }}]" value="{{ $opt }}" required>
          <span>{{ $opt }}</span>
        </label>
      @endforeach
    </div>
  @elseif($q->type === 'true_false')
    <div class="quiz-options">
      <label><input type="radio" name="answers[{{ $q->id }}]" value="true" required><span>True</span></label>
      <label><input type="radio" name="answers[{{ $q->id }}]" value="false" required><span>False</span></label>
    </div>
  @else
    <input type="text" name="answers[{{ $q->id }}]" class="form-control" placeholder="Your answer" required>
  @endif
</div>
@endforeach

<button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;padding:14px"><i class="fas fa-paper-plane"></i> Submit Quiz</button>
</form>

<script>
let remaining = {{ $remainingSeconds }};
const timerText = document.getElementById('timerText');
const form = document.getElementById('quizTakeForm');
function displayRemaining() {
  const m = Math.floor(Math.max(0, remaining) / 60), s = Math.max(0, remaining) % 60;
  timerText.textContent = m + ':' + String(s).padStart(2, '0');
}
displayRemaining();
let interval = null;
if (remaining <= 0) form.submit();
else interval = setInterval(() => {
  remaining--;
  displayRemaining();
  if (remaining <= 0) {
    clearInterval(interval);
    form.submit();
  }
}, 1000);
form.addEventListener('submit', () => clearInterval(interval));
</script>

@endsection
