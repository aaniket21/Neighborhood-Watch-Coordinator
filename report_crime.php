<?php
session_start();
require_once "config.php";

if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: index.php");
    exit;
}

$title = $description = $crime_type = $address = "";
$title_err = $description_err = $crime_type_err = $address_err = "";
$latitude = $longitude = "";

if($_SERVER["REQUEST_METHOD"] == "POST"){
    // Validate title
    if(empty(trim($_POST["title"]))){
        $title_err = "Please enter a title.";
    } else{
        $title = trim($_POST["title"]);
    }
    
    // Validate description
    if(empty(trim($_POST["description"]))){
        $description_err = "Please enter a description.";
    } else{
        $description = trim($_POST["description"]);
    }
    
    // Validate crime type
    if(empty(trim($_POST["crime_type"]))){
        $crime_type_err = "Please select a crime type.";
    } else{
        $crime_type = trim($_POST["crime_type"]);
    }
    
    // Validate address
    if(empty(trim($_POST["address"]))){
        $address_err = "Please enter an address.";
    } else{
        $address = trim($_POST["address"]);
    }
    
    // Get coordinates
    $latitude = $_POST["latitude"];
    $longitude = $_POST["longitude"];
    
    // Check input errors before inserting in database
    if(empty($title_err) && empty($description_err) && empty($crime_type_err) && empty($address_err)){
        $sql = "INSERT INTO crime_reports (user_id, title, description, latitude, longitude, address, crime_type) VALUES (?, ?, ?, ?, ?, ?, ?)";
         
        if($stmt = mysqli_prepare($conn, $sql)){    
            mysqli_stmt_bind_param($stmt, "issssss", $param_user_id, $param_title, $param_description, $param_latitude, $param_longitude, $param_address, $param_crime_type);
            
            $param_user_id = $_SESSION["id"];
            // Debug: Check user ID
            if (!isset($_SESSION["id"])) {
                die("Error: User ID not set in session");
            }
            
            // Verify user exists in database
            $check_user = mysqli_query($conn, "SELECT id FROM users WHERE id = " . $_SESSION["id"]);
            if (!$check_user || mysqli_num_rows($check_user) === 0) {
                die("Error: User ID {$_SESSION["id"]} not found in database");
            }
            
            $param_title = $title;
            $param_description = $description;
            $param_latitude = $latitude;
            $param_longitude = $longitude;
            $param_address = $address;
            $param_crime_type = $crime_type;
            
            if(mysqli_stmt_execute($stmt)){
                header("location: dashboard.php");
            } else{
                echo "Oops! Something went wrong. Please try again later.";
            }

            mysqli_stmt_close($stmt);
        }
    }
    
    mysqli_close($conn);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Neighborhood Watch - Report Crime</title>
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
        
        /* Map Styles */
        #map {
            height: 400px;
            width: 100%;
            border-radius: 20px;
            margin-bottom: 1.5rem;
            box-shadow: var(--shadow-md);
            border: 1px solid rgba(0,0,0,0.05);
            transition: var(--transition);
            overflow: hidden;
        }
        
        .dark-mode #map {
            border-color: var(--dark-border-color);
            filter: brightness(0.85) contrast(1.1);
        }
        
        #map:hover {
            box-shadow: var(--shadow-lg);
            transform: translateY(-5px);
        }
        
        /* Card Styles */
        .card {
            border: none;
            border-radius: 20px;
            box-shadow: var(--shadow-md);
            transition: var(--transition-bounce);
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
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.05), rgba(6, 182, 212, 0.05));
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
        
        .card-body {
            padding: 2rem;
            position: relative;
            z-index: 1;
        }
        
        .card-title {
            font-weight: 700;
            color: var(--dark-color);
            margin-bottom: 1.5rem;
            font-size: 1.5rem;
            position: relative;
            display: inline-block;
        }
        
        .dark-mode .card-title {
            color: var(--dark-text-color);
        }
        
        .card-title::after {
            content: '';
            position: absolute;
            bottom: -8px;
            left: 0;
            width: 40px;
            height: 3px;
            background: var(--primary-color);
            border-radius: 3px;
            transition: width 0.3s ease;
        }
        
        .card:hover .card-title::after {
            width: 100%;
        }
        
        /* Form Controls */
        .form-label {
            font-weight: 500;
            color: var(--text-color);
            margin-bottom: 0.5rem;
            font-size: 0.95rem;
        }
        
        .dark-mode .form-label {
            color: var(--dark-text-light);
        }
        
        .form-control, .form-select {
            border-radius: 10px;
            padding: 0.75rem 1rem;
            border: 1px solid rgba(0,0,0,0.1);
            background-color: var(--light-color);
            color: var(--text-color);
            transition: var(--transition);
            box-shadow: var(--shadow-sm);
        }
        
        .dark-mode .form-control, .dark-mode .form-select {
            background-color: var(--dark-card-bg);
            border-color: var(--dark-border-color);
            color: var(--dark-text-color);
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(var(--primary-rgb), 0.25);
        }
        
        .form-control::placeholder {
            color: var(--text-light);
            opacity: 0.7;
        }
        
        .dark-mode .form-control::placeholder {
            color: var(--dark-text-light);
        }
        
        textarea.form-control {
            min-height: 120px;
            resize: vertical;
        }
        
        /* Button Styles */
        .btn {
            border-radius: 10px;
            padding: 0.75rem 1.5rem;
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
        
        .btn-secondary {
            background-color: var(--light-color);
            border: 1px solid rgba(0,0,0,0.1);
            color: var(--text-color);
        }
        
        .dark-mode .btn-secondary {
            background-color: var(--dark-card-bg);
            border-color: var(--dark-border-color);
            color: var(--dark-text-color);
        }
        
        .btn-secondary:hover {
            background-color: var(--primary-light);
            color: var(--primary-color);
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
    </style>
</head>
<body>
    <!-- Toast container for notifications -->
    <div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1100">
        <div id="locationToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header">
                <i class="fas fa-map-marker-alt me-2 text-primary"></i>
                <strong class="me-auto">Location Update</strong>
                <small>Just now</small>
                <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body">
                Location updated successfully!
            </div>
        </div>
    </div>

    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="#">
                <i class="fas fa-shield-alt"></i> Neighborhood Watch
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">
                            <i class="fas fa-home"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="report_crime.php">
                            <i class="fas fa-exclamation-triangle"></i> Report Crime
                        </a>
                    </li>
                    <?php if($_SESSION["role"] === "admin"): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="admin.php">
                            <i class="fas fa-cog"></i> Admin Panel
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item me-3">
                        <button id="dark-mode-toggle" class="btn btn-sm btn-outline-light rounded-circle" title="Toggle dark mode">
                            <i class="fas fa-moon"></i>
                        </button>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-user"></i>
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="navbarDropdown">
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

    <div class="container mt-5">
        <div class="row">
            <div class="col-md-8 mx-auto">
                <div class="card animate__animated animate__fadeIn">
                    <div class="card-body">
                        <h5 class="card-title">
                            <i class="fas fa-exclamation-triangle text-warning me-2"></i>
                            Report a Crime
                        </h5>
                        <p class="text-muted mb-4">
                            Help keep your community safe by reporting suspicious activities or crimes. Your report will be reviewed by our team.
                        </p>
                        
                        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" class="needs-validation">
                            <div class="mb-4 animate__animated animate__fadeInUp">
                                <label for="map" class="form-label">
                                    <i class="fas fa-map-marker-alt me-2 text-danger"></i>
                                    Select Location
                                </label>
                                <div id="map" class="map-container"></div>
                                <input type="hidden" name="latitude" id="latitude">
                                <input type="hidden" name="longitude" id="longitude">
                                <div class="d-flex justify-content-end mt-2">
                                    <button type="button" class="btn btn-secondary btn-sm" onclick="useCurrentLocation()">
                                        <i class="fas fa-crosshairs me-2"></i> Use Current Location
                                    </button>
                                </div>
                            </div>
                            
                            <div class="mb-4 animate__animated animate__fadeInUp animate-delay-1">
                                <label for="title" class="form-label">
                                    <i class="fas fa-heading me-2 text-primary"></i>
                                    Title
                                </label>
                                <input type="text" name="title" class="form-control <?php echo (!empty($title_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $title; ?>" placeholder="Brief title describing the incident">
                                <span class="invalid-feedback"><?php echo $title_err; ?></span>
                            </div>
                            
                            <div class="mb-4 animate__animated animate__fadeInUp animate-delay-1">
                                <label for="description" class="form-label">
                                    <i class="fas fa-align-left me-2 text-primary"></i>
                                    Description
                                </label>
                                <textarea name="description" class="form-control <?php echo (!empty($description_err)) ? 'is-invalid' : ''; ?>" rows="4" placeholder="Provide detailed information about what happened"><?php echo $description; ?></textarea>
                                <span class="invalid-feedback"><?php echo $description_err; ?></span>
                            </div>
                            
                            <div class="mb-4 animate__animated animate__fadeInUp animate-delay-2">
                                <label for="crime_type" class="form-label">
                                    <i class="fas fa-tag me-2 text-primary"></i>
                                    Crime Type
                                </label>
                                <select name="crime_type" class="form-select <?php echo (!empty($crime_type_err)) ? 'is-invalid' : ''; ?>">
                                    <option value="">Select a crime type</option>
                                    <option value="Theft" <?php echo ($crime_type == "Theft") ? "selected" : ""; ?>>Theft</option>
                                    <option value="Assault" <?php echo ($crime_type == "Assault") ? "selected" : ""; ?>>Assault</option>
                                    <option value="Vandalism" <?php echo ($crime_type == "Vandalism") ? "selected" : ""; ?>>Vandalism</option>
                                    <option value="Suspicious Activity" <?php echo ($crime_type == "Suspicious Activity") ? "selected" : ""; ?>>Suspicious Activity</option>
                                    <option value="Other" <?php echo ($crime_type == "Other") ? "selected" : ""; ?>>Other</option>
                                </select>
                                <span class="invalid-feedback"><?php echo $crime_type_err; ?></span>
                            </div>
                            
                            <div class="mb-4 animate__animated animate__fadeInUp animate-delay-2">
                                <label for="address" class="form-label">
                                    <i class="fas fa-map me-2 text-primary"></i>
                                    Address
                                </label>
                                <input type="text" name="address" class="form-control <?php echo (!empty($address_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $address; ?>" placeholder="Street address or location description">
                                <span class="invalid-feedback"><?php echo $address_err; ?></span>
                            </div>
                            
                            <div class="d-grid gap-2 animate__animated animate__fadeInUp animate-delay-3">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fas fa-paper-plane me-2"></i> Submit Report
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>
    <script>
        // Initialize map
        var map = L.map('map').setView([20.5937, 78.9629], 5); // Default to India center
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors'
        }).addTo(map);

        var marker;
        var currentLocationMarker;
        
        // Initialize toast
        const locationToast = new bootstrap.Toast(document.getElementById('locationToast'));
        
        // Function to show toast notification
        function showToast(message, type = 'success') {
            const toast = document.getElementById('locationToast');
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
                icon.className = 'fas fa-map-marker-alt me-2 text-primary';
            }
            
            // Show the toast
            const bsToast = new bootstrap.Toast(toast);
            bsToast.show();
        }

        // Handle map click
        map.on('click', function(e) {
            if (marker) {
                map.removeLayer(marker);
            }
            marker = L.marker(e.latlng, {
                icon: L.divIcon({
                    className: 'custom-marker',
                    html: '<i class="fas fa-map-marker-alt" style="color: #ef4444; font-size: 24px;"></i>',
                    iconSize: [24, 24],
                    iconAnchor: [12, 24]
                })
            }).addTo(map);
            
            document.getElementById('latitude').value = e.latlng.lat;
            document.getElementById('longitude').value = e.latlng.lng;
            
            // Show toast notification
            showToast('Location selected successfully!', 'success');
        });

        // Use current location
        function useCurrentLocation() {
            if ("geolocation" in navigator) {
                showToast('Fetching your current location...', 'info');
                
                navigator.geolocation.getCurrentPosition(function(position) {
                    var lat = position.coords.latitude;
                    var lng = position.coords.longitude;
                    
                    if (currentLocationMarker) {
                        map.removeLayer(currentLocationMarker);
                    }
                    if (marker) {
                        map.removeLayer(marker);
                    }
                    
                    currentLocationMarker = L.marker([lat, lng], {
                        icon: L.divIcon({
                            className: 'custom-marker',
                            html: '<i class="fas fa-map-marker-alt" style="color: #3b82f6; font-size: 24px;"></i>',
                            iconSize: [24, 24],
                            iconAnchor: [12, 24]
                        })
                    }).addTo(map);
                    
                    map.setView([lat, lng], 15);
                    
                    document.getElementById('latitude').value = lat;
                    document.getElementById('longitude').value = lng;
                    
                    // Show toast notification
                    showToast('Current location detected!', 'success');
                    
                    // Add pulse animation to the marker
                    const markerElement = document.querySelector('.custom-marker i');
                    if (markerElement) {
                        markerElement.classList.add('animate__animated', 'animate__heartBeat');
                        setTimeout(() => {
                            markerElement.classList.remove('animate__animated', 'animate__heartBeat');
                        }, 1000);
                    }
                    
                }, function(error) {
                    // Handle errors
                    let errorMessage = 'Unable to retrieve your location';
                    switch(error.code) {
                        case error.PERMISSION_DENIED:
                            errorMessage = "Location access was denied by your browser";
                            break;
                        case error.POSITION_UNAVAILABLE:
                            errorMessage = "Location information is unavailable";
                            break;
                        case error.TIMEOUT:
                            errorMessage = "Location request timed out";
                            break;
                        case error.UNKNOWN_ERROR:
                            errorMessage = "An unknown error occurred";
                            break;
                    }
                    showToast(errorMessage, 'danger');
                });
            } else {
                showToast("Geolocation is not supported by this browser", 'danger');
            }
        }
        
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
                    
                    // Show toast notification
                    showToast('Dark mode enabled', 'info');
                } else {
                    icon.classList.remove('fa-sun');
                    icon.classList.add('fa-moon');
                    localStorage.setItem('darkMode', 'false');
                    
                    // Add animation to the toggle
                    darkModeToggle.classList.add('animate__animated', 'animate__rubberBand');
                    setTimeout(() => {
                        darkModeToggle.classList.remove('animate__animated', 'animate__rubberBand');
                    }, 1000);
                    
                    // Show toast notification
                    showToast('Light mode enabled', 'info');
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