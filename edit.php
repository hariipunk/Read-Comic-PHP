<?php
session_start();

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header('Location: loreg.php'); 
    exit;
}

require_once 'db.php'; 

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $stmt = $pdo->prepare("SELECT * FROM books WHERE id = :id");
    $stmt->execute(['id' => $id]);
    $book = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$book) {
        header('Location: home.php');
        exit;
    }
} else {
    header('Location: home.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'];
    $rating = $_POST['rating'];
    $chapter = $_POST['chapter'];
    $genre = $_POST['genre'];
    $type = $_POST['type'];
    $release = $_POST['release'];
    $synopsis = $_POST['synopsis'];
    $status = $_POST['status'];
    $image = $book['image']; 

    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $imageTmp = $_FILES['image']['tmp_name'];
        $fileExtension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));

        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
        if (!in_array($fileExtension, $allowedExtensions)) {
            echo "Format file tidak valid.";
            exit;
        }

        $safeTitle = preg_replace('/[^A-Za-z0-9_\-]/', '_', $title);
        $imageName = "thumbnail_" . $safeTitle . '.' . $fileExtension;
        $imageDestination = "uploads/" . $imageName;

        if (file_exists($book['image']) && is_file($book['image'])) {
            unlink($book['image']);
        }

        if (move_uploaded_file($imageTmp, $imageDestination)) {
            $image = $imageDestination; 
        } else {
            echo "Gagal mengunggah gambar.";
            exit;
        }
    }

    $stmt = $pdo->prepare("UPDATE books SET title = :title, rating = :rating, chapter = :chapter, genre = :genre, 
                          type = :type, `release` = :release, status = :status, image = :image, synopsis = :synopsis 
                          WHERE id = :id");
    $stmt->execute([
        'title' => $title,
        'rating' => $rating,
        'chapter' => $chapter,
        'genre' => $genre,
        'type' => $type,
        'release' => $release,
        'status' => $status,
        'image' => $image,
        'synopsis' => $synopsis,
        'id' => $id
    ]);

    header('Location: home.php');
    exit;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Buku</title>
    <link rel="stylesheet" href="home.css">
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
                <a href="home.php">Home</a>
                <a href="logout.php">Logout</a>
            </div>
        </div>
    </nav>
    <!-- Form Edit Buku -->
    <div class="container">
        <h1>Edit Buku</h1>
        <form action="edit.php?id=<?php echo $book['id']; ?>" method="post" enctype="multipart/form-data">
            <div>
                <label for="title">Judul Buku:</label>
                <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($book['title']); ?>" required>
            </div>
            <div>
                <label for="rating">Rating:</label>
                <input type="number" id="rating" name="rating" step="0.1" min="0" max="5" value="<?php echo htmlspecialchars($book['rating']); ?>" required>
            </div>
            <div>
                <label for="chapter">Jumlah Chapter:</label>
                <input type="number" id="chapter" name="chapter" value="<?php echo htmlspecialchars($book['chapter']); ?>" min="1" required>
            </div>
            <div>
                <label for="genre">Genre:</label>
                <input type="text" id="genre" name="genre" value="<?php echo htmlspecialchars($book['genre']); ?>" required>
            </div>
            <div>
                <label for="type">Type:</label>
                <input type="text" id="type" name="type" value="<?php echo htmlspecialchars($book['type']); ?>" required>
            </div>
            <div>
                <label for="release">Release Date:</label>
                <input type="date" id="release" name="release" value="<?php echo htmlspecialchars($book['release']); ?>" required>
            </div>
            <div>
                <label for="status">Status:</label>
                <input type="text" id="status" name="status" value="<?php echo htmlspecialchars($book['status']); ?>" required>
            </div>
            <div>
                <label for="synopsis">Sinopsis:</label>
                <textarea id="synopsis" name="synopsis" class="textarea-synopsis" rows="4" required><?php echo htmlspecialchars($book['synopsis']); ?></textarea>
            </div>

            <div>
                <label for="image">Unggah Gambar (opsional):</label>
                <input type="file" id="image" name="image" accept="image/*">
                <img src="<?php echo htmlspecialchars($book['image']); ?>" alt="Current Image" width="100" />
            </div>
            <button type="submit" name="update" class="button">Update Buku</button>
        </form>
    </div>
</body>
 <!-- Script -->
    <script>
        function toggleNavbar() {
            const navbarLinks = document.getElementById('navbarLinks');
            navbarLinks.classList.toggle('active');
        }
    </script>
<footer class="footer">
    &copy; 2024 AnHar. Semua hak cipta dilindungi.
</footer>
</html>
