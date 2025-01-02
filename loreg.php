<?php
session_start();
require_once 'db.php'; 

$loginError = '';
if (isset($_POST['login'])) {
    $usernameOrEmail = $_POST['username_or_email'];
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :username OR email = :email");
    $stmt->execute(['username' => $usernameOrEmail, 'email' => $usernameOrEmail]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id']; 
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role']; 

        if ($user['role'] === 'member') {
            header('Location: member.php');
        } else {
            header('Location: home.php');
        }
        exit;
    } else {
        $loginError = 'Username atau password salah.';
    }
}

$registerError = '';
$registerSuccess = '';
if (isset($_POST['register'])) {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];

    if ($password !== $confirmPassword) {
        $registerError = 'Password tidak cocok.';
    } elseif (strlen($password) < 6) {
        $registerError = 'Password harus memiliki minimal 6 karakter.';
    } else {
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
        try {
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (:username, :email, :password, 'member')");
            $stmt->execute([
                'username' => $username,
                'email' => $email,
                'password' => $hashedPassword
            ]);
            $registerSuccess = 'Pendaftaran berhasil! Silakan login.';
        } catch (PDOException $e) {
            $registerError = 'Username atau email sudah digunakan.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login & Register</title>
    <link rel="stylesheet" href="loreg.css">
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
                <a href="loreg.php">Login/Register</a>
            </div>
        </div>
    </nav>
    <div class="container">
        <div class="form-toggle-buttons">
            <button type="button" id="loginBtn" onclick="showLoginForm()">Login</button>
            <button type="button" id="registerBtn" onclick="showRegisterForm()">Register</button>
        </div>
        <div class="welcome-section" id="loginForm">
            <h1>Login</h1>
            <form action="" method="post">
                <?php if ($loginError): ?>
                    <p style="color: red;"><?php echo $loginError; ?></p>
                <?php endif; ?>
                <div>
                    <label for="username_or_email">Username or Email</label>
                    <input type="text" name="username_or_email" id="username_or_email" required>
                </div>
                <div>
                    <label for="password">Password</label>
                    <input type="password" name="password" id="password" required>
                </div>
                <button type="submit" name="login" class="button">Login</button>
            </form>
        </div>
        <div class="welcome-section" id="registerForm" style="display: none;">
            <h1>Register</h1>
            <form action="" method="post">
                <?php if ($registerError): ?>
                    <p style="color: red;"><?php echo $registerError; ?></p>
                <?php elseif ($registerSuccess): ?>
                    <p style="color: green;"><?php echo $registerSuccess; ?></p>
                <?php endif; ?>
                <div>
                    <label for="reg_username">Username</label>
                    <input type="text" name="username" id="reg_username" required>
                </div>
                <div>
                    <label for="reg_email">Email</label>
                    <input type="email" name="email" id="reg_email" required>
                </div>
                <div>
                    <label for="reg_password">Password</label>
                    <input type="password" name="password" id="reg_password" required>
                </div>
                <div>
                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" name="confirm_password" id="confirm_password" required>
                </div>
                <button type="submit" name="register" class="button">Register</button>
            </form>
        </div>
    </div>
    <script>
        function toggleNavbar() {
            const navbarLinks = document.getElementById('navbarLinks');
            navbarLinks.classList.toggle('active');
        }

        function showLoginForm() {
            document.getElementById('loginForm').style.display = 'block';
            document.getElementById('registerForm').style.display = 'none';
            document.getElementById('loginBtn').classList.add('active');
            document.getElementById('registerBtn').classList.remove('active');
        }

        function showRegisterForm() {
            document.getElementById('loginForm').style.display = 'none';
            document.getElementById('registerForm').style.display = 'block';
            document.getElementById('registerBtn').classList.add('active');
            document.getElementById('loginBtn').classList.remove('active');
        }
    </script>
</body>
<footer class="footer">
    &copy; 2024 AnHar. Semua hak cipta dilindungi.
    <br>
    <a href="#">Kebijakan Privasi</a> | <a href="#">Syarat dan Ketentuan</a>
</footer>
</html>
