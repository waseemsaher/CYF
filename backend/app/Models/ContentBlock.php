<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class ContentBlock extends Model
{
    use HasFactory, HasTranslations;

    protected $table = 'content_blocks';

    /** @var list<string> */
    protected array $translatable = ['content'];

    protected $fillable = [
        'key',
        'group',
        'content',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'content' => 'array',
        ];
    }
}
