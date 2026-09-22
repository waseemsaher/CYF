<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Actions;

use App\Models\Course;
use App\Models\Discount;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class CalculateCoursePrice
{
    /**
     * @return array{list_price_cents: int, discount_cents: int, amount_due_cents: int, discount_id: int|null}
     */
    public function handle(Course $course, ?CarbonImmutable $at = null): array
    {
        $at ??= CarbonImmutable::now();
        $listPriceCents = (int) $course->getAttribute('price_cents');

        /** @var Collection<int, Discount> $discounts */
        $discounts = Discount::query()
            ->where('is_active', true)
            ->whereDate('starts_at', '<=', $at)
            ->whereDate('ends_at', '>=', $at)
            ->where(function (Builder $query) use ($course): void {
                $query
                    ->where('scope', 'all')
                    ->orWhere(function (Builder $scopedQuery) use ($course): void {
                        $scopedQuery
                            ->where('scope', 'courses')
                            ->whereHas('courses', function (Builder $courseQuery) use ($course): void {
                                $courseQuery->whereKey($course->getKey());
                            });
                    });
            })
            ->get();

        $bestDiscountCents = 0;
        $bestDiscountId = null;
        $amountDueCents = $listPriceCents;

        foreach ($discounts as $discount) {
            $discountCents = $this->discountCents($listPriceCents, $discount);
            $candidateAmountCents = max(0, $listPriceCents - $discountCents);

            if ($candidateAmountCents < $amountDueCents) {
                $amountDueCents = $candidateAmountCents;
                $bestDiscountCents = $listPriceCents - $candidateAmountCents;
                $bestDiscountId = (int) $discount->getKey();
            }
        }

        return [
            'list_price_cents' => $listPriceCents,
            'discount_cents' => $bestDiscountCents,
            'amount_due_cents' => $amountDueCents,
            'discount_id' => $bestDiscountId,
        ];
    }

    private function discountCents(int $listPriceCents, Discount $discount): int
    {
        $value = (int) $discount->getAttribute('value');

        return $discount->getAttribute('type') === 'percent'
            ? intdiv($listPriceCents * $value, 100)
            : $value;
    }
}
