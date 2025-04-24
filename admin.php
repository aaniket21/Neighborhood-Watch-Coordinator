<?php
session_start();
require_once "config.php";

if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== "admin"){
    header("location: index.php");
    exit;
}

// Handle report status updates
if(isset($_POST['report_id']) && isset($_POST['status'])) {
    $sql = "UPDATE crime_reports SET status = ? WHERE id = ?";
    if($stmt = mysqli_prepare($conn, $sql)){
        mysqli_stmt_bind_param($stmt, "si", $_POST['status'], $_POST['report_id']);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
}

// Handle user role updates
if(isset($_POST['user_id']) && isset($_POST['role'])) {
    $sql = "UPDATE users SET role = ? WHERE id = ?";
    if($stmt = mysqli_prepare($conn, $sql)){
        mysqli_stmt_bind_param($stmt, "si", $_POST['role'], $_POST['user_id']);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
}

// Get all crime reports
$crime_reports = [];
$sql = "SELECT cr.*, u.username 
        FROM crime_reports cr 
        JOIN users u ON cr.user_id = u.id 
        ORDER BY cr.created_at DESC";
if($result = mysqli_query($conn, $sql)){
    while($row = mysqli_fetch_assoc($result)){
        $crime_reports[] = $row;
    }
}

// Get all users
$users = [];
$sql = "SELECT * FROM users ORDER BY created_at DESC";
if($result = mysqli_query($conn, $sql)){
    while($row = mysqli_fetch_assoc($result)){
        $users[] = $row;
    }
}

// Get stats for admin dashboard
$stats = [
    'total_reports' => count($crime_reports),
    'verified_reports' => 0,
    'pending_reports' => 0,
    'rejected_reports' => 0,
    'total_users' => count($users),
    'admin_users' => 0
];

// Calculate report stats
foreach($crime_reports as $report) {
    if($report['status'] === 'verified') {
        $stats['verified_reports']++;
    } elseif($report['status'] === 'pending') {
        $stats['pending_reports']++;
    } elseif($report['status'] === 'rejected') {
        $stats['rejected_reports']++;
    }
}

// Calculate user stats
foreach($users as $user) {
    if($user['role'] === 'admin') {
        $stats['admin_users']++;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Neighborhood Watch - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            /* Modern color palette */
            --primary-color: #6366f1;
            --primary-light: #e0e7ff;
            --secondary-color: #4f46e5;
            --accent-color: #06b6d4;
            --light-color: #f8fafc;
            --dark-color: #0f172a;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --danger-color: #ef4444;
            --info-color: #3b82f6;
            --text-color: #1e293b;
            --text-light: #64748b;
            --bg-color: #f1f5f9;
            --card-bg: #ffffff;
            --sidebar-bg: #ffffff;
            --navbar-bg: linear-gradient(135deg, #6366f1, #4f46e5);
            
            /* Enhanced shadows for depth */
            --shadow-sm: 0 1px 3px rgba(0,0,0,0.12), 0 1px 2px rgba(0,0,0,0.05);
            --shadow-md: 0 4px 6px rgba(15,23,42,0.08), 0 2px 4px rgba(15,23,42,0.05);
            --shadow-lg: 0 10px 15px rgba(15,23,42,0.07), 0 5px 10px rgba(15,23,42,0.05);
            --shadow-xl: 0 20px 25px rgba(15,23,42,0.05), 0 10px 10px rgba(15,23,42,0.04);
            
            /* Smooth transitions */
            --transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
            --transition-bounce: all 0.5s cubic-bezier(0.68, -0.55, 0.27, 1.55);
            
            /* Dark mode variables */
            --dark-bg-color: #0f172a;
            --dark-card-bg: #1e293b;
            --dark-text-color: #e2e8f0;
            --dark-text-light: #94a3b8;
            --dark-border-color: #334155;
            
            /* RGB values for opacity variations */
            --primary-rgb: 99, 102, 241;
            --danger-rgb: 239, 68, 68;
            --success-rgb: 16, 185, 129;
            --warning-rgb: 245, 158, 11;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--bg-color);
            color: var(--text-color);
            line-height: 1.6;
            min-height: 100vh;
            position: relative;
            overflow-x: hidden;
            background-image: 
                radial-gradient(circle at 25% 10%, rgba(99, 102, 241, 0.05) 0%, transparent 20%),
                radial-gradient(circle at 75% 75%, rgba(79, 70, 229, 0.05) 0%, transparent 20%);
            transition: background-color 0.3s ease, color 0.3s ease;
        }
        
        body.dark-mode {
            background-color: var(--dark-bg-color);
            color: var(--dark-text-color);
            background-image: 
                radial-gradient(circle at 25% 10%, rgba(99, 102, 241, 0.1) 0%, transparent 20%),
                radial-gradient(circle at 75% 75%, rgba(79, 70, 229, 0.1) 0%, transparent 20%);
        }
        
        .animate-delay-1 {
            animation-delay: 0.2s;
        }
        
        .animate-delay-2 {
            animation-delay: 0.4s;
        }
        
        .animate-delay-3 {
            animation-delay: 0.6s;
        }
        
        /* Navbar Styles */
        .navbar {
            background: var(--navbar-bg);
            box-shadow: var(--shadow-md);
            padding: 0.8rem 0;
            position: sticky;
            top: 0;
            z-index: 1030;
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
        }
        
        .navbar-brand {
            font-weight: 700;
            font-size: 1.5rem;
            display: flex;
            align-items: center;
            color: white;
            letter-spacing: 0.5px;
            position: relative;
            overflow: hidden;
        }
        
        .navbar-brand i {
            margin-right: 10px;
            font-size: 1.8rem;
            transform: translateY(0);
            transition: transform 0.3s ease;
        }
        
        .navbar-brand:hover i {
            transform: translateY(-2px) scale(1.1);
        }
        
        .nav-link {
            font-weight: 500;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            transition: var(--transition);
            margin: 0 2px;
            position: relative;
            overflow: hidden;
        }
        
        .nav-link::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            width: 0;
            height: 2px;
            background-color: white;
            transition: width 0.3s ease, left 0.3s ease;
        }
        
        .nav-link:hover::after, .nav-link.active::after {
            width: 80%;
            left: 10%;
        }
        
        .nav-link:hover, .nav-link.active {
            background-color: rgba(255,255,255,0.15);
            transform: translateY(-2px);
        }
        
        .nav-link i {
            margin-right: 8px;
            transition: transform 0.3s ease;
        }
        
        .nav-link:hover i {
            transform: scale(1.2);
        }
        
        /* Card Styles */
        .card {
            border: none;
            border-radius: 20px;
            box-shadow: var(--shadow-sm);
            transition: var(--transition-bounce);
            margin-bottom: 1.5rem;
            overflow: hidden;
            background-color: var(--card-bg);
            border: 1px solid rgba(0,0,0,0.03);
            position: relative;
        }
        
        .dark-mode .card {
            background-color: var(--dark-card-bg);
            border-color: var(--dark-border-color);
        }
        
        .card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.1), rgba(6, 182, 212, 0.1));
            opacity: 0;
            transition: opacity 0.3s ease;
            pointer-events: none;
            z-index: 0;
        }
        
        .card:hover {
            transform: translateY(-7px);
            box-shadow: var(--shadow-xl);
        }
        
        .card:hover::before {
            opacity: 1;
        }
        
        .card-header {
            background-color: transparent;
            border-bottom: 1px solid rgba(0,0,0,0.05);
            font-weight: 600;
            padding: 1.2rem 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: relative;
            z-index: 1;
        }
        
        .dark-mode .card-header {
            border-bottom-color: var(--dark-border-color);
        }
        
        .card-header h5 {
            margin: 0;
            font-weight: 600;
            color: var(--dark-color);
            display: flex;
            align-items: center;
            position: relative;
        }
        
        .dark-mode .card-header h5 {
            color: var(--dark-text-color);
        }
        
        .card-header h5 i {
            margin-right: 12px;
            color: var(--primary-color);
            font-size: 1.2rem;
            transition: transform 0.3s ease;
        }
        
        .card:hover .card-header h5 i {
            transform: scale(1.2) rotate(5deg);
        }
        
        /* Report and User Cards */
        .report-card, .user-card {
            position: relative;
            overflow: hidden;
            border: none !important;
            transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
            margin-bottom: 1.5rem;
        }
        
        .report-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 6px;
            height: 100%;
            background: var(--primary-color);
            border-radius: 0 3px 3px 0;
        }
        
        .report-card.verified::before {
            background: var(--success-color);
            box-shadow: 0 0 15px rgba(16, 185, 129, 0.4);
        }
        
        .report-card.pending::before {
            background: var(--warning-color);
            box-shadow: 0 0 15px rgba(245, 158, 11, 0.4);
        }
        
        .report-card.rejected::before {
            background: var(--danger-color);
            box-shadow: 0 0 15px rgba(239, 68, 68, 0.4);
        }
        
        .report-card:hover, .user-card:hover {
            transform: translateY(-5px) scale(1.01);
            box-shadow: var(--shadow-lg);
        }
        
        /* User Avatar */
        .user-avatar {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-right: 12px;
            font-size: 1.1rem;
            box-shadow: var(--shadow-md);
            position: relative;
            overflow: hidden;
            transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
        }
        
        .user-avatar::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(45deg, rgba(255,255,255,0.2), rgba(255,255,255,0));
            z-index: 1;
        }
        
        .user-avatar:hover {
            transform: scale(1.1) rotate(5deg);
        }
        
        .user-avatar.small {
            width: 36px;
            height: 36px;
            font-size: 1rem;
        }
        
        .user-avatar.admin {
            background: linear-gradient(135deg, var(--warning-color), var(--danger-color));
        }
        
        /* Crime Type Badge */
        .crime-type-badge {
            font-size: 0.75rem;
            padding: 0.5em 1em;
            border-radius: 50px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            z-index: 1;
        }
        
        .crime-type-badge::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(45deg, rgba(255,255,255,0.1), rgba(255,255,255,0));
            z-index: -1;
        }
        
        .crime-type-badge:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }
        
        /* Form Controls */
        .form-select {
            border-radius: 10px;
            border: 1px solid rgba(0,0,0,0.1);
            padding: 0.5rem 2rem 0.5rem 1rem;
            font-size: 0.9rem;
            transition: var(--transition);
            background-color: var(--light-color);
            color: var(--text-color);
            box-shadow: var(--shadow-sm);
        }
        
        .dark-mode .form-select {
            background-color: var(--dark-card-bg);
            border-color: var(--dark-border-color);
            color: var(--dark-text-color);
        }
        
        .form-select:focus {
            box-shadow: 0 0 0 0.25rem rgba(var(--primary-rgb), 0.25);
            border-color: var(--primary-color);
        }
        
        .status-select {
            width: auto;
            min-width: 120px;
        }
        
        .role-select {
            width: auto;
            min-width: 100px;
        }
        
        /* Button Styles */
        .btn {
            border-radius: 10px;
            padding: 0.5rem 1.2rem;
            font-weight: 500;
            transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
            position: relative;
            overflow: hidden;
            box-shadow: var(--shadow-sm);
            letter-spacing: 0.3px;
        }
        
        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(45deg, rgba(255,255,255,0.1), rgba(255,255,255,0));
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .btn:hover::before {
            opacity: 1;
        }
        
        .btn:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-md);
        }
        
        .btn:active {
            transform: translateY(0);
        }
        
        /* Dark Mode Toggle */
        #dark-mode-toggle {
            width: 38px;
            height: 38px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: var(--transition-bounce);
            background-color: transparent;
            border: 2px solid rgba(255, 255, 255, 0.5);
        }
        
        #dark-mode-toggle:hover {
            transform: rotate(10deg) scale(1.1);
            background-color: rgba(255, 255, 255, 0.1);
        }
        
        #dark-mode-toggle i {
            font-size: 1rem;
            transition: transform 0.3s ease;
        }
        
        #dark-mode-toggle:hover i {
            transform: rotate(20deg);
        }
        
        /* Text Colors for Dark Mode */
        .dark-mode .text-muted {
            color: var(--dark-text-light) !important;
        }
        
        .dark-mode .card-title {
            color: var(--dark-text-color);
        }
        
        /* Toast Notifications */
        .toast {
            border: none;
            border-radius: 15px;
            box-shadow: var(--shadow-lg);
            overflow: hidden;
            background-color: var(--card-bg);
            opacity: 1;
        }
        
        .dark-mode .toast {
            background-color: var(--dark-card-bg);
            color: var(--dark-text-color);
        }
        
        .toast-header {
            background-color: transparent;
            border-bottom: 1px solid rgba(0,0,0,0.05);
            padding: 0.8rem 1rem;
        }
        
        .dark-mode .toast-header {
            border-bottom-color: var(--dark-border-color);
            color: var(--dark-text-color);
        }
        
        .toast-body {
            padding: 1rem;
            font-weight: 500;
        }
        
        .toast.show {
            animation: toastFadeIn 0.5s cubic-bezier(0.68, -0.55, 0.27, 1.55);
        }
        
        @keyframes toastFadeIn {
            from {
                transform: translateY(-20px) scale(0.8);
                opacity: 0;
            }
            to {
                transform: translateY(0) scale(1);
                opacity: 1;
            }
        }
        
        /* Badge Styles */
        .badge {
            padding: 0.5em 1em;
            font-weight: 600;
            letter-spacing: 0.5px;
            border-radius: 50px;
            box-shadow: var(--shadow-sm);
            transition: var(--transition);
        }
        
        .badge:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }
        
        /* Stat Cards */
        .stat-card {
            border-radius: 20px;
            padding: 1.5rem;
            transition: var(--transition-bounce);
            box-shadow: var(--shadow-md);
            border: none;
            position: relative;
            overflow: hidden;
            z-index: 1;
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(255,255,255,0.1), rgba(255,255,255,0));
            z-index: -1;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-10px);
            box-shadow: var(--shadow-xl);
        }
        
        .stat-card:hover::before {
            opacity: 1;
        }
        
        .stat-icon-container {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            background: rgba(var(--primary-rgb), 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            transition: all 0.5s cubic-bezier(0.34, 1.56, 0.64, 1);
        }
        
        .stat-icon-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle at center, rgba(255, 255, 255, 0.3) 0%, rgba(255, 255, 255, 0) 70%);
        }
        
        .stat-icon-container.primary {
            background: rgba(var(--primary-rgb), 0.2);
        }
        
        .stat-icon-container.success {
            background: rgba(var(--success-rgb), 0.2);
        }
        
        .stat-icon-container.warning {
            background: rgba(var(--warning-rgb), 0.2);
        }
        
        .stat-icon-container.info {
            background: rgba(59, 130, 246, 0.2);
        }
        
        .stat-icon-container.danger {
            background: rgba(var(--danger-rgb), 0.2);
        }
        
        .stat-card:hover .stat-icon-container {
            transform: scale(1.1) rotate(10deg);
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.15);
        }
        
        .stat-icon-container i {
            font-size: 2rem;
            position: relative;
            z-index: 1;
            transition: transform 0.5s cubic-bezier(0.34, 1.56, 0.64, 1);
        }
        
        .stat-icon-container.primary i {
            color: var(--primary-color);
        }
        
        .stat-icon-container.success i {
            color: var(--success-color);
        }
        
        .stat-icon-container.warning i {
            color: var(--warning-color);
        }
        
        .stat-icon-container.info i {
            color: var(--info-color);
        }
        
        .stat-icon-container.danger i {
            color: var(--danger-color);
        }
        
        .stat-card:hover .stat-icon-container i {
            transform: scale(1.2) rotate(10deg);
        }
        
        .stat-value {
            font-size: 2.5rem;
            font-weight: 700;
            margin: 0.5rem 0;
            color: var(--dark-color);
        }
        
        .dark-mode .stat-value {
            color: var(--dark-text-color);
        }
        
        .stat-label {
            font-size: 1rem;
            font-weight: 500;
            color: var(--text-light);
            margin: 0;
        }
        
        .dark-mode .stat-label {
            color: var(--dark-text-light);
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <!-- Toast container for notifications -->
    <div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1100">
        <div id="statusToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header">
                <i class="fas fa-bell me-2 text-primary"></i>
                <strong class="me-auto">Notification</strong>
                <small>Just now</small>
                <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body">
                Status updated successfully!
            </div>
        </div>
    </div>

    <div class="container mt-4">
        <div class="row">
            <div class="col-12 mb-4">
                <div class="d-flex justify-content-between align-items-center">
                    <h2 class="animate__animated animate__fadeIn">
                        <i class="fas fa-shield-alt text-primary me-2"></i> Admin Control Panel
                    </h2>
                    <div class="animate__animated animate__fadeIn">
                        <span class="badge bg-primary p-2">
                            <i class="fas fa-user-shield me-1"></i> Administrator
                        </span>
                    </div>
                </div>
                <p class="text-muted animate__animated animate__fadeIn animate-delay-1">
                    Manage crime reports and user accounts from this central dashboard.
                </p>
                <hr>
            </div>
            
            <!-- Stats Cards -->
            <div class="col-12 mb-4">
                <div class="row">
                    <div class="col-md-3 animate__animated animate__fadeInUp">
                        <div class="card stat-card">
                            <div class="card-body text-center">
                                <div class="stat-icon-container primary">
                                    <i class="fas fa-file-alt"></i>
                                </div>
                                <h3 class="stat-value" data-count="<?php echo $stats['total_reports']; ?>">0</h3>
                                <p class="stat-label">Total Reports</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 animate__animated animate__fadeInUp animate-delay-1">
                        <div class="card stat-card">
                            <div class="card-body text-center">
                                <div class="stat-icon-container success">
                                    <i class="fas fa-check-circle"></i>
                                </div>
                                <h3 class="stat-value" data-count="<?php echo $stats['verified_reports']; ?>">0</h3>
                                <p class="stat-label">Verified Reports</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 animate__animated animate__fadeInUp animate-delay-2">
                        <div class="card stat-card">
                            <div class="card-body text-center">
                                <div class="stat-icon-container warning">
                                    <i class="fas fa-clock"></i>
                                </div>
                                <h3 class="stat-value" data-count="<?php echo $stats['pending_reports']; ?>">0</h3>
                                <p class="stat-label">Pending Reports</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 animate__animated animate__fadeInUp animate-delay-3">
                        <div class="card stat-card">
                            <div class="card-body text-center">
                                <div class="stat-icon-container info">
                                    <i class="fas fa-users"></i>
                                </div>
                                <h3 class="stat-value" data-count="<?php echo $stats['total_users']; ?>">0</h3>
                                <p class="stat-label">Total Users</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6 animate__animated animate__fadeInLeft">
                <div class="card admin-card mb-4">
                    <div class="card-header">
                        <h5><i class="fas fa-exclamation-triangle me-2"></i>Crime Reports</h5>
                    </div>
                    <div class="card-body">
                        <?php foreach($crime_reports as $report): ?>
                        <div class="card report-card <?php echo $report['status']; ?>">
                            <div class="card-body">
                                <div class="d-flex align-items-center mb-2">
                                    <div class="user-avatar small">
                                        <?php echo strtoupper(substr($report['username'], 0, 1)); ?>
                                    </div>
                                    <h6 class="card-subtitle mb-0 text-muted">
                                        <?php echo htmlspecialchars($report['username']); ?>
                                    </h6>
                                </div>
                                <h5 class="card-title"><?php echo htmlspecialchars($report['title']); ?></h5>
                                <p class="card-text"><?php echo htmlspecialchars($report['description']); ?></p>
                                
                                <div class="d-flex flex-wrap gap-2 mb-3">
                                    <span class="crime-type-badge bg-<?php 
                                        if($report['crime_type'] == 'Theft') echo 'warning';
                                        elseif($report['crime_type'] == 'Assault') echo 'danger';
                                        elseif($report['crime_type'] == 'Vandalism') echo 'info';
                                        elseif($report['crime_type'] == 'Suspicious Activity') echo 'secondary';
                                        else echo 'primary';
                                    ?>">
                                        <?php echo htmlspecialchars($report['crime_type']); ?>
                                    </span>
                                    <span class="crime-type-badge bg-<?php 
                                        if($report['status'] == 'verified') echo 'success';
                                        elseif($report['status'] == 'rejected') echo 'danger';
                                        else echo 'warning';
                                    ?>">
                                        <?php echo ucfirst($report['status']); ?>
                                    </span>
                                </div>
                                
                                <p class="card-text">
                                    <small class="text-muted">
                                        <i class="fas fa-map-marker-alt me-1"></i> <?php echo htmlspecialchars($report['address']); ?><br>
                                        <i class="far fa-clock me-1"></i> <?php echo date('F j, Y, g:i a', strtotime($report['created_at'])); ?>
                                    </small>
                                </p>
                                
                                <div class="d-flex justify-content-end">
                                    <form method="post" class="d-inline">
                                        <input type="hidden" name="report_id" value="<?php echo $report['id']; ?>">
                                        <select name="status" class="form-select form-select-sm status-select" onchange="this.form.submit()">
                                            <option value="pending" <?php echo $report['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                            <option value="verified" <?php echo $report['status'] === 'verified' ? 'selected' : ''; ?>>Verified</option>
                                            <option value="rejected" <?php echo $report['status'] === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                                        </select>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6 animate__animated animate__fadeInRight">
                <div class="card admin-card mb-4">
                    <div class="card-header">
                        <h5><i class="fas fa-users me-2"></i>User Management</h5>
                    </div>
                    <div class="card-body">
                        <?php foreach($users as $user): ?>
                        <div class="card user-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="d-flex align-items-center">
                                        <div class="user-avatar me-3 <?php echo $user['role'] === 'admin' ? 'admin' : ''; ?>">
                                            <?php echo strtoupper(substr($user['username'], 0, 1)); ?>
                                        </div>
                                        <div>
                                            <h5 class="card-title mb-1"><?php echo htmlspecialchars($user['username']); ?></h5>
                                            <p class="card-text mb-0">
                                                <small class="text-muted">
                                                    <i class="fas fa-envelope me-1"></i> <?php echo htmlspecialchars($user['email']); ?><br>
                                                    <i class="fas fa-calendar-alt me-1"></i> Joined: <?php echo date('F j, Y', strtotime($user['created_at'])); ?>
                                                </small>
                                            </p>
                                        </div>
                                    </div>
                                    <div>
                                        <form method="post">
                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                            <select name="role" class="form-select form-select-sm role-select" onchange="this.form.submit()">
                                                <option value="user" <?php echo $user['role'] === 'user' ? 'selected' : ''; ?>>User</option>
                                                <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                            </select>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Dark mode functionality
        (function() {
            const darkModeToggle = document.getElementById('dark-mode-toggle');
            const body = document.body;
            const icon = darkModeToggle.querySelector('i');
            
            // Check for saved dark mode preference
            const isDarkMode = localStorage.getItem('darkMode') === 'true';
            
            // Apply dark mode if saved preference exists
            if (isDarkMode) {
                body.classList.add('dark-mode');
                icon.classList.remove('fa-moon');
                icon.classList.add('fa-sun');
            }
            
            // Toggle dark mode on button click
            darkModeToggle.addEventListener('click', function() {
                body.classList.toggle('dark-mode');
                
                // Toggle icon
                if (body.classList.contains('dark-mode')) {
                    icon.classList.remove('fa-moon');
                    icon.classList.add('fa-sun');
                    localStorage.setItem('darkMode', 'true');
                    
                    // Add animation to the toggle
                    darkModeToggle.classList.add('animate__animated', 'animate__rubberBand');
                    setTimeout(() => {
                        darkModeToggle.classList.remove('animate__animated', 'animate__rubberBand');
                    }, 1000);
                } else {
                    icon.classList.remove('fa-sun');
                    icon.classList.add('fa-moon');
                    localStorage.setItem('darkMode', 'false');
                    
                    // Add animation to the toggle
                    darkModeToggle.classList.add('animate__animated', 'animate__rubberBand');
                    setTimeout(() => {
                        darkModeToggle.classList.remove('animate__animated', 'animate__rubberBand');
                    }, 1000);
                }
            });
        })();
        
        // Add RGB variables for CSS usage
        (function() {
            const root = document.documentElement;
            const computedStyle = getComputedStyle(root);
            
            // Get primary color and convert to RGB
            const primaryColor = computedStyle.getPropertyValue('--primary-color').trim();
            const primaryRGB = hexToRgb(primaryColor) || '99, 102, 241';
            
            // Set the RGB variables
            root.style.setProperty('--primary-rgb', primaryRGB);
            
            // Helper function to convert hex to RGB
            function hexToRgb(hex) {
                // If hex is in the format #RRGGBB
                if (hex.startsWith('#')) {
                    hex = hex.substring(1);
                }
                
                // Convert to RGB
                const r = parseInt(hex.substring(0, 2), 16);
                const g = parseInt(hex.substring(2, 4), 16);
                const b = parseInt(hex.substring(4, 6), 16);
                
                if (isNaN(r) || isNaN(g) || isNaN(b)) {
                    return null;
                }
                
                return `${r}, ${g}, ${b}`;
            }
        })();
        
        // Add animations to cards on scroll
        document.addEventListener('DOMContentLoaded', function() {
            const reportCards = document.querySelectorAll('.report-card');
            const userCards = document.querySelectorAll('.user-card');
            
            // Add staggered animation classes to report cards
            reportCards.forEach((card, index) => {
                card.classList.add('animate__animated', 'animate__fadeInLeft');
                card.style.animationDelay = `${0.1 + (index * 0.05)}s`;
            });
            
            // Add staggered animation classes to user cards
            userCards.forEach((card, index) => {
                card.classList.add('animate__animated', 'animate__fadeInRight');
                card.style.animationDelay = `${0.1 + (index * 0.05)}s`;
            });
            
            // Animate stat counters
            animateCounters();
            
            // Function to animate counters
            function animateCounters() {
                const counters = document.querySelectorAll('.stat-value[data-count]');
                
                counters.forEach(counter => {
                    const target = parseInt(counter.getAttribute('data-count'));
                    const duration = 2000; // 2 seconds
                    const step = Math.ceil(target / (duration / 30)); // Update every 30ms
                    let current = 0;
                    
                    const updateCounter = () => {
                        current += step;
                        if (current > target) {
                            current = target;
                            clearInterval(timer);
                        }
                        counter.textContent = current;
                    };
                    
                    const timer = setInterval(updateCounter, 30);
                });
            }
            
            // Initialize toast
            const statusToast = new bootstrap.Toast(document.getElementById('statusToast'));
            
            // Function to show toast notification
            function showToast(message, type = 'success') {
                const toast = document.getElementById('statusToast');
                const toastBody = toast.querySelector('.toast-body');
                const icon = toast.querySelector('.toast-header i');
                
                // Set message
                toastBody.textContent = message;
                
                // Set icon and color based on type
                if (type === 'success') {
                    icon.className = 'fas fa-check-circle me-2 text-success';
                } else if (type === 'warning') {
                    icon.className = 'fas fa-exclamation-triangle me-2 text-warning';
                } else if (type === 'danger') {
                    icon.className = 'fas fa-times-circle me-2 text-danger';
                } else {
                    icon.className = 'fas fa-bell me-2 text-primary';
                }
                
                // Show the toast
                const bsToast = new bootstrap.Toast(toast);
                bsToast.show();
            }
            
            // Add hover effect to status selects
            const statusSelects = document.querySelectorAll('.status-select');
            statusSelects.forEach(select => {
                select.addEventListener('change', function() {
                    const option = this.options[this.selectedIndex];
                    const card = this.closest('.report-card');
                    const reportTitle = card.querySelector('.card-title').textContent;
                    
                    // Remove all status classes
                    card.classList.remove('pending', 'verified', 'rejected');
                    
                    // Add the new status class
                    card.classList.add(this.value);
                    
                    // Add animation
                    card.classList.add('animate__animated', 'animate__pulse');
                    setTimeout(() => {
                        card.classList.remove('animate__animated', 'animate__pulse');
                    }, 1000);
                    
                    // Show toast notification
                    let toastType = 'info';
                    if (this.value === 'verified') toastType = 'success';
                    if (this.value === 'rejected') toastType = 'danger';
                    if (this.value === 'pending') toastType = 'warning';
                    
                    showToast(`Report "${reportTitle}" status updated to ${this.value}`, toastType);
                });
            });
            
            // Add hover effect to role selects
            const roleSelects = document.querySelectorAll('.role-select');
            roleSelects.forEach(select => {
                select.addEventListener('change', function() {
                    const userCard = this.closest('.user-card');
                    const avatar = userCard.querySelector('.user-avatar');
                    const username = userCard.querySelector('.card-title').textContent;
                    
                    // Toggle admin class based on selection
                    if (this.value === 'admin') {
                        avatar.classList.add('admin');
                    } else {
                        avatar.classList.remove('admin');
                    }
                    
                    // Add animation
                    avatar.classList.add('animate__animated', 'animate__heartBeat');
                    setTimeout(() => {
                        avatar.classList.remove('animate__animated', 'animate__heartBeat');
                    }, 1000);
                    
                    // Show toast notification
                    const toastType = this.value === 'admin' ? 'success' : 'info';
                    showToast(`User "${username}" role updated to ${this.value}`, toastType);
                });
            });
        });
    </script>
<script>
// Enhanced User Dropdown Functionality
(function() {
    const dropdownToggle = document.getElementById('navbarDropdown');
    const dropdownMenu = dropdownToggle && dropdownToggle.nextElementSibling;

    if (dropdownToggle && dropdownMenu) {
        // Toggle dropdown on click
        dropdownToggle.addEventListener('click', function(e) {
            e.preventDefault();
            dropdownMenu.classList.toggle('show');
        });

        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!dropdownToggle.contains(e.target) && !dropdownMenu.contains(e.target)) {
                dropdownMenu.classList.remove('show');
            }
        });
    }
})();
</script>
</body>
    <?php include 'chatbot.php'; ?>
</html>