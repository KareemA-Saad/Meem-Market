<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Resources\V1\Admin\CompetitiveFeatureResource as AdminCompetitiveFeatureResource;
use App\Http\Resources\V1\PartnerResource as PublicPartnerResource;
use App\Http\Resources\V1\SectionResource as PublicSectionResource;
use App\Http\Resources\V1\SliderResource as PublicSliderResource;
use App\Models\CompetitiveFeature;
use App\Models\Partner;
use App\Models\Section;
use App\Models\Setting;
use App\Models\Slider;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

#[OA\Tag(name: "Admin Homepage", description: "Homepage overview and preview")]
class HomepageAdminController extends ApiController
{
    #[OA\Get(
        path: "/api/v1/admin/homepage/overview",
        operationId: "adminHomepageOverview",
        summary: "Get homepage overview",
        tags: ["Admin Homepage"],
        security: [["sanctum" => []]],
        responses: [new OA\Response(response: 200, description: "Success")]
    )]
    public function overview(): JsonResponse
    {
        $summary = [
            'sliders' => [
                'total' => Slider::count(),
                'active' => Slider::where('is_active', true)->count(),
            ],
            'sections' => [
                'total' => Section::count(),
                'active' => Section::where('is_active', true)->count(),
            ],
            'partners' => [
                'total' => Partner::count(),
                'active' => Partner::where('is_active', true)->count(),
            ],
            'features' => [
                'total' => CompetitiveFeature::count(),
                'active' => CompetitiveFeature::where('is_active', true)->count(),
            ],
        ];

        $publicationChecklist = [
            'has_active_slider' => $summary['sliders']['active'] > 0,
            'has_active_sections' => $summary['sections']['active'] > 0,
            'has_active_partners' => $summary['partners']['active'] > 0,
            'has_active_features' => $summary['features']['active'] > 0,
            'has_general_settings' => Setting::query()->where('group', 'general')->exists(),
            'has_contact_settings' => Setting::query()->where('group', 'contact')->exists(),
        ];

        return $this->success([
            'summary' => $summary,
            'publication_checklist' => $publicationChecklist,
            'is_publish_ready' => !in_array(false, $publicationChecklist, true),
            'generated_at' => now()->toIso8601String(),
        ]);
    }

    #[OA\Get(
        path: "/api/v1/admin/homepage/preview",
        operationId: "adminHomepagePreview",
        summary: "Get homepage preview payload",
        tags: ["Admin Homepage"],
        security: [["sanctum" => []]],
        responses: [new OA\Response(response: 200, description: "Success")]
    )]
    public function preview(): JsonResponse
    {
        return $this->success([
            'homepage' => [
                'sliders' => PublicSliderResource::collection(Slider::where('is_active', true)->orderBy('sort_order')->get()),
                'sections' => PublicSectionResource::collection(Section::where('is_active', true)->orderBy('sort_order')->get()),
                'partners' => PublicPartnerResource::collection(Partner::where('is_active', true)->orderBy('sort_order')->get()),
                'features' => AdminCompetitiveFeatureResource::collection(
                    CompetitiveFeature::where('is_active', true)->orderBy('sort_order')->take(3)->get()
                ),
            ],
            'generated_at' => now()->toIso8601String(),
        ]);
    }
}
