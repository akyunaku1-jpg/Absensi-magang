create table if not exists public.users (
  id bigint generated always as identity primary key,
  username text not null unique,
  password_hash text not null,
  role text not null check (role in ('admin', 'user')),
  created_at timestamp without time zone not null default now()
);

create table if not exists public.companies (
  id bigint generated always as identity primary key,
  name text not null,
  address text not null,
  supervisor_name text not null,
  created_at timestamp without time zone not null default now(),
  updated_at timestamp without time zone not null default now()
);

create table if not exists public.students (
  id bigint generated always as identity primary key,
  name text not null,
  nis text not null unique,
  phone text not null,
  school_origin text not null,
  major text not null,
  user_username text null,
  status text not null check (status in ('Active', 'Completed')),
  internship_start_date date not null,
  internship_end_date date not null,
  company_id bigint null references public.companies(id) on delete set null,
  created_at timestamp without time zone not null default now(),
  updated_at timestamp without time zone not null default now()
);

create table if not exists public.attendances (
  id bigint generated always as identity primary key,
  user_id bigint not null references public.users(id) on delete cascade,
  attendance_date date not null,
  status text not null default 'Present' check (status in ('Present', 'Sick', 'Permission', 'Absent')),
  check_in_at timestamp without time zone not null,
  note text null,
  created_at timestamp without time zone not null default now(),
  unique (user_id, attendance_date)
);
