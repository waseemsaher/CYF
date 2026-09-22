<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected $table = 'payments';

    protected $fillable = [
        'user_id',
        'course_id',
        'term_id',
        'method',
        'list_price_cents',
        'discount_cents',
        'amount_due_cents',
        'sender_identifier',
        'proof_path',
        'proof_hash',
        'student_note',
        'status',
        'rejection_reason',
        'reviewed_by',
        'reviewed_at',
        'teacher_share_percent',
        'teacher_share_cents',
        'platform_share_cents',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'list_price_cents' => 'integer',
            'discount_cents' => 'integer',
            'amount_due_cents' => 'integer',
            'teacher_share_percent' => 'integer',
            'teacher_share_cents' => 'integer',
            'platform_share_cents' => 'integer',
            'reviewed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Course, $this>
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * @return BelongsTo<Term, $this>
     */
    public function term(): BelongsTo
    {
        return $this->belongsTo(Term::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function isPending(): bool
    {
        return $this->getAttribute('status') === 'pending';
    }

    public function isApproved(): bool
    {
        return $this->getAttribute('status') === 'approved';
    }

    /**
     * Check if a duplicate proof hash exists on another payment.
     */
    public function hasDuplicateProof(): bool
    {
        return static::query()
            ->where('proof_hash', $this->getAttribute('proof_hash'))
            ->where('id', '!=', $this->getKey())
            ->exists();
    }
}
