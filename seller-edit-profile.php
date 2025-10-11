<?php
require_once 'includes/seller_header.php';
require_once 'config/database.php';

requireSeller();

$userId = $_SESSION['user_id'];

// Get seller info
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND user_type = 'seller'");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Handle profile update
if (isset($_POST['update_profile'])) {
    $firstName = sanitizeInput($_POST['first_name']);
    $lastName = sanitizeInput($_POST['last_name']);
    $email = sanitizeInput($_POST['email']);
    $phone = sanitizeInput($_POST['phone']);
    $address = sanitizeInput($_POST['address']);

    // check email unique (exclude self)
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $stmt->execute([$email, $userId]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existing) {
        echo "<p class=\"error-message\">Error: Email already exists.</p>";
    } else {
        $stmt = $pdo->prepare("UPDATE users SET first_name = ?, last_name = ?, email = ?, phone = ?, address = ? WHERE id = ?");
        $stmt->execute([$firstName, $lastName, $email, $phone, $address, $userId]);
        echo "<p class=\"success-message\">Profile updated successfully!</p>";
        header("Refresh:2");
    }
}

// Handle password change
if (isset($_POST['change_password'])) {
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (!$user) {
        echo "<p class=\"error-message\">User not found.</p>";
    } elseif (!password_verify($currentPassword, $user['password'])) {
        echo "<p class=\"error-message\">Error: Current password is incorrect.</p>";
    } elseif ($newPassword !== $confirmPassword) {
        echo "<p class=\"error-message\">Error: New passwords do not match.</p>";
    } else {
        $hashed = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->execute([$hashed, $userId]);
        echo "<p class=\"success-message\">Password changed successfully!</p>";
    }
}
?>

