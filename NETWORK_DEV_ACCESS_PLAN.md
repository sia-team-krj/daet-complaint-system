# LAN Device Development Access Plan

**Date:** September 25, 2026
**Scope:** Allow a phone on the same Wi-Fi network to use `composer run dev`
**Status:** Implemented and verified on September 25, 2026

## Changes

- Bind Laravel's development server to `0.0.0.0:8000`.
- Keep Vite bound to `0.0.0.0:5173`.
- Make Vite HMR host configurable through `VITE_HMR_HOST` so a phone does not try to connect to its own `localhost`.
- Document the required `.env` values for the computer's LAN IP and MinIO image access.

## Verification

- Confirm the dev command exposes ports 8000 and 5173 on the LAN.
- Confirm the phone can load the app, Vite assets, and uploaded evidence images.
- Do not expose PostgreSQL or Redis directly to the phone; they remain server-side services.
