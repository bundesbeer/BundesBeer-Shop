<?php
session_start();

/* =============================================================
   Bundesbeer – Merch Shop
   ============================================================= */

$PAYPAL_ADDRESS = "https://www.paypal.com/paypalme/calyndris";
$ADMIN_EMAIL = "bundesbeer.dc@gmail.com";
$ORDERS_DIR = __DIR__ . "/orders";
$DISCORD_WEBHOOK = "https://discord.com/api/webhooks/1444032674716057732/TQMYAQSYozcW3nSKVtBTh96DPIKFUSCi7s3n1VlugSeY_1ObP1NhbkJeovWl9QnAWpNx";

$productsJson = file_get_contents(__DIR__ . "/data/products.json");
$CATALOG = json_decode($productsJson, true);

$MAP = [];
foreach ($CATALOG as $p) $MAP[$p["id"]] = $p;


/* -------------------------------
   BESTELLVERARBEITUNG
   ------------------------------- */
$successMsg = $errorMsg = "";

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["checkout_flag"])) {

    $name    = trim($_POST["name"] ?? "");
    $email   = trim($_POST["email"] ?? "");
    $address = trim($_POST["address"] ?? "");
    $discord = trim($_POST["discord"] ?? "");
    $cartRaw = $_POST["cart"] ?? "[]";

    if ($name === "" || $email === "" || $discord === "" || $address === "") {
        $errorMsg = "Bitte alle Pflichtfelder ausfüllen.";
    } else {

        $items = json_decode($cartRaw, true);
        if (!is_array($items) || count($items) == 0) {
            $errorMsg = "Dein Warenkorb ist leer.";
        } else {

            $lines = [];
            $subtotal = 0;

            foreach ($items as $it) {

                $id  = $it["id"];
                $qty = intval($it["qty"]);

                // Sonderprodukt? → kein MAP Eintrag
                if ($id === "custom-static-001") {
                    $nameFull = "Personalisierte Sonderbestellung";
                    $price = floatval($it["price"]);
                } else {
                    if (!isset($MAP[$id])) {
                        $errorMsg = "Ungültiger Artikel entdeckt.";
                        break;
                    }
                    $prod = $MAP[$id];
                    $nameFull = $prod["name"];
                    $price = $prod["price"];
                }

                $sum = $qty * $price;
                $subtotal += $sum;

                $cat = strtoupper($it["category"] ?? "UNBEKANNT");

                $variant = "";
                if (!empty($it["size"]))  $variant .= " (Größe: ".$it["size"].")";
                if (!empty($it["color"])) $variant .= " (Farbe: ".$it["color"].")";
                if (!empty($it["custom"])) $variant .= " (Personalisierung: ".$it["custom"].")";

                $lines[] = "[{$cat}] {$qty}x {$nameFull}{$variant} — " . number_format($sum, 2) . " €";

            }

            if ($errorMsg === "") {

                if (!is_dir($ORDERS_DIR)) mkdir($ORDERS_DIR);

                $orderId = "BB" . date("YmdHis") . rand(100, 999);
                $file = $ORDERS_DIR . "/order_{$orderId}.txt";

                $content = [];
                $content[] = "=== Bundesbeer Bestellung ===";
                $content[] = "Bestell-ID: $orderId";
                $content[] = "Zeit: " . date("Y-m-d H:i:s");
                $content[] = "";
                $content[] = "Kunde: $name";
                $content[] = "E-Mail: $email";
                $content[] = "Discord: $discord";
                $content[] = "Adresse:";
                $content[] = $address;
                $content[] = "";
                $content[] = "Produkte:";
                foreach ($lines as $l) $content[] = $l;
                $content[] = "";
                $content[] = "Summe: " . number_format($subtotal,2) . " €";
                $content[] = "";
                $content[] = "Zahlung per PayPal Friends an:";
                $content[] = $PAYPAL_ADDRESS;

                file_put_contents($file, implode("\n", $content));

                /* ================================================================
                DISCORD WEBHOOK — Bestellung senden
                ================================================================ */

                function discord_split_field($label, $text) {
                    $chunks = [];
                    while (strlen($text) > 1024) {
                        $cut = substr($text, 0, 1024);
                        $pos = strrpos($cut, "\n");
                        if ($pos === false) $pos = 1024;

                        $chunks[] = substr($text, 0, $pos);
                        $text = substr($text, $pos);
                    }
                    $chunks[] = $text;

                    $fields = [];
                    foreach ($chunks as $i => $part) {
                        $fields[] = [
                            "name" => $label . ($i > 0 ? " (Teil ".($i+1).")" : ""),
                            "value" => $part
                        ];
                    }

                    return $fields;
                }

                if (!empty($DISCORD_WEBHOOK)) {

                    // 🔥 Produkte vorbereiten
                    $productText = implode("\n", $lines);

                    // 🔥 Automatisches Splitten, falls > 1024 Zeichen
                    $productFields = discord_split_field("📦 Produkte", $productText);

                    $discordMessage = [
                        "content" => "📦 **Neue Bundesbeer Bestellung eingegangen!**",
                        "embeds" => [
                            [
                                "title" => "Bestell-ID: $orderId",
                                "color" => 15844367, // Bier-Gold
                                "fields" => array_merge([

                                    [
                                        "name" => "👤 Kunde",
                                        "value" => $name,
                                        "inline" => true
                                    ],
                                    [
                                        "name" => "🍺 Discord",
                                        "value" => $discord,
                                        "inline" => true
                                    ],

                                ], $productFields, [

                                    [
                                        "name" => "💰 Gesamtbetrag",
                                        "value" => number_format($subtotal,2) . " €"
                                    ],
                                    [
                                        "name" => "🏠 Adresse",
                                        "value" => $address
                                    ],
                                    [
                                        "name" => "⏰ Zeit",
                                        "value" => date("d.m.Y H:i")
                                    ]

                                ])
                            ]
                        ]
                    ];

                    $options = [
                        "http" => [
                            "header"  => "Content-Type: application/json",
                            "method"  => "POST",
                            "content" => json_encode($discordMessage, JSON_UNESCAPED_UNICODE)
                        ]
                    ];

                    // 🔥 SENDEN
                    @file_get_contents($DISCORD_WEBHOOK, false, stream_context_create($options));
                }


                $_SESSION["success"] = "Danke! Deine Bestellung ($orderId) wurde gespeichert. Wir melden uns.";
                header("Location: index.php");
                exit;
            }
        }
    }
}
?>
<!doctype html>
<html lang="de">
<head>
<meta charset="utf-8">
<title>Bundesbeer – Merch Bundles</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<style>
:root {
    --dark-blue: #0a192f;
    --card-bg: rgba(255, 255, 255, 0.98);
    --accent-yellow: #ffcc00;
    --accent-red: #e63946;
    --primary-blue: #112240;
    --text-dark: #0a192f;
}

