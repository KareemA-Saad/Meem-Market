<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Requests\Admin\BulkContactMessageRequest;
use App\Http\Requests\Admin\UpdateContactMessageRequest;
use App\Http\Resources\V1\Admin\ContactMessageResource;
use App\Models\ContactMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use OpenApi\Attributes as OA;

#[OA\Tag(name: "Admin Contact Messages", description: "Contact message moderation and inbox operations")]
class ContactMessageAdminController extends ApiController
{
    #[OA\Get(
        path: "/api/v1/admin/contact-messages",
        operationId: "adminListContactMessages",
        summary: "List contact messages",
        tags: ["Admin Contact Messages"],
        security: [["sanctum" => []]],
        parameters: [
            new OA\Parameter(name: "is_read", in: "query", required: false, schema: new OA\Schema(type: "boolean"), example: false),
            new OA\Parameter(name: "email", in: "query", required: false, schema: new OA\Schema(type: "string"), example: "john@example.com"),
            new OA\Parameter(name: "search", in: "query", required: false, schema: new OA\Schema(type: "string"), example: "pricing"),
            new OA\Parameter(name: "date_from", in: "query", required: false, schema: new OA\Schema(type: "string", format: "date"), example: "2026-01-01"),
            new OA\Parameter(name: "date_to", in: "query", required: false, schema: new OA\Schema(type: "string", format: "date"), example: "2026-12-31"),
            new OA\Parameter(name: "sort_by", in: "query", required: false, schema: new OA\Schema(type: "string", enum: ["id", "name", "email", "created_at", "updated_at"]), example: "created_at"),
            new OA\Parameter(name: "sort_dir", in: "query", required: false, schema: new OA\Schema(type: "string", enum: ["asc", "desc"]), example: "desc"),
            new OA\Parameter(name: "per_page", in: "query", required: false, schema: new OA\Schema(type: "integer"), example: 20),
            new OA\Parameter(name: "page", in: "query", required: false, schema: new OA\Schema(type: "integer"), example: 1),
        ],
        responses: [
            new OA\Response(response: 200, description: "Success"),
            new OA\Response(response: 401, description: "Unauthenticated", content: new OA\JsonContent(ref: "#/components/schemas/AdminErrorResponse")),
            new OA\Response(response: 403, description: "Forbidden", content: new OA\JsonContent(ref: "#/components/schemas/AdminErrorResponse")),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $query = ContactMessage::query();

        if ($request->filled('is_read')) {
            $query->where('is_read', $request->boolean('is_read'));
        }

        if ($request->filled('email')) {
            $query->where('email', 'like', '%' . (string) $request->query('email') . '%');
        }

        if ($request->filled('search')) {
            $search = (string) $request->query('search');
            $query->where(function ($q) use ($search): void {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('subject', 'like', "%{$search}%")
                    ->orWhere('message', 'like', "%{$search}%");
            });
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', (string) $request->query('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', (string) $request->query('date_to'));
        }

        $allowedSorts = ['id', 'name', 'email', 'created_at', 'updated_at'];
        $sortBy = in_array($request->query('sort_by'), $allowedSorts, true)
            ? (string) $request->query('sort_by')
            : 'created_at';
        $sortDir = strtolower((string) $request->query('sort_dir', 'desc')) === 'asc' ? 'asc' : 'desc';
        $query->orderBy($sortBy, $sortDir)->orderBy('id', 'desc');

        $perPage = min(max((int) $request->query('per_page', 20), 1), 100);

        return $this->paginated($query->paginate($perPage), ContactMessageResource::class);
    }

