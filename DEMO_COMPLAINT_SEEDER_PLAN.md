# Demo Complaint Seeder Plan

**Date:** September 25, 2026
**Scope:** Realistic users, public/private complaints, spam examples, and duplicate-complaint scenarios
**Status:** Implemented and verified on September 25, 2026

## Goal

Create a repeatable seeder that gives the dashboard realistic data for validating complaint visibility, department routing, spam handling, and similar-complaint detection.

## Planned Seed Data

- Demo resident, staff, and admin accounts with known credentials.
- Public complaints that appear in transparency views.
- Private complaints that remain visible only to the owner and authorized staff/admin.
- Normal complaints across multiple departments.
- Spam examples with repeated keywords, suspicious contact patterns, and duplicate text.
- Similar complaint examples with the same issue and location but different reporters/timestamps.
- Enough records to make queue counts, filters, public/private visibility, and audit trails meaningful.

## Detection Support

Before seeding, inspect the current application for spam and similarity logic. If absent, add a small reusable detection service with conservative rules:

- Normalize complaint text for repeated-word and keyword signals.
- Flag likely spam without deleting the original complaint.
- Detect likely duplicates/similar complaints using normalized title, description, category, and nearby location when available.
- Expose the result to staff/admin views without hiding legitimate resident reports.

The seeder must remain deterministic and safe to run in development only.

## Verification

- Add tests for public/private visibility.
- Add tests for spam flags and similar-complaint matches.
- Run the seeder in a test database or transaction-safe environment.
- Run the full test suite and frontend build.

## Acceptance Criteria

- A single documented command seeds realistic demo records.
- Public and private complaints have visibly different behavior.
- Spam examples are identifiable in staff/admin tooling.
- Similar complaints are grouped or surfaced without blocking legitimate complaints.
- Existing complaint submission and audit behavior remains intact.
