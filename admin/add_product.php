<?php
require "config.php";
if (!($_SESSION["logged_in"] ?? false)) { header("Location: login.php"); exit; }

$dataFile = __DIR__ . "/../data/products.json";
$uploadDir = __DIR__ . "/../uploads/";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $name = trim($_POST["name"]);
    $price = floatval($_POST["price"]);
    $sizes = array_filter(array_map('trim', explode(",", $_POST["sizes"] ?? "")));
    $colors = array_filter(array_map('trim', explode(",", $_POST["colors"] ?? "")));
    $category = $_POST["category"] ?? "merch";

    // Automatische ID
    $id = strtolower(preg_replace("/[^a-zA-Z0-9]+/", "-", $name)) . "-" . rand(1000,9999);

    // ---------- Bild Upload ----------
    $imgPath = "";
    if (isset($_FILES["img"]) && $_FILES["img"]["error"] === 0) {

        $ext = pathinfo($_FILES["img"]["name"], PATHINFO_EXTENSION);
        $ext = strtolower($ext);

        // nur erlaubte Formate
        if (in_array($ext, ["jpg","jpeg","png","webp"])) {

            $fileName = $id . "." . $ext;
            $target = $uploadDir . $fileName;

            if (move_uploaded_file($_FILES["img"]["tmp_name"], $target)) {
                $imgPath = "/test/uploads/" . $fileName;
            }
        }
    }

    // Wenn kein Bild hochgeladen wurde:
    if ($imgPath === "") {
        $imgPath = "/test/uploads/"; // Fallback (falls gewünscht)
    }

    $products = json_decode(file_get_contents($dataFile), true);

    $products[] = [
        "id" => $id,
        "name" => $name,
        "price" => $price,
        "img" => $imgPath,
        "sizes" => $sizes,
        "colors" => $colors,
        "category" => $category
    ];

    file_put_contents($dataFile, json_encode($products, JSON_PRETTY_PRINT));

    $success = "Produkt wurde erfolgreich hinzugefügt!";
}
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Neues Produkt – Admin</title>
<style>
body{font-family:Arial;background:#f0e4bb;padding:20px;}
.box{background:white;padding:20px;border-radius:12px;border:2px solid #caa748;max-width:500px;margin:auto;}
input{width:100%;padding:10px;margin-bottom:10px;border-radius:8px;border:2px solid #caa748;}
button{padding:12px;background:#a97b1e;color:white;border:none;border-radius:10px;width:100%;}
</style>
</head>
<body>

<div class="box">
<h2>➕ Neues Produkt hinzufügen</h2>

<?php if(isset($success)): ?>
<p style="color:green;"><?php echo $success; ?></p>
<?php endif; ?>

<?php
$CATEGORIES = [
    "custom" => "🎨 Personalisierte Produkte",
    "meme"   => "😂 Meme Shirts",
    "merch"  => "🍺 Regelmäßiger Merch"
];
?>

<h2>Neues Produkt</h2>

<form method="post" enctype="multipart/form-data">

    <label>Name:</label>
    <input type="text" name="name" required>

    <label>Preis (€):</label>
    <input type="number" step="0.01" name="price" required>

    <label>Bild:</label>
    <input type="file" name="img" required>

    <label>Größen (kommagetrennt):</label>
    <input type="text" name="sizes">

    <label>Farben (kommagetrennt):</label>
    <input type="text" name="colors">

     <label>Kategorie:</label>
    <select name="category" required>
        <?php foreach($CATEGORIES as $key=>$label): ?>
            <option value="<?= $key ?>"><?= $label ?></option>
        <?php endforeach; ?>
    </select>

    <br><br>

    <button type="submit">Speichern</button>
</form>


<p><a href="panel.php">← Zurück</a></p>
</div>

</body>
</html>
