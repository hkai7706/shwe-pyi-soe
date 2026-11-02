@extends('layouts.school')
@section('title',__('ui.announcements'))
@section('content')
<div class="page-heading"><div><span class="eyebrow">{{ __('ui.community') }}</span><h1>{{ __('ui.announcements') }}</h1><p>{{ __('ui.notice_intro') }}</p></div></div>
<nav class="chips announcement-pills" aria-label="{{ __('ui.announcements') }}"><a class="chip {{ !request('category') ? 'selected' : '' }}" href="{{ route('announcements') }}">{{ __('ui.all') }}</a>@foreach(['exam','holiday','fees','event','meeting','general'] as $category)<a class="chip {{ request('category') === $category ? 'selected' : '' }}" href="{{ route('announcements',['category'=>$category]) }}">{{ __('ui.'.$category) }}</a>@endforeach</nav>
<div class="notice-grid">@forelse($notices as $notice)<x-notice :notice="$notice"/>@empty<div class="card empty">{{ __('ui.no_data') }}</div>@endforelse</div><div class="pagination">{{ $notices->links() }}</div>
@endsection
