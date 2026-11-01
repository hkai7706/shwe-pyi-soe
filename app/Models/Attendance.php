<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class Attendance extends Model
{
    protected $guarded = [];

    protected $table = 'attendance';

    protected function date(): Attribute
    {
        return Attribute::make(
            get: fn (string $value) => Carbon::parse($value),
            set: fn ($value) => Carbon::parse($value)->toDateString(),
        );
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function localized(string $field): string
    {
        return (string) ($this->getAttribute($field.'_'.app()->getLocale()) ?: $this->getAttribute($field.'_en'));
    }
}
