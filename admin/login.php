<?php
session_start();

// If already logged in, go to dashboard
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header("Location: index.php");
    exit;
}

// Adjust this path to your database connection file
require_once __DIR__ . '/../includes/db.php'; 

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (empty($username) || empty($password)) {
        $error = "Please enter both username and password.";
    } else {
        // Prepare SQL
        if ($stmt = $conn->prepare("SELECT id, username, password FROM admins WHERE username = ?")) {
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows == 1) {
                // FIX: Initialize variables to satisfy IDE warnings
                $id = 0; 
                $db_username = ''; 
                $db_password_hash = '';

                // Bind result variables
                $stmt->bind_result($id, $db_username, $db_password_hash);
                $stmt->fetch();

                // Verify Password
                if (password_verify($password, $db_password_hash)) {
                    // Password is correct: Start Session
                    session_regenerate_id(true);
                    $_SESSION['admin_logged_in'] = true;
                    $_SESSION['admin_id'] = $id;
                    $_SESSION['admin_username'] = $db_username;
                    
                    header("Location: index.php");
                    exit;
                } else {
                    $error = "Invalid password.";
                }
            } else {
                $error = "Invalid username.";
            }
            $stmt->close();
        } else {
            $error = "Database error: Could not prepare statement.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Login</title>
    <!-- Remix Icon for the Eye Toggle -->
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
    <style>
        :root {
            /* Palette */
            --pink-100: #ffe4ef;
            --pink-200: #ffb6d2;
            --pink-300: #ff82b3;
            --pink-400: #ff3f8e;

            /* Neutrals */
            --white: #ffffff;
            --off-white: #fafafa;
            --gray-100: #f0f0f0;
            --gray-200: #dcdcdc;
            --gray-300: #b3b3b3;
            --gray-400: #7a7a7a;
            --gray-500: #3d3d3d;
            --black: #1a1a1a;

            /* Actions */
            --error: #e63946;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--pink-100); /* Soft pink background */
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }

        .login-card {
            background: var(--white);
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 8px 24px rgba(255, 63, 142, 0.15); /* Pinkish shadow */
            width: 100%;
            max-width: 380px;
            border: 1px solid var(--pink-200);
        }

        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .login-header h2 {
            color: var(--black);
            margin: 0;
            font-size: 24px;
        }

        .login-header p {
            color: var(--gray-400);
            margin-top: 8px;
            font-size: 14px;
        }

        .form-group {
            margin-bottom: 20px;
            position: relative;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: var(--gray-500);
            font-size: 14px;
            font-weight: 600;
        }

        /* Input Styling */
        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 12px 15px;
            padding-right: 40px; /* Space for eye icon */
            border: 2px solid var(--gray-100);
            border-radius: 8px;
            font-size: 15px;
            box-sizing: border-box;
            transition: all 0.3s ease;
            color: var(--black);
            background: var(--off-white);
        }

        input:focus {
            outline: none;
            border-color: var(--pink-400);
            background: var(--white);
            box-shadow: 0 0 0 3px var(--pink-100);
        }

        /* Toggle Password Icon */
        .password-toggle {
            position: absolute;
            right: 15px;
            top: 38px; /* Aligns with input text */
            cursor: pointer;
            color: var(--gray-400);
            font-size: 18px;
        }
        
        .password-toggle:hover {
            color: var(--pink-400);
        }

        /* Button Styling */
        .btn-login {
            width: 100%;
            padding: 14px;
            background: var(--pink-400);
            color: var(--white);
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s;
            margin-top: 10px;
        }

        .btn-login:hover {
            background: var(--pink-300);
        }

        /* Error Message */
        .alert {
            background: #ffebee;
            color: var(--error);
            padding: 12px;
            border-radius: 6px;
            font-size: 14px;
            text-align: center;
            margin-bottom: 20px;
            border: 1px solid #ffcdd2;
        }

        .footer-links {
            text-align: center;
            margin-top: 20px;
        }

        .footer-links a {
            color: var(--gray-400);
            text-decoration: none;
            font-size: 13px;
            transition: color 0.2s;
        }

        .footer-links a:hover {
            color: var(--pink-400);
            text-decoration: underline;
        }
    </style>
</head>
<body>

    <div class="login-card">
        <div class="login-header">
            <h2>Welcome Back</h2>
            <p>Please log in to the Admin Dashboard</p>
        </div>

        <?php if($error): ?>
            <div class="alert">
                <i class="ri-error-warning-line"></i> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <form action="" method="post">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" placeholder="Enter username" required>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="Enter password" required>
                <!-- Eye Icon -->
                <i class="ri-eye-line password-toggle" id="togglePassword"></i>
            </div>

            <button type="submit" class="btn-login">Sign In</button>
        </form>
        
        <!-- <div class="footer-links">
            <a href="forgot_password.php">Forgot your password?</a>
        </div> -->
    </div>

    <script>
        const togglePassword = document.querySelector('#togglePassword');
        const password = document.querySelector('#password');

        togglePassword.addEventListener('click', function (e) {
            // Toggle the type attribute
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);
            
            // Toggle the eye icon class
            if (type === 'text') {
                this.classList.remove('ri-eye-line');
                this.classList.add('ri-eye-off-line');
            } else {
                this.classList.remove('ri-eye-off-line');
                this.classList.add('ri-eye-line');
            }
        });
    </script>
</body>
</html>