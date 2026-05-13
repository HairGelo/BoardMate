-- Run this in phpMyAdmin or MySQL CLI before using the app

CREATE DATABASE IF NOT EXISTS dormitory_db;
USE dormitory_db;

CREATE TABLE IF NOT EXISTS buildings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    address TEXT,
    num_floors INT NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS rooms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    building_id INT NOT NULL,
    floor_number INT NOT NULL,
    room_number VARCHAR(20) NOT NULL,
    room_type ENUM('single','double','studio','suite') DEFAULT 'single',
    bathroom_type ENUM('private','communal') DEFAULT 'communal',
    monthly_rate DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    max_occupants INT NOT NULL DEFAULT 1,
    status ENUM('vacant','occupied','maintenance') DEFAULT 'vacant',
    notes TEXT,
    FOREIGN KEY (building_id) REFERENCES buildings(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS tenants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100),
    phone VARCHAR(20),
    emergency_contact_name VARCHAR(100),
    emergency_contact_phone VARCHAR(20),
    id_type VARCHAR(50),
    id_number VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS leases (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT NOT NULL,
    room_id INT NOT NULL,
    move_in_date DATE NOT NULL,
    move_out_date DATE,
    status ENUM('active','ended') DEFAULT 'active',
    deposit_amount DECIMAL(10,2) DEFAULT 0.00,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    lease_id INT NOT NULL,
    billing_month DATE NOT NULL,
    amount_due DECIMAL(10,2) NOT NULL,
    amount_paid DECIMAL(10,2) DEFAULT 0.00,
    paid_at TIMESTAMP NULL,
    status ENUM('unpaid','partial','paid') DEFAULT 'unpaid',
    notes TEXT,
    FOREIGN KEY (lease_id) REFERENCES leases(id) ON DELETE CASCADE
);
