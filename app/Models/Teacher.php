<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Teacher extends Model
{
    protected $guarded = [];

    public const PUBLIC_COLUMNS = ['id', 'name_en', 'name_my', 'photo', 'photo_mime', 'subject', 'qualification', 'experience_years', 'main_grade_id'];

    protected $hidden = ['phone', 'photo_data'];

    public function grade(): BelongsTo
    {
        return $this->belongsTo(Grade::class, 'main_grade_id');
    }

    public function headedClasses(): HasMany
    {
        return $this->hasMany(SchoolClass::class, 'head_teacher_id');
    }

    public function localized(string $field): string
    {
        return (string) ($this->getAttribute($field.'_'.app()->getLocale()) ?: $this->getAttribute($field.'_en'));
    }
}
