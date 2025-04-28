<?php
session_start();
require_once "config.php";

if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: index.php");
    exit;
}

// Get user's crime reports
$user_reports = [];
$sql = "SELECT * FROM crime_reports WHERE user_id = ? ORDER BY created_at DESC";
if($stmt = mysqli_prepare($conn, $sql)){
    mysqli_stmt_bind_param($stmt, "i", $_SESSION["id"]);
    if(mysqli_stmt_execute($stmt)){
        $result = mysqli_stmt_get_result($stmt);
        while($row = mysqli_fetch_assoc($result)){
            $user_reports[] = $row;
        }
    }
    mysqli_stmt_close($stmt);
}

// Get nearby crime reports
$nearby_reports = [];
$sql = "SELECT cr.*, u.username 
        FROM crime_reports cr 
        JOIN users u ON cr.user_id = u.id 
        WHERE cr.status = 'verified'
        ORDER BY cr.created_at DESC";
if($result = mysqli_query($conn, $sql)){
    while($row = mysqli_fetch_assoc($result)){
        $nearby_reports[] = $row;
    }
}

// Get stats for dashboard
$stats = [
    'total_reports' => 0,
    'verified_reports' => 0,
    'reports_last_week' => 0
];
$sql = "SELECT 
        (SELECT COUNT(*) FROM crime_reports) as total_reports,
        (SELECT COUNT(*) FROM crime_reports WHERE status = 'verified') as verified_reports,
        (SELECT COUNT(*) FROM crime_reports WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)) as reports_last_week";
