<?php
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$message = '';
$msg_type = 'success';

// Handle enrollment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['course_id'])) {
    $course_id = (int)$_POST['course_id'];
    $action    = $_POST['action'] ?? 'enroll';

    if ($action === 'enroll') {
        // Check seats
        $seat_check = $conn->prepare("SELECT seats, enrolled FROM courses WHERE id = ?");
        $seat_check->bind_param("i", $course_id);
        $seat_check->execute();
        $course_data = $seat_check->get_result()->fetch_assoc();

        if (!$course_data) {
            $message = 'Course not found.';
            $msg_type = 'danger';
        } elseif ($course_data['enrolled'] >= $course_data['seats']) {
            $message = 'Sorry, this course is full.';
            $msg_type = 'danger';
        } else {
            $stmt = $conn->prepare("INSERT IGNORE INTO enrollments (user_id, course_id) VALUES (?, ?)");
            $stmt->bind_param("ii", $user_id, $course_id);
            if ($stmt->execute() && $stmt->affected_rows > 0) {
                $conn->query("UPDATE courses SET enrolled = enrolled + 1 WHERE id = $course_id");
                $message = 'You have successfully enrolled in this course!';
            } else {
                $message = 'You are already enrolled in this course.';
                $msg_type = 'info';
            }
        }
    } elseif ($action === 'drop') {
        $stmt = $conn->prepare("DELETE FROM enrollments WHERE user_id = ? AND course_id = ?");
        $stmt->bind_param("ii", $user_id, $course_id);
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            $conn->query("UPDATE courses SET enrolled = GREATEST(enrolled - 1, 0) WHERE id = $course_id");
            $message = 'You have dropped this course.';
            $msg_type = 'danger';
        }
    }
}

// Get enrolled course IDs
$enrolled_ids_result = $conn->prepare("SELECT course_id FROM enrollments WHERE user_id = ?");
$enrolled_ids_result->bind_param("i", $user_id);
$enrolled_ids_result->execute();
$enrolled_rows = $enrolled_ids_result->get_result()->fetch_all(MYSQLI_ASSOC);
$enrolled_ids = array_column($enrolled_rows, 'course_id');

// Filters
$filter_cat   = $_GET['category'] ?? '';
$filter_level = $_GET['level'] ?? '';
$search       = trim($_GET['search'] ?? '');

$where = [];
$params = [];
$types = '';

if ($filter_cat) { $where[] = "category = ?"; $params[] = $filter_cat; $types .= 's'; }
if ($filter_level) { $where[] = "level = ?"; $params[] = $filter_level; $types .= 's'; }
if ($search) { $where[] = "(title LIKE ? OR description LIKE ? OR instructor LIKE ?)"; $like = "%$search%"; $params[] = $like; $params[] = $like; $params[] = $like; $types .= 'sss'; }

$sql = "SELECT * FROM courses" . ($where ? " WHERE " . implode(" AND ", $where) : "") . " ORDER BY created_at DESC";
$stmt = $conn->prepare($sql);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$courses = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Categories for filter
$categories = $conn->query("SELECT DISTINCT category FROM courses ORDER BY category")->fetch_all(MYSQLI_ASSOC);

function getCatClass($cat) {
    $map = ['Technology'=>'cat-tech','Data Science'=>'cat-data','Design'=>'cat-design','Cloud'=>'cat-cloud','Marketing'=>'cat-marketing'];
    return $map[$cat] ?? 'cat-default';
}
function getCatEmoji($cat) {
    $map = ['Technology'=>'💻','Data Science'=>'📊','Design'=>'🎨','Cloud'=>'☁️','Marketing'=>'📣','Mobile'=>'📱'];
    return $map[$cat] ?? '📚';
}
function getLevelClass($lvl) {
    return ['Beginner'=>'badge-beginner','Intermediate'=>'badge-intermediate','Advanced'=>'badge-advanced'][$lvl] ?? 'badge-beginner';
}

