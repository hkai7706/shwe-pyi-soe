@extends('layouts.school',['isStaff'=>true])
@section('title',__('ui.'.$resource))
@section('content')
<div class="page-heading"><div><span class="eyebrow">{{ __('ui.'.$resource) }}</span><h1>{{ $record->exists ? __('ui.edit') : __('ui.create') }}</h1></div><a class="text-link" href="{{ route('admin.records',$resource) }}">{{ __('ui.cancel') }}</a></div>
<form class="card edit-form" method="POST" enctype="multipart/form-data" action="{{ $record->exists ? route('admin.update',[$resource,$record->id]) : route('admin.store',$resource) }}">@csrf @if($record->exists) @method('PUT') @endif
<div class="form-grid">
@if($resource !== 'announcements')
@foreach(['name_en','name_my'] as $field)<label>{{ __('ui.'.$field) }}<input name="{{ $field }}" value="{{ old($field,$record->$field) }}" required maxlength="255" @if($field === 'name_my') lang="my" @endif></label>@endforeach
<label class="full-width">{{ __('ui.photo') }}<input type="file" name="photo" accept="image/jpeg,image/png,image/webp"><small>{{ __('ui.photo_help') }}</small>@if($record->photo)<x-avatar :person="$record"/>@endif</label>
@if($resource === 'students')
<label>{{ __('ui.class') }}<select name="school_class_id" required>@foreach($classes as $class)<option value="{{ $class->id }}" @selected(old('school_class_id',$record->school_class_id) == $class->id)>{{ $class->label }}</option>@endforeach</select></label><label>{{ __('ui.main_subject') }}<select name="main_subject"><option value="">{{ __('ui.choose_main_subject') }}</option>@foreach(['biology','economics'] as $subject)<option value="{{ $subject }}" @selected(old('main_subject',$record->main_subject) === $subject)>{{ __('ui.'.$subject) }}</option>@endforeach</select><small>{{ __('ui.main_subject_help') }}</small></label><label>{{ __('ui.gender') }}<select name="gender">@foreach(['male','female','other'] as $gender)<option @selected(old('gender',$record->gender) === $gender) value="{{ $gender }}">{{ __('ui.'.$gender) }}</option>@endforeach</select></label>
@foreach(['date_of_birth','enrolled_on'] as $field)<label>{{ __('ui.'.$field) }}<input type="date" name="{{ $field }}" value="{{ old($field,$record->$field?->format('Y-m-d')) }}" required></label>@endforeach
@foreach(['parent_name','parent_phone','address'] as $field)<label>{{ __('ui.'.$field) }}<input name="{{ $field }}" value="{{ old($field,$record->$field) }}" @required($field !== 'address')></label>@endforeach
<label class="checkbox-label"><input type="checkbox" name="is_active" value="1" @checked(old('is_active',$record->exists ? $record->is_active : true))>{{ __('ui.is_active') }}</label>
@else
<label>{{ __('ui.grade') }}<select name="main_grade_id">@foreach($grades as $grade)<option value="{{ $grade->id }}" @selected(old('main_grade_id',$record->main_grade_id) == $grade->id)>{{ $grade->localized('name') }}</option>@endforeach</select></label>
@foreach(['subject','qualification','experience_years','phone'] as $field)<label>{{ __('ui.'.$field) }}<input name="{{ $field }}" value="{{ old($field,$record->$field) }}" type="{{ $field === 'experience_years' ? 'number' : 'text' }}" @if($field === 'experience_years') min="0" max="80" @endif @required($field !== 'phone')></label>@endforeach
@endif
@else
@foreach(['title_en','title_my'] as $field)<label>{{ __('ui.'.$field) }}<input name="{{ $field }}" value="{{ old($field,$record->$field) }}" required maxlength="255"></label>@endforeach
@foreach(['body_en','body_my'] as $field)<label>{{ __('ui.'.$field) }}<textarea rows="6" name="{{ $field }}" required>{{ old($field,$record->$field) }}</textarea></label>@endforeach
<label>{{ __('ui.category') }}<select name="category">@foreach(['exam','holiday','fees','event','meeting','general'] as $category)<option value="{{ $category }}" @selected(old('category',$record->category) === $category)>{{ __('ui.'.$category) }}</option>@endforeach</select></label>
@foreach(['event_date','is_new_until'] as $field)<label>{{ __('ui.'.$field) }}<input type="date" name="{{ $field }}" value="{{ old($field,$record->$field?->format('Y-m-d')) }}"></label>@endforeach
<label>{{ __('ui.published_at') }}<input type="datetime-local" name="published_at" value="{{ old('published_at',($record->published_at ?? now())->format('Y-m-d\TH:i')) }}" required></label>
@foreach(['when_text','audience_text','action_text'] as $field)<label>{{ __('ui.'.$field) }}<input name="{{ $field }}" value="{{ old($field,$record->$field) }}" maxlength="255"></label>@endforeach
<label>{{ __('ui.target') }}<select name="target[]" multiple size="5">@foreach($grades as $grade)<option value="{{ $grade->id }}" @selected(in_array($grade->id,old('target',$record->target ?? [])))>{{ $grade->localized('name') }}</option>@endforeach</select></label><label class="checkbox-label"><input type="checkbox" name="is_important" value="1" @checked(old('is_important',$record->is_important))>{{ __('ui.is_important') }}</label>
@endif
</div><div class="form-actions"><button class="button">{{ __('ui.save') }}</button><a class="text-link" href="{{ route('admin.records',$resource) }}">{{ __('ui.cancel') }}</a></div></form>
@endsection
