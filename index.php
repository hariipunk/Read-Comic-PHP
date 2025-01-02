<?php
session_start();
require_once 'db.php'; 

$itemsPerPage = 9;

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;

$offset = ($page - 1) * $itemsPerPage;

$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM books");
$stmt->execute();
$totalBooks = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$totalPages = ceil($totalBooks / $itemsPerPage);

$stmt = $pdo->prepare("SELECT * FROM books ORDER BY updated_at DESC LIMIT :limit OFFSET :offset");
$stmt->bindValue(':limit', $itemsPerPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$books = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Buku</title>
    <link rel="stylesheet" href="index.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
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
        <h1>Daftar Buku</h1>
        <div class="item-grid">
            <?php if (count($books) > 0): ?>
                <?php foreach ($books as $book): ?>
                    <?php
                    $stmt = $pdo->prepare("SELECT * FROM chapters WHERE book_id = :book_id ORDER BY chapter_number DESC");
                    $stmt->execute(['book_id' => $book['id']]);
                    $chapters = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    ?>
                    
                    <div class="item">
                        <img src="<?php echo htmlspecialchars($book['image']); ?>" alt="Thumbnail">
                        <div class="item-content">
                            <h2 class="item-title">
                                <a href="read.php?id=<?php echo $book['id']; ?>">
                                    <?php echo htmlspecialchars($book['title']); ?>
                                </a>
                            </h2>
                            <div class="item-details">
                                <span class="item-chapter">
                                    <?php                                
                                    if (count($chapters) > 0) {
                                        $latestChapter = htmlspecialchars($chapters[0]['chapter_number']); 
                                        echo "Chapter " . $latestChapter;
                                        echo '';
                                    } else {
                                        echo "No chapters available.";
                                    }
                                    ?>
                                </span>
                            </div>

                            <div class="item-details">
                            <?php if (count($chapters) > 1): ?>
                                <div class="item-old-chapter">
                                        <?php
                                        echo "Chapter " . htmlspecialchars($chapters[1]['chapter_number']);
                                        ?>
                                    
                                </div>
                            <?php endif; ?>
                            </div>
                            <div class="item-rating">
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
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>Belum ada buku yang diunggah.</p>
            <?php endif; ?>
        </div>
        
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="?page=<?php echo $page - 1; ?>" class="pagination-prev">Prev</a>
            <?php endif; ?>

            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a href="?page=<?php echo $i; ?>" class="pagination-page <?php echo $i === $page ? 'active' : ''; ?>">
                    <?php echo $i; ?>
                </a>
            <?php endfor; ?>

            <?php if ($page < $totalPages): ?>
                <a href="?page=<?php echo $page + 1; ?>" class="pagination-next">Next</a>
            <?php endif; ?>
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
    <br>
    <a href="#">Kebijakan Privasi</a> | <a href="#">Syarat dan Ketentuan</a>
</footer>
</html>
