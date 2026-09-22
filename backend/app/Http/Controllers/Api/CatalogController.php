<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;

class CatalogController extends Controller
{
    public function index(): JsonResponse
    {
        /** @var Collection<int, Course> $courses */
        $courses = Course::query()
            ->where('status', 'published')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $payload = [];

        foreach ($courses as $course) {
            $payload[] = [
                'id' => $course->getKey(),
                'slug' => $course->getAttribute('slug'),
                'title' => $course->getAttribute('title'),
                'description' => $course->getAttribute('description'),
                'price_cents' => (int) $course->getAttribute('price_cents'),
                'status' => $course->getAttribute('status'),
                'sort_order' => (int) $course->getAttribute('sort_order'),
            ];
        }

        return response()->json([
            'data' => $payload,
        ]);
    }
}
