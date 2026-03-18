## Raw Concept
**Task:**
Implement Category, Tag, and Generic Taxonomy CRUD API with hierarchical support.

**Changes:**
- Created TaxonomyController to handle categories, tags, and custom taxonomies.
- Implemented hierarchical support for categories and custom hierarchical taxonomies.
- Added term count maintenance logic on relationship changes.
- Implemented bulk actions for term deletion.

**Files:**
- app/Http/Controllers/Api/V1/Admin/TaxonomyController.php
- app/Http/Resources/V1/Admin/TermResource.php
- app/Http/Resources/V1/Admin/TermCollection.php
- app/Http/Requests/Admin/StoreTermRequest.php
- app/Models/Term.php
- app/Models/TermTaxonomy.php

**Flow:**
Request -> TaxonomyController -> TermTaxonomy Model -> Recalculate Counts -> TermResource

**Timestamp:** 2026-02-18

## Narrative
### Structure
The TaxonomyController handles multiple taxonomies (category, post_tag, etc.) via type parameters. TermTaxonomy model manages the relationship between terms and their classifications.

### Features
Auto-slug generation from names (unique within taxonomy), hierarchical parent-child relationships for categories, and "hide_empty" filtering in listings.

### Rules
1. Cannot delete the default_category.
2. Deleting a term only removes relationships, not the associated posts.
3. Slugs must be unique within their specific taxonomy.
