<?php
session_start();
require_once 'db.php';

if (!isset($_GET['id'])) {
    header('Location: home.php');
    exit;
}

$chapter_id = $_GET['id'];

$stmt = $pdo->prepare("SELECT chapters.*, books.title AS book_title 
                       FROM chapters 
                       JOIN books ON chapters.book_id = books.id 
                       WHERE chapters.id = :id");
$stmt->execute(['id' => $chapter_id]);
$chapter = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$chapter) {
    echo "Chapter tidak ditemukan.";
    exit;
}

$isBookmarked = false;
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $stmtCheckBookmark = $pdo->prepare("SELECT id FROM bookmarks WHERE user_id = :user_id AND chapter_id = :chapter_id");
    $stmtCheckBookmark->execute(['user_id' => $user_id, 'chapter_id' => $chapter_id]);
    $isBookmarked = $stmtCheckBookmark->fetch(PDO::FETCH_ASSOC) ? true : false;
}

if (isset($_POST['bookmark']) && isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $book_id = $chapter['book_id']; 

    $stmtCheck = $pdo->prepare("SELECT id FROM bookmarks WHERE user_id = :user_id AND book_id = :book_id");
    $stmtCheck->execute(['user_id' => $user_id, 'book_id' => $book_id]);
    $existingBookmark = $stmtCheck->fetch(PDO::FETCH_ASSOC);

    if ($existingBookmark) {
        $stmtUpdate = $pdo->prepare("UPDATE bookmarks SET chapter_id = :chapter_id, created_at = NOW() WHERE id = :id");
        $stmtUpdate->execute([
            'chapter_id' => $chapter_id,
            'id' => $existingBookmark['id']
        ]);
    } else {
        $stmtInsert = $pdo->prepare("INSERT INTO bookmarks (user_id, book_id, chapter_id) VALUES (:user_id, :book_id, :chapter_id)");
        $stmtInsert->execute([
            'user_id' => $user_id,
            'book_id' => $book_id,
            'chapter_id' => $chapter_id
        ]);
    }

    header("Location: chapter.php?id=" . $chapter_id);
    exit;
}

$stmtNext = $pdo->prepare("SELECT id FROM chapters 
                           WHERE book_id = :book_id AND chapter_number > :current_chapter_number 
                           ORDER BY chapter_number ASC LIMIT 1");
$stmtNext->execute([
    'book_id' => $chapter['book_id'],
    'current_chapter_number' => $chapter['chapter_number']
]);
$nextChapter = $stmtNext->fetch(PDO::FETCH_ASSOC);

$stmtPrev = $pdo->prepare("SELECT id FROM chapters 
                           WHERE book_id = :book_id AND chapter_number < :current_chapter_number 
                           ORDER BY chapter_number DESC LIMIT 1");
$stmtPrev->execute([
    'book_id' => $chapter['book_id'],
    'current_chapter_number' => $chapter['chapter_number']
]);
$prevChapter = $stmtPrev->fetch(PDO::FETCH_ASSOC);

$images = explode(',', $chapter['chapter_image']);
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chapter <?php echo htmlspecialchars($chapter['id']); ?> - <?php echo htmlspecialchars($chapter['book_title']); ?></title>
    <link rel="stylesheet" href="chapter.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
</head>
<body>
    <nav class="navbar">
        <div class="navbar-container">
            <a href="#" class="navbar-logo">BukuKu</a>
            <div class="navbar-hamburger" onclick="toggleNavbar()">
                <div></div>
                <div></div>
                <div></div>
            </div>
            <div class="navbar-links" id="navbarLinks">
                <a href="index.php">Home</a>
                <?php if (isset($_SESSION['username'])): ?>
                <?php if ($_SESSION['role'] === 'admin'): ?>
                    <a href="home.php">Admin Dashboard</a>
                    <a href="logout.php">Logout</a>
                <?php elseif ($_SESSION['role'] === 'member'): ?>
                    <a href="member.php">Dashboard</a>
                    <a href="logout.php">Logout</a>
                <?php endif; ?>
                <?php else: ?>
                    <a href="loreg.php">Login</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

<div class="container">
    <h1><?php echo htmlspecialchars($chapter['book_title']); ?> - Chapter <?php echo htmlspecialchars($chapter['chapter_number']); ?></h1>
    <p>Uploaded on: <?php echo htmlspecialchars($chapter['created_at']); ?></p>

    <div class="image-gallery">
        <?php foreach ($images as $image): ?>
            <img src="<?php echo htmlspecialchars($image); ?>" alt="Chapter Image">
        <?php endforeach; ?>
    </div>
<?php if (isset($_SESSION['username'])): ?>
    <form method="post" class="bookmark-form">
        <button type="submit" name="bookmark" class="bookmark-button" 
            <?php echo $isBookmarked ? 'disabled style="pointer-events: none; opacity: 0.5;"' : ''; ?>>
            <?php echo $isBookmarked ? 'Bookmarked' : 'Save Bookmark'; ?>
        </button>
    </form>
<?php else: ?>
    <p class="login-prompt">Login to use the bookmark feature!</p>
<?php endif; ?>
 <div class="navigation-buttons">
    <a 
        href="<?php echo $prevChapter ? 'chapter.php?id=' . $prevChapter['id'] : '#'; ?>" 
        class="prev-button"
        <?php echo $prevChapter ? '' : 'disabled style="pointer-events: none; opacity: 0.5;"'; ?>
    >
        Previous Chapter
    </a>
    <a 
        href="<?php echo $nextChapter ? 'chapter.php?id=' . $nextChapter['id'] : '#'; ?>" 
        class="next-button"
        <?php echo $nextChapter ? '' : 'disabled style="pointer-events: none; opacity: 0.5;"'; ?>
    >
        Next Chapter
    </a>
 </div>
</div>
    <script>
        function toggleNavbar() {
            const navbarLinks = document.getElementById('navbarLinks');
            navbarLinks.classList.toggle('active');
        }
    </script>
    <footer class="footer">
        &copy; 2024 AnHar. Semua hak cipta dilindungi.
    </footer>
</body>
</html>
