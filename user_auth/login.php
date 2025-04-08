<?php
session_start();
require_once 'config.php';

$message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $ip_address = $_SERVER['REMOTE_ADDR'];

    if (empty($username) || empty($password)) {
        $message = "All fields are required";
    } else {
        // Prepare and bind
        $stmt = $conn->prepare("SELECT id, username, password FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                // Successful login
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                
                // Record successful login
                $stmt = $conn->prepare("INSERT INTO login_history (user_id, ip_address, success) VALUES (?, ?, 1)");
                $stmt->bind_param("is", $user['id'], $ip_address);
                $stmt->execute();
                
                // Redirect to homepage instead of dashboard
                header("Location: ../index.html");
                exit();
            } else {
                // Failed login attempt
                $stmt = $conn->prepare("INSERT INTO login_history (user_id, ip_address, success) VALUES (?, ?, 0)");
                $stmt->bind_param("is", $user['id'], $ip_address);
                $stmt->execute();
                
                $message = "Invalid username or password";
            }
        } else {
            $message = "Invalid username or password";
        }
        $stmt->close();
    }
}

// If we get here, there was an error
if (!empty($message)) {
    // Redirect back to login page with error message
    header("Location: ../auth/login.html?error=" . urlencode($message));
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - EverySip</title>
    <link rel="stylesheet" href="../style.css">
    <style>
        .auth-container {
            max-width: 400px;
            margin: 50px auto;
            padding: 20px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
        }
        .form-group input {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .btn-submit {
            background: #4CAF50;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            width: 100%;
        }
        .btn-submit:hover {
            background: #45a049;
        }
        .message {
            margin-bottom: 15px;
            padding: 10px;
            border-radius: 4px;
        }
        .error {
            background: #ffebee;
            color: #c62828;
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <h2>Login</h2>
        <?php if($message): ?>
            <div class="message error">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
            <div class="form-group">
                <label for="username">Username:</label>
                <input type="text" id="username" name="username" required>
            </div>
            
            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <button type="submit" class="btn-submit">Login</button>
        </form>
        
        <p style="text-align: center; margin-top: 15px;">
            Don't have an account? <a href="register.php">Register here</a>
        </p>
    </div>
</body>
</html> 