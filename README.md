# Room Scheduling Tracker

A fully functioning room scheduling tracker in **object-oriented PHP** (no framework, plain PDO), built from the database in `RST_DB.xlsx`.

Book rooms, see who's using a room right now on a floor map, check availability, and manage recurring schedules — with conflicts blocked before anything is saved.

## What it does

- **Auth & roles** — register / log in with hashed passwords (`password_hash`). Roles: `admin`, `teacher`, `student` (plus `scheduler` / `viewer`). The first account on a fresh install becomes admin. Admins manage maps, rooms and users; teachers/students can book.
- **Separate logins** — admin and student logins are different pages, not one form: `/login` is a chooser, `/login/admin` only accepts admin accounts, `/login/student` is for students (teachers log in there too). An admin account is rejected on the student page and vice versa.
- **Floor maps** — CRUD for maps (`tblMaps`: name, floor, width/height, background image, status). Each map renders as an interactive SVG: rooms are drawn from their `shape_data` JSON (rectangle / polygon / path) and coloured green/red by whether they are free or busy right now.
- **Rooms** — CRUD for rooms (`tblRooms`), with shape JSON, description and `active` / `unavailable` status.
- **Schedules** — CRUD with recurrence: `one_time`, `daily`, `weekly` (pick weekdays, 1=Mon…7=Sun via `tblScheduleDays`), `monthly`. Saving a schedule materialises its occurrences in `tblScheduleOccurrences`.
- **Conflict detection** — before a schedule is saved, every date in the series is checked against existing occurrences. Overlapping bookings are rejected; back-to-back bookings (one ends at 10:00, the next starts at 10:00) are allowed.
- **Exceptions** — skip, move or modify a single occurrence of a recurring series (`tblScheduleExceptions`), e.g. skip Nov 9 for maintenance. Occurrences are regenerated when an exception is added.
- **Occurrences** — list by date range, cancel / restore / complete individual occurrences.
- **Availability checker** — pick a date + time slot and see every free room (optionally on one map).
- **Calendar** — month view built from occurrences, with room filter.
- **Dashboard** — room/free-busy status right now, today's bookings, upcoming bookings, counts.

## Database — exactly as supplied

Source: `RST_DB.xlsx` (data dictionary, one sheet). The schema in `database/rst_db.sql` matches it table-for-table, column-for-column — nothing renamed:

| Table | Purpose |
|---|---|
| `tblUsers` | `user_id`, `username`, `email`, `password_hash`, `profile_img`, `role`, `section`, `org_name`, `phone_number` |
| `tblMaps` | `map_id`, `map_name`, `floor_number`, `map_width`, `map_height`, `background_image`, `map_status`, `created_at`, `updated_at` |
| `tblRooms` | `room_id`, `map_id` (FK), `room_name`, `shape_type`, `shape_data` (JSON), `room_description`, `room_status`, `created_at`, `updated_at` |
| `tblSchedules` | `schedule_id`, `organizer_id` (FK), `room_id` (FK), `schedule_title`, `schedule_description`, `recurrence_type`, `start_date`, `end_date`, `start_time`, `end_time`, `schedule_status`, `created_at`, `updated_at` |
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
There is no `app/` folder — this is a plain web PHP project: drop the
folder into `htdocs` (XAMPP) and it runs from the project root.

## Setup (MySQL — recommended)

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

Demo logins after `seed.sql` (password for all three: **`password123`** — change them before going live):

| Username | Email | Role | Login page |
|---|---|---|---|
| `admin` | admin@example.com | admin | `/login/admin` |
| `jdoe` | jdoe@example.com | teacher | `/login/student` |
| `ana` | ana@example.com | student, BSIT-1A | `/login/student` |

## Folder name / XAMPP — it just works

The project folder can be called anything — `room-scheduling-tracker`,
`Room-Scheduling-Tracker`, `my-app`, whatever you rename it to in `htdocs`.
No folder name is written anywhere in the code: every link, redirect and
stylesheet/script URL goes through `url()` in `core/helpers.php`, which
detects the folder from the running script (`base_path()`), and the router
in `index.php` strips that same prefix before matching routes. If you ever
need to force it, set `APP_URL=http://localhost/MyFolder` in `.env`.

Clean URLs (`/login/admin`, `/schedules`, …) are handled by `.htaccess`
at the project root (Apache `mod_rewrite`, on by default in XAMPP).
If the design (CSS) is not showing, or pages 404 in XAMPP, check these:
1. Open the project through the folder URL, e.g.
   `http://localhost/Room-Scheduling-Tracker/login` — not `http://localhost/login`.
2. In XAMPP `httpd.conf`, `mod_rewrite` must be loaded and `AllowOverride All`
   set for `htdocs` (both are defaults). Without rewrite, the app still works
   with URLs like `http://localhost/Room-Scheduling-Tracker/index.php/schedules`.
3. Hard-refresh the browser (Ctrl+F5) so a cached unstyled page is replaced.

Seed data also includes: Main Building Floor 1 + Floor 2, Rooms 101–103 / 201–202, a weekly IT Department Meeting (Mondays 09:00–10:30, Room 101), a BSIT-1A lecture (Mon/Wed/Fri 13:00–14:30, Room 102), a one-time CSS org meeting, and a skipped occurrence on 2026-11-09 — the examples from the spreadsheet.

## Quick demo without MySQL (SQLite)

```bash
cp .env.example .env
# in .env set: DB_DRIVER=sqlite  DB_PATH=storage/rst.db
sqlite3 storage/rst.db < database/sqlite_schema.sql
php -S localhost:8000
# register the first account — it becomes the admin
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

MIT — do what you want with it, school project friendly.
