<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use OpenApi\Attributes as OA;
use App\Http\Resources\V1\CountryResource;
use App\Models\Country;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CountryController extends Controller
{
    #[OA\Get(
        path: "/api/v1/countries",
        operationId: "getCountries",
        tags: ["Countries"],
        summary: "Get list of countries",
        description: "Returns active countries with their branches",
        responses: [
            new OA\Response(response: 200, description: "Successful operation"),
        ]
    )]
    public function index(): JsonResponse
    {
        $countries = Country::with([
            'branches' => function ($query) {
                $query->where('is_active', true)->orderBy('sort_order');
            }
        ])
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        return response()->json([
            'data' => CountryResource::collection($countries),
        ]);
    }
}
