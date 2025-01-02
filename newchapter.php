<?php
session_start();

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header('Location: loreg.php'); 
    exit;
}

require_once 'db.php'; 

if (isset($_GET['book_id'])) {
    $book_id = $_GET['book_id'];
} else {
    header('Location: home.php');
    exit;
}

$stmt = $pdo->prepare("SELECT title FROM books WHERE id = :book_id");
$stmt->execute(['book_id' => $book_id]);
$book = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$book) {
    header('Location: home.php');
    exit;
}

$bookTitle = $book['title']; 
$bookFolder = "uploads/" . preg_replace('/[^A-Za-z0-9_\-]/', '_', $bookTitle);

if (!is_dir($bookFolder)) {
    mkdir($bookFolder, 0755, true);
}

$stmt = $pdo->prepare("SELECT IFNULL(MAX(chapter_number), 0) AS max_chapter FROM chapters WHERE book_id = :book_id");
$stmt->execute(['book_id' => $book_id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
$nextChapterNumber = $row['max_chapter'] + 1;

$chapterFolder = $bookFolder . "/chapter_" . $nextChapterNumber;
if (!is_dir($chapterFolder)) {
    mkdir($chapterFolder, 0755, true); 
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = [];
    $uploadedImages = [];

    if (empty($_FILES['chapter_images']['name'][0]) && empty($_POST['image_url'])) {
        $errors[] = "Harap unggah gambar melalui file atau URL.";
    }

    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif']; 
    $maxFileSize = 100 * 1024 * 1024; 

    if (!empty($_FILES['chapter_images']['name'][0])) {
        $chapterPrefix = "chp" . $nextChapterNumber . "_"; 

        $existingFiles = scandir($chapterFolder);
        $existingNumbers = [];
        foreach ($existingFiles as $file) {
            if (preg_match('/^chp' . $nextChapterNumber . '_(\d+)\.(jpg|jpeg|png|gif)$/i', $file, $matches)) {
                $existingNumbers[] = (int)$matches[1];
            }
        }

        $counter = empty($existingNumbers) ? 1 : (max($existingNumbers) + 1);

        foreach ($_FILES['chapter_images']['tmp_name'] as $key => $tmpName) {
            $fileExtension = strtolower(pathinfo($_FILES['chapter_images']['name'][$key], PATHINFO_EXTENSION));
            if (!in_array($fileExtension, $allowedExtensions)) {
                $errors[] = "Tipe file tidak valid: " . $_FILES['chapter_images']['name'][$key];
                continue;
            }

            if ($_FILES['chapter_images']['size'][$key] > $maxFileSize) {
                $errors[] = "Ukuran file terlalu besar: " . $_FILES['chapter_images']['name'][$key];
                continue;
            }

            if (!getimagesize($tmpName)) {
                $errors[] = "File bukan gambar yang valid: " . $_FILES['chapter_images']['name'][$key];
                continue;
            }

            $imageName = "chp" . $nextChapterNumber . "_" . $counter . '.' . $fileExtension;
            $imageDestination = $chapterFolder . "/" . $imageName;

            if (move_uploaded_file($tmpName, $imageDestination)) {
                $uploadedImages[] = $imageDestination;
                $counter++;
            } else {
                $errors[] = "Gagal mengunggah gambar: " . $_FILES['chapter_images']['name'][$key];
            }
        }
    }

    if (!empty($_POST['image_url'])) {
        $imageUrls = array_filter(array_map('trim', explode(',', $_POST['image_url']))); 
        $counter = 1; 

        foreach ($imageUrls as $url) {
            $urlHeaders = get_headers($url, 1);

            if (!isset($urlHeaders['Content-Type']) || !preg_match('/image\/(jpeg|png|gif)/i', $urlHeaders['Content-Type'])) {
                $errors[] = "URL tidak valid atau bukan gambar: $url";
                continue;
            }

            $fileExtension = explode('/', $urlHeaders['Content-Type'])[1];
            if (!in_array($fileExtension, $allowedExtensions)) {
                $errors[] = "Ekstensi gambar dari URL tidak valid: $url";
                continue;
            }

            $imageName = "chp" . $nextChapterNumber . "_url_" . $counter . '.' . $fileExtension;
            $imageDestination = $chapterFolder . "/" . $imageName;

            if (@file_put_contents($imageDestination, file_get_contents($url))) {
                $uploadedImages[] = $imageDestination;
                $counter++; 
            } else {
                $errors[] = "Gagal mengunduh gambar dari URL: $url";
            }
        }
    }

    if (empty($errors)) {
        $imagesString = implode(',', $uploadedImages);

        $stmt = $pdo->prepare("INSERT INTO chapters (book_id, chapter_number, chapter_image) VALUES (:book_id, :chapter_number, :chapter_image)");
        $stmt->execute([
            'book_id' => $book_id,
            'chapter_number' => $nextChapterNumber,
            'chapter_image' => $imagesString
        ]);
         
$stmt = $pdo->prepare("UPDATE books SET updated_at = NOW() WHERE id = :book_id");
$stmt->execute(['book_id' => $book_id]);
        
        header("Location: read.php?id=$book_id");
        exit;
    }
}
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Chapter</title>
    <link rel="stylesheet" href="home.css">
    <style>
                
.preview-container {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
}
.preview-container img {
    max-width: 150px;
    max-height: 150px;
    border: 1px solid #ddd;
    border-radius: 5px;
}
.loading-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.6);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 1000;
    color: white;
    flex-direction: column;
}

