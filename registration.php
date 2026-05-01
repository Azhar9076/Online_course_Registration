<?php
require_once 'db.php';

if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $phone     = trim($_POST['phone'] ?? '');
    $password  = $_POST['password'] ?? '';
    $confirm   = $_POST['confirm_password'] ?? '';

    if (empty($full_name) || empty($email) || empty($password)) {
        $error = 'Please fill in all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        // Check if email already exists
        $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $check->bind_param("s", $email);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            $error = 'This email address is already registered.';
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (full_name, email, phone, password) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $full_name, $email, $phone, $hashed);
            if ($stmt->execute()) {
                $success = 'Account created successfully! You can now sign in.';
            } else {
                $error = 'Registration failed. Please try again.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account — CourseCosmosium</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="auth-wrapper">

    <!-- Left Hero Panel -->
    <div class="auth-hero">
        <div class="auth-hero-logo">Course<span>Cosmosium</span></div>
        <h1>Start your <em>journey</em> to mastery</h1>
        <p>Join a growing community of learners. Access expert-curated courses across technology, design, data science and more — completely free.</p>
        <div class="auth-stats">
            <div class="auth-stat">
                <div class="num">6+</div>
                <div class="label">Courses</div>
            </div>
            <div class="auth-stat">
                <div class="num">Free</div>
                <div class="label">Forever</div>
            </div>
            <div class="auth-stat">
                <div class="num">Cert.</div>
                <div class="label">On Completion</div>
            </div>
        </div>
    </div>

    <!-- Right Form Panel -->
    <div class="auth-panel">
        <div class="auth-form-container">
            <h2>Create Account</h2>
            <p class="sub">Already have an account? <a href="login.php">Sign in</a></p>

            <?php if ($error): ?>
                <div class="alert alert-danger">⚠ <?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success">✓ <?= htmlspecialchars($success) ?> <a href="login.php">Sign In →</a></div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label for="full_name">Full Name *</label>
                    <input type="text" id="full_name" name="full_name" class="form-control"
                           placeholder="Jane Doe" value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>" required>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="email">Email Address *</label>
                        <input type="email" id="email" name="email" class="form-control"
                               placeholder="you@example.com" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="phone">Phone (Optional)</label>
                        <input type="text" id="phone" name="phone" class="form-control"
                               placeholder="+1 555 0000" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="password">Password *</label>
                        <input type="password" id="password" name="password" class="form-control"
                               placeholder="Min. 6 characters" required>
                    </div>
                    <div class="form-group">
                        <label for="confirm_password">Confirm Password *</label>
                        <input type="password" id="confirm_password" name="confirm_password" class="form-control"
                               placeholder="Repeat password" required>
                    </div>
                </div>

                <div style="margin-bottom: 20px;"></div>

                <button type="submit" class="btn btn-primary btn-full">Create My Account →</button>
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