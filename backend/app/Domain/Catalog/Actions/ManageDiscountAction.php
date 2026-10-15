<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Actions;

use App\Models\Discount;

class ManageDiscountAction
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): Discount
    {
        $courseIds = $attributes['course_ids'] ?? null;
        unset($attributes['course_ids']);

        /** @var Discount $discount */
        $discount = Discount::query()->create($attributes);

        if (($discount->getAttribute('scope') === 'courses') && is_array($courseIds)) {
            $discount->courses()->sync($courseIds);
        }

        return $discount->load('courses');
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(Discount $discount, array $attributes): Discount
    {
        $hasCourseIds = array_key_exists('course_ids', $attributes);
        $courseIds = $attributes['course_ids'] ?? [];
        unset($attributes['course_ids']);

        $discount->fill($attributes);
        $discount->save();

        if ($discount->getAttribute('scope') === 'all') {
            $discount->courses()->detach();
        } elseif ($hasCourseIds && is_array($courseIds)) {
            $discount->courses()->sync($courseIds);
        }

        return $discount->refresh()->load('courses');
    }

    public function delete(Discount $discount): void
    {
        $discount->courses()->detach();
        $discount->delete();
    }
}
