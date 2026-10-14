<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\Translatable\HasTranslations;

/**
 * @property Pivot|null $pivot
 */
class Course extends Model
{
    use HasTranslations;
    use SoftDeletes;

    protected static function booted(): void
    {
        static::deleting(function (Course $course): void {
            if (! $course->isForceDeleting() && ! str_contains((string) $course->slug, '-deleted-')) {
                $suffix = '-deleted-'.time().'-'.Str::lower(Str::random(6));
                $baseSlug = Str::limit((string) $course->slug, 255 - strlen($suffix), '');
                $course->slug = $baseSlug.$suffix;
                $course->saveQuietly();
            }
        });
    }

    protected $table = 'courses';

    /** @var list<string> */
    protected array $translatable = ['title', 'description'];

    protected $fillable = [
        'slug',
        'title',
        'description',
        'cover_image_path',
        'price_cents',
        'status',
        'is_general',
        'telegram_channel_id',
        'telegram_group_id',
        'telegram_invite_link',
        'teacher_share_percent',
        'sort_order',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'title' => 'array',
            'description' => 'array',
            'price_cents' => 'integer',
            'is_general' => 'boolean',
            'teacher_share_percent' => 'integer',
            'sort_order' => 'integer',
            'deleted_at' => 'datetime',
        ];
    }

    public function setTelegramChannelIdAttribute(mixed $value): void
    {
        $this->attributes['telegram_channel_id'] = self::normalizeTelegramChatId($value);
    }

    public function setTelegramGroupIdAttribute(mixed $value): void
    {
        $this->attributes['telegram_group_id'] = self::normalizeTelegramChatId($value);
    }

    public static function normalizeTelegramChatId(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $str = trim((string) $value);
        if ($str === '') {
            return null;
        }

        // Handle raw channel/group IDs (e.g. -4331858990 from Telegram Web URLs or 4331858990)
        if (preg_match('/^-?(\d{10,})$/', $str, $matches) && ! str_starts_with($str, '-100')) {
            return '-100'.$matches[1];
        }

        return $str;
    }

    /**
     * @return HasMany<CourseAudience, $this>
     */
    public function audiences(): HasMany
    {
        return $this->hasMany(CourseAudience::class);
    }

    /**
     * @return BelongsToMany<Discount, $this>
     */
    public function discounts(): BelongsToMany
    {
        return $this->belongsToMany(Discount::class, 'discount_course');
    }

    /**
     * @return BelongsToMany<Coupon, $this>
     */
    public function coupons(): BelongsToMany
    {
        return $this->belongsToMany(Coupon::class, 'coupon_course');
    }

    /**
     * @return HasMany<CourseSection, $this>
     */
    public function sections(): HasMany
    {
        return $this->hasMany(CourseSection::class)->orderBy('position');
    }

    /**
     * @return HasMany<CourseItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(CourseItem::class)->orderBy('position');
    }

    /**
     * @return HasMany<Enrollment, $this>
     */
    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    /**
     * @return HasMany<Quiz, $this>
     */
    public function quizzes(): HasMany
    {
        return $this->hasMany(Quiz::class);
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function teachers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'course_teacher', 'course_id', 'teacher_id')
            ->withPivot('teacher_share_percent')
            ->withTimestamps();
    }
}
