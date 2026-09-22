<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;

class CourseSection extends Model
{
    use HasFactory, HasTranslations;

    protected $table = 'course_sections';

    /** @var list<string> */
    protected array $translatable = ['title'];

    protected $fillable = [
        'course_id',
        'title',
        'position',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'title' => 'array',
            'position' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Course, $this>
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * @return HasMany<CourseItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(CourseItem::class, 'section_id')->orderBy('position');
    }
}
