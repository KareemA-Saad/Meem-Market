## Relations
@compliance/security/input_sanitization_gap.md

## Raw Concept
**Task:**
Implement multi-layer input sanitization and XSS protection

**Changes:**
- Created SanitizeInput middleware for global XSS protection
- Created SanitizesInput trait for fine-grained control in FormRequests
- Configured 5 HTMLPurifier profiles for different content types
- Updated 15 FormRequests to use the sanitization trait

**Files:**
- app/Http/Middleware/SanitizeInput.php
- app/Traits/SanitizesInput.php
- config/purifier.php

**Flow:**
Request -> SanitizeInput Middleware (Global) -> FormRequest@prepareForValidation (Trait) -> Validation -> Controller

**Timestamp:** 2026-02-02

## Narrative
### Structure
- `app/Http/Middleware/SanitizeInput.php`: Global input processing
- `app/Traits/SanitizesInput.php`: Reusable trait for FormRequests
- `config/purifier.php`: HTMLPurifier configuration profiles

### Dependencies
- `Mews\Purifier` (HTMLPurifier wrapper)
- `App\Http\Middleware\SanitizeInput` (Global middleware)
- `App\Traits\SanitizesInput` (FormRequest trait)

### Features
- Two-layer approach: Global middleware + Selective FormRequest trait
- Global XSS protection: Strips tags from plain text, preserves safe tags in rich content fields
- HTMLPurifier profiles: `default`, `strict`, `rich`, `plain`, `email`
- Automatic sanitization of translatable fields (locale arrays)
- Protection against event handlers, `javascript:` protocols, and CSS exploits