if($stmt = mysqli_prepare($conn, $sql)){
    if(mysqli_stmt_execute($stmt)){
        $result = mysqli_stmt_get_result($stmt);
        $stats = mysqli_fetch_assoc($result);
    }
    mysqli_stmt_close($stmt);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Neighborhood Watch - Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css" />
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
            --navbar-glass-bg: rgba(255, 255, 255, 0.1);
            --primary-rgb: 99, 102, 241;
            --secondary-rgb: 79, 70, 229;
            --accent-rgb: 6, 182, 212;
            
            /* Enhanced shadows for depth */
            --shadow-sm: 0 1px 3px rgba(0,0,0,0.12), 0 1px 2px rgba(0,0,0,0.05);
            --shadow-md: 0 4px 6px rgba(15,23,42,0.08), 0 2px 4px rgba(15,23,42,0.05);
            --shadow-lg: 0 10px 15px rgba(15,23,42,0.07), 0 5px 10px rgba(15,23,42,0.05);
            --shadow-xl: 0 20px 25px rgba(15,23,42,0.05), 0 10px 10px rgba(15,23,42,0.04);
            --shadow-glow: 0 0 15px rgba(99, 102, 241, 0.5);
            
            /* Smooth transitions */
            --transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
            --transition-bounce: all 0.5s cubic-bezier(0.68, -0.55, 0.27, 1.55);
            --transition-slow: all 0.6s cubic-bezier(0.25, 0.8, 0.25, 1);
            
            /* Dark mode variables (will be toggled with JS) */
            --dark-bg-color: #0f172a;
            --dark-card-bg: #1e293b;
            --dark-text-color: #e2e8f0;
            --dark-text-light: #94a3b8;
            --dark-border-color: #334155;
            --dark-navbar-glass-bg: rgba(15, 23, 42, 0.3);
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
        
        .navbar {
            background: var(--navbar-bg);
            box-shadow: var(--shadow-lg);
            padding: 0.8rem 0;
            position: sticky;
            top: 0;
            z-index: 1030;
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            transition: all 0.4s ease;
        }
        
        .navbar.scrolled {
            padding: 0.5rem 0;
            background: rgba(99, 102, 241, 0.95);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
        }
        
        .dark-mode .navbar {
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
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
            padding: 0.25rem 0.5rem;
            border-radius: 12px;
            transition: var(--transition-bounce);
        }
        
        .navbar-brand::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: var(--navbar-glass-bg);
            border-radius: 12px;
            opacity: 0;
            transition: opacity 0.3s ease;
            z-index: -1;
        }
        
        .navbar-brand:hover::before {
            opacity: 1;
        }
        
        .navbar-brand i {
            margin-right: 12px;
            font-size: 1.8rem;
            transform: translateY(0);
            transition: transform 0.4s cubic-bezier(0.68, -0.55, 0.27, 1.55);
            filter: drop-shadow(0 0 8px rgba(255, 255, 255, 0.5));
        }
        
        .navbar-brand:hover i {
            transform: translateY(-3px) scale(1.15) rotate(5deg);
        }
        
        .navbar-brand span.gradient-text {
            background: linear-gradient(to right, #fff, var(--accent-color));
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            font-weight: 700;
        }
        
        .nav-link {
            font-weight: 500;
            padding: 0.6rem 1.2rem;
            border-radius: 10px;
            transition: var(--transition-bounce);
            margin: 0 3px;
            position: relative;
            overflow: hidden;
            color: rgba(255, 255, 255, 0.9) !important;
            z-index: 1;
        }
        
        .nav-link::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: var(--navbar-glass-bg);
            border-radius: 10px;
            opacity: 0;
            transition: opacity 0.3s ease;
            z-index: -1;
        }
        
        .nav-link::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            width: 0;
            height: 3px;
            background: linear-gradient(90deg, transparent, #fff, transparent);
            transition: width 0.4s ease, left 0.4s ease;
            box-shadow: 0 0 10px rgba(255, 255, 255, 0.7);
            z-index: -1;
        }
        
        .nav-link:hover::after, .nav-link.active::after {
            width: 80%;
            left: 10%;
        }
        
        .nav-link:hover::before, .nav-link.active::before {
            opacity: 1;
        }
        
        .nav-link:hover, .nav-link.active {
            color: #fff !important;
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .nav-link i {
            margin-right: 8px;
            transition: transform 0.4s cubic-bezier(0.68, -0.55, 0.27, 1.55);
            position: relative;
            top: 1px;
        }
        
        .nav-link:hover i {
            transform: scale(1.3) rotate(5deg);
        }
        
        .nav-link.active i {
            transform: scale(1.2);
        }
        
        .navbar-toggler {
            border: none;
            background: var(--navbar-glass-bg);
            padding: 0.5rem;
            border-radius: 10px;
            transition: var(--transition);
        }
        
        .navbar-toggler:focus {
            box-shadow: 0 0 0 3px rgba(255, 255, 255, 0.25);
        }
        
        .navbar-toggler:hover {
            background: rgba(255, 255, 255, 0.2);
        }
        
        #map {
            height: 450px;
            width: 100%;
            border-radius: 20px;
            box-shadow: var(--shadow-md);
            transition: var(--transition);
            border: none;
            z-index: 1;
            position: relative;
            overflow: hidden;
        }
        
        #map::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            border-radius: 20px;
            padding: 2px;
            background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
            -webkit-mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
            -webkit-mask-composite: xor;
            mask-composite: exclude;
            opacity: 0;
            transition: opacity 0.3s ease;
            z-index: 2;
            pointer-events: none;
        }
        
        #map:hover {
            box-shadow: var(--shadow-xl);
        }
        
        #map:hover::before {
            opacity: 1;
        }
        
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
        
        .crime-card {
            position: relative;
            overflow: hidden;
            border: none !important;
            transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
        }
        
        .crime-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 6px;
            height: 100%;
            background: var(--primary-color);
            border-radius: 0 3px 3px 0;
        }
        
        .crime-card::after {
            content: '';
            position: absolute;
            top: 0;
            left: 6px;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, 
                rgba(var(--primary-rgb), 0.08) 0%, 
                rgba(var(--primary-rgb), 0.02) 50%, 
                rgba(var(--primary-rgb), 0) 100%);
            opacity: 0;
            transition: opacity 0.3s ease;
            pointer-events: none;
        }
        
        .crime-card:hover::after {
            opacity: 1;
        }
        
        .crime-card.verified::before {
            background: var(--success-color);
            box-shadow: 0 0 15px rgba(16, 185, 129, 0.4);
        }
        
        .crime-card.pending::before {
            background: var(--warning-color);
            box-shadow: 0 0 15px rgba(245, 158, 11, 0.4);
        }
        
        .crime-card.rejected::before {
            background: var(--danger-color);
            box-shadow: 0 0 15px rgba(239, 68, 68, 0.4);
        }
        
        .crime-card:hover {
            transform: translateY(-5px) scale(1.01);
            box-shadow: var(--shadow-lg);
        }
        
        .stat-card {
            text-align: center;
            padding: 2rem 1rem;
            color: white;
            border-radius: 20px;
            position: relative;
            overflow: hidden;
            transition: var(--transition-bounce);
            box-shadow: var(--shadow-md);
            border: none;
            z-index: 1;
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.1);
            opacity: 0;
            transition: opacity 0.3s ease;
            z-index: -1;
        }
        
        .stat-card::after {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 120%;
            height: 200%;
            background: linear-gradient(
                to bottom right,
                rgba(255, 255, 255, 0.3),
                rgba(255, 255, 255, 0.1)
            );
            transform: rotate(30deg);
            pointer-events: none;
            transition: transform 0.5s ease;
            z-index: -1;
        }
        
        .stat-card:hover {
            transform: translateY(-10px) scale(1.03);
            box-shadow: var(--shadow-xl);
        }
        
        .stat-card:hover::before {
            opacity: 1;
        }
        
        .stat-card:hover::after {
            transform: rotate(30deg) translate(-10%, -10%);
        }
        
        .stat-icon-container {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.2);
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
        
        .stat-card:hover .stat-icon-container {
            transform: scale(1.1) rotate(10deg);
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.15);
        }
        
        .stat-card i {
            font-size: 2.5rem;
            opacity: 0.9;
            position: relative;
            z-index: 1;
            transition: transform 0.5s cubic-bezier(0.34, 1.56, 0.64, 1);
        }
        
        .stat-card:hover i {
            transform: scale(1.2) rotate(10deg);
        }
        
        .stat-card .stat-value {
            font-size: 2.8rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            letter-spacing: 1px;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            background: rgba(255, 255, 255, 0.2);
            border-radius: 10px;
            padding: 0.2rem 0.5rem;
            display: inline-block;
            min-width: 80px;
        }
        
        .stat-card .stat-label {
            font-size: 1.1rem;
            opacity: 0.9;
            font-weight: 500;
            letter-spacing: 0.5px;
            margin-top: 0.5rem;
        }
        
        .bg-gradient-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            position: relative;
        }
        
        .bg-gradient-primary::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: url("data:image/svg+xml,%3Csvg width='100' height='100' viewBox='0 0 100 100' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M11 18c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm48 25c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm-43-7c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm63 31c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM34 90c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm56-76c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM12 86c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm28-65c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm23-11c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-6 60c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm29 22c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zM32 63c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm57-13c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-9-21c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM60 91c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM35 41c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM12 60c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2z' fill='%23ffffff' fill-opacity='0.05' fill-rule='evenodd'/%3E%3C/svg%3E");
            opacity: 0.5;
            pointer-events: none;
        }
        
        .bg-gradient-success {
            background: linear-gradient(135deg, #10b981, #059669);
            position: relative;
        }
        
        .bg-gradient-success::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: url("data:image/svg+xml,%3Csvg width='100' height='100' viewBox='0 0 100 100' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M11 18c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm48 25c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm-43-7c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm63 31c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM34 90c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm56-76c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM12 86c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm28-65c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm23-11c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-6 60c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm29 22c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zM32 63c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm57-13c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-9-21c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM60 91c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM35 41c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM12 60c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2z' fill='%23ffffff' fill-opacity='0.05' fill-rule='evenodd'/%3E%3C/svg%3E");
            opacity: 0.5;
            pointer-events: none;
        }
        
        .bg-gradient-warning {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            position: relative;
        }
        
        .bg-gradient-warning::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: url("data:image/svg+xml,%3Csvg width='100' height='100' viewBox='0 0 100 100' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M11 18c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm48 25c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm-43-7c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm63 31c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM34 90c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm56-76c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM12 86c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm28-65c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm23-11c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-6 60c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm29 22c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zM32 63c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm57-13c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-9-21c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM60 91c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM35 41c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM12 60c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2z' fill='%23ffffff' fill-opacity='0.05' fill-rule='evenodd'/%3E%3C/svg%3E");
            opacity: 0.5;
            pointer-events: none;
        }
        
        .bg-gradient-danger {
            background: linear-gradient(135deg, #ef4444, #b91c1c);
            position: relative;
        }
        
        .bg-gradient-danger::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: url("data:image/svg+xml,%3Csvg width='100' height='100' viewBox='0 0 100 100' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M11 18c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm48 25c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm-43-7c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm63 31c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM34 90c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm56-76c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM12 86c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm28-65c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm23-11c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-6 60c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm29 22c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zM32 63c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm57-13c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-9-21c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM60 91c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM35 41c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM12 60c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2z' fill='%23ffffff' fill-opacity='0.05' fill-rule='evenodd'/%3E%3C/svg%3E");
            opacity: 0.5;
            pointer-events: none;
        }
        
        .sidebar {
            height: calc(100vh - 76px);
            overflow-y: auto;
            position: sticky;
            top: 76px;
            padding-right: 15px;
            transition: transform 0.3s ease;
        }
        
        .sidebar::-webkit-scrollbar {
            width: 6px;
        }
        
        .sidebar::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }
        
        .sidebar::-webkit-scrollbar-thumb {
            background: var(--primary-color);
            border-radius: 10px;
        }
        
        .dark-mode .sidebar::-webkit-scrollbar-track {
            background: var(--dark-border-color);
        }
        
        .dark-mode .sidebar::-webkit-scrollbar-thumb {
            background: var(--primary-color);
        }
        
        /* Add a subtle hover effect to the sidebar */
        .sidebar:hover {
            transform: translateX(5px);
        }
        
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
        
        .news-card {
            border-bottom: 1px solid rgba(0,0,0,0.05);
            transition: var(--transition);
            padding: 1.2rem 0;
            position: relative;
            overflow: hidden;
        }
        
        .dark-mode .news-card {
            border-bottom-color: var(--dark-border-color);
        }
        
        .news-card:last-child {
            border-bottom: none;
        }
        
        .news-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, 
                rgba(var(--primary-rgb), 0.08) 0%, 
                rgba(var(--primary-rgb), 0.03) 50%, 
                rgba(var(--primary-rgb), 0) 100%);
            opacity: 0;
            transition: opacity 0.3s ease;
            pointer-events: none;
        }
        
        .news-card:hover {
            transform: translateX(5px);
        }
        
        .news-card:hover::before {
            opacity: 1;
        }
        
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
            transition: transform 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
            border: 2px solid rgba(255, 255, 255, 0.7);
        }
        
        .user-avatar::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(45deg, rgba(255,255,255,0.3), rgba(255,255,255,0));
            z-index: 1;
        }
        
        .user-avatar::after {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(
                to bottom right,
                rgba(255, 255, 255, 0) 0%,
                rgba(255, 255, 255, 0) 40%,
                rgba(255, 255, 255, 0.6) 50%,
                rgba(255, 255, 255, 0) 60%,
                rgba(255, 255, 255, 0) 100%
            );
            transform: rotate(45deg);
            z-index: 2;
            opacity: 0;
            transition: opacity 0.6s ease, transform 0.6s ease;
        }
        
        .user-avatar:hover::after {
            opacity: 1;
            transform: rotate(45deg) translate(50%, 50%);
        }
        
        .user-avatar:hover {
            transform: scale(1.15) rotate(5deg);
            box-shadow: 0 0 15px rgba(var(--primary-rgb), 0.5);
            border-color: white;
        }
        
        .user-avatar.small {
            width: 36px;
            height: 36px;
            font-size: 1rem;
            border-width: 1.5px;
        }
        
        .pulse {
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% {
                box-shadow: 0 0 0 0 rgba(99, 102, 241, 0.7);
            }
            70% {
                box-shadow: 0 0 0 15px rgba(99, 102, 241, 0);
            }
            100% {
                box-shadow: 0 0 0 0 rgba(99, 102, 241, 0);
            }
        }
        
        .floating-btn {
            position: fixed;
            bottom: 30px;
            right: 30px;
            z-index: 1000;
            width: 64px;
            height: 64px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            box-shadow: 0 6px 24px rgba(99, 102, 241, 0.4);
            transition: var(--transition-bounce);
            border: none;
            overflow: hidden;
        }
        
        .floating-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle at center, rgba(255,255,255,0.2) 0%, rgba(255,255,255,0) 70%);
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .floating-btn::after {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(45deg, 
                rgba(255,255,255,0) 0%, 
                rgba(255,255,255,0.1) 50%, 
                rgba(255,255,255,0) 100%);
            transform: rotate(45deg);
            animation: shimmer 3s infinite;
            z-index: 1;
        }
        
        @keyframes shimmer {
            0% {
                transform: translateX(-100%) rotate(45deg);
            }
            100% {
                transform: translateX(100%) rotate(45deg);
            }
        }
        
        .floating-btn:hover {
            transform: scale(1.15) translateY(-8px) rotate(5deg);
            box-shadow: 0 15px 35px rgba(99, 102, 241, 0.5);
            color: white;
        }
        
        .floating-btn:hover::before {
            opacity: 1;
        }
        
        .floating-btn i {
            position: relative;
            z-index: 2;
            transition: transform 0.3s ease;
        }
        
        .floating-btn:hover i {
            transform: rotate(90deg);
        }
        
        .crime-marker {
            background-color: var(--danger-color);
            width: 28px;
            height: 28px;
            border-radius: 50%;
            border: 3px solid white;
            animation: crimePulse 2s infinite;
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.4);
            position: relative;
            z-index: 1;
        }
        
        .crime-marker::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: rgba(239, 68, 68, 0.2);
            z-index: -1;
            animation: ripple 2s infinite;
        }
        
        @keyframes crimePulse {
            0% {
                transform: scale(0.8);
                opacity: 0.9;
            }
            70% {
                transform: scale(1.2);
                opacity: 0.6;
            }
            100% {
                transform: scale(0.8);
                opacity: 0.9;
            }
        }
        
        @keyframes ripple {
            0% {
                width: 0;
                height: 0;
                opacity: 0.5;
            }
            100% {
                width: 60px;
                height: 60px;
                opacity: 0;
            }
        }
        
        .loading-spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 1s cubic-bezier(0.68, -0.55, 0.27, 1.55) infinite;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            50% { transform: rotate(180deg); }
            100% { transform: rotate(360deg); }
        }
        
        .safety-tip {
            background-color: rgba(6, 182, 212, 0.08);
            border-left: 4px solid var(--accent-color);
            padding: 1.5rem;
            border-radius: 0 16px 16px 0;
            margin-bottom: 1.5rem;
            transition: var(--transition);
            position: relative;
            overflow: hidden;
            box-shadow: var(--shadow-sm);
        }
        
        .dark-mode .safety-tip {
            background-color: rgba(6, 182, 212, 0.05);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .safety-tip::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, 
                rgba(6, 182, 212, 0.1) 0%, 
                rgba(6, 182, 212, 0) 60%);
            opacity: 0;
            transition: opacity 0.3s ease;
            pointer-events: none;
        }
        
        .safety-tip:hover {
            background-color: rgba(6, 182, 212, 0.12);
            transform: translateX(8px) scale(1.02);
            box-shadow: var(--shadow-md);
        }
        
        .safety-tip:hover::before {
            opacity: 1;
        }
        
        .safety-tip h6 {
            color: var(--dark-color);
            font-weight: 600;
            margin-bottom: 0.8rem;
            display: flex;
            align-items: center;
            font-size: 1.1rem;
        }
        
        .dark-mode .safety-tip h6 {
            color: var(--dark-text-color);
        }
        
        .safety-tip h6 i {
            margin-right: 12px;
            color: var(--accent-color);
            font-size: 1.2rem;
            transition: transform 0.3s ease;
        }
        
        .safety-tip:hover h6 i {
            transform: scale(1.2) rotate(10deg);
        }
        
        .safety-tip p {
            color: var(--text-light);
            margin-bottom: 0;
            line-height: 1.6;
            font-size: 0.95rem;
        }
        
        .dark-mode .safety-tip p {
            color: var(--dark-text-light);
        }
        
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
        
        .btn-outline-primary {
            border: 2px solid var(--primary-color);
            color: var(--primary-color);
            background-color: transparent;
        }
        
        .dark-mode .btn-outline-primary {
            color: var(--primary-light);
            border-color: var(--primary-light);
        }
        
        .btn-outline-primary:hover {
            background-color: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border: none;
            color: white;
            box-shadow: 0 4px 10px rgba(99, 102, 241, 0.3);
        }
        
        .btn-primary:hover {
            background: linear-gradient(135deg, var(--secondary-color), var(--primary-color));
            box-shadow: 0 6px 15px rgba(99, 102, 241, 0.4);
        }
        
        .dark-mode .btn {
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
        }
        
        .quick-action-btn {
            padding: 0.8rem 1.2rem;
            transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
            text-align: left;
            position: relative;
            overflow: hidden;
        }
        
        .quick-action-btn:hover {
            transform: translateY(-5px) scale(1.02);
        }
        
        .quick-action-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            background: rgba(99, 102, 241, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            transition: all 0.3s ease;
        }
        
        .quick-action-icon.danger {
            background: rgba(239, 68, 68, 0.2);
        }
        
        .quick-action-icon.success {
            background: rgba(16, 185, 129, 0.2);
        }
        
        .quick-action-btn:hover .quick-action-icon {
            transform: rotate(10deg);
        }
        
        .quick-action-icon i {
            font-size: 1.2rem;
            color: var(--primary-color);
        }
        
        .quick-action-icon.danger i {
            color: var(--danger-color);
        }
        
        .quick-action-icon.success i {
            color: var(--success-color);
        }
        
        .dropdown-menu {
            border: none;
            box-shadow: var(--shadow-lg);
            border-radius: 16px;
            padding: 0.8rem;
            margin-top: 10px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            background-color: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            transform: translateY(10px);
            opacity: 0;
            visibility: hidden;
            transition: all 0.4s cubic-bezier(0.68, -0.55, 0.27, 1.55);
        }
        
        .dark-mode .dropdown-menu {
            background-color: rgba(30, 41, 59, 0.95);
            border-color: var(--dark-border-color);
        }
        
        .dropdown-menu.show {
            transform: translateY(0);
            opacity: 1;
            visibility: visible;
        }
        
        .dropdown-item {
            padding: 0.7rem 1.2rem;
            border-radius: 10px;
            font-weight: 500;
            color: var(--text-color);
            display: flex;
            align-items: center;
            transition: var(--transition);
            margin-bottom: 0.3rem;
            position: relative;
            overflow: hidden;
        }
        
        .dark-mode .dropdown-item {
            color: var(--dark-text-color);
        }
        
        .dropdown-item::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 0;
            height: 100%;
            background: linear-gradient(90deg, 
                rgba(var(--primary-rgb), 0.1) 0%, 
                rgba(var(--primary-rgb), 0.05) 50%, 
                rgba(var(--primary-rgb), 0) 100%);
            transition: width 0.3s ease;
            z-index: -1;
        }
        
        .dropdown-item:hover::before {
            width: 100%;
        }
        
        .dropdown-item i {
            margin-right: 10px;
            font-size: 1rem;
            color: var(--primary-color);
            transition: transform 0.3s ease;
        }
        
        .dropdown-item:hover {
            background-color: transparent;
            transform: translateX(5px);
        }
        
        .dropdown-item:hover i {
            transform: scale(1.2) rotate(5deg);
        }
        
        .dropdown-divider {
            border-color: rgba(0, 0, 0, 0.05);
            margin: 0.5rem 0;
        }
        
        .dark-mode .dropdown-divider {
            border-color: var(--dark-border-color);
        }
        
        /* Dark mode toggle button */
        #dark-mode-toggle {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--navbar-glass-bg);
            border: none;
            color: white;
            font-size: 1rem;
            cursor: pointer;
            transition: var(--transition-bounce);
            box-shadow: var(--shadow-sm);
            position: relative;
            overflow: hidden;
        }
        
        #dark-mode-toggle::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle at center, rgba(255,255,255,0.2) 0%, rgba(255,255,255,0) 70%);
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        #dark-mode-toggle:hover {
            transform: scale(1.15) rotate(15deg);
            box-shadow: 0 0 15px rgba(255, 255, 255, 0.3);
        }
        
        #dark-mode-toggle:hover::before {
            opacity: 1;
        }
        
        #dark-mode-toggle i {
            transition: transform 0.5s cubic-bezier(0.68, -0.55, 0.27, 1.55);
        }
        
        #dark-mode-toggle:hover i {
            transform: rotate(360deg);
        }
        
        .dark-mode #dark-mode-toggle i.fa-moon {
            display: none;
        }
        
        .dark-mode #dark-mode-toggle i.fa-sun {
            display: inline-block;
        }
        
        #dark-mode-toggle i.fa-sun {
            display: none;
        }
        
        .dropdown-item i {
            width: 20px;
            text-align: center;
            margin-right: 10px;
            color: var(--primary-color);
        }
        
        .quick-action-btn {
            padding: 0.8rem;
            border-radius: 12px;
            font-weight: 500;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: var(--transition);
        }
        
        .quick-action-btn i {
            margin-right: 10px;
            font-size: 1.2rem;
        }
        
        .crime-card .card-title {
            color: var(--dark-color);
            font-weight: 600;
            margin-bottom: 0.75rem;
        }
        
        .crime-card .card-text {
            color: var(--text-light);
            margin-bottom: 1rem;
            font-size: 0.95rem;
        }
        
        .leaflet-control-zoom {
            border: none !important;
            box-shadow: var(--shadow-md) !important;
            border-radius: 12px !important;
            overflow: hidden;
        }
        
        .leaflet-control-zoom a {
            border-radius: 0 !important;
            border: none !important;
            width: 36px !important;
            height: 36px !important;
            line-height: 36px !important;
        }
        
        .leaflet-control-zoom a:first-child {
            border-bottom: 1px solid rgba(0,0,0,0.05) !important;
        }
        
        .leaflet-popup-content {
            margin: 12px !important;
        }
        
        .leaflet-popup-content-wrapper {
            border-radius: 12px !important;
            box-shadow: var(--shadow-lg) !important;
            border: none !important;
        }
        
        .leaflet-popup-content button {
            border: none;
            background-color: var(--primary-color);
            color: white;
            padding: 0.4rem 0.8rem;
            border-radius: 8px;
            font-size: 0.9rem;
            transition: var(--transition);
        }
        
        .leaflet-popup-content button:hover {
            background-color: var(--secondary-color);
        }
        
        .leaflet-popup-tip {
            box-shadow: none !important;
        }
        
        .no-reports {
            padding: 3rem 1rem;
            text-align: center;
            background-color: rgba(75, 181, 67, 0.05);
            border-radius: 12px;
            margin: 1rem 0;
        }
        
        .no-reports i {
            font-size: 3rem;
            color: var(--success-color);
            margin-bottom: 1rem;
        }
        
        .no-reports h5 {
            color: var(--success-color);
            font-weight: 600;
        }
        
        .no-reports p {
            color: var(--text-light);
            max-width: 400px;
            margin: 0 auto;
        }
        
        /* Modern scrollbar for the entire page */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        
        ::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }
        
        ::-webkit-scrollbar-thumb {
            background: var(--primary-color);
            border-radius: 10px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: var(--secondary-color);
        }
        
        /* Responsive adjustments */
        @media (max-width: 992px) {
            .sidebar {
                height: auto;
                position: static;
                margin-top: 2rem;
                padding-right: 0;
            }
            
            .stat-card {
                margin-bottom: 1rem;
            }
        }
        
        /* Animation classes */
        .animate-delay-1 {
            animation-delay: 0.1s;
        }
        
        .animate-delay-2 {
            animation-delay: 0.2s;
        }
        
        .animate-delay-3 {
            animation-delay: 0.3s;
        }
        
        /* Custom tooltip */
        .custom-tooltip {
            position: relative;
            display: inline-block;
        }
        
        .custom-tooltip .tooltip-text {
            visibility: hidden;
            width: 120px;
            background-color: var(--dark-color);
            color: #fff;
            text-align: center;
            border-radius: 6px;
            padding: 5px;
            position: absolute;
            z-index: 1;
            bottom: 125%;
            left: 50%;
            transform: translateX(-50%);
            opacity: 0;
            transition: opacity 0.3s;
            font-size: 0.8rem;
        }
        
        .custom-tooltip:hover .tooltip-text {
            visibility: visible;
            opacity: 1;
        }
        
        /* Gradient text */
        .gradient-text {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            display: inline-block;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark sticky-top animate__animated animate__fadeIn">
        <div class="container">
            <a class="navbar-brand" href="#">
                <i class="fas fa-shield-alt"></i>
                <span>Neighborhood <span class="gradient-text">Watch</span></span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link active animate__animated animate__fadeInDown" href="dashboard.php">
                            <i class="fas fa-tachometer-alt"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link animate__animated animate__fadeInDown" style="animation-delay: 0.1s" href="report_crime.php">
                            <i class="fas fa-plus-circle"></i> Report Crime
                        </a>
                    </li>
                    <?php if($_SESSION["role"] === "admin"): ?>
                    <li class="nav-item">
                        <a class="nav-link animate__animated animate__fadeInDown" style="animation-delay: 0.2s" href="admin.php">
                            <i class="fas fa-cog"></i> Admin Panel
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item me-3 animate__animated animate__fadeInDown" style="animation-delay: 0.3s">
                        <button id="dark-mode-toggle" title="Toggle dark mode">
                            <i class="fas fa-moon"></i>
                            <i class="fas fa-sun"></i>
                        </button>
                    </li>
                    <li class="nav-item dropdown animate__animated animate__fadeInDown" style="animation-delay: 0.4s">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <div class="d-flex align-items-center">
                                <div class="user-avatar">
                                    <?php echo strtoupper(substr($_SESSION["username"], 0, 1)); ?>
                                </div>
                                <span class="d-none d-sm-inline"><?php echo htmlspecialchars($_SESSION["username"]); ?></span>
                            </div>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user"></i> Profile</a></li>
                            <li><a class="dropdown-item" href="settings.php"><i class="fas fa-cog"></i> Settings</a></li>
                            <!-- <li><a class="dropdown-item" href="notifications.php"><i class="fas fa-bell"></i> Notifications</a></li> -->
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-fluid mt-4">
        <div class="row">
            <div class="col-lg-8">
                <!-- Stats Cards -->
                <div class="row mb-4">
                    <div class="col-md-4 animate__animated animate__fadeInLeft">
                        <div class="stat-card bg-gradient-primary">
                            <div class="stat-icon-container">
                                <i class="fas fa-exclamation-circle"></i>
                            </div>
                            <div class="stat-value" data-count="<?php echo $stats['total_reports']; ?>">0</div>
                            <div class="stat-label">Total Reports</div>
                        </div>
                    </div>
                    <div class="col-md-4 animate__animated animate__fadeInUp animate-delay-1">
                        <div class="stat-card bg-gradient-success">
                            <div class="stat-icon-container">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <div class="stat-value" data-count="<?php echo $stats['verified_reports']; ?>">0</div>
                            <div class="stat-label">Verified Reports</div>
                        </div>
                    </div>
                    <div class="col-md-4 animate__animated animate__fadeInRight animate-delay-2">
                        <div class="stat-card bg-gradient-warning">
                            <div class="stat-icon-container">
                                <i class="fas fa-calendar-week"></i>
                            </div>
                            <div class="stat-value" data-count="<?php echo $stats['reports_last_week']; ?>">0</div>
                            <div class="stat-label">This Week</div>
                        </div>
                    </div>
                </div>
                
                <!-- Map Card -->
                <div class="card mb-4 animate__animated animate__fadeIn">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5><i class="fas fa-map-marked-alt"></i>Crime Heatmap</h5>
                        <div>
                            <button id="refresh-map-btn" class="btn btn-sm btn-outline-primary custom-tooltip">
                                <i class="fas fa-sync-alt"></i> Refresh
                                <span class="tooltip-text">Update your location</span>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div id="map"></div>
                    </div>
                </div>
                
                <!-- Nearby Crime Reports -->
                <div class="card animate__animated animate__fadeIn">
                    <div class="card-header">
                        <h5><i class="fas fa-bell"></i>Nearby Crime Reports</h5>
                    </div>
                    <div class="card-body">
                        <?php if(count($nearby_reports) > 0): ?>
                            <div id="nearby-reports">
                                <?php foreach($nearby_reports as $report): ?>
                                <div class="card crime-card verified mb-3 animate__animated animate__fadeInUp">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <div class="d-flex align-items-center mb-2">
                                                    <div class="user-avatar small">
                                                        <?php echo strtoupper(substr($report['username'], 0, 1)); ?>
                                                    </div>
                                                    <h6 class="card-subtitle mb-0 text-muted"><?php echo htmlspecialchars($report['username']); ?></h6>
                                                </div>
                                                <h5 class="card-title"><?php echo htmlspecialchars($report['title']); ?></h5>
                                                <p class="card-text"><?php echo htmlspecialchars($report['description']); ?></p>
                                            </div>
                                            <span class="crime-type-badge bg-danger"><?php echo htmlspecialchars($report['crime_type']); ?></span>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center mt-2">
                                            <small class="text-muted"><i class="fas fa-clock me-1"></i><?php echo date('F j, Y, g:i a', strtotime($report['created_at'])); ?></small>
                                            <button class="btn btn-sm btn-outline-primary view-on-map" 
                                                    data-lat="<?php echo $report['latitude']; ?>" 
                                                    data-lng="<?php echo $report['longitude']; ?>">
                                                <i class="fas fa-map-marker-alt"></i> View on Map
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="no-reports animate__animated animate__fadeIn">
                                <i class="fas fa-check-circle"></i>
                                <h5>No Recent Crime Reports in Your Area</h5>
                                <p>Your neighborhood appears to be safe at the moment. Stay vigilant and report any suspicious activities.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Sidebar -->
            <div class="col-lg-4">
                <div class="sidebar">
                    <!-- Safety Tips -->
                    <div class="card animate__animated animate__fadeIn">
                        <div class="card-header">
                            <h5><i class="fas fa-lightbulb"></i>Safety Tips</h5>
                        </div>
                        <div class="card-body">
                            <div class="safety-tip">
                                <h6><i class="fas fa-home"></i>Home Security</h6>
                                <p>Always lock doors and windows when leaving home. Consider installing a security system with cameras and motion sensors.</p>
                            </div>
                            <div class="safety-tip">
                                <h6><i class="fas fa-walking"></i>Walking at Night</h6>
                                <p>Stay in well-lit areas and be aware of your surroundings. Walk with confidence and avoid distractions like phones.</p>
                            </div>
                            <div class="safety-tip">
                                <h6><i class="fas fa-car"></i>Vehicle Safety</h6>
                                <p>Never leave valuables in plain sight. Always lock your car doors and park in well-lit areas when possible.</p>
                            </div>
                            <!-- <div class="text-center mt-3">
                                <button class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-plus me-1"></i> More Tips
                                </button>
                            </div> -->
                        </div>
                    </div>
                    
                    <!-- Local News -->
                    <div class="card animate__animated animate__fadeIn">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5><i class="fas fa-newspaper"></i> News</h5>
                            <button id="refresh-news-btn" class="btn btn-sm btn-outline-primary">
                                <span id="refresh-icon"><i class="fas fa-sync-alt"></i></span> Refresh
                            </button>
                        </div>
                        <div class="card-body p-0">
                            <div id="news-container" class="p-3">
                                <div class="text-center py-3">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                    <p class="mt-2">Loading news...</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Quick Actions -->
                    <!-- <div class="card animate__animated animate__fadeIn">
                        <div class="card-header">
                            <h5><i class="fas fa-bolt"></i>Quick Actions</h5>
                        </div>
                        <div class="card-body">
                            <div class="d-grid gap-3">
                                <button class="btn btn-primary quick-action-btn animate__animated animate__fadeInUp">
                                    <div class="d-flex align-items-center">
                                        <div class="quick-action-icon">
                                            <i class="fas fa-phone-alt"></i>
                                        </div>
                                        <span>Emergency Contacts</span>
                                    </div>
                                </button>
                                <button class="btn btn-outline-danger quick-action-btn animate__animated animate__fadeInUp animate-delay-1">
                                    <div class="d-flex align-items-center">
                                        <div class="quick-action-icon danger">
                                            <i class="fas fa-exclamation-triangle"></i>
                                        </div>
                                        <span>Report Emergency</span>
                                    </div>
                                </button>
                                <button class="btn btn-outline-success quick-action-btn animate__animated animate__fadeInUp animate-delay-2">
                                    <div class="d-flex align-items-center">
                                        <div class="quick-action-icon success">
                                            <i class="fas fa-users"></i>
                                        </div>
                                        <span>Community Groups</span>
                                    </div>
                                </button>
                            </div>
                        </div>
                    </div> -->
                </div>
            </div>
        </div>
    </div>

    <!-- Floating Action Button -->
    <a href="report_crime.php" class="floating-btn pulse animate__animated animate__bounceIn">
        <i class="fas fa-plus"></i>
    </a>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Initialize map with more options
        var map = L.map('map', {
            zoomControl: false,
            preferCanvas: true,
            fadeAnimation: true,
            zoomAnimation: true
        }).setView([20.5937, 78.9629], 5);
        
        // Add zoom control with better position
        L.control.zoom({
            position: 'topright'
        }).addTo(map);
        
        // Add tile layer with better rendering
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
            maxZoom: 19,
            detectRetina: true
        }).addTo(map);
        
        // Add scale control
        L.control.scale({
            position: 'bottomleft',
            metric: true,
            imperial: false
        }).addTo(map);

        // Function to get query parameters
        function getQueryParam(param) {
            const urlParams = new URLSearchParams(window.location.search);
            return urlParams.get(param);
        }

        // Custom crime markers with better styling
        function createCrimeMarker(lat, lng, title, type) {
            // Different colors for different crime types
            const crimeColors = {
                'theft': '#f94144',
                'burglary': '#f8961e',
                'assault': '#f3722c',
                'vandalism': '#577590',
                'other': '#6a4c93'
            };
            
            const color = crimeColors[type.toLowerCase()] || crimeColors['other'];
            
            var marker = L.marker([lat, lng], {
                icon: L.divIcon({
                    className: 'crime-marker',
                    html: `<div style="background-color: ${color}; width: 100%; height: 100%; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: 10px; font-weight: bold;">${type.charAt(0).toUpperCase()}</div>`,
                    iconSize: [24, 24]
                })
            }).addTo(map);
            
            marker.bindPopup(`
                <div style="min-width: 220px;">
                    <h6 style="margin: 0 0 8px 0; color: ${color}; font-weight: 600;">${title}</h6>
                    <div style="display: flex; align-items: center; margin-bottom: 8px;">
                        <span style="display: inline-block; width: 12px; height: 12px; background-color: ${color}; border-radius: 2px; margin-right: 8px;"></span>
                        <span style="font-size: 0.85rem; font-weight: 500;">${type}</span>
                    </div>
                    <button class="btn btn-sm btn-primary w-100" onclick="focusOnMarker(${lat}, ${lng})" style="background-color: ${color}; border-color: ${color};">
                        <i class="fas fa-search-location"></i> Focus on Map
                    </button>
                </div>
            `);
            
            return marker;
        }

        // Focus on marker function with smooth animation
        function focusOnMarker(lat, lng) {
            map.flyTo([lat, lng], 16, {
                duration: 1,
                easeLinearity: 0.25
            });
        }

        // View on map button handler
        document.querySelectorAll('.view-on-map').forEach(button => {
            button.addEventListener('click', function() {
                const lat = parseFloat(this.getAttribute('data-lat'));
                const lng = parseFloat(this.getAttribute('data-lng'));
                focusOnMarker(lat, lng);
                
                // Add a pulsing circle to highlight the location
                const highlight = L.circle([lat, lng], {
                    color: '#4361ee',
                    fillColor: '#4361ee',
                    fillOpacity: 0.2,
                    radius: 100
                }).addTo(map);
                
                // Remove the highlight after 3 seconds
                setTimeout(() => {
                    map.removeLayer(highlight);
                }, 3000);
            });
        });

        // Only request geolocation and redirect if lat/lng are not in the URL
        var urlLat = getQueryParam('lat');
        var urlLng = getQueryParam('lng');

        if (!urlLat || !urlLng) {
            if ("geolocation" in navigator) {
                // Show loading state for map
                document.getElementById('map').innerHTML = `
                    <div style="display: flex; justify-content: center; align-items: center; height: 100%; background-color: #f8f9fa; border-radius: 16px;">
                        <div class="text-center">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <p class="mt-2">Detecting your location...</p>
                        </div>
                    </div>
                `;
                
                navigator.geolocation.getCurrentPosition(function(position) {
                    var lat = position.coords.latitude;
                    var lng = position.coords.longitude;
                    // Redirect with user's location
                    window.location.href = `dashboard.php?lat=${lat}&lng=${lng}`;
                }, function(error) {
                    console.error("Geolocation error:", error);
                    // Default to a known location if geolocation fails
                    window.location.href = `dashboard.php?lat=20.5937&lng=78.9629`;
                });
            } else {
                // Default to a known location if geolocation is not supported
                window.location.href = `dashboard.php?lat=20.5937&lng=78.9629`;
            }
        } else {
            // If lat/lng are present in URL, set the map view and add user marker
            var lat = parseFloat(urlLat);
            var lng = parseFloat(urlLng);
            map.setView([lat, lng], 13);
            
            // Add user marker with custom icon
            L.marker([lat, lng], {
                icon: L.divIcon({
                    className: 'pulse',
                    html: '<i class="fas fa-user" style="color: #4361ee; font-size: 24px;"></i>',
                    iconSize: [24, 24],
                    iconAnchor: [12, 12]
                })
            }).addTo(map)
                .bindPopup('<div style="text-align: center;"><strong>Your Location</strong><br><small>We use this to show nearby reports</small></div>')
                .openPopup();
            
            // Add circle around user location to show radius
            L.circle([lat, lng], {
                color: '#4361ee',
                fillColor: '#4361ee',
                fillOpacity: 0.1,
                radius: 10000 // 10km in meters
            }).addTo(map);
            
            // Add a subtle grid pattern to the circle
            L.circle([lat, lng], {
                color: '#4361ee',
                fillColor: 'transparent',
                fillOpacity: 0,
                radius: 10000,
                dashArray: '10, 10'
            }).addTo(map);
        }

        // Add markers for crime reports with custom icons
        <?php foreach($nearby_reports as $report): ?>
        createCrimeMarker(
            <?php echo $report['latitude']; ?>, 
            <?php echo $report['longitude']; ?>, 
            '<?php echo htmlspecialchars($report['title']); ?>',
            '<?php echo htmlspecialchars($report['crime_type']); ?>'
        );
        <?php endforeach; ?>

        // Refresh map button
        document.getElementById('refresh-map-btn').addEventListener('click', function() {
            if ("geolocation" in navigator) {
                // Show loading state
                const btn = this;
                const originalHtml = btn.innerHTML;
                btn.innerHTML = '<span class="loading-spinner"></span> Updating...';
                btn.disabled = true;
                
                navigator.geolocation.getCurrentPosition(function(position) {
                    var lat = position.coords.latitude;
                    var lng = position.coords.longitude;
                    window.location.href = `dashboard.php?lat=${lat}&lng=${lng}`;
                }, function(error) {
                    console.error("Geolocation error:", error);
                    btn.innerHTML = originalHtml;
                    btn.disabled = false;
                    
                    // Show error toast
                    showToast('Failed to update location. Please try again.', 'danger');
                });
            }
        });

        // Enhanced news fetching with loading states
        async function fetchNews(city) {
            try {
                const newsContainer = document.getElementById('news-container');
                const refreshIcon = document.getElementById('refresh-icon');
                
                // Show loading state
                newsContainer.innerHTML = `
                    <div class="text-center py-3">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2">Loading news...</p>
                    </div>
                `;
                
                refreshIcon.innerHTML = '<span class="loading-spinner"></span>';
                
                // Simulate API delay for demo purposes
                await new Promise(resolve => setTimeout(resolve, 1000));
                
                const url = city ? `news.php?city=${encodeURIComponent(city)}` : 'news.php';
                const response = await fetch(url);
                
                if (!response.ok) throw new Error('Network response was not ok');
                
                const data = await response.json();
                newsContainer.innerHTML = '';
                
                if (data.articles && data.articles.length > 0) {
                    data.articles.forEach((article, index) => {
                        const newsCard = document.createElement('div');
                        newsCard.className = `card news-card animate__animated animate__fadeInUp`;
                        newsCard.style.animationDelay = `${index * 0.1}s`;
                        
                        // Format the date nicely
                        const pubDate = new Date(article.pubDate);
                        const formattedDate = pubDate.toLocaleDateString('en-US', {
                            month: 'short',
                            day: 'numeric',
                            hour: '2-digit',
                            minute: '2-digit'
                        });
                        
                        newsCard.innerHTML = `
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start">
                                    <h6 class="card-title flex-grow-1" style="font-weight: 600;">${article.title}</h6>
                                    <small class="text-muted ms-2">${formattedDate}</small>
                                </div>
                                <p class="card-text text-muted" style="font-size: 0.9rem;">${truncateText(article.description || 'No description available', 120)}</p>
                                <div class="d-flex justify-content-between align-items-center">
                                    <a href="${article.link}" target="_blank" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-external-link-alt me-1"></i> Read More
                                    </a>
                                    <small class="text-muted" style="font-size: 0.8rem;">${article.source || ''}</small>
                                </div>
                            </div>
                        `;
                        newsContainer.appendChild(newsCard);
                    });
                } else {
                    newsContainer.innerHTML = `
                        <div class="text-center py-4">
                            <i class="fas fa-newspaper fa-3x text-muted mb-3" style="opacity: 0.5;"></i>
                            <h5 style="color: var(--text-light);">No Recent News</h5>
                            <p class="text-muted">Check back later for updates</p>
                        </div>
                    `;
                }
                
                refreshIcon.innerHTML = '<i class="fas fa-sync-alt"></i>';
                
            } catch (error) {
                console.error('Error fetching news:', error);
                document.getElementById('news-container').innerHTML = `
                    <div class="text-center py-4">
                        <i class="fas fa-exclamation-triangle fa-3x text-danger mb-3"></i>
                        <h5>Failed to Load News</h5>
                        <p class="text-muted">Please try again later</p>
                        <button class="btn btn-sm btn-primary" onclick="fetchNews('${city || ''}')">
                            <i class="fas fa-sync-alt me-1"></i> Retry
                        </button>
                    </div>
                `;
                document.getElementById('refresh-icon').innerHTML = '<i class="fas fa-sync-alt"></i>';
            }
        }

        // Helper to truncate text
        function truncateText(text, maxLength) {
            if (text.length <= maxLength) return text;
            return text.substring(0, maxLength) + '...';
        }

        // Helper to get city from lat/lng using Nominatim
        async function getCityFromLatLng(lat, lng) {
            try {
                const response = await fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}`);
                const data = await response.json();
                if (data.address && (data.address.city || data.address.town || data.address.village)) {
                    return data.address.city || data.address.town || data.address.village;
                } else if (data.address && data.address.state) {
                    return data.address.state;
                } else {
                    return 'India'; // fallback
                }
            } catch (e) {
                return 'India';
            }
        }

        // Show toast notification
        function showToast(message, type = 'success') {
            const toast = document.createElement('div');
            toast.className = `toast show position-fixed bottom-0 end-0 mb-4 me-4`;
            toast.style.zIndex = '1100';
            toast.innerHTML = `
                <div class="toast-header bg-${type} text-white">
                    <strong class="me-auto">Notification</strong>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast"></button>
                </div>
                <div class="toast-body">
                    ${message}
                </div>
            `;
            
            document.body.appendChild(toast);
            
            setTimeout(() => {
                toast.classList.remove('show');
                setTimeout(() => {
                    toast.remove();
                }, 300);
            }, 3000);
        }

        // On page load, try to get user's city and fetch news
        (async function() {
            var urlLat = getQueryParam('lat');
            var urlLng = getQueryParam('lng');
            if (urlLat && urlLng) {
                const city = await getCityFromLatLng(urlLat, urlLng);
                fetchNews(city);
            } else {
                fetchNews('India');
            }
            
            // Initialize safety tips carousel
            initSafetyTips();
            
            // Initialize charts
            initCharts();
        })();

        // Add refresh button handler
        document.getElementById('refresh-news-btn').addEventListener('click', async function() {
            var urlLat = getQueryParam('lat');
            var urlLng = getQueryParam('lng');
            let city = 'India';
            if (urlLat && urlLng) {
                city = await getCityFromLatLng(urlLat, urlLng);
            }
            fetchNews(city);
        });

        // Initialize safety tips carousel
        function initSafetyTips() {
            const tips = document.querySelectorAll('.safety-tip');
            let currentTip = 0;
            
            // Show first tip initially
            if (tips.length > 0) {
                tips[currentTip].classList.add('animate__animated', 'animate__fadeIn');
            }
            
            // Rotate tips every 10 seconds
            setInterval(() => {
                if (tips.length > 0) {
                    tips[currentTip].classList.remove('animate__fadeIn');
                    currentTip = (currentTip + 1) % tips.length;
                    tips[currentTip].classList.add('animate__animated', 'animate__fadeIn');
                }
            }, 10000);
        }

        // Initialize charts
        function initCharts() {
            // This would be implemented with Chart.js for crime statistics
            // Placeholder for future implementation
        }
        
        // Animate stat counters
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
        
        // Call the counter animation when the page loads
        document.addEventListener('DOMContentLoaded', function() {
            animateCounters();
            
            // Add scroll effect for navbar
            const navbar = document.querySelector('.navbar');
            window.addEventListener('scroll', function() {
                if (window.scrollY > 50) {
                    navbar.classList.add('scrolled');
                } else {
                    navbar.classList.remove('scrolled');
                }
            });
        });
        
        // Dark mode functionality
        (function() {
            const darkModeToggle = document.getElementById('dark-mode-toggle');
            const body = document.body;
            
            // Check for saved dark mode preference
            const isDarkMode = localStorage.getItem('darkMode') === 'true';
            
            // Apply dark mode if saved preference exists
            if (isDarkMode) {
                body.classList.add('dark-mode');
            }
            
            // Toggle dark mode on button click
            darkModeToggle.addEventListener('click', function() {
                // Add animation to the toggle
                darkModeToggle.classList.add('animate__animated', 'animate__rubberBand');
                
                // Toggle dark mode
                body.classList.toggle('dark-mode');
                
                // Save preference
                localStorage.setItem('darkMode', body.classList.contains('dark-mode') ? 'true' : 'false');
                
                // Remove animation class after animation completes
                setTimeout(() => {
                    darkModeToggle.classList.remove('animate__animated', 'animate__rubberBand');
                }, 1000);
                
                // Show toast notification
                const mode = body.classList.contains('dark-mode') ? 'Dark' : 'Light';
                showToast(`${mode} mode enabled`, 'info');
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