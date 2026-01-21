-- sql/add_indexes.sql
-- Add database indexes for performance optimization

-- Indexes for tokens table
CREATE INDEX IF NOT EXISTS idx_tokens_created_at ON tokens(created_at);
CREATE INDEX IF NOT EXISTS idx_tokens_status ON tokens(status);
CREATE INDEX IF NOT EXISTS idx_tokens_gender ON tokens(gender);
CREATE INDEX IF NOT EXISTS idx_tokens_customer_id ON tokens(customer_id);

-- Indexes for customers table
CREATE INDEX IF NOT EXISTS idx_customers_phone ON customers(phone);
CREATE INDEX IF NOT EXISTS idx_customers_referral_code ON customers(referral_code);

-- Indexes for services table
CREATE INDEX IF NOT EXISTS idx_services_is_active ON services(is_active);
CREATE INDEX IF NOT EXISTS idx_services_gender_type ON services(gender_type);

-- Indexes for rewards table
CREATE INDEX IF NOT EXISTS idx_rewards_customer_id ON rewards(customer_id);
CREATE INDEX IF NOT EXISTS idx_rewards_status ON rewards(status);

-- Indexes for referrals table
CREATE INDEX IF NOT EXISTS idx_referrals_referrer ON referrals(referrer_customer_id);
CREATE INDEX IF NOT EXISTS idx_referrals_referred ON referrals(referred_customer_id);
