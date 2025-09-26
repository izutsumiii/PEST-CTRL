<?php
require_once 'includes/header.php';
require_once 'includes/functions.php';
require_once 'config/database.php';

requireLogin();

$userId = $_SESSION['user_id'];

// Get user info
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Handle form submission
if (isset($_POST['update_profile'])) {
    $firstName = sanitizeInput($_POST['first_name']);
    $lastName = sanitizeInput($_POST['last_name']);
    $email = sanitizeInput($_POST['email']);
    $phone = sanitizeInput($_POST['phone']);
    $address = sanitizeInput($_POST['address']);
    
    // Check if email already exists (excluding current user)
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND id != ?");
    $stmt->execute([$email, $userId]);
    $existingUser = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existingUser) {
        echo "<p>Error: Email already exists.</p>";
    } else {
        $stmt = $pdo->prepare("UPDATE users SET first_name = ?, last_name = ?, email = ?, phone = ?, address = ? WHERE id = ?");
        $stmt->execute([$firstName, $lastName, $email, $phone, $address, $userId]);
        
        echo "<p>Profile updated successfully!</p>";
        header("Refresh:2");
    }
}

if (isset($_POST['change_password'])) {
    $currentPassword = $_POST['current_password'];
    $newPassword = $_POST['new_password'];
    $confirmPassword = $_POST['confirm_password'];
    
    // Verify current password
    if (password_verify($currentPassword, $user['password'])) {
        if ($newPassword === $confirmPassword) {
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$hashedPassword, $userId]);
            
            echo "<p>Password changed successfully!</p>";
        } else {
            echo "<p>Error: New passwords do not match.</p>";
        }
    } else {
        echo "<p>Error: Current password is incorrect.</p>";
    }
}
?>

<main>
<link href="assets/css/pest-ctrl.css?v=<?php echo time(); ?>" rel="stylesheet">
<div class="profile-editor">
    <div class="personal-info">
        <h2>Personal Information</h2>
        <form method="POST" action="">
            <div>
                <label for="first_name">First Name:</label>
                <input type="text" id="first_name" name="first_name" value="<?php echo $user['first_name']; ?>" required>
            </div>
            
            <div>
                <label for="last_name">Last Name:</label>
                <input type="text" id="last_name" name="last_name" value="<?php echo $user['last_name']; ?>" required>
            </div>
            
            <div>
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" value="<?php echo $user['email']; ?>" required>
            </div>
            
            <div>
                <label for="phone">Phone:</label>
                <input type="tel" id="phone" name="phone" value="<?php echo $user['phone']; ?>">
            </div>
            
            <div>
                <label for="address">Address:</label>
                <textarea id="address" name="address"><?php echo $user['address']; ?></textarea>
            </div>
            
            <button type="submit" name="update_profile">Update Profile</button>
        </form>
    </div>
    
    <div class="password-change">
        <h2>Change Password</h2>
        <form method="POST" action="">
            <div>
                <label for="current_password">Current Password:</label>
                <input type="password" id="current_password" name="current_password" required>
            </div>
            
            <div>
                <label for="new_password">New Password:</label>
                <input type="password" id="new_password" name="new_password" required>
            </div>
            
            <div>
                <label for="confirm_password">Confirm New Password:</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
            </div>
            
            <button type="submit" name="change_password">Change Password</button>
        </form>
    </div>
    </div>


</main>
<style>
/* Normalize layout so global header spacing applies without extra offset */
main { margin-top: 0; }


/* Main profile editor container */
.profile-editor {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 30px;
    max-width: 1000px;
    margin: 20px auto;
    padding: 0 20px;
}

/* Section styling */
.profile-editor .personal-info,
.profile-editor .password-change {
    background: var(--primary-light);
    padding: 25px;
    border-radius: var(--radius-xl);
    box-shadow: 0 4px 20px var(--shadow-light);
    border: 1px solid var(--border-secondary);
}

.profile-editor .personal-info h2,
.profile-editor .password-change h2 {
    color: var(--primary-dark);
    margin-bottom: 20px;
    font-size: 1.5rem;
    padding-bottom: 10px;
    border-bottom: 2px solid var(--accent-yellow);
}

/* Form styling */
.profile-editor form {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.profile-editor form div {
    display: flex;
    flex-direction: column;
}

/* Label styling */
.profile-editor label {
    margin-bottom: 5px;
    color: var(--primary-dark);
    font-weight: 500;
    font-size: 1rem;
}

/* Input styling */
.profile-editor input[type="text"],
.profile-editor input[type="email"],
.profile-editor input[type="tel"],
.profile-editor input[type="password"],
.profile-editor textarea {
    padding: 12px;
    border: 1px solid var(--border-secondary);
    border-radius: var(--radius-md);
    font-size: 1rem;
    transition: var(--transition-normal);
    background: var(--bg-secondary);
}

.profile-editor input:focus,
.profile-editor textarea:focus {
    outline: none;
    border-color: #FFD736;
    box-shadow: 0 0 0 2px rgba(255, 215, 54, 0.25);
}

/* Textarea specific styling */
.profile-editor textarea {
    min-height: 80px;
    resize: vertical;
    font-family: inherit;
}

/* Button styling */
.profile-editor button[type="submit"] {
    background: #FFD736;
    color: #130325;
    padding: 12px 24px;
    border: none;
    border-radius: var(--radius-md);
    font-size: 1rem;
    cursor: pointer;
    transition: var(--transition-normal);
    margin-top: 10px;
    font-weight: 600;
}

.profile-editor button[type="submit"]:hover {
    background: #e6c230;
    transform: translateY(-1px);
}

.profile-editor button[type="submit"]:active {
    transform: translateY(1px);
}

/* Success/Error message styling */
.profile-editor p {
    padding: 10px;
    border-radius: 5px;
    margin: 10px 0;
    text-align: center;
    font-weight: 500;
}

/* Success message (would need to be added via PHP class) */
.profile-editor .success-message {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

/* Error message (would need to be added via PHP class) */
.profile-editor .error-message {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

/* Responsive design */
@media (max-width: 768px) {
    .profile-editor {
        grid-template-columns: 1fr;
        gap: 20px;
        margin: 10px;
        padding: 0 10px;
    }
    
    .profile-editor .personal-info,
    .profile-editor .password-change {
        padding: 20px;
    }
    
    .profile-editor h1 {
        font-size: 1.5rem;
        margin: 15px 0;
    }
    
    .profile-editor .personal-info h2,
    .profile-editor .password-change h2 {
        font-size: 1.3rem;
    }
}

@media (max-width: 480px) {
    .profile-editor .personal-info,
    .profile-editor .password-change {
        padding: 15px;
    }
    
    .profile-editor input[type="text"],
    .profile-editor input[type="email"],
    .profile-editor input[type="tel"],
    .profile-editor input[type="password"],
    .profile-editor textarea {
        padding: 10px;
    }
    
    .profile-editor button[type="submit"] {
        padding: 10px 20px;
    }
}


</style>


<?php require_once 'includes/footer.php'; ?>