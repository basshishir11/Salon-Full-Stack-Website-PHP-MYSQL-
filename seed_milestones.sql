-- Seed loyalty_milestones table with default milestones

INSERT IGNORE INTO loyalty_milestones (visits_required, reward_type, description, value_amount) VALUES
(5, 'Free Wash', 'Free hair wash on your 5th visit', 100.00),
(7, 'Discount', '10% off on your 7th visit', 0.00),
(10, 'Free Haircut', 'Free basic haircut on your 10th visit', 300.00),
(12, 'Premium Service', 'Free premium service on your 12th visit', 500.00);
