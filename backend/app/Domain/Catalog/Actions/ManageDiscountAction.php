<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Actions;

use App\Models\Discount;
use App\Models\User;

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

        $causer = auth()->user();
        $name = (string) ($discount->getTranslation('name', 'ar') ?: $discount->getTranslation('name', 'en'));

        $activity = activity('discounts')->performedOn($discount);
        if ($causer instanceof User) {
            $activity->causedBy($causer);
        }
        $activity->log("Created discount {$name}");

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

        $causer = auth()->user();
        $name = (string) ($discount->getTranslation('name', 'ar') ?: $discount->getTranslation('name', 'en'));

        $activity = activity('discounts')->performedOn($discount);
        if ($causer instanceof User) {
            $activity->causedBy($causer);
        }
        $activity->log("Updated discount {$name}");

        return $discount->refresh()->load('courses');
    }

    public function delete(Discount $discount): void
    {
        $causer = auth()->user();
        $name = (string) ($discount->getTranslation('name', 'ar') ?: $discount->getTranslation('name', 'en'));

        $activity = activity('discounts')->performedOn($discount);
        if ($causer instanceof User) {
            $activity->causedBy($causer);
        }
        $activity->log("Deleted discount {$name}");

        $discount->courses()->detach();
        $discount->delete();
    }
}
