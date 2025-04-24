<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center" href="dashboard.php">
            <i class="fas fa-shield-alt me-2"></i> Neighborhood Watch
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNavDropdown" aria-controls="navbarNavDropdown" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNavDropdown">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link" href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="report_crime.php"><i class="fas fa-exclamation-triangle"></i> Report Crime</a>
                </li>
                <?php if(isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                <li class="nav-item">
                    <a class="nav-link" href="admin.php"><i class="fas fa-cog"></i> Admin Panel</a>
                </li>
                <?php endif; ?>
            </ul>
            <?php if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true): ?>
            <ul class="navbar-nav ms-auto">
                <li class="nav-item dropdown animate__animated animate__fadeInDown" style="animation-delay: 0.4s">
                    <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <div class="d-flex align-items-center">
                            <div class="user-avatar bg-light text-primary fw-bold rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 36px; height: 36px; font-size: 1.2rem;">
                                <?php echo strtoupper(substr($_SESSION["username"], 0, 1)); ?>
                            </div>
                            <span class="d-none d-sm-inline"> <?php echo htmlspecialchars($_SESSION["username"]); ?> </span>
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
            <?php else: ?>
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link" href="login.php"><i class="fas fa-sign-in-alt"></i> Login</a>
                </li>
            </ul>
            <?php endif; ?>
        </div>
    </div>
</nav>
