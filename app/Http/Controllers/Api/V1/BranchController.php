<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use OpenApi\Attributes as OA;
use App\Http\Resources\V1\BranchResource;
use App\Models\Branch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BranchController extends Controller
{
    #[OA\Get(
        path: "/api/v1/branches",
        operationId: "getBranches",
        tags: ["Branches"],
        summary: "Get list of branches",
        description: "Returns all branches, filterable by country_id",
        parameters: [
            new OA\Parameter(name: "country_id", description: "Country ID", required: false, in: "query", schema: new OA\Schema(type: "integer")),
        ],
        responses: [
            new OA\Response(response: 200, description: "Successful operation"),
            new OA\Response(response: 400, description: "Bad Request"),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $branches = Branch::query()
            ->when($request->query('country_id'), function ($query, $countryId) {
                $query->where('country_id', $countryId);
            })
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        return response()->json([
            'data' => BranchResource::collection($branches),
        ]);
    }

    #[OA\Get(
        path: "/api/v1/branches/{slug}",
        operationId: "getBranchBySlug",
        tags: ["Branches"],
        summary: "Get branch information",
        description: "Returns branch data and offer categories",
        parameters: [
            new OA\Parameter(name: "slug", description: "Branch Slug", required: true, in: "path", schema: new OA\Schema(type: "string")),
        ],
        responses: [
            new OA\Response(response: 200, description: "Successful operation"),
            new OA\Response(response: 404, description: "Resource Not Found"),
        ]
    )]
    public function show(string $slug): JsonResponse
    {
        $branch = Branch::with([
            'offerCategories' => function ($query) {
                $query->active()
                    ->ordered()
                    ->with([
                        'offers' => function ($q) {
                            $q->active()->ordered();
                        }
                    ])
                    ->withCount('offers');
            }
        ])
            ->where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        return response()->json([
            'data' => new BranchResource($branch),
        ]);
    }
}
