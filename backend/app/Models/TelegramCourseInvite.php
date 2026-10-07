<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property int $course_id
 * @property string $invite_link
 * @property Carbon $expires_at
 * @property Carbon|null $used_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User $user
 * @property-read Course $course
 */
class TelegramCourseInvite extends Model
{
    protected $table = 'telegram_course_invites';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'course_id',
        'invite_link',
        'expires_at',
        'used_at',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'expires_at' => 'datetime',
        'used_at' => 'datetime',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'used_at' => 'datetime',
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
     * True when the invite link is still within its validity window and hasn't been explicitly spent.
     */
    public function isOutstanding(): bool
    {
        return $this->used_at === null && $this->expires_at->isFuture();
    }
}
