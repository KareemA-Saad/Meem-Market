<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use OpenApi\Attributes as OA;
use App\Http\Resources\V1\OfferResource;
use App\Models\Offer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Request;

class OfferController extends Controller
{
    #[OA\Get(
        path: "/api/v1/offers",
        operationId: "getOffers",
        tags: ["Offers"],
        summary: "Get list of offers",
        description: "Returns offers, filterable by branch_slug and category_slug",
        parameters: [
            new OA\Parameter(name: "branch_slug", description: "Branch Slug", required: false, in: "query", schema: new OA\Schema(type: "string")),
            new OA\Parameter(name: "category_slug", description: "Category Slug", required: false, in: "query", schema: new OA\Schema(type: "string")),
            new OA\Parameter(name: "page", description: "Page Number", required: false, in: "query", schema: new OA\Schema(type: "integer")),
        ],
        responses: [
            new OA\Response(response: 200, description: "Successful operation"),
            new OA\Response(response: 400, description: "Bad Request"),
        ]
    )]
    public function index(Request $request): AnonymousResourceCollection
    {
        $offers = Offer::with(['offerCategory.branch'])
            ->active()
            ->when($request->query('branch_slug'), function (Builder $query, $branchSlug) {
                $query->whereHas('offerCategory.branch', function (Builder $q) use ($branchSlug) {
                    $q->where('slug', $branchSlug);
                });
            })
            ->when($request->query('category_slug'), function (Builder $query, $categorySlug) {
                $query->whereHas('offerCategory', function (Builder $q) use ($categorySlug) {
                    $q->where('slug', $categorySlug);
                });
            })
            ->ordered()
            ->paginate(20);

        return OfferResource::collection($offers);
    }

    #[OA\Get(
        path: "/api/v1/offers/{id}",
        operationId: "getOfferById",
        tags: ["Offers"],
        summary: "Get a single offer by ID",
        description: "Returns a single active offer with its category and branch",
        parameters: [
            new OA\Parameter(name: "id", description: "Offer ID", required: true, in: "path", schema: new OA\Schema(type: "integer")),
        ],
        responses: [
            new OA\Response(response: 200, description: "Successful operation"),
            new OA\Response(response: 404, description: "Offer not found"),
        ]
    )]
    public function show(int $id): OfferResource|JsonResponse
    {
        $offer = Offer::with(['offerCategory.branch'])
            ->active()
            ->find($id);

        if (!$offer) {
            return response()->json(['message' => 'Offer not found'], 404);
        }

        return new OfferResource($offer);
    }
}
