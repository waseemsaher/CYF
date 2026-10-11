<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Domain\Settings\Services\CtaBlockService;
use App\Http\Controllers\Controller;
use App\Models\CtaBlock;
use App\Models\User;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CtaBlockController extends Controller
{
    /**
     * Public endpoint to fetch all CTA blocks (cached with short TTL).
     */
    public function publicIndex(CtaBlockService $service): JsonResponse
    {
        return response()->json([
            'data' => $service->getPublicMap(),
        ])->header('Cache-Control', 'public, max-age=60, stale-while-revalidate=300');
    }

    /**
     * Admin endpoint to list all CTA slots with live status.
     */
    public function adminIndex(CtaBlockService $service): JsonResponse
    {
        $this->authorizeAdminRoleOnly();

        return response()->json([
            'data' => $service->listForAdmin(),
        ]);
    }

    /**
     * Admin endpoint to update a CTA slot.
     */
    public function update(Request $request, string $key, CtaBlockService $service): JsonResponse
    {
        $this->authorizeAdminRoleOnly();

        if (! preg_match('/^[a-z0-9_]{2,64}$/', $key)) {
            abort(422, 'Invalid CTA slot key.');
        }

        /** @var array{
         *   label_ar: string,
         *   headline_ar?: string|null,
         *   subtext_ar?: string|null,
         *   url: string,
         *   style: string,
         *   is_active: bool,
         *   starts_at?: string|null,
         *   ends_at?: string|null,
         *   open_in_new_tab: bool
         * } $validated */
        $validated = $request->validate([
            'label_ar' => [
                'required',
                'string',
                'max:120',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (CtaBlockService::sanitizeText(is_string($value) ? $value : '') === null) {
                        $fail('نص الزر مطلوب ولا يمكن أن يكون فارغاً.');
                    }
                },
            ],
            'headline_ar' => ['nullable', 'string', 'max:180'],
            'subtext_ar' => ['nullable', 'string', 'max:350'],
            'url' => [
                'required',
                'string',
                'max:500',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (! is_string($value) || ! CtaBlockService::isValidUrl($value)) {
                        $fail('الرابط يجب أن يبدأ بـ https:// أو يكون مساراً داخلياً يبدأ بـ / أو #.');
                    }
                },
            ],
            'style' => ['required', 'string', Rule::in(['primary', 'secondary', 'outline'])],
            'is_active' => ['required', 'boolean'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => [
                'nullable',
                'date',
                function (string $attribute, mixed $value, Closure $fail) use ($request): void {
                    $startsAt = $request->input('starts_at');
                    if (is_string($value) && $value !== '' && is_string($startsAt) && $startsAt !== '') {
                        if (strtotime($value) <= strtotime($startsAt)) {
                            $fail('تاريخ الانتهاء يجب أن يكون بعد تاريخ البدء.');
                        }
                    }
                },
            ],
            'open_in_new_tab' => ['required', 'boolean'],
        ]);

        /** @var User $user */
        $user = $request->user();

        $block = $service->upsert($key, $validated, (int) $user->getKey());

        activity('cta')
            ->causedBy($user)
            ->performedOn($block)
            ->withProperties(['key' => $key, 'is_active' => $block->is_active])
            ->log('cta_block_updated');

        $serialized = $service->serialize($block);
        $serialized['status'] = $block->computedStatus();
        $serialized['is_visible'] = $block->isCurrentlyVisible();

        return response()->json([
            'data' => $serialized,
        ]);
    }

    /**
     * Admin endpoint to toggle active/inactive in one click.
     */
    public function toggle(Request $request, string $key, CtaBlockService $service): JsonResponse
    {
        $this->authorizeAdminRoleOnly();

        /** @var CtaBlock|null $block */
        $block = CtaBlock::query()->where('key', $key)->first();
        if (! $block) {
            abort(404, 'CTA slot not found.');
        }

        /** @var User $user */
        $user = $request->user();

        $updated = $service->toggleActive($block, (int) $user->getKey());

        activity('cta')
            ->causedBy($user)
            ->performedOn($updated)
            ->withProperties(['key' => $key, 'is_active' => $updated->is_active])
            ->log('cta_block_toggled');

        $serialized = $service->serialize($updated);
        $serialized['status'] = $updated->computedStatus();
        $serialized['is_visible'] = $updated->isCurrentlyVisible();

        return response()->json([
            'data' => $serialized,
        ]);
    }

    private function authorizeAdminRoleOnly(): void
    {
        /** @var User|null $user */
        $user = request()->user();
        if (! $user || ! $user->hasRole(['superadmin', 'admin'])) {
            abort(403, 'This action is restricted to administrators.');
        }
    }
}
