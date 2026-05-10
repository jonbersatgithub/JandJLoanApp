-- Create database
CREATE DATABASE IF NOT EXISTS loan_management;
USE loan_management;

-- Users table for authentication
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    role ENUM('admin', 'manager', 'staff') DEFAULT 'staff',
    is_active BOOLEAN DEFAULT TRUE,
    last_login DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_username (username),
    INDEX idx_email (email)
);

-- Loans table
CREATE TABLE IF NOT EXISTS loans (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    borrower_name VARCHAR(100) NOT NULL,
    loan_amount DECIMAL(10,2) NOT NULL,
    interest_rate DECIMAL(5,2) NOT NULL,
    loan_term INT NOT NULL COMMENT 'in months',
    loan_type ENUM('add_on', 'flat') DEFAULT 'add_on',
    monthly_payment DECIMAL(10,2),
    status ENUM('active', 'paid', 'defaulted') DEFAULT 'active',
    start_date DATE NOT NULL,
    due_date DATE,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id),
    INDEX idx_user_id (user_id),
    INDEX idx_status (status)
);

-- Payments table
CREATE TABLE IF NOT EXISTS payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    loan_id INT NOT NULL,
    payment_amount DECIMAL(10,2) NOT NULL,
    payment_date DATE NOT NULL,
    remaining_balance DECIMAL(10,2),
    received_by INT,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (loan_id) REFERENCES loans(id) ON DELETE CASCADE,
    FOREIGN KEY (received_by) REFERENCES users(id),
    INDEX idx_loan_id (loan_id),
    INDEX idx_payment_date (payment_date)
);

-- Activity logs table
CREATE TABLE IF NOT EXISTS activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    action VARCHAR(100),
    description TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    INDEX idx_user_id (user_id),
    INDEX idx_created_at (created_at)
);

-- Insert sample admin user (password: Admin@123)
INSERT INTO users (username, email, password_hash, full_name, role) 
VALUES ('admin', 'admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator', 'admin');

-- Insert sample manager (password: Manager@123)
INSERT INTO users (username, email, password_hash, full_name, role) 
VALUES ('manager', 'manager@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Loan Manager', 'manager');

-- Insert sample loans with user_id
INSERT INTO loans (user_id, borrower_name, loan_amount, interest_rate, loan_term, loan_type, monthly_payment, start_date, due_date, created_by) 
VALUES 
(1, 'John Doe', 10000, 5.5, 12, 'add_on', 857.00, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 12 MONTH), 1),
(1, 'Jane Smith', 25000, 4.5, 24, 'flat', 1092.00, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 24 MONTH), 1);