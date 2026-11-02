@extends('layouts.school')
@section('title',$student->localized('name'))
@section('content')
<a class="text-link back-link" href="{{ route('grade',$student->schoolClass->grade) }}">← {{ $student->schoolClass->label }}</a>
<section class="card profile-heading"><x-avatar :person="$student" large/><div><span class="eyebrow">{{ __('ui.student') }}</span><h1>{{ $student->localized('name') }}</h1><p>{{ $student->schoolClass->label }}</p></div><div class="profile-meta">@if($student->schoolClass->headTeacher)<span>{{ __('ui.head_teacher') }}</span><strong>{{ $student->schoolClass->headTeacher->localized('name') }}</strong>@else<span>{{ __('ui.head_teacher_announced') }}</span>@endif<span>{{ __('ui.attendance') }} · {{ $progress->first()['row']['attendance'] !== null ? $progress->first()['row']['attendance'].'%' : '—' }}</span></div></section>
<div class="section-heading"><div><h2>{{ __('ui.progress') }}</h2><p>{{ __('ui.progress_intro') }}</p></div></div>
<div class="progress-grid">@foreach($progress as $item)<section class="card progress-card"><span class="badge soft">{{ __('ui.exam_marks',['exam'=>$item['exam']->localized('name')]) }}</span>@if($item['exam']->is_final)<span class="new-dot">{{ __('ui.final') }}</span>@endif
@if($item['row']['complete'])<strong class="progress-total">{{ $item['row']['total'] }}<small>{{ __('ui.total') }}</small></strong><div class="progress-track"><div style="width:{{ $item['row']['average'] }}%"></div></div><div class="progress-meta"><span>{{ __('ui.rank') }} <b>{{ $item['row']['rank'] }}</b></span><span>{{ __('ui.average') }} <b>{{ $item['row']['average'] }}</b></span></div>@else<p class="muted no-marks">{{ __('ui.no_marks_yet') }}</p>@endif
<dl class="subject-results">@foreach($item['row']['student']->marks as $mark)<div><dt>{{ $mark->subject->localized('name') }}</dt><dd>{{ $mark->score }}</dd></div>@endforeach</dl></section>@endforeach</div><p class="table-note">{{ __('ui.rank_help') }} {{ __('ui.attendance_help') }}</p>
@endsection
