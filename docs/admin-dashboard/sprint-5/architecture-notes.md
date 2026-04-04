# Architecture Notes - Sprint 5

## API Layer
- Added `SliderAdminController` with:
  - list/create/show/update/delete
  - bulk state actions
  - reorder operation
- Added multipart-safe POST update endpoint to avoid PHP PUT multipart parsing limitations.
- Added `media_type` list filter support (`image`/`video`).

## Validation
- Added dedicated form requests:
  - `StoreSliderRequest`
  - `UpdateSliderRequest`
  - `BulkSliderRequest`
  - `ReorderSlidersRequest`
- Boolean normalization added for `is_active` values in form-data contexts.
- `media_type` validated as enum: `image`, `video`.

## Storage and Media
- Slider image upload uses existing `MediaService`.
- Persisted slider image field stores uploaded media GUID URL.
- Added `media_type` column to classify each slider as image or video.

## Performance
- Added composite/secondary indexes to `sliders` for admin list and sort patterns.
- Added `sliders_media_active_sort_idx` to support media-aware admin filtering.
