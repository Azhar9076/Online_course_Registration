<?php
require_once 'db.php';

// Fetch courses for display
$courses_result = $conn->query("SELECT * FROM courses ORDER BY created_at DESC LIMIT 6");
$courses = $courses_result ? $courses_result->fetch_all(MYSQLI_ASSOC) : [];

$total_courses = $conn->query("SELECT COUNT(*) as c FROM courses")->fetch_assoc()['c'] ?? 0;
$total_students = $conn->query("SELECT COUNT(*) as c FROM users WHERE role='student'")->fetch_assoc()['c'] ?? 0;

function getCatClass($cat) {
    $map = ['Technology'=>'cat-tech','Data Science'=>'cat-data','Design'=>'cat-design','Cloud'=>'cat-cloud','Marketing'=>'cat-marketing'];
    return $map[$cat] ?? 'cat-default';
}

function getCatEmoji($cat) {
    $map = ['Technology'=>'💻','Data Science'=>'📊','Design'=>'🎨','Cloud'=>'☁️','Marketing'=>'📣','Mobile'=>'📱'];
    return $map[$cat] ?? '📚';
}

function getLevelBadgeClass($level) {
    $map = ['Beginner'=>'badge-beginner','Intermediate'=>'badge-intermediate','Advanced'=>'badge-advanced'];
    return $map[$level] ?? 'badge-beginner';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CoursePortal — Learn Without Limits</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="landing-hero">

    <!-- Navigation -->
    <nav class="landing-nav">
        <div class="logo">Course<span>Cosmosium</span></div>
        <div class="nav-actions">
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="dashboard.php" class="btn btn-outline">Dashboard</a>
                <a href="logout.php" class="btn btn-primary">Logout</a>
            <?php else: ?>
                <a href="login.php" class="btn btn-outline">Sign In</a>
                <a href="registration.php" class="btn btn-primary">Get Started</a>
            <?php endif; ?>
        </div>
    </nav>

    <!-- Hero -->
    <section class="hero-section">
        <div class="hero-tag">✦ Open Enrollment Now Active</div>
        <h1>Learn Skills That <em>Actually</em> Matter Today</h1>
        <p>Expert-led courses in technology, design, data science and more. Build real skills, earn certificates, and advance your career.</p>
        <div class="hero-actions">
            <a href="registration.php" class="btn btn-primary" style="padding: 14px 32px; font-size: 15px;">Start Learning Free</a>
            <a href="#courses" class="btn btn-outline" style="padding: 14px 32px; font-size: 15px;">Browse Courses</a>
        </div>

        <!-- Stats -->
        <div style="display:flex; gap: 48px; margin-top: 60px; padding-top: 40px; border-top: 1px solid var(--border);">
            <div class="auth-stat">
                <div class="num"><?= $total_courses ?>+</div>
                <div class="label">Courses</div>
            </div>
            <div class="auth-stat">
                <div class="num"><?= $total_students ?>+</div>
                <div class="label">Students</div>
            </div>
            <div class="auth-stat">
                <div class="num">100%</div>
                <div class="label">Free Access</div>
            </div>
        </div>
    </section>

    <!-- Courses -->
    <section class="courses-section" id="courses">
        <h2>Featured Courses</h2>
        <p class="sub">Hand-picked programs designed for the modern learner</p>
        <div class="courses-grid">
            <?php foreach ($courses as $c): ?>
            <?php
                $pct = $c['seats'] > 0 ? round(($c['enrolled'] / $c['seats']) * 100) : 0;
                $remaining = $c['seats'] - $c['enrolled'];
            ?>
            <div class="course-card">
                <div class="course-thumb <?= getCatClass($c['category']) ?>">
                    <?= getCatEmoji($c['category']) ?>
                    <span class="course-level-badge <?= getLevelBadgeClass($c['level']) ?>"><?= $c['level'] ?></span>
                </div>
                <div class="course-body">
                    <div class="course-category"><?= htmlspecialchars($c['category']) ?></div>
                    <div class="course-title"><?= htmlspecialchars($c['title']) ?></div>
                    <div class="course-instructor">by <?= htmlspecialchars($c['instructor']) ?></div>
                    <div class="course-desc"><?= htmlspecialchars($c['description']) ?></div>
                    <div class="course-meta">
                        <span>⏱ <?= htmlspecialchars($c['duration']) ?></span>
                        <span>🪑 <?= $remaining ?> seats left</span>
                    </div>
                    <div class="seats-bar"><div class="seats-bar-fill" style="width: <?= $pct ?>%"></div></div>
                    <div class="seats-info"><?= $c['enrolled'] ?> / <?= $c['seats'] ?> enrolled</div>
                    <a href="<?= isset($_SESSION['user_id']) ? 'course.php' : 'login.php' ?>" class="btn btn-primary btn-full">
                        <?= isset($_SESSION['user_id']) ? 'View Course' : 'Enroll Now' ?>
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>

</div>
</body>
</html>