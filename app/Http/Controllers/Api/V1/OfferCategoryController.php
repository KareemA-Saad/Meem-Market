<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\OfferCategoryResource;
use App\Models\OfferCategory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use OpenApi\Attributes as OA;

class OfferCategoryController extends Controller
{
    #[OA\Get(
        path: "/api/v1/offer-categories",
        operationId: "getOfferCategories",
        tags: ["Offer Categories"],
        summary: "Get list of offer categories",
        description: "Returns active offer categories, optionally filtered by branch",
        parameters: [
            new OA\Parameter(name: "branch_slug", description: "Branch Slug", required: false, in: "query", schema: new OA\Schema(type: "string")),
        ],
        responses: [
            new OA\Response(response: 200, description: "Successful operation"),
        ]
    )]
    public function index(Request $request): AnonymousResourceCollection
    {
        $categories = OfferCategory::active()
            ->when($request->query('branch_slug'), function (Builder $query, $branchSlug) {
                $query->whereHas('branch', function (Builder $q) use ($branchSlug) {
                    $q->where('slug', $branchSlug);
                });
            })
            ->ordered()
            ->get();

        return OfferCategoryResource::collection($categories);
    }
}
