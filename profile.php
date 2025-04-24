<?php
session_start();
require_once "config.php";

if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: index.php");
    exit;
}

// Fetch user info
$user_id = $_SESSION["id"];
$sql = "SELECT username, email, created_at FROM users WHERE id = ?";
if($stmt = mysqli_prepare($conn, $sql)){
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    if(mysqli_stmt_execute($stmt)){
        mysqli_stmt_bind_result($stmt, $username, $email, $created_at);
        mysqli_stmt_fetch($stmt);
    }
    mysqli_stmt_close($stmt);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - Neighborhood Watch</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <?php include 'navbar.php'; ?>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card shadow-lg">
                    <div class="card-body text-center">
                        <div class="user-avatar mb-3" style="font-size:2.5rem;display:inline-block;">
                            <?php echo strtoupper(substr($username, 0, 1)); ?>
                        </div>
                        <h3 class="mb-1"><?php echo htmlspecialchars($username); ?></h3>
                        <p class="text-muted mb-2">Email: <?php echo htmlspecialchars($email); ?></p>
                        <p class="text-muted">Member since: <?php echo date('F j, Y', strtotime($created_at)); ?></p>
                        <a href="settings.php" class="btn btn-outline-primary mt-3"><i class="fas fa-cog"></i> Edit Profile</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
    <?php include 'chatbot.php'; ?>
</html>
