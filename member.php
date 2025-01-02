<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'member') {
    header('Location: loreg.php'); 
    exit;
}

require_once 'db.php'; 

$limit = 9; 
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1; 
$offset = ($page - 1) * $limit; 

if (isset($_POST['delete_bookmark_id'])) {
    $delete_bookmark_id = $_POST['delete_bookmark_id'];

    $deleteStmt = $pdo->prepare("DELETE FROM bookmarks WHERE id = :bookmark_id AND user_id = :user_id");
    $deleteStmt->execute(['bookmark_id' => $delete_bookmark_id, 'user_id' => $_SESSION['user_id']]);

    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

$user_id = $_SESSION['user_id']; 
$stmt = $pdo->prepare("
    SELECT 
        bookmarks.id AS bookmark_id, 
        books.title AS book_title, 
        books.image AS book_image, 
        books.rating AS book_rating, 
        chapters.chapter_number, 
        chapters.id AS chapter_id,
        chapters.created_at AS chapter_created_at, 
        chapters.book_id AS book_id
    FROM bookmarks 
    JOIN books ON bookmarks.book_id = books.id 
    JOIN chapters ON bookmarks.chapter_id = chapters.id 
    WHERE bookmarks.user_id = :user_id 
    ORDER BY bookmarks.created_at DESC
    LIMIT :limit OFFSET :offset
");
$stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
$stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
$stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$bookmarks = $stmt->fetchAll(PDO::FETCH_ASSOC);

$countStmt = $pdo->prepare("
    SELECT COUNT(*) AS total_bookmarks
    FROM bookmarks 
    WHERE bookmarks.user_id = :user_id
");
$countStmt->execute(['user_id' => $user_id]);
$totalBookmarks = $countStmt->fetch(PDO::FETCH_ASSOC)['total_bookmarks'];
$totalPages = ceil($totalBookmarks / $limit);

$latestChapters = [];
foreach ($bookmarks as $bookmark) {
    $book_id = $bookmark['book_id'];
    $chapter_number = $bookmark['chapter_number'];
    
    $chapterStmt = $pdo->prepare("SELECT * FROM chapters WHERE book_id = :book_id ORDER BY chapter_number DESC LIMIT 1");
    $chapterStmt->execute(['book_id' => $book_id]);
    $latestChapter = $chapterStmt->fetch(PDO::FETCH_ASSOC);

    $latestChapters[$book_id] = $latestChapter;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Halaman Member</title>
    <link rel="stylesheet" href="member.css"> 
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
                <a href="logout.php">Logout</a>
            </div>
        </div>
    </nav>
    <div class="container">
        <h1>Selamat datang, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h1>
        <div class="bookmark-section">
            <h2>Your Bookmarks</h2>
            <?php if (count($bookmarks) > 0): ?>
            <ul class="bookmark-list">
                <?php foreach ($bookmarks as $bookmark): ?>
                    <?php
                        $is_new = false;
                        $latestChapter = $latestChapters[$bookmark['book_id']];
                        if ($latestChapter && $bookmark['chapter_number'] < $latestChapter['chapter_number']) {
                            $is_new = true; 
                        }
                    ?>
                    <li class="bookmark-item">
                        <div class="bookmark-content">
                            <img src="<?php echo htmlspecialchars($bookmark['book_image']); ?>" alt="Cover of <?php echo htmlspecialchars($bookmark['book_title']); ?>" class="bookmark-image">
                            <div class="bookmark-details">
                                <a href="chapter.php?id=<?php echo htmlspecialchars($bookmark['chapter_id']); ?>">
                                    <?php echo htmlspecialchars($bookmark['book_title']); ?> - Chapter <?php echo htmlspecialchars($bookmark['chapter_number']); ?>
                                </a>

                                <?php if ($is_new && $latestChapter): ?>
                                    <div class="new-chapter-wrapper">
                                        <div class="new-chapter">
                                            <a href="chapter.php?id=<?php echo htmlspecialchars($latestChapter['id']); ?>">
                                                Chapter <?php echo htmlspecialchars($latestChapter['chapter_number']); ?>
                                            </a>
                                        </div>

                                        <?php if ($is_new): ?>
                                            <span class="new-label">New</span>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>

                                <div class="book-rating">
                                    <span>Rating: <?php echo htmlspecialchars($bookmark['book_rating']); ?> / 5</span>
                                </div>
                                <button class="delete-bookmark-btn" onclick="confirmDelete(<?php echo $bookmark['bookmark_id']; ?>)">Hapus</button>
                            </div>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>

            <!-- Pagination -->
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?page=<?php echo $page - 1; ?>">Previous</a>
                <?php endif; ?>

                <span>Page <?php echo $page; ?> of <?php echo $totalPages; ?></span>

                <?php if ($page < $totalPages): ?>
                    <a href="?page=<?php echo $page + 1; ?>">Next</a>
                <?php endif; ?>
            </div>

            <?php else: ?>
                <p>You have no bookmarks yet. Start reading and save your progress!</p>
            <?php endif; ?>
        </div>
    </div>
    
    <div id="confirmDeleteModal" class="modal">
        <div class="modal-content">
            <span class="close-btn" onclick="closeModal()">&times;</span>
            <h2>Apakah kamu yakin ingin menghapus bookmark ini?</h2>
            <form action="" method="POST" id="deleteForm">
                <input type="hidden" name="delete_bookmark_id" id="deleteBookmarkId">
                <button type="submit" class="confirm-btn">Ya, Hapus</button>
                <button type="button" class="cancel-btn" onclick="closeModal()">Batal</button>
            </form>
        </div>
    </div>
    <footer class="footer">
        &copy; 2024 AnHar. Semua hak cipta dilindungi.
    </footer>
    <script>
        function toggleNavbar() {
            const navbarLinks = document.getElementById('navbarLinks');
            navbarLinks.classList.toggle('active');
        }

        function confirmDelete(bookmarkId) {
            document.getElementById('deleteBookmarkId').value = bookmarkId;
            document.getElementById('confirmDeleteModal').style.display = 'block';
        }

        function closeModal() {
            document.getElementById('confirmDeleteModal').style.display = 'none';
        }

        window.onclick = function(event) {
            if (event.target == document.getElementById('confirmDeleteModal')) {
                closeModal();
            }
        }
    </script>
</body>
</html>