/* Animierter Hintergrund: Schwarz -> Dunkelblau -> Blau */
body {
    margin: 0;
    font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
    background: linear-gradient(270deg, #000000, #0a192f, #1a3a5f);
    background-size: 600% 600%;
    animation: backgroundAnimation 15s ease infinite;
    color: #ffffff;
    -webkit-tap-highlight-color: transparent;
    min-height: 100vh;
}

@keyframes backgroundAnimation {
    0% { background-position: 0% 50%; }
    50% { background-position: 100% 50%; }
    100% { background-position: 0% 50%; }
}

.container { 
    max-width: 1100px; 
    margin: auto; 
    padding: 15px; 
    box-sizing: border-box;
}

header {
    background: rgba(10, 25, 47, 0.85);
    backdrop-filter: blur(10px);
    position: sticky;
    top: 0;
    z-index: 1000;
    border-bottom: 1px solid rgba(255,255,255,0.1);
    padding: 10px 0;
}

.logo-text {
    font-size: 24px;
    font-weight: 800;
    color: var(--accent-yellow);
    text-transform: uppercase;
}

/* Responsive Grid für Handy & PC */
.product-grid { 
    display: grid; 
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); 
    gap: 25px; 
    margin-bottom: 40px;
}

@media (max-width: 480px) {
    .product-grid { grid-template-columns: 1fr; }
    .drawer-panel { width: 100% !important; }
    .news-nav { padding: 10px 5px; font-size: 20px; }
}

.card {
    background: var(--card-bg);
    border-radius: 20px;
    padding: 20px;
    display: flex;
    flex-direction: column;
    color: var(--text-dark);
    box-shadow: 0 10px 25px rgba(0,0,0,0.4);
    height: 100%;
    box-sizing: border-box;
    transition: transform 0.3s ease;
}

.card:hover { transform: translateY(-5px); }

.product-img {
    width: 100%;
    height: 220px;
    object-fit: cover;
    border-radius: 12px;
    background: #eee;
    margin-bottom: 15px;
}

/* Buttons */
.btn {
    padding: 12px 15px;
    border: none;
    border-radius: 10px;
    cursor: pointer;
    font-weight: bold;
    font-size: 14px;
    transition: 0.2s;
}

