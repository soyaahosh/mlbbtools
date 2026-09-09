-- ==============================================================================
-- KETUPAT MLBB TOOLS & DIAMOND GIVEAWAY - SUPABASE DATABASE SCHEMA
-- ==============================================================================
-- Run this script in the Supabase SQL Editor (Dashboard -> SQL Editor -> New Query)
-- It creates the tables, indexes, Row Level Security (RLS) policies, and admin views.
-- ==============================================================================

-- 1. USERS TABLE
-- Stores user profile, MLBB account details, points balance, tickets, and location info.
CREATE TABLE IF NOT EXISTS public.users (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    email TEXT UNIQUE NOT NULL,
    username TEXT,
    mlbb_id TEXT,
    mlbb_server TEXT,
    mlbb_ign TEXT,
    mlbb_region TEXT,
    mlbb_region_code TEXT,
    points INTEGER NOT NULL DEFAULT 150,
    diamonds_claimed INTEGER NOT NULL DEFAULT 0,
    giveaway_tickets INTEGER NOT NULL DEFAULT 0,
    mega_tickets INTEGER NOT NULL DEFAULT 0,
    daily_tickets INTEGER NOT NULL DEFAULT 0,
    daily_streak INTEGER NOT NULL DEFAULT 1,
    location_text TEXT,
    latitude NUMERIC(10, 6),
    longitude NUMERIC(10, 6),
    login_time TEXT,
    avatar_data TEXT,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT timezone('utc'::text, now()) NOT NULL,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT timezone('utc'::text, now()) NOT NULL
);

-- 2. REDEMPTIONS TABLE
-- Stores diamond store redemption orders pending manual or automated delivery.
CREATE TABLE IF NOT EXISTS public.redemptions (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    order_id TEXT UNIQUE NOT NULL,
    user_email TEXT NOT NULL,
    mlbb_id TEXT NOT NULL,
    mlbb_server TEXT NOT NULL,
    mlbb_ign TEXT,
    diamonds INTEGER NOT NULL,
    points_cost INTEGER NOT NULL,
    pack_name TEXT,
    status TEXT NOT NULL DEFAULT 'Processing (7-14 Days)',
    notes TEXT,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT timezone('utc'::text, now()) NOT NULL,
    completed_at TIMESTAMP WITH TIME ZONE
);

-- 3. GIVEAWAY ENTRIES TABLE
-- Stores ticket purchase transactions and participants for Mega and Daily giveaways.
CREATE TABLE IF NOT EXISTS public.giveaway_entries (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    user_email TEXT NOT NULL,
    mlbb_id TEXT,
    mlbb_server TEXT,
    mlbb_ign TEXT,
    pool_type TEXT NOT NULL, -- 'mega' or 'daily'
    ticket_count INTEGER NOT NULL DEFAULT 1,
    points_spent INTEGER NOT NULL DEFAULT 0,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT timezone('utc'::text, now()) NOT NULL
);

-- ==============================================================================
-- INDEXES FOR HIGH-SPEED QUERYING
-- ==============================================================================
CREATE INDEX IF NOT EXISTS idx_users_email ON public.users(email);
CREATE INDEX IF NOT EXISTS idx_users_mlbb_id ON public.users(mlbb_id);
CREATE INDEX IF NOT EXISTS idx_redemptions_order_id ON public.redemptions(order_id);
CREATE INDEX IF NOT EXISTS idx_redemptions_user_email ON public.redemptions(user_email);
CREATE INDEX IF NOT EXISTS idx_redemptions_status ON public.redemptions(status);
CREATE INDEX IF NOT EXISTS idx_redemptions_created_at ON public.redemptions(created_at DESC);
CREATE INDEX IF NOT EXISTS idx_giveaway_pool_type ON public.giveaway_entries(pool_type);
CREATE INDEX IF NOT EXISTS idx_giveaway_user_email ON public.giveaway_entries(user_email);

-- ==============================================================================
-- ROW LEVEL SECURITY (RLS) POLICIES
-- Allows the mobile app client (using Supabase anon key) to read and write records.
-- ==============================================================================
ALTER TABLE public.users ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.redemptions ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.giveaway_entries ENABLE ROW LEVEL SECURITY;

-- Clean up existing policies if script is re-run
DROP POLICY IF EXISTS "Anon can select users" ON public.users;
DROP POLICY IF EXISTS "Anon can insert users" ON public.users;
DROP POLICY IF EXISTS "Anon can update users" ON public.users;

DROP POLICY IF EXISTS "Anon can select redemptions" ON public.redemptions;
DROP POLICY IF EXISTS "Anon can insert redemptions" ON public.redemptions;
DROP POLICY IF EXISTS "Anon can update redemptions" ON public.redemptions;

DROP POLICY IF EXISTS "Anon can select giveaway entries" ON public.giveaway_entries;
DROP POLICY IF EXISTS "Anon can insert giveaway entries" ON public.giveaway_entries;

-- Policies for public.users
CREATE POLICY "Anon can select users" ON public.users
    FOR SELECT TO anon, authenticated USING (true);

CREATE POLICY "Anon can insert users" ON public.users
    FOR INSERT TO anon, authenticated WITH CHECK (true);

CREATE POLICY "Anon can update users" ON public.users
    FOR UPDATE TO anon, authenticated USING (true) WITH CHECK (true);

-- Policies for public.redemptions
CREATE POLICY "Anon can select redemptions" ON public.redemptions
    FOR SELECT TO anon, authenticated USING (true);

CREATE POLICY "Anon can insert redemptions" ON public.redemptions
    FOR INSERT TO anon, authenticated WITH CHECK (true);

CREATE POLICY "Anon can update redemptions" ON public.redemptions
    FOR UPDATE TO anon, authenticated USING (true) WITH CHECK (true);

-- Policies for public.giveaway_entries
CREATE POLICY "Anon can select giveaway entries" ON public.giveaway_entries
    FOR SELECT TO anon, authenticated USING (true);

CREATE POLICY "Anon can insert giveaway entries" ON public.giveaway_entries
    FOR INSERT TO anon, authenticated WITH CHECK (true);

-- ==============================================================================
-- ADMIN CONVENIENCE VIEWS
-- Convenient dashboards for admin to inspect orders and pick giveaway winners
-- ==============================================================================

-- 1. View all pending redemptions requiring diamond top-up
CREATE OR REPLACE VIEW public.view_admin_pending_redemptions AS
SELECT 
    order_id,
    user_email,
    mlbb_id,
    mlbb_server,
    mlbb_ign,
    diamonds,
    points_cost,
    pack_name,
    status,
    created_at
FROM public.redemptions
WHERE status ILIKE '%Processing%'
ORDER BY created_at ASC;

-- 2. View user leaderboard by points and claimed diamonds
CREATE OR REPLACE VIEW public.view_user_leaderboard AS
SELECT 
    username,
    mlbb_ign,
    mlbb_id,
    mlbb_server,
    mlbb_region,
    points,
    diamonds_claimed,
    giveaway_tickets,
    created_at,
    updated_at
FROM public.users
ORDER BY points DESC;

-- 3. View giveaway entries summarized by user for transparent winner draws
CREATE OR REPLACE VIEW public.view_giveaway_participants AS
SELECT 
    pool_type,
    user_email,
    mlbb_ign,
    mlbb_id,
    mlbb_server,
    SUM(ticket_count) as total_tickets,
    SUM(points_spent) as total_points_spent,
    MAX(created_at) as last_entered_at
FROM public.giveaway_entries
GROUP BY pool_type, user_email, mlbb_ign, mlbb_id, mlbb_server
ORDER BY pool_type, total_tickets DESC;
