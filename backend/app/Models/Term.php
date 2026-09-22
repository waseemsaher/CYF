<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class Term extends Model
{
    use HasTranslations;

    protected $table = 'terms';

    public $timestamps = false;

    /** @var list<string> */
    protected array $translatable = ['name'];

    protected $fillable = [
        'name',
        'starts_at',
        'ends_at',
        'is_current',
        'sort_order',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'name' => 'array',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'is_current' => 'boolean',
            'sort_order' => 'integer',
        ];
    }
}
