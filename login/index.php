<?php
session_start();

function get_db_connection() {
    $env_data = read_env_file('../.env');
    
    $db_host = $env_data['DB_SERVER'] ?? '';
    $db_username = $env_data['DB_USERNAME'] ?? '';
    $db_password = $env_data['DB_PASSWORD'] ?? '';
    $db_name = $env_data['DB_NAME'] ?? '';
    
    $conn = new mysqli($db_host, $db_username, $db_password, $db_name);
    
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    return $conn;
}

function read_env_file($file_path) {
    $env_file = file_get_contents($file_path);
    $env_lines = explode("\n", $env_file);
    $env_data = [];
    
    foreach ($env_lines as $line) {
        $line_parts = explode('=', $line);
        if (count($line_parts) == 2) {
            $key = trim($line_parts[0]);
            $value = trim($line_parts[1]);
            $env_data[$key] = $value;
        }
    }
    
    return $env_data;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];
    
    $conn = get_db_connection();
    
    $stmt = $conn->prepare("SELECT userid, password, confirmed FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();
    
    if ($stmt->num_rows == 1) {
        $stmt->bind_result($userid, $hashed_password, $confirmed);
        $stmt->fetch();
        
        if (password_verify($password, $hashed_password)) {
            if ($confirmed == 'Yes') {
                $_SESSION['userid'] = $userid;
                header("Location: ../dashboard"); 
                exit();
            } else {
                $login_error = "Kérem fogadja el a visszaigazolót az emailben!";
            }
        } else {
            $login_error = "Hibás email cím vagy jelszó!";
        }
    } else {
        $login_error = "Hibás email cím vagy jelszó!";
    }
    
    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <title>Bejelentkezés</title>
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
<div class="container">
    <h2>Bejelentkezés</h2>
    <?php if (isset($login_error)): ?>
        <div class="alert alert-danger"><?php echo $login_error; ?></div>
    <?php endif; ?>
    <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
        <div class="form-group">
            <label for="email">Email cím</label>
            <input type="email" class="form-control" id="email" name="email" required>
        </div>
        <div class="form-group">
            <label for="password">Jelszó</label>
            <input type="password" class="form-control" id="password" name="password" required>
        </div>
        <button type="submit" class="btn btn-primary">Bejelentkezés</button>
    </form>
</div>
</body>
</html>
