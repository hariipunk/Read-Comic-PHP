<?php
session_start();
require_once 'db.php'; 

$bookId = $_GET['id'];
$stmt = $pdo->prepare("SELECT * FROM books WHERE id = :id");
$stmt->bindParam(':id', $bookId, PDO::PARAM_INT);
$stmt->execute();
$book = $stmt->fetch(PDO::FETCH_ASSOC);

$userIp = $_SERVER['REMOTE_ADDR'];

$readCheckStmt = $pdo->prepare("SELECT COUNT(*) FROM book_reads WHERE book_id = :book_id AND ip_address = :ip_address");
$readCheckStmt->execute(['book_id' => $bookId, 'ip_address' => $userIp]);
$readCount = $readCheckStmt->fetchColumn();

if ($readCount == 0) {
    $insertReadStmt = $pdo->prepare("INSERT INTO book_reads (book_id, ip_address) VALUES (:book_id, :ip_address)");
    $insertReadStmt->execute(['book_id' => $bookId, 'ip_address' => $userIp]);

    $newReadCount = $book['read_count'] + 1;
    $updateStmt = $pdo->prepare("UPDATE books SET read_count = :read_count WHERE id = :id");
    $updateStmt->bindParam(':read_count', $newReadCount, PDO::PARAM_INT);
    $updateStmt->bindParam(':id', $bookId, PDO::PARAM_INT);
    $updateStmt->execute();
}

$chapterStmt = $pdo->prepare("SELECT * FROM chapters WHERE book_id = :book_id ORDER BY chapter_number DESC");
$chapterStmt->execute(['book_id' => $bookId]);
$chapters = $chapterStmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_comment'])) {
    $comment = $_POST['comment'];
    $name = isset($_SESSION['username']) ? $_SESSION['username'] : $_POST['name'];
    if (!empty($comment)) {
        $stmt = $pdo->prepare("INSERT INTO comments (book_id, user_name, comment, created_at) VALUES (:book_id, :user_name, :comment, NOW())");
        $stmt->execute([
            'book_id' => $bookId,
            'user_name' => $name,
            'comment' => $comment
        ]);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($book['title']); ?></title>
    <link rel="stylesheet" href="read.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
</head>
<body>
    <!-- Navbar -->
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
        <div class="book-detail">
            <div class="left-column">
                <div class="book-thumbnail">
                    <img src="<?php echo htmlspecialchars($book['image']); ?>" alt="Thumbnail">
                </div>
                    
                <div class="book-info">
                    <div class="info-box">
                    <h1><?php echo htmlspecialchars($book['title']); ?></h1>
                    <div class="rating">
                        <?php
                        $fullStars = floor($book['rating']);
                        $halfStar = ($book['rating'] - $fullStars) >= 0.5 ? 1 : 0;
                        for ($i = 0; $i < $fullStars; $i++) {
                            echo '<i class="fa fa-star"></i>';
                        }
                        if ($halfStar) {
                            echo '<i class="fa fa-star-half-o"></i>';
                        }
                        for ($i = $fullStars + $halfStar; $i < 5; $i++) {
                            echo '<i class="fa fa-star-o"></i>';
                        }
                        ?>
                    </div>
                    </div>
                    <div class="inpo-box">
                    <p class="genre"><strong>Genre :</strong> <?php echo htmlspecialchars($book['genre']); ?></p>
                    <p class="type"><strong>Type :</strong> <?php echo htmlspecialchars($book['type']); ?></p>
                    <p class="release"><strong>Release :</strong> <?php echo htmlspecialchars($book['release']); ?></p>
                    <p class="status"><strong>Status :</strong> <?php echo htmlspecialchars($book['status']); ?></p>
                    <p class="chapter"><strong>Total Chapters :</strong> <?php echo htmlspecialchars($book['chapter']); ?></p>
                    </div>
                </div>
            </div>
            <div class="bottom-column">
                <div class="book-synopsis">
                    <h2>Synopsis</h2>
                    <p><?php echo nl2br(htmlspecialchars($book['synopsis'])); ?></p>
                </div>
                <div class="book-chapters">
                    <h2>Chapters</h2>
                    <?php if (count($chapters) > 0): ?>
                        <ul>
                            <?php foreach ($chapters as $chapter): ?>
                                <li>
                                    <a href="chapter.php?id=<?php echo $chapter['id']; ?>">
                                        Chapter <?php echo $chapter['chapter_number']; ?>
                                    </a>
                                    <span class="chapter-update-date">
                                        <?php echo date('d M Y', strtotime($chapter['created_at'])); ?>
                                    </span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p>Belum ada chapter untuk buku ini.</p>
                    <?php endif; ?>
                </div>
                <div class="comment-section">
                    <h2>Kolom Komentar</h2>
                    <?php if (isset($_SESSION['username'])): ?>
                        <form action="" method="POST">
                            <textarea name="comment" placeholder="Tulis komentar kamu..." required></textarea>
                            <button type="submit" name="submit_comment" class="button">Kirim Komentar</button>
                        </form>
                    <?php else: ?>
                        <form action="" method="POST">
                            <input type="text" name="name" placeholder="Masukkan nama kamu" required>
                            <textarea name="comment" placeholder="Tulis komentar kamu..." required></textarea>
                            <button type="submit" name="submit_comment" class="button">Kirim Komentar</button>
                        </form>
                    <?php endif; ?>
                        <h3>Komentar:</h3>
                        <?php
                        $commentsStmt = $pdo->prepare("SELECT * FROM comments WHERE book_id = :book_id ORDER BY created_at DESC");
                        $commentsStmt->execute(['book_id' => $bookId]);
                        $comments = $commentsStmt->fetchAll(PDO::FETCH_ASSOC);

                        foreach ($comments as $comment): ?>
                            <div class="comment">
                                <strong><?php echo htmlspecialchars($comment['user_name']); ?>:</strong>
                                <p><?php echo nl2br(htmlspecialchars($comment['comment'])); ?></p>
                                <small><?php echo $comment['created_at']; ?></small>
                            </div>
                        <?php endforeach; ?>
                    
                </div>
            </div>
        </div>
    </div>

    <script>
        function toggleNavbar() {
            const navbarLinks = document.getElementById('navbarLinks');
            navbarLinks.classList.toggle('active');
        }
    </script>
</body>
<footer class="footer">
    &copy; 2024 AnHar. Semua hak cipta dilindungi.
</footer>
</html>