    #[OA\Get(
        path: "/api/v1/admin/contact-messages/{id}",
        operationId: "adminShowContactMessage",
        summary: "Show contact message",
        tags: ["Admin Contact Messages"],
        security: [["sanctum" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"), example: 1),
        ],
        responses: [
            new OA\Response(response: 200, description: "Success"),
            new OA\Response(response: 404, description: "Not found", content: new OA\JsonContent(ref: "#/components/schemas/AdminErrorResponse")),
            new OA\Response(response: 401, description: "Unauthenticated", content: new OA\JsonContent(ref: "#/components/schemas/AdminErrorResponse")),
            new OA\Response(response: 403, description: "Forbidden", content: new OA\JsonContent(ref: "#/components/schemas/AdminErrorResponse")),
        ]
    )]
    public function show(int $id): JsonResponse
    {
        $message = ContactMessage::query()->find($id);

        if (!$message) {
            return $this->error('Contact message not found.', 404, null, 'CONTACT_MESSAGE_NOT_FOUND');
        }

        return $this->success(new ContactMessageResource($message));
    }

    #[OA\Put(
        path: "/api/v1/admin/contact-messages/{id}",
        operationId: "adminUpdateContactMessage",
        summary: "Update contact message read status",
        tags: ["Admin Contact Messages"],
        security: [["sanctum" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"), example: 1),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["is_read"],
                properties: [
                    new OA\Property(property: "is_read", type: "boolean", example: true),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Success"),
            new OA\Response(response: 404, description: "Not found", content: new OA\JsonContent(ref: "#/components/schemas/AdminErrorResponse")),
            new OA\Response(response: 422, description: "Validation error", content: new OA\JsonContent(ref: "#/components/schemas/AdminValidationErrorResponse")),
            new OA\Response(response: 401, description: "Unauthenticated", content: new OA\JsonContent(ref: "#/components/schemas/AdminErrorResponse")),
            new OA\Response(response: 403, description: "Forbidden", content: new OA\JsonContent(ref: "#/components/schemas/AdminErrorResponse")),
        ]
    )]
    public function update(UpdateContactMessageRequest $request, int $id): JsonResponse
    {
        $message = ContactMessage::query()->find($id);

        if (!$message) {
            return $this->error('Contact message not found.', 404, null, 'CONTACT_MESSAGE_NOT_FOUND');
        }

        $isRead = (bool) $request->validated()['is_read'];
        $message->update([
            'is_read' => $isRead,
            'read_at' => $isRead ? now() : null,
        ]);

        $message->refresh();

        Log::info('admin.contact_messages.updated', [
            'actor_id' => $request->user()?->id,
            'resource_id' => $message->id,
            'is_read' => $isRead,
        ]);

        return $this->success(new ContactMessageResource($message));
    }

    #[OA\Delete(
        path: "/api/v1/admin/contact-messages/{id}",
        operationId: "adminDeleteContactMessage",
        summary: "Delete contact message",
        tags: ["Admin Contact Messages"],
        security: [["sanctum" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"), example: 1),
        ],
        responses: [
            new OA\Response(response: 200, description: "Deleted"),
            new OA\Response(response: 404, description: "Not found", content: new OA\JsonContent(ref: "#/components/schemas/AdminErrorResponse")),
            new OA\Response(response: 401, description: "Unauthenticated", content: new OA\JsonContent(ref: "#/components/schemas/AdminErrorResponse")),
            new OA\Response(response: 403, description: "Forbidden", content: new OA\JsonContent(ref: "#/components/schemas/AdminErrorResponse")),
        ]
    )]
    public function destroy(Request $request, int $id): JsonResponse
    {
        $message = ContactMessage::query()->find($id);

        if (!$message) {
            return $this->error('Contact message not found.', 404, null, 'CONTACT_MESSAGE_NOT_FOUND');
        }

        $message->delete();

        Log::info('admin.contact_messages.deleted', [
            'actor_id' => $request->user()?->id,
            'resource_id' => $id,
        ]);

        return $this->success(['message' => 'Contact message deleted successfully.']);
    }

    #[OA\Post(
        path: "/api/v1/admin/contact-messages/bulk",
        operationId: "adminBulkContactMessages",
        summary: "Bulk action for contact messages",
        tags: ["Admin Contact Messages"],
        security: [["sanctum" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["action", "ids"],
                properties: [
                    new OA\Property(property: "action", type: "string", enum: ["delete", "mark_read", "mark_unread"], example: "mark_read"),
                    new OA\Property(property: "ids", type: "array", items: new OA\Items(type: "integer"), example: [1, 2, 3]),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Bulk action completed"),
            new OA\Response(response: 422, description: "Validation error", content: new OA\JsonContent(ref: "#/components/schemas/AdminValidationErrorResponse")),
            new OA\Response(response: 401, description: "Unauthenticated", content: new OA\JsonContent(ref: "#/components/schemas/AdminErrorResponse")),
            new OA\Response(response: 403, description: "Forbidden", content: new OA\JsonContent(ref: "#/components/schemas/AdminErrorResponse")),
        ]
    )]
    public function bulk(BulkContactMessageRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $action = $validated['action'];
        $ids = $validated['ids'];

        $messages = ContactMessage::query()->whereIn('id', $ids)->get();

        if ($messages->isEmpty()) {
            return $this->error('No valid contact messages found.', 422, null, 'NO_VALID_RESOURCES');
        }

        $affected = 0;

        DB::transaction(function () use ($action, $messages, &$affected): void {
            if ($action === 'delete') {
                foreach ($messages as $message) {
                    $message->delete();
                    $affected++;
                }

                return;
            }

            if ($action === 'mark_read') {
                $affected = ContactMessage::query()
                    ->whereIn('id', $messages->pluck('id'))
                    ->update(['is_read' => true, 'read_at' => now()]);

                return;
            }

            $affected = ContactMessage::query()
                ->whereIn('id', $messages->pluck('id'))
                ->update(['is_read' => false, 'read_at' => null]);
        });

        Log::info('admin.contact_messages.bulk', [
            'actor_id' => $request->user()?->id,
            'action' => $action,
            'ids' => $messages->pluck('id')->values()->all(),
            'affected' => $affected,
        ]);

        return $this->success([
            'message' => "{$affected} contact message(s) processed successfully.",
            'affected' => $affected,
        ]);
    }
}
