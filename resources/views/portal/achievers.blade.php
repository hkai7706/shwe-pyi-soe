@extends('layouts.school')
@section('title',__('ui.achievers'))
@section('content')
<div class="page-heading"><div><span class="eyebrow">{{ __('ui.community') }}</span><h1>{{ __('ui.achievers') }}</h1><p>{{ __('ui.achievers_intro') }}</p></div><span class="trophy-heading"><x-icon name="achievers"/></span></div><x-exam-switcher :exam="$exam" :exams="$exams"/><div class="achiever-grid">@forelse($achievers as $row)<x-achiever :row="$row" :exam="$exam"/>@empty<div class="card empty">{{ __('ui.no_data') }}</div>@endforelse</div><p class="table-note">{{ __('ui.rank_help') }}</p>
@endsection
