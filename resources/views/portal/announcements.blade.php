@extends('layouts.school')
@section('title',__('ui.announcements'))
@section('content')
<div class="page-heading"><div><span class="eyebrow">{{ __('ui.community') }}</span><h1>{{ __('ui.announcements') }}</h1><p>{{ __('ui.notice_intro') }}</p></div><form method="GET"><label class="sr-only" for="grade">{{ __('ui.grade') }}</label><select id="grade" name="grade" onchange="this.form.submit()"><option value="">{{ __('ui.all_grades') }}</option>@foreach($grades as $grade)<option value="{{ $grade->id }}" @selected(request('grade') == $grade->id)>{{ $grade->localized('name') }}</option>@endforeach</select><noscript><button>{{ __('ui.filter') }}</button></noscript></form></div>
<div class="notice-grid">@forelse($notices as $notice)<x-notice :notice="$notice"/>@empty<div class="card empty">{{ __('ui.no_data') }}</div>@endforelse</div><div class="pagination">{{ $notices->links() }}</div>
@endsection