.btn-primary { background: var(--accent-yellow); color: #000; width: 100%; }
.btn-primary:hover { background: #fff; box-shadow: 0 0 10px var(--accent-yellow); }
.btn-dark { background: var(--primary-blue); color: #fff; border: 1px solid rgba(255,255,255,0.1); }
.btn-danger { background: var(--accent-red); color: #fff; width: 100%; }

/* News Slider mit GIF Hintergrund & zentrierten Pfeilen */
.news-wrapper {
    position: relative;
    width: 100%;
    border-radius: 20px;
    overflow: hidden;
    margin-bottom: 40px;
    border: 2px solid rgba(255,255,255,0.1);
    box-shadow: 0 15px 35px rgba(0,0,0,0.5);
}

#news-slider { 
    height: 350px; 
    background: url('img/beer-10.gif') center center;
    background-size: cover;
    position: relative;
}

.news-slide { 
    position: absolute; 
    inset: 0; 
    background-size: contain; 
    background-repeat: no-repeat;
    background-position: center; 
    opacity: 0; 
    transition: 0.5s; 
    background-color: rgba(0, 0, 0, 0.2); 
}

.news-slide.active { opacity: 1; }

.news-nav {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    background: rgba(10, 25, 47, 0.7);
    color: white;
    font-size: 28px;
    border: none;
    padding: 20px 15px;
    cursor: pointer;
    z-index: 100;
    transition: 0.3s;
}

.news-nav:hover { background: var(--accent-yellow); color: #000; }
.news-nav.left { left: 0; border-radius: 0 10px 10px 0; }
.news-nav.right { right: 0; border-radius: 10px 0 0 10px; }

.news-text {
    position: absolute;
    bottom: 25px;
    left: 50%;
    transform: translateX(-50%);
    background: rgba(10, 25, 47, 0.9);
    color: var(--accent-yellow);
    padding: 12px 25px;
    border-radius: 12px;
    font-weight: bold;
    text-align: center;
    border: 1px solid var(--accent-yellow);
    white-space: nowrap;
}

/* Warenkorb Drawer */
.drawer {
    position: fixed; 
    inset: 0; 
    background: rgba(0,0,0,0.8);
    display: none; 
    justify-content: flex-end; 
    z-index: 2000;
}
.drawer.open { display: flex; }

.drawer-panel {
    background: #fdfdfd; 
    width: 420px; 
    height: 100%; 
    padding: 30px; 
    overflow-y: auto; 
    color: var(--text-dark);
    box-sizing: border-box;
    box-shadow: -5px 0 25px rgba(0,0,0,0.5);
}

.field {
    width: 100%;
    padding: 12px;
    border: 2px solid #ddd;
    border-radius: 10px;
    margin-bottom: 12px;
    box-sizing: border-box;
    font-size: 16px;
    font-family: inherit;
}

.field:focus { border-color: var(--accent-yellow); outline: none; }

h2 { 
    color: var(--accent-yellow); 
    border-left: 6px solid var(--accent-red); 
    padding-left: 15px; 
    margin-top: 50px; 
    text-transform: uppercase;
    letter-spacing: 1px;
}

hr { border: 0; border-top: 1px solid rgba(255,255,255,0.1); margin: 40px 0; }
</style>
</head>
<body>

<header>
  <div class="container" style="display:flex;align-items:center;justify-content:space-between;">

    <div style="display:flex;align-items:center;gap:10px; position:relative;">

      <!-- Logo -->
      <img src="/test/img/logo.png" 
          alt="Bundesbeer Logo"
          onerror="this.style.display='none'; this.nextElementSibling.style.display='inline-block';"
          style="height:42px; width:auto; border-radius:8px;">

      <span class="logo" style="display:none;font-size:32px;">🍺</span>

      <!-- Titel -->
      <strong id="secret-admin" style="font-size:20px; cursor:pointer;">
        Bundesbeer Shop
      </strong>

      <!-- Unsichtbarer Admin-Link -->
      <a href="/test/admin/login.php"
         style="
            position:absolute;
            inset:0;
            opacity:0;         /* UNSICHTBAR */
            cursor:pointer;    
         ">
      </a>

    </div>

    <div style="display:flex;align-items:center;gap:12px;">

      <button class="btn btn-dark" id="openCart">
        🧺 Warenkorb <span id="cartCount">0</span>
      </button>

    </div>

  </div>
</header>

<div class="news-wrapper">
    <button class="news-nav left" id="news-prev">❮</button>

    <div id="news-slider"></div>

    <button class="news-nav right" id="news-next">❯</button>

    <div id="news-dots"></div>
</div>





<div class="container">

    <h2> Personalisierte Produkte</h2>
    <div class="product-grid" id="area-custom"></div>

    <hr style="margin:40px 0">

    <h2> Meme Shirts</h2>
    <div class="product-grid" id="area-meme"></div>

    <hr style="margin:40px 0">

    <h2> Merch</h2>
    <div class="product-grid" id="area-merch"></div>

</div>
<!-- ============================
     WARENKORB / CHECKOUT
     ============================ -->
<div class="drawer" id="drawer">
  <div class="drawer-panel">

      <h2>🧺 Dein Warenkorb</h2>
      <div id="cartList"></div>

      <hr>

      <h3>Lieferdaten</h3>

      <form method="post" id="checkoutForm">
        <input type="hidden" name="checkout_flag" value="1">
        <input type="hidden" name="cart" id="cartPayload">

        <input class="field" required name="name" placeholder="Vollständiger Name *">
        <input class="field" required name="email" type="email" placeholder="E-Mail *">
        <input class="field" required name="discord" placeholder="Discord (z.B. @name) *">
        <textarea class="field" required name="address" placeholder="Adresse (Straße, PLZ Ort, Land) *"></textarea>

        <div class="card" style="background:#fff8d2">
            <strong>Zahlung per PayPal Friends:</strong><br>
            Sende den Gesamtbetrag an:<br>

            <strong>
                <a href="<?php echo $PAYPAL_ADDRESS; ?>" target="_blank" 
                   style="color:#0a5ddb;text-decoration:underline;">
                <?php echo $PAYPAL_ADDRESS; ?>
                </a>
            </strong>
            <br><br>
            Danach auf „Ich habe überwiesen“ klicken.
        </div>

        <button class="btn btn-primary" style="width:100%; margin-top:10px">
          Ich habe überwiesen ✔
        </button>

      </form>

      <button class="btn btn-danger" style="width:100%; margin-top:10px" id="closeCartBtn">Schließen</button>
  </div>
</div>


<!-- ======================================================
     JAVASCRIPT
====================================================== -->
<script>
// === NEWS SLIDER ===
fetch("/test/data/news.json")
    .then(r => r.json())
    .then(news => {
        const slider = document.getElementById("news-slider");
        const dots = document.getElementById("news-dots");

        news.forEach((n,i) => {
            const div = document.createElement("div");
            div.className = "news-slide" + (i === 0 ? " active" : "");
            div.style.backgroundImage = `url(${n.image})`;
            div.onclick = () => location.href = n.link;
            div.innerHTML = `<div class='news-text'>${n.title}</div>`;
            slider.appendChild(div);

            // Punkte
            const dot = document.createElement("div");
            dot.className = "news-dot" + (i === 0 ? " active" : "");
            dot.dataset.index = i;
            dots.appendChild(dot);
        });

        let index = 0;
        const slides = document.querySelectorAll(".news-slide");
        const dotEls = document.querySelectorAll(".news-dot");

        function show(i) {
            slides[index].classList.remove("active");
            dotEls[index].classList.remove("active");
            index = (i + slides.length) % slides.length;
            slides[index].classList.add("active");
            dotEls[index].classList.add("active");
        }

        // Buttons
        document.getElementById("news-prev").onclick = () => show(index - 1);
        document.getElementById("news-next").onclick = () => show(index + 1);

        // Punkte anklickbar
        dotEls.forEach(dot => {
            dot.onclick = () => show(Number(dot.dataset.index));
        });

        // Auto-Slide
        setInterval(() => show(index + 1), 5000);
    });




</script>




<script>
/* Produktdaten laden */
const PRODUCTS = <?php echo json_encode($CATALOG); ?>;

/* Warenkorb */
let cart = JSON.parse(localStorage.getItem("bb_cart") || "[]");
function saveCart(){ localStorage.setItem("bb_cart", JSON.stringify(cart)); renderCart(); }
function money(n){ return Number(n).toFixed(2)+" €"; }

/* Kategorien */
// Bereiche holen
const customArea = document.getElementById("area-custom");
const memeArea   = document.getElementById("area-meme");
const merchArea  = document.getElementById("area-merch");


// Produkte in Kategorien einordnen
const GROUPS = { custom: [], meme: [], merch: [] };

PRODUCTS.forEach(p => {
    if (!p.category) p.category = "merch";
    GROUPS[p.category].push(p);
});

// Rendern
GROUPS.custom.forEach(p => renderProductCard(p, customArea));
GROUPS.meme.forEach(p => renderProductCard(p, memeArea));
GROUPS.merch.forEach(p => renderProductCard(p, merchArea));




/* ==========================
   Normale Produkte rendern
========================== */
function renderProductCard(p, parent) {

    const c = document.createElement("div");
    c.className = "card";

    let customFields = "";

    // Falls Kategorie == "custom": spezielles Eingabefeld
    if (p.category === "custom") {
        customFields = `
            <label>📝 Personalisierung:</label>
            <textarea class="field custom-text" placeholder="Wunschtext, Name, Nummer ..."></textarea>
        `;
    }

    c.innerHTML = `
        <img src="${p.img}" class="product-img">

        <div class="card-inner">
            <h3>${p.name}</h3>
            <p class="price"><strong>${money(p.price)}</strong></p>

            ${p.sizes?.length ? `
            <label>Größe:</label>
            <select class="field size-select">
                ${p.sizes.map(s => `<option>${s}</option>`).join("")}
            </select>` : ""}

            ${p.colors?.length ? `
            <label>Farbe:</label>
            <select class="field color-select">
                ${p.colors.map(c => `<option>${c}</option>`).join("")}
            </select>` : ""}

            ${customFields}

            <button class="btn btn-dark add-btn" data-id="${p.id}">
                ➕ In den Warenkorb
            </button>
        </div>
    `;

    parent.appendChild(c);
}




/* ============================
   ADD TO CART
============================ */
document.body.addEventListener("click", e => {
    if (!e.target.dataset.id) return;

    const id = e.target.dataset.id;
    const p = PRODUCTS.find(x => x.id === id);

    const isCustom = (p.category === "custom");
    const cardEl = e.target.closest(".card");

    const size  = cardEl.querySelector(".size-select")?.value || null;
    const color = cardEl.querySelector(".color-select")?.value || null;

    let customText = null;
    if (isCustom) {
        customText = cardEl.querySelector(".custom-text")?.value || "";
    }

    const found = cart.find(i =>
        i.id === id &&
        i.size === size &&
        i.color === color &&
        i.custom === customText
    );

    if (found) {
        found.qty++;
    } else {
        cart.push({
        id: p.id,
        name: p.name,
        price: p.price,
        size: size,
        color: color,
        custom: customText,
        category: p.category,   // ← HIER NEU
        qty: 1
        });
    }

    saveCart();
    openDrawer();
});


/* ============================
   WARENKORB
============================ */
const drawer = document.getElementById("drawer");
const cartList = document.getElementById("cartList");
const cartCount = document.getElementById("cartCount");

function openDrawer(){ drawer.classList.add("open"); }
function closeDrawer(){ drawer.classList.remove("open"); }

document.getElementById("openCart").onclick = openDrawer;
document.getElementById("closeCartBtn").onclick = closeDrawer;

function renderCart() {
    cartCount.textContent = cart.reduce((a,b)=>a+b.qty,0);

    if (cart.length === 0) {
        cartList.innerHTML = "<p>Warenkorb ist leer.</p>";
        return;
    }

    cartList.innerHTML = cart.map((it, i) => `
        <div class="card" style="margin-bottom:10px">

            <span style="font-size:13px; opacity:0.8;">Kategorie: 
                <strong>[${(it.category || "merch").toUpperCase()}]</strong>
            </span><br>

            <strong>${it.name}</strong><br>
            ${it.size ? `Größe: ${it.size}<br>` : ""}
            ${it.color ? `Farbe: ${it.color}<br>` : ""}
            ${it.custom ? `Personalisierung: ${it.custom}<br>` : ""}
            
            ${money(it.price)} / Stück<br>

            <div class="qty-wrapper">
                <button class="qty-btn" onclick="changeQty(${i}, -1)">−</button>
                <span class="qty-number">${it.qty}</span>
                <button class="qty-btn" onclick="changeQty(${i}, 1)">+</button>
            </div>

        </div>
    `).join("");

}

function changeQty(i, delta) {
    cart[i].qty += delta;
    if (cart[i].qty <= 0) cart.splice(i, 1);
    saveCart();
    renderCart();
}


/* ============================
   CHECKOUT
============================ */
document.getElementById("checkoutForm").addEventListener("submit", () => {
    document.getElementById("cartPayload").value = JSON.stringify(cart);
    cart = [];
    saveCart();
    closeDrawer();
});

renderCart();
</script>

</body>
</html>
