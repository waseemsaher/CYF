<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Domain\Catalog\Actions\ManageDiscountAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDiscountRequest;
use App\Http\Requests\UpdateDiscountRequest;
use App\Models\Course;
use App\Models\Discount;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class AdminDiscountController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', Discount::class);

        $perPage = (int) $request->query('per_page', 15);
        $perPage = max(1, min($perPage, 100));

        $discounts = Discount::query()
            ->with('courses')
            ->orderByDesc('id')
            ->paginate($perPage);

        return response()->json([
            'data' => $discounts->getCollection()->map(fn (Discount $discount): array => $this->payload($discount))->all(),
            'meta' => [
                'current_page' => $discounts->currentPage(),
                'last_page' => $discounts->lastPage(),
                'per_page' => $discounts->perPage(),
                'total' => $discounts->total(),
            ],
        ]);
    }

    public function store(StoreDiscountRequest $request, ManageDiscountAction $manageDiscount): JsonResponse
    {
        Gate::authorize('manage', Discount::class);

        $discount = $manageDiscount->create($request->validated());

        return response()->json(['data' => $this->payload($discount)], 201);
    }

    public function update(UpdateDiscountRequest $request, Discount $discount, ManageDiscountAction $manageDiscount): JsonResponse
    {
        Gate::authorize('update', $discount);

        $discount = $manageDiscount->update($discount, $request->validated());

        return response()->json(['data' => $this->payload($discount)]);
    }

    public function destroy(Discount $discount, ManageDiscountAction $manageDiscount): JsonResponse
    {
        Gate::authorize('delete', $discount);

        $manageDiscount->delete($discount);

        return response()->json(null, 204);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(Discount $discount): array
    {
        /** @var Collection<int, Course> $courses */
        $courses = $discount->relationLoaded('courses')
            ? $discount->getRelation('courses')
            : $discount->courses()->get();

        $startsAt = $discount->getAttribute('starts_at');
        $endsAt = $discount->getAttribute('ends_at');

        return [
            'id' => $discount->getKey(),
            'name' => $discount->getTranslations('name'),
            'type' => $discount->getAttribute('type'),
            'value' => (int) $discount->getAttribute('value'),
            'scope' => $discount->getAttribute('scope'),
            'starts_at' => $startsAt ? Carbon::parse($startsAt)->format('Y-m-d') : null,
            'ends_at' => $endsAt ? Carbon::parse($endsAt)->format('Y-m-d') : null,
            'is_active' => (bool) $discount->getAttribute('is_active'),
            'courses' => $courses->map(fn (Course $course): array => [
                'id' => $course->getKey(),
                'slug' => $course->getAttribute('slug'),
                'title' => $course->getTranslations('title'),
            ])->values()->all(),
            'course_ids' => $courses->pluck('id')->all(),
            'created_at' => $discount->getAttribute('created_at')?->toISOString(),
            'updated_at' => $discount->getAttribute('updated_at')?->toISOString(),
        ];
    }
}

