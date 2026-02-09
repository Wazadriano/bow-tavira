-- PostgreSQL initialization script for Tavira BOW
-- This runs when the container is first created

-- Enable useful extensions
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";
CREATE EXTENSION IF NOT EXISTS "pg_trgm";  -- For fuzzy text search

-- Create schemas if needed
-- CREATE SCHEMA IF NOT EXISTS bow;

-- Grant privileges
GRANT ALL PRIVILEGES ON DATABASE tavira_bow TO tavira;

-- Create indexes function for full-text search (optional)
-- Can be used later for advanced search functionality

-- Log initialization
DO $$
BEGIN
    RAISE NOTICE 'Tavira BOW database initialized successfully';
END $$;
