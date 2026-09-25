# Multi-Photo Evidence Plan

**Date:** September 25, 2026
**Status:** Implemented and verified on September 25, 2026
**Scope:** Resident complaint filing and evidence display

## Confirmed requirements

- Allow a resident to attach multiple photos to one complaint.
- Enforce a maximum of five photos per complaint.
- Provide a mobile camera option using the device camera.
- Keep a gallery/file-picker option for desktop and existing photo libraries.
- Keep photo evidence mandatory.
- Preserve existing one-photo submissions and stored `image_path` values.

## Storage contract

- Add nullable `image_paths` JSON column to `complaints`.
- Continue writing the first stored path to `image_path` so existing views, exports, and older records remain compatible.
- Write all stored paths to `image_paths`; legacy records without that column are read through a model accessor that falls back to `image_path`.
- Extract GPS metadata from the first photo that contains valid coordinates, rather than only the first selected file.
- Delete newly stored files if the database transaction fails.

## Form behavior

- Gallery input: `images[]`, `multiple`, image MIME types.
- Camera input: `camera_photo`, `capture="environment"`, image MIME types.
- Show a live `0/5` count, thumbnail previews, and remove controls.
- Enforce the five-file limit in both browser UI and server validation.
- Stream evidence through an authenticated, policy-protected application route so MinIO stays localhost-only.
- Keep address/map location as the fallback when no selected photo has GPS.

## Verification

- Add coverage for multi-photo storage, camera input, maximum-count rejection, legacy `image` submissions, and GPS fallback behavior where practical.
- Run migrations, the full test suite, Blade compilation, and the production Vite build.
- Restart the Tailscale-bound PHP server after dependency or PHP-ini changes.
- Smoke-test a real MinIO write through the application filesystem disk.

## Verification completed

- `php artisan test`: 72 tests / 329 assertions passing.
- `npm run build`: production assets generated successfully.
- `php artisan view:cache`: Blade templates compile successfully.
- Real HTTPS multi-photo submission stored MinIO objects and redirected successfully.
- Real HTTPS 3 MB upload succeeded with the configured `25M` per-file / `30M` request limits.
- Real HTTPS camera-field submission succeeded and was cleaned up after verification.
- The missing `league/flysystem-aws-s3-v3` adapter was restored from `composer.lock`.
