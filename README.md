# Room Scheduling Tracker

A fully functioning room scheduling tracker in **object-oriented PHP** (no framework, plain PDO), built from the database in `RST_DB.xlsx`.

Book rooms, see who's using a room right now on a floor map, check availability, and manage recurring schedules. Overlapping bookings are allowed - an admin confirms which one goes ahead.

## What it does

- **Auth & roles** - register / log in with hashed passwords (`password_hash`). Roles: `admin`, `teacher`, `student`, `officer` (org officer), `mayor` (class mayor, plus `scheduler` / `viewer`). The first account on a fresh install becomes admin. **Only admins, teachers, class mayors and org officers can book rooms / make schedules (Add Reserve)** - students log in only to check schedules (read-only; booking buttons are hidden from them and the server rejects their booking requests). Admins manage maps, rooms and users.
- **Admin confirmation** - every schedule has an admin confirmation (`admin_confirmed` on tblSchedules; run `database/migrations/001_add_admin_confirmed.sql` once on an existing database). Schedules stay active without confirmation. Overlapping bookings are never blocked: both stay active, and an admin confirms one (green light) from the schedule page - confirming one does not delete the other. Availability, the room list, room details and the dashboard all show each booking's confirmation state (✓ Admin confirmed / Awaiting confirmation), and rooms with bookings can still be booked.
- **Confirmations** - deletes, cancels and discards ask in a Bootstrap modal ("Are you sure?") instead of the browser's own dialog.
- **Map editor** - the Room Properties panel has a Shape selector (rectangle / polygon, converted from the room's current bounds) and a Discard changes button that restores the last saved layout. Polygons are fully editable: drag the white corner dots to reshape them, change Width / Height to scale them, or use + Add corner to add a point on the longest edge. Old path-shaped rooms cannot be dragged until converted - select one and hit Convert to polygon. The add-room tool is now ▣ (blue) so it can't be mistaken for zoom-in ＋.
- **Rooms list (visual, not tables)** - week strip (Sun–Sat with the selected day highlighted), filter bar (time, room search, floor, status, teacher/mayor search, Recent Filters), rooms grouped by floor as cards showing live status (AVAILABLE / CONFIRMED / RESERVED / UNAVAILABLE), booking time and who booked it. Click a card to open the room.
- **Room details (visual)** - big status badge, room info, today's timeline, today's and upcoming bookings as cards, and **＋ Add Reserve** shown only to admins, teachers, class mayors and org officers.
- **One login for everyone** - a single login page (`/login`) accepts every role, admins included. Old links `/login/admin` and `/login/student` redirect to it. Admins log in and see the user side first, like everyone else.
- **Two different sides** - the user side is friendly and visual: the dashboard with stat cards and the live map, the room cards, the timeline. The admin side is for managing: a **Switch to Admin** button in the user sidebar opens the dark admin side (`/admin`) with its own dashboard (awaiting confirmations, user/room counts, quick manage links), a dark sidebar (Dashboard, Maps, Rooms, Schedules, History, User List), and the admin-only pages (User List, map/room forms, map editor) all use that admin layout. While on the admin side, the shared pages (rooms, schedules, maps) stay in the admin layout too; **Back to user view** returns to the user dashboard. The user side hides admin-level details (room shapes, raw recurrence codes like `one_time` - shown as "One time", "Weekly" and so on), and the campus map page is just the map - no room table under it.
- **Map decorations** - in the map editor, the ▭ and T tools add blank coloured boxes and text labels for designing the map (hallways, ground areas, building names). Boxes can be dragged, resized from the corner dot, recoloured and labelled; texts can be dragged and restyled (size, colour). They save with the layout and show on the public map too. Stored as JSON in `tblMaps.decorations` (run `database/migrations/002_add_map_decorations_and_room_image.sql` once on an existing database).
- **Room photos** - an admin can add a photo of what a room looks like on the room form (JPG/PNG/GIF/WebP, under 2 MB). It shows on the room details page and as a thumbnail on the room cards; without a photo, admins see a "No photo yet" box with an Add a photo link. Stored in `uploads/rooms/` as `room_{id}.{ext}` (`room_image` on tblRooms, same migration 002 as above).
- **Home page = login, then the user dashboard** - opening the app sends guests straight to the login page; logged-in users land on the dashboard: four stat cards (Rooms, Free right now, Occupied now, Bookings today), floor tabs that switch the map right there, the interactive floor map with live room status (green available, red occupied now, amber reserved later today, grey unavailable), and a side panel with the selected room's details, legend and quick search. Click a room to open its details page; hovering previews it in the side panel with today's bookings.
- **Interactive map** - rooms animate on hover/select, busy rooms pulse, floor tabs and quick search filter live, and each room page has a timeline view of today's bookings (Figma Room Details).
- **Admin map editor** - `/maps/{id}/editor`: drag rooms to move them, drag the white corner dot to resize rectangles, drag a polygon's corner dots to reshape it, edit name/position/size/description/status in the Room Properties panel and watch the map redesign live, then Save Changes. EXPORT downloads the map + rooms as JSON; IMPORT uploads a JSON export back in (matched by room id, then name - nothing is deleted). Toolbar has back, select/add-room tools, zoom +/− and fullscreen; legend sits under the map.
- **Sidebar** - categorized: Campus Map (Campus Map, Rooms), Schedule (Schedules, Calendar, Availability, History), Admin (User List, admins only), Account (My Profile).
- **History, not Occurrences** - the old Occurrences page is now called **History**: every booking, past and present, including cancelled and completed ones. Schedules shows active schedules by default (pick "All statuses" to see cancelled and archived ones too).
- **Room details** - Current schedule and instructor at a glance, all of today's schedules with a coloured bar each, and the Timeline View with the same colours: blue = confirmed, yellow = reserved (awaiting confirmation), red = cancelled, green = completed. A gold outline also marks confirmed bookings.
- **Cross-floor search** - Quick search on the map searches every floor, not just the one shown. It counts the matches ("3 of 5") and Prev / Next jump to each one, switching floors when the next match is on another floor and selecting the room there.
- **Profile** - `/profile`: avatar with Change Picture, editable username/email/section/organization/phone, password change, and Delete Account. Roles can only be changed by an admin on the User List.
- **Calendar** - month grid with today highlighted and bookings as coloured pills (click one to open the schedule).
- **Floor maps** - CRUD for maps (`tblMaps`: name, floor, width/height, background image, status). Each map renders as an interactive SVG: rooms are drawn from their `shape_data` JSON (rectangle / polygon / path) and coloured green/red by whether they are free or busy right now.
- **Rooms** - CRUD for rooms (`tblRooms`), with shape JSON, description and `active` / `unavailable` status.
- **Schedules** - CRUD with recurrence: `one_time`, `daily`, `weekly` (pick weekdays, 1=Mon…7=Sun via `tblScheduleDays`), `monthly`. Saving a schedule materialises its occurrences in `tblScheduleOccurrences`. Overlapping bookings are allowed and flagged instead of blocked (back-to-back still never counts as a conflict).
- **Conflict detection** - before a schedule is saved, every date in the series is checked against existing occurrences. Overlapping bookings are rejected; back-to-back bookings (one ends at 10:00, the next starts at 10:00) are allowed.
- **Exceptions** - skip, move or modify occurrences of a recurring series (`tblScheduleExceptions`), e.g. skip Nov 9 for maintenance. Add as many as you need in one go ("Add another exception") and save them all at once; each made exception can be removed again from the Exceptions list, which puts the original occurrence back. Occurrences are regenerated whenever exceptions are added or removed.
- **History** - every occurrence by date range, cancel / restore / complete individual occurrences.
- **Availability checker** - pick a date + time slot and see every free room (optionally on one map).
- **Calendar** - month view built from occurrences, with room filter.
- **Dashboard** - the user landing page: stat cards, floor tabs, the interactive map and the room side panel. Room/free-busy status right now and today's bookings live on the map and in the panel. Admins have their own dashboard at `/admin` (see "Two different sides" above).
- **Room timeline** - room details always show the day timeline (7 AM - 7 PM, zoom, presets, drag to zoom, now-line), even when nothing is booked today - the track is empty and the note says the room is free all day.

## Database - exactly as supplied

Source: `RST_DB.xlsx` (data dictionary, one sheet). The schema in `database/rst_db.sql` matches it table-for-table, column-for-column - nothing renamed:

| Table | Purpose |
|---|---|
| `tblUsers` | `user_id`, `username`, `email`, `password_hash`, `profile_img`, `role`, `section`, `org_name`, `phone_number` |
| `tblMaps` | `map_id`, `map_name`, `floor_number`, `map_width`, `map_height`, `background_image`, `map_status`, `created_at`, `updated_at` |
| `tblRooms` | `room_id`, `map_id` (FK), `room_name`, `shape_type`, `shape_data` (JSON), `room_description`, `room_status`, `created_at`, `updated_at` |
| `tblSchedules` | `schedule_id`, `organizer_id` (FK), `room_id` (FK), `schedule_title`, `schedule_description`, `recurrence_type`, `start_date`, `end_date`, `start_time`, `end_time`, `schedule_status`, `admin_confirmed`, `created_at`, `updated_at` |
| `tblScheduleDays` | `schedule_day_id`, `schedule_id` (FK), `day_of_week` (1=Mon…7=Sun) |
| `tblScheduleExceptions` | `exception_id`, `schedule_id` (FK), `original_date`, `exception_type`, `new_date`, `new_room_id` (FK), `new_start_time`, `new_end_time`, `exception_reason`, `created_by` (FK), `created_at` |
| `tblScheduleOccurrences` | `occurrence_id`, `schedule_id` (FK), `occurrence_date`, `room_id` (FK), `start_time`, `end_time`, `occurrence_status` |

## Project layout

```
index.php                    # front controller + router (project root)
config/config.php            # env-based config (.env loader included)
database/
  rst_db.sql                 # MySQL schema (from RST_DB.xlsx)
  seed.sql                   # demo data (see below)
  sqlite_schema.sql          # SQLite equivalent, for a quick local demo
assets/css/style.css         # plain CSS, no build step
assets/js/app.js             # weekday toggle + delete confirms (optional)
core/
  Database.php               # PDO singleton (MySQL + SQLite)
  Auth.php                   # session auth + role checks
  helpers.php                # esc(), flash(), csrf, redirect, …
models/                      # User, Map, Room, Schedule,
                             #   ScheduleOccurrence, ScheduleException
services/
  RecurrenceService.php      # expands a series into occurrences
  AvailabilityService.php    # overlap / free-room checks
controllers/                 # Auth, Dashboard, Map, Room, Schedule,
                             #   Occurrence, Availability, User
views/                       # PHP templates (layouts + one folder per area)
uploads/profiles/  uploads/maps/   # user uploads (git-ignored)
```
There is no `app/` folder - this is a plain web PHP project: drop the
folder into `htdocs` (XAMPP) and it runs from the project root.

## Setup (MySQL - recommended)

Requirements: PHP 8.1+ with `pdo_mysql`, MySQL 8 / MariaDB 10.4+.

```bash
# 1. Create the database
mysql -u root -p < database/rst_db.sql

# 2. Demo data (optional)
mysql -u root -p rst_db < database/seed.sql

# 3. Configure
cp .env.example .env
# edit .env: DB_HOST, DB_NAME=rst_db, DB_USER, DB_PASS

# 4. Run from the project root (or drop the folder into htdocs for XAMPP)
php -S localhost:8000
# open http://localhost:8000
```

Demo logins after `seed.sql` (password for all three: **`password123`** - change them before going live):

| Username | Email | Role | Can book? |
|---|---|---|---|
| `admin` | admin@example.com | admin | Yes |
| `jdoe` | jdoe@example.com | teacher | Yes |
| `officer` | officer@example.com | org officer | Yes |
| `mayor` | mayor@example.com | class mayor, BSIT-1A | Yes |
| `ana` | ana@example.com | student, BSIT-1A | No - views only |

## Folder name / XAMPP - it just works

The project folder can be called anything - `room-scheduling-tracker`,
`Room-Scheduling-Tracker`, `my-app`, whatever you rename it to in `htdocs`.
No folder name is written anywhere in the code: every link, redirect and
stylesheet/script URL goes through `url()` in `core/helpers.php`, which
detects the folder from the running script (`base_path()`), and the router
in `index.php` strips that same prefix before matching routes. If you ever
need to force it, set `APP_URL=http://localhost/MyFolder` in `.env`.

Clean URLs (`/login`, `/schedules`, …) are handled by `.htaccess`
at the project root (Apache `mod_rewrite`, on by default in XAMPP).
If the design (CSS) is not showing, or pages 404 in XAMPP, check these:
1. Open the project through the folder URL, e.g.
   `http://localhost/Room-Scheduling-Tracker/login` - not `http://localhost/login`.
2. In XAMPP `httpd.conf`, `mod_rewrite` must be loaded and `AllowOverride All`
   set for `htdocs` (both are defaults). Without rewrite, the app still works
   with URLs like `http://localhost/Room-Scheduling-Tracker/index.php/schedules`.
3. Hard-refresh the browser (Ctrl+F5) so a cached unstyled page is replaced.

Seed data also includes: Main Building Floor 1 + Floor 2, Rooms 101–103 / 201–202, a weekly IT Department Meeting (Mondays 09:00–10:30, Room 101), a BSIT-1A lecture (Mon/Wed/Fri 13:00–14:30, Room 102), a one-time CSS org meeting, and a skipped occurrence on 2026-11-09 - the examples from the spreadsheet.

## Quick demo without MySQL (SQLite)

```bash
cp .env.example .env
# in .env set: DB_DRIVER=sqlite  DB_PATH=storage/rst.db
sqlite3 storage/rst.db < database/sqlite_schema.sql
php -S localhost:8000
# register the first account - it becomes the admin
```

## How the tricky parts work

- **Recurrence** (`RecurrenceService::generate`): one_time = start date only; daily = every day; weekly = only the weekdays in `tblScheduleDays`; monthly = same day-of-month. Open-ended series (no `end_date`) are capped at 90 days per generation. Exceptions are applied while generating: `skipped` writes a cancelled occurrence, `moved`/`modified` writes a cancelled original plus the replacement.
- **Conflicts** (`AvailabilityService`): an overlap is `existing.start < requested.end AND existing.end > requested.start`, ignoring cancelled occurrences. The check runs over the whole expanded series before insert, inside a transaction with occurrence generation.
- **Floor map**: `Room::shapeToSvg()` turns `shape_data` into `<rect>` / `<polygon>` / `<path>`. Coordinates are map-relative (map is 1200×800 in the seed data).

## Security notes

- All queries use PDO prepared statements; passwords are hashed, never stored plain.
- Forms carry a CSRF token; destructive actions are POST-only with a confirm step.
- Output is escaped with `htmlspecialchars` via `e()`.
- `.env` (real credentials) is git-ignored; only `.env.example` is committed.

## License

MIT - do what you want with it, school project friendly.
