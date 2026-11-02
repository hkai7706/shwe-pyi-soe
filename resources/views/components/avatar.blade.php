@props(['person','large'=>false])
@if($person->photo_mime)
<img class="avatar {{ $large ? 'avatar-large' : '' }}" src="{{ route('portrait', ['type' => $person instanceof \App\Models\Student ? 'students' : 'teachers', 'id' => $person->id]) }}" alt="{{ $person->localized('name') }}" loading="lazy">
@elseif($person->photo)
<img class="avatar {{ $large ? 'avatar-large' : '' }}" src="{{ route('media', ['path' => $person->photo]) }}" alt="{{ $person->localized('name') }}" loading="lazy">
@else
<span class="avatar initials {{ $large ? 'avatar-large' : '' }}" aria-hidden="true">{{ collect(explode(' ', $person->name_en))->filter()->take(2)->map(fn($part)=>mb_substr($part,0,1))->implode('') }}</span>
@endif