.progress-bar-container {
    width: 100%;
    background-color: #f3f3f3;
    border-radius: 5px;
    margin-top: 10px;
    display: none; 
}

.progress-bar {
    width: 0;
    height: 20px;
    background-color: #4caf50;
    border-radius: 5px;
}

.spinner {
    border: 4px solid rgba(255, 255, 255, 0.3);
    border-radius: 50%;
    border-top: 4px solid #fff;
    width: 50px;
    height: 50px;
    animation: spin 1s linear infinite;
}

.modal-content button {
    background-color: #1abc9c; 
    color: #fff; 
    border: none; 
    padding: 10px 20px; 
    font-size: 1em; 
    border-radius: 5px; 
    cursor: pointer; 
    font-family: 'Poppins', sans-serif; 
    transition: background-color 0.3s ease, transform 0.2s ease;
}

.modal-content button:hover {
    background-color: #16a085; 
    transform: scale(1.05); 
}

.modal-content button:active {
    transform: scale(0.95); 
}

.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(44, 62, 80, 0.8); 
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 1000; 
}

.modal-content {
    background: #fff; 
    border-radius: 10px; 
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
    padding: 20px 30px; 
    max-width: 400px; 
    text-align: center; 
    animation: fadeIn 0.3s ease-out; 
}

.modal-content p {
    font-size: 1.2em; 
    color: #34495e; 
    margin-bottom: 20px; 
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(-20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="navbar-container">
            <a href="home.php" class="navbar-logo">BukuKu</a>
            <div class="navbar-links">
                <a href="logout.php">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <h1>Tambah Chapter untuk Buku "<?php echo htmlspecialchars($bookTitle); ?>" - Chapter ke-<?php echo $nextChapterNumber; ?></h1>

        <form action="newchapter.php?book_id=<?php echo $book_id; ?>" method="post" enctype="multipart/form-data" id="uploadForm">
            <div>
                <label for="chapter_images">Unggah Gambar Chapter:</label>
                <input type="file" id="chapter_images" name="chapter_images[]" accept="image/*" multiple>
            </div>
        
            <div>
                <label for="image_url">Masukkan URL Gambar:</label>
                <textarea id="image_url" name="image_url" placeholder="Masukkan URL gambar di sini..."></textarea>
            </div>
            <div class="preview-container" id="preview-container"></div>
            <div class="progress-bar-container" id="progress-bar-container">
                <div class="progress-bar" id="progress-bar"></div>
            </div>
            <?php if (!empty($errors)): ?>
                <?php foreach ($errors as $error): ?>
                    <p style="color: red;"><?php echo $error; ?></p>
                <?php endforeach; ?>
            <?php endif; ?>
            <button type="submit">Unggah</button>
        </form>
    </div>

    <div id="loading" style="display: none;">
        <div class="loading-overlay">
            <div class="spinner"></div>
            <p>Mengunggah gambar, harap tunggu...</p>
        </div>
    </div>
    <div id="successModal" style="display: none;">
    <div class="modal-overlay">
        <div class="modal-content">
            <p>Gambar berhasil diunggah!</p>
            <button id="closeModal">Tutup</button>
        </div>
    </div>
    </div>
    <footer class="footer">
        &copy; 2024 AnHar. Semua hak cipta dilindungi.
    </footer>
    <script>
        const form = document.querySelector('form');
        const chapterImagesInput = document.getElementById('chapter_images');
        const previewContainer = document.getElementById('preview-container');
        const progressBarContainer = document.getElementById('progress-bar-container');
        const progressBar = document.getElementById('progress-bar');
        const loadingOverlay = document.getElementById('loading');
        
        chapterImagesInput.addEventListener('change', function(event) {
            const files = event.target.files;
            previewContainer.innerHTML = ''; 

            Array.from(files).forEach(file => {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    previewContainer.appendChild(img);
                };
                reader.readAsDataURL(file);
            });
        });

        form.addEventListener('submit', function(event) {
            event.preventDefault(); 
            loadingOverlay.style.display = 'block'; 
            progressBarContainer.style.display = 'block';

            const formData = new FormData(form);
            const xhr = new XMLHttpRequest();
            xhr.open('POST', form.action, true);

            xhr.upload.addEventListener('progress', function(e) {
                if (e.lengthComputable) {
                    const percent = (e.loaded / e.total) * 100;
                    progressBar.style.width = percent + '%';
                }
            });

            const successModal = document.getElementById('successModal');
            const closeModalButton = document.getElementById('closeModal');
            
            closeModalButton.addEventListener('click', function() {
                successModal.style.display = 'none';
            });
            
            xhr.onload = function() {
                loadingOverlay.style.display = 'none'; 
                progressBarContainer.style.display = 'none'; 
                if (xhr.status === 200) {
                    successModal.style.display = 'block'; 
                } else {
                    alert('Terjadi kesalahan saat mengunggah gambar.');
                }
            };

            xhr.send(formData);
        });
    </script>
</body>
</html>