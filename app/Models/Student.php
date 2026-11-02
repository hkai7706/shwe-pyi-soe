<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Student extends Model
{
    protected $guarded = [];

    public const PUBLIC_COLUMNS = ['id', 'school_class_id', 'name_en', 'name_my', 'photo', 'main_subject'];

    protected $hidden = ['parent_name', 'parent_phone', 'date_of_birth', 'address', 'gender', 'enrolled_on'];

    protected function casts(): array
    {
        return ['date_of_birth' => 'date', 'enrolled_on' => 'date', 'is_active' => 'boolean'];
    }

    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class);
    }

    public function marks(): HasMany
    {
        return $this->hasMany(Mark::class);
    }

    public function attendance(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function fee(): HasOne
    {
        return $this->hasOne(Fee::class);
    }

    public function localized(string $field): string
    {
        return (string) ($this->getAttribute($field.'_'.app()->getLocale()) ?: $this->getAttribute($field.'_en'));
    }
}
