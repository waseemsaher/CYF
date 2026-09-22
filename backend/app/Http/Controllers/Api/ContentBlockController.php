<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Domain\Settings\Services\ContentBlockService;
use App\Http\Controllers\Controller;
use App\Models\ContentBlock;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContentBlockController extends Controller
{
    /**
     * Public endpoint to view a content block (landing text, legal policy).
     */
    public function show(string $key, ContentBlockService $service): JsonResponse
    {
        $content = $service->get($key);

        if (! $content) {
            abort(404, 'Content block not found.');
        }

        return response()->json([
            'data' => [
                'key' => $key,
                'content' => $content,
            ],
        ]);
    }

    /**
     * Admin endpoint to list all content blocks.
     */
    public function index(): JsonResponse
    {
        $this->authorizeAdmin();

        $blocks = ContentBlock::query()->get()->map(fn (ContentBlock $b) => [
            'id' => $b->id,
            'key' => $b->key,
            'group' => $b->group,
            'content' => $b->getTranslations('content'),
        ]);

        return response()->json(['data' => $blocks]);
    }

    /**
     * Admin endpoint to update a content block.
     */
    public function update(Request $request, string $key, ContentBlockService $service): JsonResponse
    {
        $this->authorizeAdmin();

        $validated = $request->validate([
            'content' => ['required', 'array'],
            'content.ar' => ['required', 'string'],
            'content.en' => ['required', 'string'],
            'group' => ['nullable', 'string'],
        ]);

        $block = $service->set($key, $validated['content'], $validated['group'] ?? null);

        activity('content')
            ->causedBy($request->user())
            ->performedOn($block)
            ->withProperties(['key' => $key])
            ->log('content_block_updated');

        return response()->json(['data' => $block]);
    }

    private function authorizeAdmin(): void
    {
        /** @var User $user */
        $user = request()->user();
        if (! $user->hasRole(['superadmin', 'admin']) && ! $user->can('content.manage')) {
            abort(403);
        }
    }
}
