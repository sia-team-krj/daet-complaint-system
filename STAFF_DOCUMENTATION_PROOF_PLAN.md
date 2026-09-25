# Staff Documentation Proof Plan

**Date:** September 25, 2026
**Status:** Implemented and verified on September 25, 2026
**Scope:** Staff complaint status updates

## Requirement

Staff must provide verifiable documentation photos when moving a complaint to `In Progress` or `Resolved`, so residents and administrators can see proof of work being carried out or completed.

## Rules

- Require at least one documentation image for `In Progress` and `Resolved` transitions.
- Allow up to five documentation images per update.
- Accept JPG, PNG, and WEBP files up to 5 MB each.
- Provide a gallery picker and a mobile camera option.
- Keep documentation optional for other valid status transitions.
- Attach paths to the immutable `ComplaintLog` entry created for that status update.
- Never expose MinIO directly; stream documentation through an authenticated complaint-policy route.
- Delete newly stored files if the update transaction fails.
- Record the documentation count in the activity audit metadata.

## Planned implementation

1. Add a JSON `documentation_paths` field to `complaint_logs`.
2. Extend the staff update endpoint and timeline model.
3. Add gallery/camera inputs and clear requirement copy to the staff response form.
4. Display documentation thumbnails in staff, resident, and admin timelines.
5. Add a policy-protected documentation streaming route.
6. Add feature tests for required proof, optional proof, storage, audit records, and authorization.
7. Run migrations, full tests, Blade compilation, and the production asset build.

## Acceptance criteria

- Staff cannot mark a verified complaint `In Progress` or `Resolved` without at least one proof image.
- Valid documentation updates store all selected images and create a timeline entry.
- Documentation is visible only to authorized complaint viewers.
- Existing status transition and privacy rules continue to pass.
- Staff can use a mobile camera or choose photos from the device.
- Camera captures append to the existing selection, and every selected photo can be previewed and removed before submission.

## Verification completed

- `php artisan test`: 79 tests / 366 assertions passing.
- `npm run build`: production assets generated successfully.
- `php artisan view:cache`: Blade templates compile successfully.
- The staff proof form accepts multiple camera captures, previews each image, and removes individual gallery or camera selections before submission.
- Mechanical design detection was run on the changed staff, resident, admin, and CSS targets; reported warnings are pre-existing styles outside the documentation proof block.
