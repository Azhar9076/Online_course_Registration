<?php
require_once 'db.php';

if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Please fill in all fields.';
    } else {
        $stmt = $conn->prepare("SELECT id, full_name, password, role FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role']      = $user['role'];
            header("Location: dashboard.php");
            exit();
        } else {
            $error = 'Invalid email or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In — CourseCosmosium</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="auth-wrapper">

    <!-- Left Hero Panel -->
    <div class="auth-hero">
        <div class="auth-hero-logo">Course<span>Cosmosium</span></div>
        <h1>Welcome back to your <em>learning journey</em></h1>
        <p>Pick up right where you left off. Your courses, progress, and community are waiting for you.</p>
        <div class="auth-stats">
            <div class="auth-stat">
                <div class="num">6+</div>
                <div class="label">Courses</div>
            </div>
            <div class="auth-stat">
                <div class="num">100%</div>
                <div class="label">Free</div>
            </div>
            <div class="auth-stat">
                <div class="num">∞</div>
                <div class="label">Potential</div>
            </div>
        </div>
    </div>

    <!-- Right Form Panel -->
    <div class="auth-panel">
        <div class="auth-form-container">
            <h2>Sign In</h2>
            <p class="sub">Don't have an account? <a href="registration.php">Create one free</a></p>

            <?php if ($error): ?>
                <div class="alert alert-danger">⚠ <?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" class="form-control"
                           placeholder="you@example.com" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" class="form-control"
                           placeholder="••••••••" required>
                </div>

                <div style="margin-bottom: 20px;"></div>

                <button type="submit" class="btn btn-primary btn-full">Sign In →</button>
            </form>

            <div class="divider"></div>
            <p class="text-center" style="font-size: 13px; color: var(--text-muted);">
                <a href="index.php">← Back to Home</a>
            </p>
        </div>
    </div>

</div>
</body>
</html>