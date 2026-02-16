<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use OpenApi\Attributes as OA;
use App\Http\Requests\StoreContactRequest;
use App\Models\ContactMessage;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    #[OA\Get(
        path: "/api/v1/contact",
        operationId: "getContactInfo",
        tags: ["Contact"],
        summary: "Get contact information",
        description: "Returns contact and social settings",
        responses: [
            new OA\Response(response: 200, description: "Successful operation"),
        ]
    )]
    public function index(): JsonResponse
    {
        $contactSettings = Setting::byGroup('contact')->get();
        $socialSettings = Setting::byGroup('social')->get();

        return response()->json([
            'data' => [
                'contact' => $contactSettings->pluck('value', 'key'),
                'social' => $socialSettings->pluck('value', 'key'),
            ],
        ]);
    }

    #[OA\Post(
        path: "/api/v1/contact",
        operationId: "storeContactMessage",
        tags: ["Contact"],
        summary: "Submit a contact message",
        description: "Stores a new contact message",
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["name", "email", "message"],
                properties: [
                    new OA\Property(property: "name", type: "string", example: "John Doe"),
                    new OA\Property(property: "email", type: "string", format: "email", example: "john@example.com"),
                    new OA\Property(property: "phone", type: "string", example: "0500000000"),
                    new OA\Property(property: "subject", type: "string", example: "Inquiry"),
                    new OA\Property(property: "message", type: "string", example: "Hello Meem Market"),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: "Message sent successfully"),
            new OA\Response(response: 422, description: "Validation Error"),
        ]
    )]
    public function store(StoreContactRequest $request): JsonResponse
    {
        $message = ContactMessage::create($request->validated());

        return response()->json([
            'message' => 'Your message has been sent successfully.',
            'data' => $message,
        ], 201);
    }
}
