# Product

<!-- impeccable:product-schema 1 -->

## Platform

web

## Users

- **Residents (Citizens)**: Citizens living across the 25 barangays of Daet, Camarines Norte reporting municipal infrastructure and utility concerns (e.g., road damage, streetlights, waste, flooding); tracking resolution progress; and viewing public civic transparency updates while protecting their personal identity through pseudo-names / display aliases.
- **Approvers (LGU Triage Staff)**: Department supervisors or intake officers reviewing incoming complaints assigned to their department, verifying legitimacy, and approving or rejecting reports before assigning them to field workers.
- **Workers (LGU Field & Operations Personnel)**: Department personnel handling remediation tasks on the ground, posting operational updates/photos, and marking complaints as completed.
- **Admins (LGU System Administrators)**: Municipal administrators managing departments, staff accounts, invitation codes, and system-wide configurations while having complete visibility across all complaints, cross-department routing, and immutable audit logs.

## Product Purpose

Deliver a modern, transparent, geo-spatial complaint and public utility service management platform for the Local Government Unit (LGU) of Daet, Camarines Norte ("Daet Listens"). The platform connects citizens directly with municipal departments to report, route, resolve, and publicly track community infrastructure issues, fostering civic accountability and community trust.

## Positioning

Unlike traditional opaque governmental complaint dropboxes or generic ticketing systems, Daet Listens anchors every action in an immutable, transparent audit trail (`complaint_logs`). It pairs open municipal accountability on a public civic feed with guaranteed citizen privacy using pseudo-names.

## Operating Context

- Operates within the municipal jurisdiction of Daet, Camarines Norte, covering all 25 official barangays (Alawihao, Awitan, Bagasbas, Barangay I–VIII, Bibirao, Borabod, Calasgasan, Camambugan, Cobangbang, Dogongan, Gahonon, Gubat, Lag-on, Magang, Mambalite, Mancruz, Pamorangon, San Isidro).
- Coordinates municipal workflows across 8 core LGU departments:
  - Engineering Office (ENG)
  - Health Office (HLTH)
  - General Services Office (GSO)
  - Municipal Planning and Development Office (MPDO)
  - Waste Management Office (WST)
  - Social Welfare and Development Office (SWDO)
  - Business Permit and Licensing Office (BPLO)
  - Office of the Mayor (OM)
- Web-based application accessible across desktop and mobile browsers, supporting both citizens on mobile devices in the field and municipal staff at office workstations or mobile dispatch.

## Capabilities and Constraints

- **Fixed 4-Role Architecture**: `admin`, `approver`, `worker`, `resident` with fixed permissions (no dynamic role creation).
- **Audit Footprint**: Every status change, assignment, department routing, and note is preserved in an audit log with timestamp, user attribution, and context.
- **Public Portal with Anonymity**: Public transparency feed and tracking view with citizen names automatically masked by pseudo-names / display aliases (`prefers_anonymity`).
- **Geo-Spatial Pinning**: Complaints support location pinning (lat/lng), barangay tagging, and photo attachments.
- **Department Routing**: Category-to-department routing matching reported issues to the designated municipal office.
- **Lifecycle Auto-Close**: Complaints automatically close after 7 days of inactivity following completion.
- **Secure Staff Onboarding**: Single-use invitation codes with 10-minute expiry for onboarding staff to specific departments.

## Brand Commitments

- **Name**: "Daet Listens" / Daet Public Service Complaint & Transparency Management System.
- **Official Identity**: Official seal of the Municipality of Daet, Camarines Norte (`public/images/lgulogo.png`).
- **Tone & Voice**: Transparent, accountable, respectful, authoritative yet accessible to everyday citizens.

## Evidence on Hand

- Official 25 barangays cataloged in `app/Models/User.php`.
- Department structure and seeds defined in `database/seeders/DepartmentSeeder.php`.
- Official LGU crest asset located at `public/images/lgulogo.png`.
- Existing Laravel 12 application codebase with PostgreSQL, Redis, Mailpit, MinIO configurations, and Blade UI scaffolding.

## Product Principles

- **Accountability Through Immutability**: Every action, status transition, reassignment, and remark must produce an immutable audit log entry.
- **Transparency Balanced with Citizen Privacy**: Default to public visibility of municipal progress while strictly preserving citizen privacy through pseudo-names.
- **Predictable Role Boundaries**: Maintain clear division of responsibilities—Residents file, Approvers triage, Workers execute, Admins oversee.
- **Responsive Municipal Service**: Streamline the path from citizen report to verified department action with clear visual progress cues and automated workflows.

## Accessibility & Inclusion

- Responsive mobile-first design suitable for varied citizen devices and mobile network conditions in Camarines Norte.
- Adherence to WCAG 2.1 AA standards for contrast, keyboard navigability, and screen reader readability.
