<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Announcement extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['target' => 'array', 'event_date' => 'date', 'is_new_until' => 'date', 'published_at' => 'datetime', 'is_important' => 'boolean'];
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('published_at', '<=', now());
    }

    public function localized(string $field): string
    {
        return (string) ($this->getAttribute($field.'_'.app()->getLocale()) ?: $this->getAttribute($field.'_en'));
    }
}
