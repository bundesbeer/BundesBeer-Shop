<?php
require "config.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $u = $_POST["user"] ?? "";
    $p = $_POST["pass"] ?? "";

    if ($u === $ADMIN_USER && $p === $ADMIN_PASS) {
        $_SESSION["logged_in"] = true;
        header("Location: panel.php");
        exit;
    } else {
        $error = "Falsche Login-Daten!";
    }
}
?>
<!doctype html>
<html>
<head>
<title>Admin Login – Bundesbeer</title>
<meta charset="utf-8">
<style>
body{font-family:Arial;background:#f0e4bb;margin:0;padding:40px;}
.box{background:white;padding:20px;border-radius:12px;max-width:350px;margin:auto;
     border:2px solid #caa748;}
input{width:100%;padding:10px;margin:8px 0;border:2px solid #caa748;border-radius:8px;}
button{width:100%;padding:12px;background:#a97b1e;color:white;border:none;border-radius:8px;}
</style>
</head>
<body>

<div class="box">
<h2>Admin Login</h2>

<?php if(isset($error)): ?>
<p style="color:red;"><?php echo $error; ?></p>
<?php endif; ?>

<form method="post">
    <input type="text" name="user" placeholder="Benutzername">
    <input type="password" name="pass" placeholder="Passwort">
    <button>Login</button>
</form>
</div>

</body>
</html>
