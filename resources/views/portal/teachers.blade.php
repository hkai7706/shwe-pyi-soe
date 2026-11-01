@extends('layouts.school')
@section('title',__('ui.teachers'))
@section('content')
<div class="page-heading"><div><span class="eyebrow">{{ __('ui.community') }}</span><h1>{{ __('ui.teachers') }}</h1><p>{{ __('ui.teacher_intro') }}</p></div></div>
@foreach($grades as $grade)<div class="section-heading"><h2>{{ $grade->localized('name') }} {{ __('ui.teachers') }}</h2><span class="count-pill">{{ $grade->teachers->count() }}</span></div><div class="teacher-grid">@forelse($grade->teachers as $teacher)<article class="card teacher-card"><x-avatar :person="$teacher" large/><h3>{{ $teacher->localized('name') }}</h3><p class="teacher-subject">{{ $teacher->subject }}</p><p>{{ $teacher->qualification }}</p><p class="muted">{{ $teacher->experience_years }} {{ __('ui.experience') }}</p>@foreach($teacher->headedClasses as $class)<span class="badge soft wrap">{{ __('ui.head_teacher') }} · {{ $class->label }}</span>@endforeach</article>@empty<div class="card empty">{{ __('ui.no_records') }}</div>@endforelse</div>@endforeach
@endsection
