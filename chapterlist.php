<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header('Location: loreg.php'); 
    exit;
}

if (!isset($_GET['book_id'])) {
    echo "Book ID tidak ditemukan.";
    exit;
}

$book_id = $_GET['book_id'];

$notification = "";

if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $chapter_id = $_GET['id'];

    $stmtChapter = $pdo->prepare("SELECT chapter_number FROM chapters WHERE id = :id");
    $stmtChapter->execute(['id' => $chapter_id]);
    $chapter = $stmtChapter->fetch(PDO::FETCH_ASSOC);

    if ($chapter) {
        $chapterFolder = "uploads/chapter_" . $chapter['chapter_number'];
        if (is_dir($chapterFolder)) {
            $files = glob($chapterFolder . '/*'); 
            foreach ($files as $file) {
                if (is_file($file)) unlink($file); 
            }
            rmdir($chapterFolder); 
        }

        $stmtDelete = $pdo->prepare("DELETE FROM chapters WHERE id = :id");
        $stmtDelete->execute(['id' => $chapter_id]);

        $notification = "Chapter " . $chapter['chapter_number'] . " berhasil dihapus.";
    }
}

$stmt = $pdo->prepare("SELECT title FROM books WHERE id = :book_id");
$stmt->execute(['book_id' => $book_id]);
$book = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$book) {
    echo "Buku tidak ditemukan.";
    exit;
}

$perPage = 10; 
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$start = ($page - 1) * $perPage;

$stmtTotal = $pdo->prepare("SELECT COUNT(*) FROM chapters WHERE book_id = :book_id");
$stmtTotal->execute(['book_id' => $book_id]);
$totalChapters = $stmtTotal->fetchColumn();

$stmtChapters = $pdo->prepare("SELECT * FROM chapters WHERE book_id = :book_id ORDER BY chapter_number DESC LIMIT :start, :perPage");
$stmtChapters->bindParam(':book_id', $book_id, PDO::PARAM_INT);
$stmtChapters->bindParam(':start', $start, PDO::PARAM_INT);
$stmtChapters->bindParam(':perPage', $perPage, PDO::PARAM_INT);
$stmtChapters->execute();
$chapters = $stmtChapters->fetchAll(PDO::FETCH_ASSOC);

$totalPages = ceil($totalChapters / $perPage);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chapter List - <?php echo htmlspecialchars($book['title']); ?></title>
    <link rel="stylesheet" href="editlist.css">
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
                <a href="home.php">Home</a>
                <a href="logout.php">Logout</a>
            </div>
        </div>
    </nav>
<?php if ($notification): ?>
    <div id="notification" class="notification-popup"><?php echo htmlspecialchars($notification); ?></div>
    <script>
        const notification = document.getElementById('notification');
        notification.style.display = 'block';

        setTimeout(() => {
            notification.style.display = 'none';
        }, 3000);
    </script>
<?php endif; ?>

<div class="container">
    <h1>Daftar Chapter - <?php echo htmlspecialchars($book['title']); ?></h1>

    <?php if (empty($chapters)): ?>
        <p>Belum ada chapter untuk buku ini.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Chapter</th>
                    <th>Jumlah Gambar</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($chapters as $index => $chapter): ?>
                    <tr>
                        <td><?php echo "Chapter " . $chapter['chapter_number']; ?></td>
                        <td><?php echo count(explode(',', $chapter['chapter_image'])); ?> Gambar</td>
                        <td>
                            <button class="button delete" onclick="openModal(<?php echo $chapter['id']; ?>)">Delete</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="?book_id=<?php echo $book_id; ?>&page=<?php echo $page - 1; ?>" class="prev">Prev</a>
            <?php endif; ?>

            <?php
            $visiblePages = 5;
            $startPage = max(1, $page - floor($visiblePages / 2));
            $endPage = min($totalPages, $startPage + $visiblePages - 1);

            for ($i = $startPage; $i <= $endPage; $i++):
            ?>
                <a href="?book_id=<?php echo $book_id; ?>&page=<?php echo $i; ?>" class="<?php echo $i == $page ? 'active' : ''; ?>"><?php echo $i; ?></a>
            <?php endfor; ?>

            <?php if ($page < $totalPages): ?>
                <a href="?book_id=<?php echo $book_id; ?>&page=<?php echo $page + 1; ?>" class="next">Next</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<div id="deleteModal" class="modal">
    <div class="modal-content">
        <p>Apakah Anda yakin ingin menghapus chapter ini?</p>
        <div class="modal-buttons">
            <button onclick="confirmDelete()">Ya</button>
            <button onclick="closeModal()">Tidak</button>
        </div>
    </div>
</div>

    <script>
        function toggleNavbar() {
            const navbarLinks = document.getElementById('navbarLinks');
            navbarLinks.classList.toggle('active');
        }
    </script>
<script>
    let deleteId = null;

    function openModal(id) {
        deleteId = id;
        document.getElementById('deleteModal').style.display = 'flex';
    }

    function closeModal() {
        document.getElementById('deleteModal').style.display = 'none';
        deleteId = null;
    }

    function confirmDelete() {
        if (deleteId) {
            window.location.href = `chapterlist.php?book_id=<?php echo $book_id; ?>&action=delete&id=${deleteId}`;
        }
    }
</script>

</body>
<footer class="footer">
    &copy; 2024 AnHar. Semua hak cipta dilindungi.
</footer>
</html>
