<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Requests\Admin\StoreMenuItemRequest;
use App\Http\Requests\Admin\StoreMenuRequest;
use App\Http\Requests\Admin\UpdateMenuItemRequest;
use App\Http\Requests\Admin\UpdateMenuRequest;
use App\Http\Resources\V1\Admin\MenuItemResource;
use App\Http\Resources\V1\Admin\MenuResource;
use App\Models\Post;
use App\Models\Term;
use App\Models\TermRelationship;
use App\Models\TermTaxonomy;
use App\Services\OptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use OpenApi\Attributes as OA;

/**
 * Navigation Menu management API.
 *
 * Menus are stored as terms with taxonomy='nav_menu'.
 * Menu items are stored as posts with type='nav_menu_item', linked to the menu
 * via term_relationships. Item properties (URL, type, target) live in post_meta.
 */
#[OA\Tag(name: "Admin Menus", description: "Navigation menu and menu item CRUD")]
class MenuController extends ApiController
{
    public function __construct(
        private readonly OptionService $optionService,
    ) {}

    // ═══════════════════════════════════════════════════════════
    //  Menu CRUD
    // ═══════════════════════════════════════════════════════════

    #[OA\Get(path: "/api/v1/admin/menus", operationId: "listMenus", summary: "List all navigation menus", tags: ["Admin Menus"], security: [["sanctum" => []]], responses: [new OA\Response(response: 200, description: "Menu list")])]
    public function index(): JsonResponse
    {
        $menus = TermTaxonomy::with('term')
            ->where('taxonomy', 'nav_menu')
            ->get();

        return $this->success(MenuResource::collection($menus));
    }

    #[OA\Post(path: "/api/v1/admin/menus", operationId: "createMenu", summary: "Create a navigation menu", tags: ["Admin Menus"], security: [["sanctum" => []]], requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(required: ["name"], properties: [new OA\Property(property: "name", type: "string"), new OA\Property(property: "description", type: "string")])), responses: [new OA\Response(response: 201, description: "Created")])]
    public function store(StoreMenuRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $term = Term::create([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']),
            'term_group' => 0,
        ]);

        $termTaxonomy = TermTaxonomy::create([
            'term_id' => $term->id,
            'taxonomy' => 'nav_menu',
            'description' => $validated['description'] ?? '',
            'parent' => 0,
            'count' => 0,
        ]);

        $termTaxonomy->load('term');

        return $this->success(new MenuResource($termTaxonomy), 201);
    }

    #[OA\Get(path: "/api/v1/admin/menus/{id}", operationId: "showMenu", summary: "Get a menu with its items", tags: ["Admin Menus"], security: [["sanctum" => []]], parameters: [new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))], responses: [new OA\Response(response: 200, description: "Menu with items"), new OA\Response(response: 404, description: "Not found")])]
    public function show(int $id): JsonResponse
    {
        $menu = TermTaxonomy::with('term')
            ->where('taxonomy', 'nav_menu')
            ->find($id);

        if (!$menu) {
            return $this->error('Menu not found.', 404);
        }

        // Get menu items (nav_menu_item posts linked via term_relationships)
        $itemIds = TermRelationship::where('term_taxonomy_id', $menu->id)
            ->pluck('object_id');

        $items = Post::with('meta')
            ->where('type', 'nav_menu_item')
            ->whereIn('id', $itemIds)
            ->orderBy('menu_order')
            ->get();

        return $this->success([
            'menu' => new MenuResource($menu),
            'items' => MenuItemResource::collection($items),
        ]);
    }

    #[OA\Put(path: "/api/v1/admin/menus/{id}", operationId: "updateMenu", summary: "Update menu name/description", tags: ["Admin Menus"], security: [["sanctum" => []]], parameters: [new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))], requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(properties: [new OA\Property(property: "name", type: "string"), new OA\Property(property: "description", type: "string")])), responses: [new OA\Response(response: 200, description: "Updated"), new OA\Response(response: 404, description: "Not found")])]
    public function update(UpdateMenuRequest $request, int $id): JsonResponse
    {
        $menu = TermTaxonomy::with('term')
            ->where('taxonomy', 'nav_menu')
            ->find($id);

        if (!$menu) {
            return $this->error('Menu not found.', 404);
        }

        $validated = $request->validated();

        if (isset($validated['name'])) {
            $menu->term->update([
                'name' => $validated['name'],
                'slug' => Str::slug($validated['name']),
            ]);
        }

        if (isset($validated['description'])) {
            $menu->update(['description' => $validated['description']]);
        }

        $menu->refresh();
        $menu->load('term');

        return $this->success(new MenuResource($menu));
    }

