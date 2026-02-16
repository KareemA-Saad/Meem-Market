<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use OpenApi\Attributes as OA;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    #[OA\Get(
        path: "/api/v1/settings/{group}",
        operationId: "getSettingsByGroup",
        tags: ["Settings"],
        summary: "Get settings by group",
        description: "Returns settings key-value pairs for a specific group (e.g., contact, social, general)",
        parameters: [
            new OA\Parameter(name: "group", description: "Setting Group", required: true, in: "path", schema: new OA\Schema(type: "string")),
        ],
        responses: [
            new OA\Response(response: 200, description: "Successful operation"),
            new OA\Response(response: 400, description: "Bad Request"),
        ]
    )]
    public function show(string $group): JsonResponse
    {
        $settings = Setting::byGroup($group)->get();

        return response()->json([
            'data' => $settings->pluck('value', 'key'),
        ]);
    }
}
