<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use OpenApi\Attributes as OA;
use App\Http\Resources\V1\AboutSectionResource;
use App\Http\Resources\V1\CompetitiveFeatureResource;
use App\Models\AboutSection;
use App\Models\CompetitiveFeature;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AboutController extends Controller
{
    #[OA\Get(
        path: "/api/v1/about",
        operationId: "getAboutData",
        tags: ["About"],
        summary: "Get about page data",
        description: "Returns about sections and competitive features",
        responses: [
            new OA\Response(response: 200, description: "Successful operation"),
        ]
    )]
    public function index(): JsonResponse
    {
        return response()->json([
            'data' => [
                'sections' => AboutSectionResource::collection(AboutSection::orderBy('sort_order')->get()),
                'features' => CompetitiveFeatureResource::collection(CompetitiveFeature::orderBy('sort_order')->get()),
            ],
        ]);
    }
}
