<?php
session_start();

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header('Location: loreg.php');
    exit;
}

require_once 'db.php'; 

$uploadError = '';
$updateError = '';

$perPage = 6;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$start = ($page - 1) * $perPage;

$stmtTotalBooks = $pdo->query("SELECT COUNT(*) FROM books");
$totalBooks = $stmtTotalBooks->fetchColumn();

$stmt = $pdo->prepare("SELECT * FROM books ORDER BY date DESC LIMIT :start, :perPage");
$stmt->bindParam(':start', $start, PDO::PARAM_INT);
$stmt->bindParam(':perPage', $perPage, PDO::PARAM_INT);
$stmt->execute();
$books = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalPages = ceil($totalBooks / $perPage);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update'])) { 
        $id = $_POST['id'];
        $title = $_POST['title'];
        $rating = $_POST['rating'];
        $chapter = $_POST['chapter'];
        $genre = $_POST['genre'];
        $type = $_POST['type'];
        $release = $_POST['release'];
        $status = $_POST['status'];
        $synopsis = $_POST['synopsis']; 
        $image = $_POST['image'];  
        
        $stmt = $pdo->prepare("UPDATE books SET title = :title, rating = :rating, chapter = :chapter, genre = :genre, 
                              type = :type, `release` = :release, status = :status, synopsis = :synopsis, image = :image 
                              WHERE id = :id");
        $stmt->execute([
            'title' => $title,
            'rating' => $rating,
            'chapter' => $chapter,
            'genre' => $genre,
            'type' => $type,
            'release' => $release,
            'status' => $status,
            'synopsis' => $synopsis,
            'image' => $image,
            'id' => $id
        ]);

        header('Location: home.php'); 
        exit;
    } elseif (isset($_POST['upload'])) { 
        $title = $_POST['title'];
        $rating = $_POST['rating'];
        $chapter = $_POST['chapter'];
        $genre = $_POST['genre'];
        $type = $_POST['type'];
        $release = $_POST['release'];
        $status = $_POST['status'];
        $synopsis = $_POST['synopsis']; 
        $date = date('Y-m-d H:i:s'); 
        $uploadedImage = '';

        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $imageTmp = $_FILES['image']['tmp_name'];
            $fileExtension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));

            $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
            if (!in_array($fileExtension, $allowedExtensions)) {
                $uploadError = 'Format file tidak valid.';
            } else {
                $safeTitle = preg_replace('/[^A-Za-z0-9_\-]/', '_', $title);
                $imageName = "thumbnail_" . $safeTitle . '.' . $fileExtension;
                $imageDestination = "uploads/" . $imageName;

                if (move_uploaded_file($imageTmp, $imageDestination)) {
                    $uploadedImage = $imageDestination;
                } else {
                    $uploadError = 'Gagal mengunggah gambar.';
                }
            }
        } else {
            $uploadError = 'Harap pilih gambar untuk diunggah.';
        }

        if (!$uploadError) {
            $stmt = $pdo->prepare("INSERT INTO books (title, rating, chapter, genre, type, `release`, status, synopsis, date, image) 
                                   VALUES (:title, :rating, :chapter, :genre, :type, :release, :status, :synopsis, :date, :image)");
            $stmt->execute([
                'title' => $title,
                'rating' => $rating,
                'chapter' => $chapter,
                'genre' => $genre,
                'type' => $type,
                'release' => $release,
                'status' => $status,
                'synopsis' => $synopsis,
                'date' => $date,
                'image' => $uploadedImage
            ]);
            header('Location: home.php'); 
            exit;
        }
    }
}

