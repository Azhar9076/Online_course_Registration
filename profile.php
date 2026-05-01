<?php
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$message = '';
$msg_type = 'success';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_profile') {
        $full_name = trim($_POST['full_name'] ?? '');
        $phone     = trim($_POST['phone'] ?? '');
        $bio       = trim($_POST['bio'] ?? '');

        if (empty($full_name)) {
            $message = 'Name cannot be empty.';
            $msg_type = 'danger';
        } else {
            $stmt = $conn->prepare("UPDATE users SET full_name = ?, phone = ?, bio = ? WHERE id = ?");
            $stmt->bind_param("sssi", $full_name, $phone, $bio, $user_id);
            if ($stmt->execute()) {
                $_SESSION['full_name'] = $full_name;
                $message = 'Profile updated successfully!';
            } else {
                $message = 'Update failed. Please try again.';
                $msg_type = 'danger';
            }
        }
    } elseif ($action === 'change_password') {
        $current  = $_POST['current_password'] ?? '';
        $new_pass = $_POST['new_password'] ?? '';
        $confirm  = $_POST['confirm_password'] ?? '';

        $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $db_pass = $stmt->get_result()->fetch_assoc()['password'];

        if (!password_verify($current, $db_pass)) {
            $message = 'Current password is incorrect.';
            $msg_type = 'danger';
        } elseif (strlen($new_pass) < 6) {
            $message = 'New password must be at least 6 characters.';
            $msg_type = 'danger';
        } elseif ($new_pass !== $confirm) {
            $message = 'New passwords do not match.';
            $msg_type = 'danger';
        } else {
            $hashed = password_hash($new_pass, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->bind_param("si", $hashed, $user_id);
            if ($stmt->execute()) {
                $message = 'Password changed successfully!';
            } else {
                $message = 'Password change failed.';
                $msg_type = 'danger';
            }
        }
    }
}

// Fetch fresh user data
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Enrollment stats
$stats_stmt = $conn->prepare("
    SELECT
        COUNT(*) as total,
        SUM(CASE WHEN status='completed' THEN 1 ELSE 0 END) as completed,
        SUM(CASE WHEN status='active' THEN 1 ELSE 0 END) as active
    FROM enrollments WHERE user_id = ?
");
$stats_stmt->bind_param("i", $user_id);
$stats_stmt->execute();
$stats = $stats_stmt->get_result()->fetch_assoc();

// Get initials
$initials = strtoupper(substr($user['full_name'], 0, 1));
if (strpos($user['full_name'], ' ') !== false) {
    $parts = explode(' ', $user['full_name']);
    $initials = strtoupper($parts[0][0] . end($parts)[0]);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile — CourseCosmosium</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="page-wrapper">

    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="sidebar-logo">
            <div class="logo-text">Course<span>Cosmosium</span></div>
            <div class="logo-sub">Learning Platform</div>
        </div>
        <div class="nav-section-label">Main</div>
        <a href="dashboard.php" class="nav-link">
            <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
            Dashboard
        </a>
        <a href="course.php" class="nav-link">
            <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
            All Courses
        </a>
        <a href="profile.php" class="nav-link active">
            <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            My Profile
        </a>
        <div class="sidebar-user">
            <div class="user-name"><?= htmlspecialchars($user['full_name']) ?></div>
            <div class="user-role"><?= ucfirst($user['role']) ?></div>
            <a href="logout.php" class="btn-logout">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                Sign Out
            </a>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <div class="topbar">
            <div>
                <h1 class="page-title">My Profile</h1>
                <p class="page-subtitle">Manage your account information</p>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?= $msg_type ?>"><?= $msg_type === 'success' ? '✓' : '⚠' ?> <?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <div class="profile-layout">

            <!-- Profile Summary Card -->
            <div>
                <div class="profile-card mb-6">
                    <div class="avatar-circle"><?= $initials ?></div>
                    <div class="profile-name"><?= htmlspecialchars($user['full_name']) ?></div>
                    <div class="profile-email"><?= htmlspecialchars($user['email']) ?></div>
                    <div class="profile-badge"><?= ucfirst($user['role']) ?></div>

                    <div class="divider"></div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 8px; text-align: center;">
                        <div>
                            <div style="font-family: 'Playfair Display', serif; font-size: 22px; font-weight: 700;"><?= $stats['total'] ?? 0 ?></div>
                            <div style="font-size: 11px; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px;">Total</div>
                        </div>
                        <div>
                            <div style="font-family: 'Playfair Display', serif; font-size: 22px; font-weight: 700; color: var(--success);"><?= $stats['completed'] ?? 0 ?></div>
                            <div style="font-size: 11px; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px;">Done</div>
                        </div>
                        <div>
                            <div style="font-family: 'Playfair Display', serif; font-size: 22px; font-weight: 700; color: var(--accent);"><?= $stats['active'] ?? 0 ?></div>
                            <div style="font-size: 11px; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px;">Active</div>
                        </div>
                    </div>

                    <div class="divider"></div>
                    <div style="font-size: 12px; color: var(--text-muted);">
                        Member since <?= date('F Y', strtotime($user['created_at'])) ?>
                    </div>
                </div>
            </div>

            <!-- Edit Forms -->
            <div>
                <!-- Update Profile -->
                <div class="card mb-6">
                    <h3 style="font-size: 18px; margin-bottom: 20px;">Edit Profile</h3>
                    <form method="POST">
                        <input type="hidden" name="action" value="update_profile">
                        <div class="form-group">
                            <label for="full_name">Full Name</label>
                            <input type="text" id="full_name" name="full_name" class="form-control"
                                   value="<?= htmlspecialchars($user['full_name']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Email Address</label>
                            <input type="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" disabled
                                   style="opacity: 0.6; cursor: not-allowed;">
                        </div>
                        <div class="form-group">
                            <label for="phone">Phone Number</label>
                            <input type="text" id="phone" name="phone" class="form-control"
                                   value="<?= htmlspecialchars($user['phone'] ?? '') ?>" placeholder="e.g. +1 555 000 0000">
                        </div>
                        <div class="form-group">
                            <label for="bio">Bio</label>
                            <textarea id="bio" name="bio" class="form-control"
                                      placeholder="Tell us a bit about yourself..."><?= htmlspecialchars($user['bio'] ?? '') ?></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </form>
                </div>

                <!-- Change Password -->
                <div class="card">
                    <h3 style="font-size: 18px; margin-bottom: 20px;">Change Password</h3>
                    <form method="POST">
                        <input type="hidden" name="action" value="change_password">
                        <div class="form-group">
                            <label for="current_password">Current Password</label>
                            <input type="password" id="current_password" name="current_password" class="form-control"
                                   placeholder="Enter current password" required>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="new_password">New Password</label>
                                <input type="password" id="new_password" name="new_password" class="form-control"
                                       placeholder="Min. 6 characters" required>
                            </div>
                            <div class="form-group">
                                <label for="confirm_password">Confirm New Password</label>
                                <input type="password" id="confirm_password" name="confirm_password" class="form-control"
                                       placeholder="Repeat new password" required>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-outline">Update Password</button>
                    </form>
                </div>
            </div>

        </div>
    </main>

</div>
</body>
</html>