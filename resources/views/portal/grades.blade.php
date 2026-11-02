@extends('layouts.school')
@section('title',__('ui.grades'))
@section('content')
<div class="page-heading"><div><span class="eyebrow">{{ __('ui.community') }}</span><h1>{{ __('ui.grades') }}</h1><p>{{ __('ui.grades_intro') }}</p></div><span class="badge soft"><x-icon name="calendar"/>{{ __('ui.year_value') }}</span></div>
<x-student-search :students="$students"/>
<div class="grade-grid">@foreach($grades as $grade)<a class="card grade-card" href="{{ route('grade',$grade) }}"><span class="grade-number">{{ $grade->level ?: 'KG' }}</span><div><h2>{{ $grade->localized('name') }}</h2>@if($grade->classes->sum('students_count'))<p>{{ trans_choice('ui.student_count',$grade->classes->sum('students_count')) }} <span>·</span> {{ trans_choice('ui.class_count',$grade->classes_count) }}</p>@else<p class="muted">{{ __('ui.no_students_yet') }}</p>@endif</div><span class="round-arrow"><x-icon name="arrow"/></span></a>@endforeach</div>
@endsection
