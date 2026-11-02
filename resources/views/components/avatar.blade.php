@props(['person','large'=>false])
@if($person->photo)
<img class="avatar {{ $large ? 'avatar-large' : '' }}" src="{{ route('media', ['path' => $person->photo]) }}" alt="{{ $person->localized('name') }}" loading="lazy">
@else
<span class="avatar initials {{ $large ? 'avatar-large' : '' }}" aria-hidden="true">{{ collect(explode(' ', $person->name_en))->filter()->take(2)->map(fn($part)=>mb_substr($part,0,1))->implode('') }}</span>
@endif