    #[OA\Delete(path: "/api/v1/admin/menus/{id}", operationId: "deleteMenu", summary: "Delete a menu and its items", tags: ["Admin Menus"], security: [["sanctum" => []]], parameters: [new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))], responses: [new OA\Response(response: 200, description: "Deleted"), new OA\Response(response: 404, description: "Not found")])]
    public function destroy(int $id): JsonResponse
    {
        $menu = TermTaxonomy::where('taxonomy', 'nav_menu')->find($id);

        if (!$menu) {
            return $this->error('Menu not found.', 404);
        }

        // Delete menu items linked to this menu
        $itemIds = TermRelationship::where('term_taxonomy_id', $menu->id)
            ->pluck('object_id');

        foreach (Post::whereIn('id', $itemIds)->where('type', 'nav_menu_item')->get() as $item) {
            $item->meta()->delete();
            $item->delete();
        }

        // Delete relationships and menu term
        TermRelationship::where('term_taxonomy_id', $menu->id)->delete();
        $menu->delete();
        Term::where('id', $menu->term_id)
            ->whereDoesntHave('taxonomy')
            ->delete();

        return $this->success(['message' => 'Menu deleted.']);
    }

    // ═══════════════════════════════════════════════════════════
    //  Menu Items
    // ═══════════════════════════════════════════════════════════

    #[OA\Post(path: "/api/v1/admin/menus/{id}/items", operationId: "addMenuItem", summary: "Add an item to a menu", tags: ["Admin Menus"], security: [["sanctum" => []]], parameters: [new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))], requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: "#/components/schemas/AdminMenuItem")), responses: [new OA\Response(response: 201, description: "Item added"), new OA\Response(response: 404, description: "Menu not found")])]
    public function addItem(StoreMenuItemRequest $request, int $id): JsonResponse
    {
        $menu = TermTaxonomy::where('taxonomy', 'nav_menu')->find($id);

        if (!$menu) {
            return $this->error('Menu not found.', 404);
        }

        $validated = $request->validated();

        $item = Post::create([
            'author_id' => $request->user()->id,
            'post_date' => now(),
            'post_date_gmt' => now()->utc(),
            'content' => '',
            'title' => $validated['title'],
            'excerpt' => '',
            'status' => 'publish',
            'comment_status' => 'closed',
            'ping_status' => 'closed',
            'password' => '',
            'slug' => '',
            'post_modified' => now(),
            'post_modified_gmt' => now()->utc(),
            'content_filtered' => '',
            'parent_id' => $validated['parent_item_id'] ?? 0,
            'guid' => '',
            'menu_order' => $validated['position'] ?? 0,
            'type' => 'nav_menu_item',
            'mime_type' => '',
            'comment_count' => 0,
        ]);

        // Store item meta
        $item->meta()->createMany([
            ['meta_key' => '_menu_item_type', 'meta_value' => $validated['type']],
            ['meta_key' => '_menu_item_object_id', 'meta_value' => (string) ($validated['object_id'] ?? 0)],
            ['meta_key' => '_menu_item_url', 'meta_value' => $validated['url'] ?? ''],
            ['meta_key' => '_menu_item_target', 'meta_value' => $validated['target'] ?? ''],
            ['meta_key' => '_menu_item_classes', 'meta_value' => $validated['css_classes'] ?? ''],
        ]);

        // Link item to menu via term_relationships
        TermRelationship::create([
            'object_id' => $item->id,
            'term_taxonomy_id' => $menu->id,
            'term_order' => 0,
        ]);

        // Update item count
        $menu->increment('count');

        $item->load('meta');

        return $this->success(new MenuItemResource($item), 201);
    }

