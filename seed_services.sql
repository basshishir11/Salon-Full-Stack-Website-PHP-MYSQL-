-- Seed Services for Testing

-- Insert some sample services for Men
INSERT INTO services (category_id, name, price, duration_mins, gender_type, is_active) VALUES
(1, 'Haircut', 300.00, 30, 'Men', 1),
(1, 'Beard Trim', 150.00, 15, 'Men', 1),
(1, 'Hair Color', 800.00, 60, 'Men', 1),
(1, 'Head Massage', 200.00, 20, 'Men', 1);

-- Insert some sample services for Women
INSERT INTO services (category_id, name, price, duration_mins, gender_type, is_active) VALUES
(2, 'Haircut', 500.00, 45, 'Women', 1),
(2, 'Hair Spa', 1200.00, 90, 'Women', 1),
(2, 'Facial', 1000.00, 60, 'Women', 1),
(2, 'Manicure', 400.00, 30, 'Women', 1),
(2, 'Pedicure', 500.00, 40, 'Women', 1);

-- Insert some Unisex services
INSERT INTO services (category_id, name, price, duration_mins, gender_type, is_active) VALUES
(3, 'Hair Wash', 100.00, 10, 'Unisex', 1),
(3, 'Hair Treatment', 1500.00, 90, 'Unisex', 1);
