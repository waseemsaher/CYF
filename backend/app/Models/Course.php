<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Translatable\HasTranslations;

class Course extends Model
{
    use HasTranslations;
    use SoftDeletes;

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
        'telegram_chat_id',
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
            'teacher_share_percent' => 'integer',
            'sort_order' => 'integer',
            'deleted_at' => 'datetime',
        ];
    }

    /**
     * @return HasMany<CourseAudience, $this>
     */
    public function audiences(): HasMany
    {
        return $this->hasMany(CourseAudience::class);
    }
}
