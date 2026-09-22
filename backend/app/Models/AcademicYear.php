<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class AcademicYear extends Model
{
    use HasTranslations;

    protected $table = 'academic_years';

    public $timestamps = false;

    /** @var list<string> */
    protected array $translatable = ['name'];

    protected $fillable = [
        'name',
        'sort_order',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'name' => 'array',
            'sort_order' => 'integer',
        ];
    }
}
