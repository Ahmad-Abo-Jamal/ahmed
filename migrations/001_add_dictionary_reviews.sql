-- Migration: Add dictionary reviews support
-- This migration adds 'dictionary' to the reviewable_type enum to support dictionary entries reviews

ALTER TABLE reviews MODIFY reviewable_type ENUM('product', 'article', 'service', 'influencer', 'dictionary') NOT NULL;
