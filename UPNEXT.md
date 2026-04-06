Most impactful, shortest path to a usable system:
1. Dashboard (highest priority)
Your users land here after login and it's just a stub. Build out the citizen dashboard showing their filed complaints, statuses, and ticket IDs. This makes the whole flow feel real and usable.
2. Complaint listing & detail view
You have complaints/create.blade.php but no show/index views. Users need to see their submitted complaints, track status changes, and view the audit log from complaint_logs. Add routes for /complaints (index) and /complaints/{id} (show).
3. Fix the Leaflet map
You already noted this is being debugged. Since location is core to complaint routing (lat/lng → department), this should be unblocked soon. Check if the CDN is loading before Livewire swaps the DOM — that's the most common cause.
4. Password reset
You have the stubs and Mailpit is already running, so this is low-effort to complete. Mailpit catches all outgoing mail so you can test it immediately without a real SMTP server.
5. Admin panel UI
The route exists but there's no UI. At minimum you need a complaint queue view where staff can change statuses — without this, the whole system is one-sided.
6. Staff/department routing
Related to admin: when a complaint is submitted, does it automatically appear in the right department's queue? If the routing logic isn't wired to the UI yet, that's a gap worth closing.

----------------------------------------------------------------------------------------------------------

5B. 🛠️ Admin Panel (Full Control)
Build the admin panel for "Daet Listens" — a Laravel 12 LGU complaint portal.
Admin can see all complaints across all departments, manage staff accounts,
and reassign complaints.

ROLE STRUCTURE:
- Admin user: role === 'admin', department_id is nullable (not scoped)
- AdminMiddleware: check role === 'admin', redirect if not
- Admin can create/edit/deactivate staff accounts and assign department_id

DESIGN SYSTEM (match exactly):
- Font: Cormorant Garamond (headings) + DM Sans (body)
- Colors: --navy #0B1F3A, --navy-mid #12294d, --gold #C9A84C,
  --gold-light #E2C06A, --text-dim rgba(255,255,255,0.55)
- Cards: rgba(255,255,255,0.04) bg, 1px rgba(201,168,76,0.20) border, radius 6px
- Same decorative elements: stripe bg, gold left bar, glow blobs, watermark
- Animations: fadeUp staggered d1–d5
- Layout: extends layouts.app, full-width with left sidebar nav

SIDEBAR:
- "Daet Listens Admin" at top with gold seal icon
- Nav items: Overview, All Complaints, Departments, Staff Accounts, Settings
- Gold left-border active state indicator
- Logout at bottom

OVERVIEW PAGE (/admin/dashboard):
- Stats row: Total Complaints, Unassigned, In Progress, Resolved Today,
  Avg. Resolution Days (system-wide)
- Department breakdown cards: one card per department showing
  their pending count, in-progress count, staff count
  (clicking a card filters the complaints table)
- Recent complaints table (all departments):
  ticket_id, citizen name, department name, status badge, date, "Manage" button
- Highlight unassigned complaints (department_id is null) in amber

ALL COMPLAINTS PAGE (/admin/complaints):
- Full filterable table: filter by department, status, date range, search by ticket_id
- Bulk actions: reassign department for selected complaints
- Export button (CSV) of filtered results

COMPLAINT MANAGEMENT (/admin/complaints/{complaint}):
- Full complaint details (same as staff view)
- Status update + staff notes (creates complaint_log)
- Reassign department dropdown (all departments)
- Reassign to specific staff member dropdown
  (filtered to staff belonging to selected department)
- Full audit timeline of all complaint_logs

STAFF ACCOUNT MANAGEMENT (/admin/staff):
- Table of all staff accounts: name, email, department, status (active/inactive)
- "Create Staff Account" button → modal or page with:
  name, email, temporary password, department assignment dropdown
- Edit: change department assignment, activate/deactivate account
- Cannot delete staff (soft approach: deactivate only, preserve audit trail)

BACKEND:
- AdminController: dashboard, complaints index/show/update,
  staff index/create/store/edit/update methods
- No query scoping — admin sees all
- On department reassignment: log the change in complaint_logs
- On staff creation: auto-send welcome email via Mailpit (use Mail facade)
- Routes under /admin/* with auth + admin middleware
- Eager load everything: user, department, logs->user, assignedStaff

6. 🔀 Department Routing (Updated for Staff Roles)
Wire up automatic department routing for submitted complaints in
"Daet Listens" — Laravel 12 LGU complaint portal.
Department routing must now also consider staff accounts tied to departments.

CONTEXT:
- Complaint model: department_id (FK), category field, lat/lng
- Department model: seeded LGU departments (GSO, ENGR, etc.)
- User model: role (citizen/staff/admin), department_id (staff only)
- ComplaintController handles creation and MinIO image upload
- Status uses ComplaintStatus enum: Draft/Submitted/InProgress/Resolved/Rejected

WHAT TO BUILD:

1. DepartmentRouter Service (app/Services/DepartmentRouter.php):
   - Static method: resolve(string $category): Department
   - Category → department mapping:
     'road_damage'     → Engineering
     'flooding'        → Engineering
     'streetlight'     → Engineering
     'garbage'         → General Services Office (GSO)
     'sanitation'      → GSO
     'park_maintenance'→ GSO
     'business_permit' → Business Permits & Licensing
     'noise_complaint' → Philippine National Police / Peace & Order
     'stray_animals'   → Agriculture & Veterinary
     'others'          → General Services Office (fallback)
   - If no match found: fall back to GSO (General Services)

2. ComplaintController@store integration:
   - After saving complaint, call DepartmentRouter::resolve($complaint->category)
   - Set complaint->department_id and save
   - Set initial status to Submitted (not Draft) on store
   - Create first complaint_log entry:
     (status: Submitted, note: "Complaint filed and routed to {dept name}",
     changed_by: auth()->id())

3. Complaint Create Form UX:
   - Category/type <select> field with optgroups matching departments
   - On category change (Alpine.js or vanilla JS):
     show gold helper text below select:
     "This will be routed to: {Department Name}"
   - Map the category→department in a JS object matching the PHP mapping

4. Confirmation / Success Page:
   - After successful submission show:
     ticket_id (large, Cormorant Garamond), department name,
     expected handling note, link to view complaint
   - Match navy/gold design system

5. Unassigned Complaint Safety Net:
   - Admin dashboard highlights complaints where department_id is null
   - Add a console:command or scheduled job (optional):
     php artisan complaints:route-unassigned
     Re-runs DepartmentRouter on any complaint where department_id is null

6. Tests (tests/Feature/ComplaintDepartmentRoutingTest.php):
   - Assert each category resolves to correct department
   - Assert fallback works for unknown category
   - Assert complaint_log is created on submission
   - Assert staff from correct department can see the complaint
   - Assert staff from wrong department cannot see the complaint

ROUTES TO ADD:
- POST /complaints → ComplaintController@store (existing, update logic)
- GET /complaints/success/{complaint} → show confirmation page