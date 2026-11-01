@props(['notice','compact'=>false])
<article class="notice card {{ $notice->is_important ? 'pinned' : '' }}">
<div class="notice-top"><span class="badge category-{{ $notice->category }}"><x-icon :name="$notice->category"/>{{ __('ui.'.$notice->category) }}</span><div class="badges">@if($notice->is_important)<span class="badge important">{{ __('ui.important') }}</span>@endif @if($notice->is_new_until?->endOfDay()->isFuture())<span class="new-dot">{{ __('ui.new') }}</span>@endif</div></div>
<div class="notice-content"><div class="date-block"><strong>{{ ($notice->event_date ?? $notice->published_at)->format('d') }}</strong><span>{{ ($notice->event_date ?? $notice->published_at)->locale(app()->getLocale())->translatedFormat('M Y') }}</span></div><div class="notice-copy"><h3>{{ $notice->localized('title') }}</h3><p>{{ $compact ? Str::limit($notice->localized('body'),150) : $notice->localized('body') }}</p></div></div>
@if(!$compact)<dl class="notice-details">@foreach(['when'=>'when_text','audience'=>'audience_text','action'=>'action_text'] as $label=>$field)@if($notice->$field)<div><dt>{{ __('ui.'.$label) }}</dt><dd>{{ $notice->$field }}</dd></div>@endif @endforeach
@if($notice->target)<div><dt>{{ __('ui.grades') }}</dt><dd>{{ \App\Models\Grade::whereIn('id',$notice->target)->get()->map(fn($grade)=>$grade->localized('name'))->implode(' · ') }}</dd></div>@endif</dl>@endif
</article>
