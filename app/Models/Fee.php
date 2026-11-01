<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Fee extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['amount_due' => 'decimal:2', 'amount_paid' => 'decimal:2', 'paid_on' => 'date'];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function getStatusAttribute(): string
    {
        return $this->amount_paid >= $this->amount_due ? 'paid' : ($this->amount_paid > 0 ? 'partial' : 'unpaid');
    }

    public function getLeftAttribute(): float
    {
        return max(0, (float) $this->amount_due - (float) $this->amount_paid);
    }

    public function localized(string $field): string
    {
        return (string) ($this->getAttribute($field.'_'.app()->getLocale()) ?: $this->getAttribute($field.'_en'));
    }
}
