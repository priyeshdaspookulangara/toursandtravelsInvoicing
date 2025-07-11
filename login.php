<?php
// config.php includes session_start()
require_once 'config.php';
require_once 'db.php'; // For sanitize_string, db_query, db_fetch_assoc

$page_title = "Admin Login";
$errors = [];
$message = '';

// If user is already logged in, redirect to dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';

    if (empty($username)) {
        $errors[] = "Username is required.";
    }
    if (empty($password)) {
        $errors[] = "Password is required.";
    }

    if (empty($errors)) {
        $conn = db_connect();
        if ($conn) {
            $sanitized_username = sanitize_string($username); // Sanitize before using in query

            $sql = "SELECT id, username, password, email FROM users WHERE username = '$sanitized_username'";
            $result = db_query($sql);

            if ($result) {
                if (mysqli_num_rows($result) == 1) {
                    $user = db_fetch_assoc($result);
                    if (password_verify($password, $user['password'])) {
                        // Password is correct, start session
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['username'] = $user['username'];
                        $_SESSION['user_email'] = $user['email'];

                        // Regenerate session ID for security
                        session_regenerate_id(true);

                        header("Location: index.php"); // Redirect to dashboard
                        exit;
                    } else {
                        $errors[] = "Invalid username or password.";
                    }
                } else {
                    $errors[] = "Invalid username or password.";
                }
            } else {
                // db_query handles logging the SQL error
                $errors[] = "Login failed due to a system error. Please try again later.";
            }
            // db_close(); // Connection closed by shutdown function or at end of script
        } else {
            $errors[] = "Database connection failed. Please try again later.";
        }
    }
}

include 'templates/header.php';
?>

<h2><?php echo $page_title; ?></h2>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <ul>
            <?php foreach ($errors as $error): ?>
                <li><?php echo htmlspecialchars($error); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<?php if (!empty($message)): ?>
    <div class="alert alert-success">
        <?php echo htmlspecialchars($message); ?>
    </div>
<?php endif; ?>

<form action="login.php" method="post">
    <div class="form-group">
        <label for="username">Username:</label>
        <input type="text" name="username" id="username" value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" required>
    </div>
    <div class="form-group">
        <label for="password">Password:</label>
        <input type="password" name="password" id="password" required>
    </div>
    <button type="submit" class="btn">Login</button>
</form>

<p style="margin-top: 20px;">
    Default credentials (if you ran setup.php):<br>
    Username: <strong>admin</strong><br>
    Password: <strong>password123</strong>
</p>

<?php
include 'templates/footer.php';
?>
