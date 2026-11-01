<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Grade extends Model
{
    protected $guarded = [];

    public function classes(): HasMany
    {
        return $this->hasMany(SchoolClass::class);
    }

    public function teachers(): HasMany
    {
        return $this->hasMany(Teacher::class, 'main_grade_id');
    }

    public function students(): HasManyThrough
    {
        return $this->hasManyThrough(Student::class, SchoolClass::class);
    }

    public function localized(string $field): string
    {
        return (string) ($this->getAttribute($field.'_'.app()->getLocale()) ?: $this->getAttribute($field.'_en'));
    }
}
