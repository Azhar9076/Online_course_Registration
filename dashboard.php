<?php
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch user
$user_stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user = $user_stmt->get_result()->fetch_assoc();

// Stats
$enrolled_count = $conn->prepare("SELECT COUNT(*) as c FROM enrollments WHERE user_id = ?");
$enrolled_count->bind_param("i", $user_id);
$enrolled_count->execute();
$enrolled_count = $enrolled_count->get_result()->fetch_assoc()['c'];

$completed_count = $conn->prepare("SELECT COUNT(*) as c FROM enrollments WHERE user_id = ? AND status = 'completed'");
$completed_count->bind_param("i", $user_id);
$completed_count->execute();
$completed_count = $completed_count->get_result()->fetch_assoc()['c'];

$total_courses = $conn->query("SELECT COUNT(*) as c FROM courses")->fetch_assoc()['c'];

// Enrolled courses
$enrolled_stmt = $conn->prepare("
    SELECT e.*, c.title, c.category, c.instructor, c.duration, c.level, e.enrolled_at, e.status, e.progress
    FROM enrollments e
    JOIN courses c ON e.course_id = c.id
    WHERE e.user_id = ?
    ORDER BY e.enrolled_at DESC
");
$enrolled_stmt->bind_param("i", $user_id);
$enrolled_stmt->execute();
$my_courses = $enrolled_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Available courses (not enrolled)
$available_stmt = $conn->prepare("
    SELECT * FROM courses WHERE id NOT IN
    (SELECT course_id FROM enrollments WHERE user_id = ?)
    ORDER BY created_at DESC LIMIT 3
");
$available_stmt->bind_param("i", $user_id);
$available_stmt->execute();
$available_courses = $available_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

function getCatEmoji($cat) {
    $map = ['Technology'=>'💻','Data Science'=>'📊','Design'=>'🎨','Cloud'=>'☁️','Marketing'=>'📣','Mobile'=>'📱'];
    return $map[$cat] ?? '📚';
}

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
    <title>Dashboard — CourseCosmosium</title>
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
        <a href="dashboard.php" class="nav-link active">
            <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
            Dashboard
        </a>
        <a href="course.php" class="nav-link">
            <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
            All Courses
        </a>
        <a href="profile.php" class="nav-link">
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
                <h1 class="page-title">Welcome back, <?= htmlspecialchars(explode(' ', $user['full_name'])[0]) ?> 👋</h1>
                <p class="page-subtitle">Here's an overview of your learning progress</p>
            </div>
            <a href="course.php" class="btn btn-primary">+ Enroll in Course</a>
        </div>

        <!-- Stats -->
        <div class="stats-grid">
            <div class="stat-card blue">
                <div class="label">Enrolled Courses</div>
                <div class="value"><?= $enrolled_count ?></div>
                <div class="trend">Active programs</div>
            </div>
            <div class="stat-card green">
                <div class="label">Completed</div>
                <div class="value"><?= $completed_count ?></div>
                <div class="trend up">↑ Great progress</div>
            </div>
            <div class="stat-card orange">
                <div class="label">Available</div>
                <div class="value"><?= $total_courses ?></div>
                <div class="trend">Total courses</div>
            </div>
            <div class="stat-card red">
                <div class="label">Member Since</div>
                <div class="value" style="font-size: 18px; padding-top: 6px;"><?= date('M Y', strtotime($user['created_at'])) ?></div>
                <div class="trend">Active learner</div>
            </div>
        </div>

        <!-- My Enrolled Courses -->
        <div class="card mb-6">
            <div class="section-header">
                <h2 class="section-title">My Courses</h2>
                <a href="course.php" class="btn btn-outline btn-sm">Browse All →</a>
            </div>

            <?php if (empty($my_courses)): ?>
                <div class="empty-state">
                    <div class="icon">📚</div>
                    <p>You haven't enrolled in any courses yet.</p>
                    <a href="course.php" class="btn btn-primary">Explore Courses</a>
                </div>
            <?php else: ?>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Course</th>
                                <th>Category</th>
                                <th>Instructor</th>
                                <th>Duration</th>
                                <th>Status</th>
                                <th>Enrolled On</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($my_courses as $c): ?>
                            <tr>
                                <td>
                                    <strong><?= getCatEmoji($c['category']) ?> <?= htmlspecialchars($c['title']) ?></strong>
                                </td>
                                <td><?= htmlspecialchars($c['category']) ?></td>
                                <td><?= htmlspecialchars($c['instructor']) ?></td>
                                <td><?= htmlspecialchars($c['duration']) ?></td>
                                <td><span class="badge badge-<?= $c['status'] ?>"><?= ucfirst($c['status']) ?></span></td>
                                <td><?= date('M d, Y', strtotime($c['enrolled_at'])) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- Recommended Courses -->
        <?php if (!empty($available_courses)): ?>
        <div>
            <div class="section-header">
                <h2 class="section-title">Recommended For You</h2>
                <a href="course.php" class="btn btn-outline btn-sm">View All →</a>
            </div>
            <div class="courses-grid">
                <?php foreach ($available_courses as $c): ?>
                <?php
                    $pct = $c['seats'] > 0 ? round(($c['enrolled'] / $c['seats']) * 100) : 0;
                    $catClasses = ['Technology'=>'cat-tech','Data Science'=>'cat-data','Design'=>'cat-design','Cloud'=>'cat-cloud','Marketing'=>'cat-marketing'];
                    $catClass = $catClasses[$c['category']] ?? 'cat-default';
                    $levelClass = ['Beginner'=>'badge-beginner','Intermediate'=>'badge-intermediate','Advanced'=>'badge-advanced'][$c['level']] ?? 'badge-beginner';
                ?>
                <div class="course-card">
                    <div class="course-thumb <?= $catClass ?>">
                        <?= getCatEmoji($c['category']) ?>
                        <span class="course-level-badge <?= $levelClass ?>"><?= $c['level'] ?></span>
                    </div>
                    <div class="course-body">
                        <div class="course-category"><?= htmlspecialchars($c['category']) ?></div>
                        <div class="course-title"><?= htmlspecialchars($c['title']) ?></div>
                        <div class="course-instructor">by <?= htmlspecialchars($c['instructor']) ?></div>
                        <div class="course-meta">
                            <span>⏱ <?= $c['duration'] ?></span>
                            <span>🪑 <?= $c['seats'] - $c['enrolled'] ?> left</span>
                        </div>
                        <a href="course.php" class="btn btn-primary btn-full">View & Enroll</a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </main>

</div>
</body>
</html>