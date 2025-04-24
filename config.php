<?php
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'neighborhood_watch');

// Connect to MySQL database
$conn = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD);

// Check connection
if(!$conn) {
    die("ERROR: Could not connect. " . mysqli_connect_error());
}

// Create database if not exists
$sql = "CREATE DATABASE IF NOT EXISTS " . DB_NAME;
if(mysqli_query($conn, $sql)) {
    mysqli_select_db($conn, DB_NAME);
} else {
    die("ERROR: Could not create database. " . mysqli_error($conn));
}

// Create users table
$sql = "CREATE TABLE IF NOT EXISTS users (
    id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    role ENUM('user', 'admin') DEFAULT 'user',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)";

if(!mysqli_query($conn, $sql)) {
    die("ERROR: Could not create users table. " . mysqli_error($conn));
}

// Insert default admin user if not exists
$admin_username = 'admin';
$admin_email = 'admin@example.com';
$admin_password = password_hash('admin123', PASSWORD_DEFAULT);
$check_admin_sql = "SELECT id FROM users WHERE username = '$admin_username' OR email = '$admin_email' LIMIT 1";
$result = mysqli_query($conn, $check_admin_sql);
if ($result && mysqli_num_rows($result) === 0) {
    $insert_admin_sql = "INSERT INTO users (username, password, email, role) VALUES (?, ?, ?, 'admin')";
    if ($stmt = mysqli_prepare($conn, $insert_admin_sql)) {
        mysqli_stmt_bind_param($stmt, 'sss', $admin_username, $admin_password, $admin_email);
        if (!mysqli_stmt_execute($stmt)) {
            die("ERROR: Could not insert admin user. " . mysqli_error($conn));
        }
        mysqli_stmt_close($stmt);
    }
}

// Create crime_reports table
$sql = "CREATE TABLE IF NOT EXISTS crime_reports (
    id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    title VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    latitude DECIMAL(10,8) NOT NULL,
    longitude DECIMAL(11,8) NOT NULL,
    address TEXT NOT NULL,
    crime_type VARCHAR(50) NOT NULL,
    status ENUM('pending', 'verified', 'rejected') DEFAULT 'pending',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
)";

if(!mysqli_query($conn, $sql)) {
    die("ERROR: Could not create crime_reports table. " . mysqli_error($conn));
}
?> 