$user_name = $_SESSION['full_name'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Courses — CoursePortal</title>
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
        <a href="course.php" class="nav-link active">
            <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
            All Courses
        </a>
        <a href="profile.php" class="nav-link">
            <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            My Profile
        </a>
        <div class="sidebar-user">
            <div class="user-name"><?= htmlspecialchars($user_name) ?></div>
            <div class="user-role">Student</div>
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
                <h1 class="page-title">All Courses</h1>
                <p class="page-subtitle"><?= count($courses) ?> course<?= count($courses) !== 1 ? 's' : '' ?> available</p>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?= $msg_type ?>"><?= $msg_type === 'success' ? '✓' : ($msg_type === 'danger' ? '✕' : 'ℹ') ?> <?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <!-- Filters -->
        <form method="GET" style="display: flex; gap: 12px; margin-bottom: 28px; flex-wrap: wrap;">
            <input type="text" name="search" class="form-control" placeholder="Search courses..."
                   value="<?= htmlspecialchars($search) ?>" style="flex: 1; min-width: 200px;">
            <select name="category" class="form-control" style="width: auto;">
                <option value="">All Categories</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= htmlspecialchars($cat['category']) ?>" <?= $filter_cat === $cat['category'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($cat['category']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <select name="level" class="form-control" style="width: auto;">
                <option value="">All Levels</option>
                <option value="Beginner" <?= $filter_level === 'Beginner' ? 'selected' : '' ?>>Beginner</option>
                <option value="Intermediate" <?= $filter_level === 'Intermediate' ? 'selected' : '' ?>>Intermediate</option>
                <option value="Advanced" <?= $filter_level === 'Advanced' ? 'selected' : '' ?>>Advanced</option>
            </select>
            <button type="submit" class="btn btn-outline">Filter</button>
            <?php if ($search || $filter_cat || $filter_level): ?>
                <a href="course.php" class="btn btn-outline">Clear</a>
            <?php endif; ?>
        </form>

        <!-- Courses Grid -->
        <?php if (empty($courses)): ?>
            <div class="empty-state">
                <div class="icon">🔍</div>
                <p>No courses found matching your filters.</p>
                <a href="course.php" class="btn btn-primary">Show All Courses</a>
            </div>
        <?php else: ?>
        <div class="courses-grid">
            <?php foreach ($courses as $c): ?>
            <?php
                $is_enrolled = in_array($c['id'], $enrolled_ids);
                $pct = $c['seats'] > 0 ? round(($c['enrolled'] / $c['seats']) * 100) : 0;
                $remaining = $c['seats'] - $c['enrolled'];
                $is_full = $remaining <= 0;
            ?>
            <div class="course-card">
                <div class="course-thumb <?= getCatClass($c['category']) ?>">
                    <?= getCatEmoji($c['category']) ?>
                    <span class="course-level-badge <?= getLevelClass($c['level']) ?>"><?= $c['level'] ?></span>
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
                    <div class="seats-bar">
                        <div class="seats-bar-fill" style="width: <?= $pct ?>%; background: <?= $pct >= 90 ? 'var(--danger)' : ($pct >= 70 ? 'var(--warning)' : 'var(--accent)') ?>"></div>
                    </div>
                    <div class="seats-info"><?= $c['enrolled'] ?> / <?= $c['seats'] ?> enrolled</div>

                    <form method="POST">
                        <input type="hidden" name="course_id" value="<?= $c['id'] ?>">
                        <?php if ($is_enrolled): ?>
                            <input type="hidden" name="action" value="drop">
                            <button type="submit" class="btn btn-danger btn-full"
                                    onclick="return confirm('Are you sure you want to drop this course?')">
                                Drop Course
                            </button>
                        <?php elseif ($is_full): ?>
                            <button class="btn btn-outline btn-full" disabled style="opacity:0.5; cursor:not-allowed;">
                                Course Full
                            </button>
                        <?php else: ?>
                            <input type="hidden" name="action" value="enroll">
                            <button type="submit" class="btn btn-primary btn-full">Enroll Now</button>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </main>

</div>
</body>
</html>