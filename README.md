# Internship Data Dashboard

Simple web project using PHP + Supabase for student internship and school management.

## Run locally

1. Open terminal in this folder.
2. Start built-in PHP server:

```bash
php -S localhost:8000
```

3. Copy `.env.example` to `.env` and fill:
   - `SUPABASE_URL`
   - `SUPABASE_SERVICE_ROLE_KEY`
4. In Supabase SQL editor, run `supabase_schema.sql`.
5. Open `http://localhost:8000`.

## Included features

- Student internship cards with complete info
- Colored initial avatar + banner
- Active badge glow animation
- Discharge tag with completion date
- Progress bar with warning color at 80%+ elapsed period
- Countdown boxes (months left, days left, percent left, internship phase)
- Real-time top statistics (total, active, completed, schools)
- Search + status + major filters
- Company cards (name, address, supervisor)
- Company cards include assigned student count
- Mandatory login before app access
- Login tab with role-based access
- Admin-only create, edit, and delete actions
- User View with personal internship data and timeline
- Attendance options: Present, Sick, Permission, Absent
- One attendance submission per day
- Admin attendance reports with date-range filter and status stats
- Add/edit via modal forms
- Delete confirmation dialog
- Toast notifications for actions
- Sidebar navigation with smooth transitions

## Default login accounts

- Admin: `admin` / `admin123`
- User: `ayusabrina@pkl.com` / `user123`
