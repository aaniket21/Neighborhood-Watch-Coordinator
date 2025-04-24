<?php
session_start();
require_once "config.php";

if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: index.php");
    exit;
}

// Fetch user info
$user_id = $_SESSION["id"];
$sql = "SELECT username, email FROM users WHERE id = ?";
if($stmt = mysqli_prepare($conn, $sql)){
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    if(mysqli_stmt_execute($stmt)){
        mysqli_stmt_bind_result($stmt, $username, $email);
        mysqli_stmt_fetch($stmt);
    }
    mysqli_stmt_close($stmt);
}

// Handle update
$success = $error = "";
if($_SERVER["REQUEST_METHOD"] == "POST"){
    $new_email = trim($_POST["email"]);
    if(filter_var($new_email, FILTER_VALIDATE_EMAIL)){
        $sql = "UPDATE users SET email = ? WHERE id = ?";
        if($stmt = mysqli_prepare($conn, $sql)){
            mysqli_stmt_bind_param($stmt, "si", $new_email, $user_id);
            if(mysqli_stmt_execute($stmt)){
                $success = "Email updated successfully.";
                $email = $new_email;
            } else {
                $error = "Failed to update email.";
            }
            mysqli_stmt_close($stmt);
        }
    } else {
        $error = "Invalid email address.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Neighborhood Watch</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <?php include 'navbar.php'; ?>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card shadow-lg">
                    <div class="card-body">
                        <h3 class="mb-3">Account Settings</h3>
                        <?php if($success): ?>
                            <div class="alert alert-success animate__animated animate__fadeInDown"><?php echo $success; ?></div>
                        <?php endif; ?>
                        <?php if($error): ?>
                            <div class="alert alert-danger animate__animated animate__shakeX"><?php echo $error; ?></div>
                        <?php endif; ?>
                        <form method="post">
                            <div class="mb-3">
                                <label for="email" class="form-label">Email address</label>
                                <input type="email" name="email" id="email" class="form-control" value="<?php echo htmlspecialchars($email); ?>" required>
                            </div>
                            <button type="submit" class="btn btn-primary">Update Email</button>
                        </form>
                        <hr>
                        <a href="profile.php" class="btn btn-outline-secondary mt-2"><i class="fas fa-arrow-left"></i> Back to Profile</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
    <?php include 'chatbot.php'; ?>
</html>
