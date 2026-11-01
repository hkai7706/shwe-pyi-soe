@extends('layouts.school')
@section('title',__('ui.grades'))
@section('content')
<div class="page-heading"><div><span class="eyebrow">{{ __('ui.community') }}</span><h1>{{ __('ui.grades') }}</h1><p>{{ __('ui.grades_intro') }}</p></div><span class="badge soft"><x-icon name="calendar"/>{{ __('ui.year_value') }}</span></div>
<div class="grade-grid">@foreach($grades as $grade)<a class="card grade-card" href="{{ route('grade',$grade) }}"><span class="grade-number">{{ $grade->level ?: 'KG' }}</span><div><h2>{{ $grade->localized('name') }}</h2><p>{{ $grade->classes->sum('students_count') }} {{ __('ui.students') }} <span>·</span> {{ $grade->classes_count }} {{ __('ui.class') }}</p></div><span class="round-arrow"><x-icon name="arrow"/></span></a>@endforeach</div>
@endsection
