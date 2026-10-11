<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CtaBlock extends Model
{
    use HasFactory;

    protected $table = 'cta_blocks';

    protected $fillable = [
        'key',
        'label_ar',
        'headline_ar',
        'subtext_ar',
        'url',
        'style',
        'is_active',
        'starts_at',
        'ends_at',
        'open_in_new_tab',
        'updated_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'open_in_new_tab' => 'boolean',
            'updated_by' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function computedStatus(?CarbonImmutable $now = null): string
    {
        $now ??= CarbonImmutable::now();

        if (! $this->is_active) {
            return 'inactive';
        }

        if ($this->ends_at !== null && $now->greaterThan($this->ends_at)) {
            return 'expired';
        }

        if ($this->starts_at !== null && $now->lessThan($this->starts_at)) {
            return 'scheduled';
        }

        return 'active';
    }

    public function isCurrentlyVisible(?CarbonImmutable $now = null): bool
    {
        return $this->computedStatus($now) === 'active';
    }
}