    #[OA\Put(path: "/api/v1/admin/menus/{id}/items/{itemId}", operationId: "updateMenuItem", summary: "Update a menu item", tags: ["Admin Menus"], security: [["sanctum" => []]], parameters: [new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer")), new OA\Parameter(name: "itemId", in: "path", required: true, schema: new OA\Schema(type: "integer"))], requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: "#/components/schemas/AdminMenuItem")), responses: [new OA\Response(response: 200, description: "Updated"), new OA\Response(response: 404, description: "Not found")])]
    public function updateItem(UpdateMenuItemRequest $request, int $id, int $itemId): JsonResponse
    {
        $item = $this->findMenuItem($id, $itemId);

        if (!$item) {
            return $this->error('Menu item not found.', 404);
        }

        $validated = $request->validated();

        if (isset($validated['title'])) {
            $item->title = $validated['title'];
        }
        if (isset($validated['position'])) {
            $item->menu_order = $validated['position'];
        }
        if (isset($validated['parent_item_id'])) {
            $item->parent_id = $validated['parent_item_id'] ?? 0;
        }

        $item->post_modified = now();
        $item->post_modified_gmt = now('UTC');
        $item->save();

        // Update meta fields
        $metaMap = [
            'url' => '_menu_item_url',
            'target' => '_menu_item_target',
            'css_classes' => '_menu_item_classes',
        ];

        foreach ($metaMap as $field => $metaKey) {
            if (array_key_exists($field, $validated)) {
                $item->meta()->updateOrCreate(
                    ['meta_key' => $metaKey],
                    ['meta_value' => $validated[$field] ?? ''],
                );
            }
        }

        $item->load('meta');

        return $this->success(new MenuItemResource($item));
    }

    #[OA\Delete(path: "/api/v1/admin/menus/{id}/items/{itemId}", operationId: "deleteMenuItem", summary: "Remove a menu item", tags: ["Admin Menus"], security: [["sanctum" => []]], parameters: [new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer")), new OA\Parameter(name: "itemId", in: "path", required: true, schema: new OA\Schema(type: "integer"))], responses: [new OA\Response(response: 200, description: "Removed"), new OA\Response(response: 404, description: "Not found")])]
    public function removeItem(int $id, int $itemId): JsonResponse
    {
        $item = $this->findMenuItem($id, $itemId);

        if (!$item) {
            return $this->error('Menu item not found.', 404);
        }

        TermRelationship::where('object_id', $item->id)
            ->where('term_taxonomy_id', $id)
            ->delete();

        $item->meta()->delete();
        $item->delete();

        $menu = TermTaxonomy::find($id);
        $menu?->decrement('count');

        return $this->success(['message' => 'Menu item removed.']);
    }

    // ─── Menu Locations ─────────────────────────────────────────

    #[OA\Put(path: "/api/v1/admin/menus/{id}/locations", operationId: "assignMenuLocations", summary: "Assign menu to theme locations", tags: ["Admin Menus"], security: [["sanctum" => []]], parameters: [new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))], requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(properties: [new OA\Property(property: "header", type: "integer", nullable: true), new OA\Property(property: "footer", type: "integer", nullable: true)])), responses: [new OA\Response(response: 200, description: "Locations updated")])]
    public function updateLocations(Request $request, int $id): JsonResponse
    {
        $menu = TermTaxonomy::where('taxonomy', 'nav_menu')->find($id);

        if (!$menu) {
            return $this->error('Menu not found.', 404);
        }

        $locations = json_decode($this->optionService->get('nav_menu_locations', '{}'), true) ?: [];

        if ($request->has('header')) {
            $locations['header'] = $request->input('header');
        }
        if ($request->has('footer')) {
            $locations['footer'] = $request->input('footer');
        }

        $this->optionService->set('nav_menu_locations', $locations);

        return $this->success(['locations' => $locations]);
    }

    // ═══════════════════════════════════════════════════════════
    //  Private Helpers
    // ═══════════════════════════════════════════════════════════

    /**
     * Find a menu item that belongs to a specific menu.
     */
    private function findMenuItem(int $menuId, int $itemId): ?Post
    {
        $isLinked = TermRelationship::where('term_taxonomy_id', $menuId)
            ->where('object_id', $itemId)
            ->exists();

        if (!$isLinked) {
            return null;
        }

        return Post::with('meta')
            ->where('type', 'nav_menu_item')
            ->find($itemId);
    }
}
