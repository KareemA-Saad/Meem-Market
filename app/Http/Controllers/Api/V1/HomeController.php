<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use OpenApi\Attributes as OA;
use App\Http\Resources\V1\CompetitiveFeatureResource;
use App\Http\Resources\V1\PartnerResource;
use App\Http\Resources\V1\SectionResource;
use App\Http\Resources\V1\SliderResource;
use App\Models\CompetitiveFeature;
use App\Models\Partner;
use App\Models\Section;
use App\Models\Slider;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    #[OA\Get(
        path: "/api/v1/home",
        operationId: "getHomeData",
        tags: ["Home"],
        summary: "Get home page data",
        description: "Returns sliders, sections, partners, and features",
        responses: [
            new OA\Response(response: 200, description: "Successful operation"),
            new OA\Response(response: 400, description: "Bad Request"),
        ]
    )]
    public function index(): JsonResponse
    {
        return response()->json([
            'data' => [
                'sliders' => SliderResource::collection(Slider::where('is_active', true)->orderBy('sort_order')->get()),
                'sections' => SectionResource::collection(Section::where('is_active', true)->orderBy('sort_order')->get()),
                'partners' => PartnerResource::collection(Partner::where('is_active', true)->orderBy('sort_order')->get()),
                'features' => CompetitiveFeatureResource::collection(CompetitiveFeature::orderBy('sort_order')->take(3)->get()),
            ],
        ]);
    }
}