<style>
html, body { margin:0; padding:0; min-height:100vh; background:#130325 !important; }
main { background:transparent !important; margin-left: 120px !important; padding: 20px 0 40px 0 !important; min-height: calc(100vh - 60px) !important; transition: margin-left 0.3s ease; }
main.sidebar-collapsed { margin-left: 0px !important; }
.profile-editor { display:grid; grid-template-columns: 1fr 1fr; gap: 40px; max-width: 100%; margin: 0 auto; padding: 0 20px; width: 100%; }
.personal-info, .password-change { background: var(--primary-dark); padding: 25px; border-radius: 8px; box-shadow: 0 4px 20px var(--shadow-light); border: 1px solid var(--accent-yellow); }
.personal-info h2, .password-change h2 { color: var(--primary-light); margin-bottom: 20px; font-size: 1.5rem; padding-bottom: 10px; border-bottom: 2px solid var(--accent-yellow); }
.profile-editor form { display:flex; flex-direction:column; gap:20px; }
.profile-editor form div { display:flex; flex-direction:column; }
.profile-editor label { margin-bottom:5px; color: var(--primary-light); font-weight: 500; font-size: 1rem; }
.profile-editor input[type="text"], .profile-editor input[type="email"], .profile-editor input[type="tel"], .profile-editor input[type="password"], .profile-editor textarea { padding: 6px; border: 1px solid #ced4da; border-radius: 4px; font-size: 1rem; transition: border-color 0.3s; background: #ffffff; color: #130325; }
.profile-editor input:focus, .profile-editor textarea:focus { outline:none; border-color: var(--accent-yellow); box-shadow: 0 0 0 3px rgba(255, 215, 54, 0.3); }
.profile-editor textarea { min-height: 50px; resize: vertical; font-family: inherit; }
.profile-editor button[type="submit"] { background:#FFD736; color:#130325; padding:12px 24px; border:none; border-radius:4px; font-size:1rem; cursor:pointer; transition: background-color 0.3s, transform 0.2s; margin-top:10px; font-weight:600; }
.profile-editor button[type="submit"]:hover { background:#e6c230; transform: translateY(-1px); }
.btn-secondary { background:#ffffff; color:#130325; padding:12px 24px; border:none; border-radius:4px; font-size:1rem; cursor:pointer; transition: background-color 0.3s; font-weight:600; }
.btn-secondary:hover { background:#f8f9fa; }
#changePasswordBtn { background:#ffffff !important; color:#130325 !important; }
#changePasswordBtn:hover { background:#f8f9fa !important; }
#cancelEditBtn, #cancelPwBtn { background:#dc3545 !important; color:#ffffff !important; }
#cancelEditBtn:hover, #cancelPwBtn:hover { background:#c82333 !important; }
.account-actions { display:flex; gap:10px; margin-top:30px; }
.account-actions button { background:#FFD736; color:#130325; padding:12px 24px; border:none; border-radius:4px; font-size:1rem; cursor:pointer; transition: background-color 0.3s, transform 0.2s; font-weight:600; }
.account-actions button:hover { background:#e6c230; transform: translateY(-1px); }
.success-message, .error-message { padding:10px; border-radius:5px; margin:10px 0; text-align:center; font-weight:500; }
.success-message { background:#d4edda; color:#155724; border:1px solid #c3e6cb; }
.error-message { background:#f8d7da; color:#721c24; border:1px solid #f5c6cb; }
@media (max-width: 768px){ .profile-editor { grid-template-columns: 1fr; gap:20px; margin:10px; padding:0 10px; } .personal-info, .password-change { padding:20px; } }
</style>

<main>
<div style="max-width: 100%; margin: 0 auto; padding: 0 20px;">
    <h1 style="color: var(--primary-light); text-align:left; margin: 0 0 20px 0 !important; font-size: 2rem; font-weight:700; padding-left: 20px;">My Account</h1>

    <div class="profile-editor">
        <div class="personal-info">
            <h2>Account Details</h2>
            <div class="account-summary" style="display:grid; gap:10px; margin-top:15px; margin-bottom: 20px;">
                <div class="summary-row" style="display:flex; justify-content:space-between; gap:10px; color: var(--primary-light); padding:8px 0; border-bottom:1px solid rgba(255, 215, 54, 0.2);"><strong style="color:#FFD736;">Name:</strong> <span><?php echo htmlspecialchars(trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''))); ?></span></div>
                <div class="summary-row" style="display:flex; justify-content:space-between; gap:10px; color: var(--primary-light); padding:8px 0; border-bottom:1px solid rgba(255, 215, 54, 0.2);"><strong style="color:#FFD736;">Email:</strong> <span><?php echo htmlspecialchars($user['email'] ?? ''); ?></span></div>
                <div class="summary-row" style="display:flex; justify-content:space-between; gap:10px; color: var(--primary-light); padding:8px 0; border-bottom:1px solid rgba(255, 215, 54, 0.2);"><strong style="color:#FFD736;">Phone:</strong> <span><?php echo htmlspecialchars($user['phone'] ?? ''); ?></span></div>
                <div class="summary-row" style="display:flex; justify-content:space-between; gap:10px; color: var(--primary-light); padding:8px 0; padding-bottom: 15px;"><strong style="color:#FFD736;">Address:</strong> <span style="text-align: right; max-width: 60%;"><?php echo htmlspecialchars($user['address'] ?? ''); ?></span></div>
            </div>
            <div class="account-actions" style="display:flex; gap:10px; margin-top:30px;">
                <button id="editAccountBtn" type="button">Edit Account</button>
                <button id="changePasswordBtn" type="button" class="btn-secondary">Change Password</button>
            </div>

            <form id="editSellerForm" method="POST" action="" style="display:none; margin-top:20px;">
                <div>
                    <label for="first_name">First Name:</label>
                    <input type="text" id="first_name" name="first_name" value="<?php echo htmlspecialchars($user['first_name'] ?? ''); ?>" required>
                </div>
                <div>
                    <label for="last_name">Last Name:</label>
                    <input type="text" id="last_name" name="last_name" value="<?php echo htmlspecialchars($user['last_name'] ?? ''); ?>" required>
                </div>
                <div>
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" required>
                </div>
                <div>
                    <label for="phone">Phone:</label>
                    <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                </div>
                <div>
                    <label for="address">Address:</label>
                    <textarea id="address" name="address"><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                </div>
                <div style="display:flex; gap:10px;">
                    <button type="submit" name="update_profile">Save Changes</button>
                    <button type="button" id="cancelEditBtn" class="btn-secondary">Cancel</button>
                </div>
            </form>
        </div>

        <div class="password-change">
            <h2>Change Password</h2>
            <div id="passwordHint" class="hint" style="color: var(--primary-light); opacity:0.8; font-size: 0.9rem; margin-top:8px; text-align:center; padding:15px; background: rgba(255, 215, 54, 0.1); border-radius:4px;">Click "Change Password" button to update your password.</div>
            <form id="changeSellerPasswordForm" method="POST" action="" style="display:none; margin-top:20px;">
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
                <div style="display:flex; gap:10px;">
                    <button type="submit" name="change_password">Update Password</button>
                    <button type="button" id="cancelPwBtn" class="btn-secondary">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function(){
    // Handle sidebar state for main content positioning
    const main = document.querySelector('main');
    const sidebar = document.getElementById('sellerSidebar');
    
    function updateMainMargin() {
        if (sidebar && sidebar.classList.contains('collapsed')) {
            main.classList.add('sidebar-collapsed');
        } else {
            main.classList.remove('sidebar-collapsed');
        }
    }
    
    // Check initial state
    updateMainMargin();
    
    // Listen for sidebar changes
    const observer = new MutationObserver(updateMainMargin);
    if (sidebar) {
        observer.observe(sidebar, { attributes: true, attributeFilter: ['class'] });
    }
    
    // Also listen for the hamburger button clicks as backup
    const hamburgerBtn = document.querySelector('.header-hamburger');
    if (hamburgerBtn) {
        hamburgerBtn.addEventListener('click', function() {
            setTimeout(updateMainMargin, 100); // Small delay to ensure class change happens first
        });
    }
    
    const editBtn = document.getElementById('editAccountBtn');
    const changePwBtn = document.getElementById('changePasswordBtn');
    const editForm = document.getElementById('editSellerForm');
    const pwForm = document.getElementById('changeSellerPasswordForm');
    const cancelEdit = document.getElementById('cancelEditBtn');
    const cancelPw = document.getElementById('cancelPwBtn');
    const pwHint = document.getElementById('passwordHint');

    if (editBtn && editForm) {
        editBtn.addEventListener('click', function(){
            const isVisible = editForm.style.display !== 'none';
            editForm.style.display = isVisible ? 'none' : 'block';
        });
    }
    if (changePwBtn && pwForm && pwHint) {
        changePwBtn.addEventListener('click', function(){
            const isVisible = pwForm.style.display !== 'none';
            pwForm.style.display = isVisible ? 'none' : 'block';
            pwHint.style.display = isVisible ? 'block' : 'none';
        });
    }
    if (cancelEdit && editForm) {
        cancelEdit.addEventListener('click', function(){ editForm.style.display = 'none'; });
    }
    if (cancelPw && pwForm && pwHint) {
        cancelPw.addEventListener('click', function(){ pwForm.style.display = 'none'; pwHint.style.display = 'block'; });
    }
});
</script>

