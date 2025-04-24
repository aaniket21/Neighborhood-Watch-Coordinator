<?php
session_start();
require_once "config.php";

if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: index.php");
    exit;
}

// Example: Fetch notifications for the user
$user_id = $_SESSION["id"];
$notifications = [];
$sql = "SELECT message, created_at FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 20";
if($stmt = mysqli_prepare($conn, $sql)){
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    if(mysqli_stmt_execute($stmt)){
        $result = mysqli_stmt_get_result($stmt);
        while($row = mysqli_fetch_assoc($result)){
            $notifications[] = $row;
        }
    }
    mysqli_stmt_close($stmt);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - Neighborhood Watch</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <?php include 'navbar.php'; ?>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow-lg">
                    <div class="card-body">
                        <h3 class="mb-3"><i class="fas fa-bell"></i> Notifications</h3>
                        <?php if(empty($notifications)): ?>
                            <p class="text-muted">No notifications found.</p>
                        <?php else: ?>
                            <ul class="list-group list-group-flush">
                                <?php foreach($notifications as $notif): ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <span><?php echo htmlspecialchars($notif['message']); ?></span>
                                        <span class="badge bg-secondary rounded-pill"><?php echo date('M j, Y H:i', strtotime($notif['created_at'])); ?></span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
    <?php include 'chatbot.php'; ?>
</html>