$stmt = $pdo->query("SELECT * FROM books ORDER BY date DESC");
$books = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home</title>
    <link rel="stylesheet" href="home.css">
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
                <a href="home.php">Home</a>
                <a href="logout.php">Logout</a>
            </div>
        </div>
    </nav>
    <div class="container">
        <span>Selamat datang, <?php echo htmlspecialchars($_SESSION['username']); ?>!</span>
        <h1>Unggah Buku Baru</h1>
        <?php if ($uploadError): ?>
            <p style="color: red;"><?php echo $uploadError; ?></p>
        <?php endif; ?>
        <form action="" method="post" enctype="multipart/form-data">
            <div>
                <label for="title">Judul Buku:</label>
                <input type="text" id="title" name="title" required>
            </div>
            <div>
                <label for="rating">Rating:</label>
                <input type="number" id="rating" name="rating" step="0.1" min="0" max="5" required>
            </div>
            <div>
                <label for="chapter">Jumlah Chapter:</label>
                <input type="number" id="chapter" name="chapter" min="1" required>
            </div>
            <div>
                <label for="genre">Genre:</label>
                <input type="text" id="genre" name="genre" required>
            </div>
            <div>
                <label for="type">Type:</label>
                <input type="text" id="type" name="type" required>
            </div>
            <div>
                <label for="release">Release Date:</label>
                <input type="date" id="release" name="release" required>
            </div>
            <div>
                <label for="status">Status:</label>
                <input type="text" id="status" name="status" required>
            </div>
            <div>
                <label for="synopsis">Sinopsis:</label>
                <textarea id="synopsis" name="synopsis" class="textarea-synopsis" rows="4" required></textarea>
            </div>
            <div>
                <label for="image">Unggah Gambar:</label>
                <input type="file" id="image" name="image" accept="image/*" required>
            </div>
            <button type="submit" name="upload" class="button">Unggah Buku</button>
        </form>
    </div>
<div class="container">
    <h1>Daftar Buku</h1>
    <div class="item-grid">
        <?php foreach ($books as $book): ?>
            <div class="item">
                <img src="<?php echo htmlspecialchars($book['image']); ?>" alt="Thumbnail">
                <div class="item-content">
                    <h2 class="item-title"><?php echo htmlspecialchars($book['title']); ?></h2>
                    <div class="item-details">
                        <span class="item-rating">
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
                        </span>
                    </div>
                    
                    <a href="edit.php?id=<?php echo $book['id']; ?>" class="button edit">Edit</a>
                    <button class="button delete" onclick="openModal(<?php echo $book['id']; ?>)">Hapus</button>
                    <div class="button-container">                  
                    <a href="newchapter.php?book_id=<?php echo $book['id']; ?>" class="button new-chapter">New</a>
  
                    <a href="chapterlist.php?book_id=<?php echo $book['id']; ?>" class="button chapter-list">List</a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <div class="pagination">
        <?php if ($page > 1): ?>
            <a href="?page=<?php echo $page - 1; ?>" class="prev">Prev</a>
        <?php endif; ?>

        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <a href="?page=<?php echo $i; ?>" class="<?php echo $i == $page ? 'active' : ''; ?>"><?php echo $i; ?></a>
        <?php endfor; ?>

        <?php if ($page < $totalPages): ?>
            <a href="?page=<?php echo $page + 1; ?>" class="next">Next</a>
        <?php endif; ?>
    </div>
</div>

<div id="deleteModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal()">&times;</span>
        <p>Apakah Anda yakin ingin menghapus buku ini?</p>
        <div class="modal-actions">
            <button id="confirmDelete" class="button confirm">Ya</button>
           
            <button class="button canceled" onclick="closeModal()">Tidak</button>
        </div>
    </div>
</div>
<div id="successModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeSuccessModal()">&times;</span>
        <p>Buku berhasil dihapus!</p>
    </div>
</div>

<script>
    function openModal(bookId) {
        const deleteModal = document.getElementById("deleteModal");
        const confirmButton = document.getElementById("confirmDelete");

        confirmButton.setAttribute("data-id", bookId);
        
        deleteModal.style.display = "block";
    }

    function closeModal() {
        const deleteModal = document.getElementById("deleteModal");
        deleteModal.style.display = "none";
    }

    function closeSuccessModal() {
        const successModal = document.getElementById("successModal");
        successModal.style.display = "none";
        location.reload(); 
    }

    document.getElementById("confirmDelete").addEventListener("click", function () {
        const bookId = this.getAttribute("data-id"); 
        if (bookId) {
            fetch(`delete.php?id=${bookId}`, {
                method: "GET", 
            })
            .then((response) => {
                if (response.ok) {
                    closeModal();
                    const successModal = document.getElementById("successModal");
                    successModal.style.display = "block";
                } else {
                    // Tangani error
                    alert("Gagal menghapus buku!");
                }
            })
            .catch((error) => {
                console.error("Error:", error);
                alert("Terjadi kesalahan saat menghapus buku!");
            });
        }
    });
</script>
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
