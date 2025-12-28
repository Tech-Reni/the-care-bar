<?php
// 1. Protect this page from unauthorized access
require_once __DIR__ . '/auth_session.php'; 

$page_title = 'Change Password';
// 2. Include the header
require_once __DIR__ . '/header.php';

// Variables for user feedback
$message = '';
$message_type = ''; // Will be 'success' or 'error'

// 3. Handle the form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get data from the form
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    $admin_id = $_SESSION['admin_id'];

    // --- FORM VALIDATION ---
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $message = "All fields are required.";
        $message_type = 'error';
    } elseif ($new_password !== $confirm_password) {
        $message = "The new passwords do not match.";
        $message_type = 'error';
    } elseif (strlen($new_password) < 6) {
        $message = "New password must be at least 6 characters long.";
        $message_type = 'error';
    } else {
        // --- DATABASE LOGIC ---
        // Fetch the current password from the database to verify
        $stmt = $conn->prepare("SELECT password FROM admins WHERE id = ?");
        $stmt->bind_param("i", $admin_id);
        $stmt->execute();
        $stmt->store_result();
        
        if ($stmt->num_rows == 1) {
            $db_password_hash = '';
            $stmt->bind_result($db_password_hash);
            $stmt->fetch();

            // Verify the user's "current password" input
            if (password_verify($current_password, $db_password_hash)) {
                // If correct, hash the new password
                $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
                
                // Prepare and execute the UPDATE statement
                $update_stmt = $conn->prepare("UPDATE admins SET password = ? WHERE id = ?");
                $update_stmt->bind_param("si", $new_hash, $admin_id);
                
                if ($update_stmt->execute()) {
                    $message = "Password has been updated successfully!";
                    $message_type = 'success';
                } else {
                    $message = "An error occurred while updating the password. Please try again.";
                    $message_type = 'error';
                }
                $update_stmt->close();
            } else {
                $message = "The 'Current Password' you entered is incorrect.";
                $message_type = 'error';
            }
        } else {
            $message = "Could not find your admin account. Please log out and back in.";
            $message_type = 'error';
        }
        $stmt->close();
    }
}
?>

<!-- Add custom styles for the password page -->
<style>
    /* Using your color scheme */
    .password-card {
        max-width: 600px;
        margin: 40px auto;
        background: #ffffff;
        padding: 30px 40px;
        border-radius: 12px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.07);
        border: 1px solid var(--gray-100);
    }

    .password-card h2 {
        text-align: center;
        color: var(--black);
        margin-bottom: 25px;
        font-weight: 600;
    }

    /* Feedback Message Styling */
    .message {
        padding: 12px;
        border-radius: 8px;
        text-align: center;
        margin-bottom: 20px;
        font-weight: 500;
    }

    .message.success {
        background: #d4edda; /* A soft green for success */
        color: #155724;
    }

    .message.error {
        background: var(--pink-100); /* Your pink for errors */
        color: var(--error-dark);
    }

    /* Wrapper to handle input and icon positioning */
    .password-wrapper {
        position: relative;
        width: 100%;
    }

    /* Adjust input to make room for the icon */
    .password-wrapper input {
        width: 100%;
        padding-right: 40px; /* Space for the eye icon */
        box-sizing: border-box;
    }

    /* Eye Icon Styling */
    .toggle-password {
        position: absolute;
        right: 15px;
        top: 50%;
        transform: translateY(-50%);
        cursor: pointer;
        color: var(--gray-400);
        font-size: 18px;
        z-index: 10;
        transition: color 0.3s;
    }

    .toggle-password:hover {
        color: var(--pink-400);
    }
</style>

<div class="container">
    <div class="password-card">
        <h2>Change Your Password</h2>

        <!-- Display Success/Error Messages Here -->
        <?php if (!empty($message)): ?>
            <div class="message <?php echo $message_type; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <form action="change_password.php" method="post" novalidate>
            
            <div class="form-group">
                <label for="current_password">Current Password</label>
                <div class="password-wrapper">
                    <input type="password" id="current_password" name="current_password" required class="form-control">
                    <i class="ri-eye-line toggle-password"></i>
                </div>
            </div>

            <div class="form-group">
                <label for="new_password">New Password</label>
                <div class="password-wrapper">
                    <input type="password" id="new_password" name="new_password" required class="form-control">
                    <i class="ri-eye-line toggle-password"></i>
                </div>
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirm New Password</label>
                <div class="password-wrapper">
                    <input type="password" id="confirm_password" name="confirm_password" required class="form-control">
                    <i class="ri-eye-line toggle-password"></i>
                </div>
            </div>

            <button type="submit" class="btn btn-primary" style="margin-top: 10px;">Update Password</button>
        </form>
    </div>
</div>

<!-- JavaScript to handle the toggle logic -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const toggles = document.querySelectorAll('.toggle-password');

        toggles.forEach(icon => {
            icon.addEventListener('click', function() {
                // Find the input field immediately before this icon
                const input = this.previousElementSibling;
                
                if (input) {
                    // Check current type and toggle
                    const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
                    input.setAttribute('type', type);
                    
                    // Toggle Icon Class (Open eye vs Closed eye)
                    if (type === 'text') {
                        this.classList.remove('ri-eye-line');
                        this.classList.add('ri-eye-off-line');
                    } else {
                        this.classList.remove('ri-eye-off-line');
                        this.classList.add('ri-eye-line');
                    }
                }
            }); 
        });
    });
</script>

<?php
// 4. Include the footer
require_once __DIR__ . '/footer.php';
?>