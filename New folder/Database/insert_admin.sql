-- Insert Admin User into HopeFinder Database
-- Run this SQL in phpMyAdmin (select hopefinder database, click SQL tab)

INSERT INTO users (fullname, email, mobile_number, password, role, status, login_attempts, locked_until) 
VALUES (
    'Yash Kumar Varshney',
    'yashgla@gmail.com',
    '7788554499',
    '$2y$10$JDJhJFuWYOMpQwO3L6i0XuqjY/8.K5K5K5K5K5K5K5K5K5K5K5K5K',  -- Replace with actual hash from create_admin.php
    'admin',
    'active',
    0,
    NULL
);

-- To verify, run:
-- SELECT * FROM users WHERE email = 'yashgla@gmail.com';

