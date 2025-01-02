<?php
session_start();

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header('Location: loreg.php');
    exit;
}

require_once 'db.php'; 

function deleteDirectory($dir) {
    if (!is_dir($dir)) {
        return false;
    }

    $files = array_diff(scandir($dir), array('.', '..'));

    foreach ($files as $file) {
        $filePath = $dir . DIRECTORY_SEPARATOR . $file;
        if (is_dir($filePath)) {
            deleteDirectory($filePath);
        } else {
            unlink($filePath);
        }
    }

    return rmdir($dir);
}

if (isset($_GET['id'])) {
    $id = $_GET['id'];

    $stmt = $pdo->prepare("SELECT title, image FROM books WHERE id = :id");
    $stmt->execute(['id' => $id]);
    $book = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($book) {
        $bookTitle = $book['title'];
        $thumbnail = $book['image']; 
        $bookFolder = "uploads/" . preg_replace('/[^A-Za-z0-9_\-]/', '_', $bookTitle);

        if (file_exists($thumbnail)) {
            unlink($thumbnail); 
        }

        deleteDirectory($bookFolder);
    }

    $stmt = $pdo->prepare("DELETE FROM books WHERE id = :id");
    $stmt->execute(['id' => $id]);

    echo json_encode(['status' => 'success']);
    exit;
} else {
    echo json_encode(['status' => 'error']);
    exit;
}
?>