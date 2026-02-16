<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use OpenApi\Attributes as OA;
use App\Http\Resources\V1\CareerResource;
use App\Models\Career;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CareerController extends Controller
{
    #[OA\Get(
        path: "/api/v1/careers",
        operationId: "getCareers",
        tags: ["Careers"],
        summary: "Get list of careers",
        description: "Returns active career opportunities",
        responses: [
            new OA\Response(response: 200, description: "Successful operation"),
        ]
    )]
    public function index(): JsonResponse
    {
        $careers = Career::where('is_active', true)->get();

        return response()->json([
            'data' => CareerResource::collection($careers),
        ]);
    }

    #[OA\Get(
        path: "/api/v1/careers/{slug}",
        operationId: "getCareerBySlug",
        tags: ["Careers"],
        summary: "Get career details",
        description: "Returns details of a specific job",
        parameters: [
            new OA\Parameter(name: "slug", description: "Career Slug", required: true, in: "path", schema: new OA\Schema(type: "string")),
        ],
        responses: [
            new OA\Response(response: 200, description: "Successful operation"),
            new OA\Response(response: 404, description: "Resource Not Found"),
        ]
    )]
    public function show(string $slug): JsonResponse
    {
        $career = Career::where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        return response()->json([
            'data' => new CareerResource($career),
        ]);
    }
}
