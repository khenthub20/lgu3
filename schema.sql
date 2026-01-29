-- Database: lgu3_db

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL, -- In a real app, store hashed passwords (e.g., bcrypt)
    role ENUM('admin', 'user') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Default Admin Account
-- Email: admin@lgu3.gov (derived from request context, adjusted for valid email format)
-- Pass: admin123
INSERT INTO
    users (
        full_name,
        email,
        password,
        role
    )
VALUES (
        'System Admin',
        'admin@lgu3.gov',
        'admin123',
        'admin'
    );

-- Example content table for the dashboard
CREATE TABLE reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    title VARCHAR(200),
    status ENUM(
        'pending',
        'approved',
        'rejected'
    ) DEFAULT 'pending',
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users (id)
);