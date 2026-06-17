<?php
declare(strict_types=1);

session_start();

$adminPassword = '9856';
$statusFile = __DIR__ . '/product-status.json';
$products = [
    'Sage Charm',
    'Pastel Teddy',
    'Pink Cherry',
    'Vanilla Gold',
    'Pink Wine Bow',
    'Blue Pearl',
    'Tangerine Pearl',
    'Rose Cloud',
    'Midnight Sparkle',
    'Olive Pearl',
    'Sunbeam Teddy',
    'Aqua Crystal',
    'Mint Daisy',
    'Ruby Blossom',
];

function read_statuses(string $statusFile, array $products): array
{
    $statuses = [];

    if (is_file($statusFile)) {
        $content = file_get_contents($statusFile);
        $decoded = json_decode($content ?: '{}', true);

        if (is_array($decoded)) {
            $statuses = $decoded;
        }
    }

    foreach ($products as $product) {
        if (($statuses[$product] ?? null) !== 'sold-out') {
            $statuses[$product] = 'available';
        }
    }

    return $statuses;
}

function write_statuses(string $statusFile, array $statuses): bool
{
    $result = file_put_contents(
        $statusFile,
        json_encode($statuses, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL,
        LOCK_EX
    );

    return $result !== false;
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'update';
    $product = $_POST['product'] ?? '';
    $status = $_POST['status'] ?? '';

    if ($action === 'login') {
        $password = (string) ($_POST['password'] ?? '');

        if (hash_equals($adminPassword, $password)) {
            $_SESSION['duduhook_admin'] = true;
            $message = 'Du bist eingeloggt.';
        } else {
            $error = 'Passwort stimmt nicht.';
        }
    } elseif ($action === 'logout') {
        $_SESSION['duduhook_admin'] = false;
        session_destroy();
        $message = 'Du bist ausgeloggt.';
    } elseif (empty($_SESSION['duduhook_admin'])) {
        $error = 'Bitte zuerst einloggen.';
    } elseif (!in_array($product, $products, true)) {
        $error = 'Diese Tasche wurde nicht gefunden.';
    } elseif (!in_array($status, ['available', 'sold-out'], true)) {
        $error = 'Dieser Status ist nicht gültig.';
    } else {
        $statuses = read_statuses($statusFile, $products);
        $statuses[$product] = $status;

        if (write_statuses($statusFile, $statuses)) {
            $message = $product . ' wurde auf ' . ($status === 'sold-out' ? 'Verkauft' : 'Verfügbar') . ' gesetzt.';
        } else {
            $error = 'Die Statusdatei konnte nicht gespeichert werden. Bitte prüfe die Schreibrechte für product-status.json.';
        }
    }
}

$statuses = read_statuses($statusFile, $products);
$isAdmin = !empty($_SESSION['duduhook_admin']);
?>
<!doctype html>
<html lang="de">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Duduhook Admin | Taschenstatus</title>
    <style>
      :root {
        --ink: #191714;
        --muted: #6e665d;
        --paper: #f7f4ee;
        --line: #ded7cc;
        --clay: #a85739;
        --green: #4f6f4a;
        --gold: #c79a55;
        --charcoal: #272522;
        --white: #fffaf3;
      }

      * {
        box-sizing: border-box;
      }

      body {
        margin: 0;
        color: var(--ink);
        background:
          linear-gradient(135deg, rgba(168, 87, 57, 0.14), transparent 34%),
          linear-gradient(315deg, rgba(199, 154, 85, 0.16), transparent 38%),
          var(--paper);
        font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        line-height: 1.5;
      }

      main {
        width: min(980px, calc(100% - 32px));
        margin: 0 auto;
        padding: 36px 0 52px;
      }

      .hero {
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto;
        gap: 18px;
        align-items: end;
        margin-bottom: 22px;
        padding: 26px;
        border: 1px solid rgba(25, 23, 20, 0.12);
        border-radius: 8px;
        color: var(--white);
        background: linear-gradient(135deg, #191714, #5b3829);
        box-shadow: 0 22px 70px rgba(25, 23, 20, 0.14);
      }

      h1 {
        margin: 0 0 8px;
        font-family: Georgia, "Times New Roman", serif;
        font-size: clamp(2.4rem, 7vw, 4.8rem);
        line-height: 0.95;
      }

      p {
        margin-top: 0;
        color: var(--muted);
      }

      .hero p {
        max-width: 620px;
        margin-bottom: 0;
        color: rgba(255, 250, 243, 0.78);
      }

      .notice,
      .error {
        margin: 22px 0;
        padding: 14px 16px;
        border-radius: 8px;
        font-weight: 800;
      }

      .notice {
        color: #19371f;
        background: #dff0df;
      }

      .error {
        color: #5a1d18;
        background: #f4d9d5;
      }

      .grid {
        display: grid;
        gap: 14px;
      }

      .login-card {
        display: grid;
        gap: 14px;
        max-width: 460px;
        padding: 22px;
        border: 1px solid var(--line);
        border-radius: 8px;
        background: var(--white);
        box-shadow: 0 20px 54px rgba(25, 23, 20, 0.08);
      }

      .login-card label {
        display: grid;
        gap: 8px;
        color: var(--muted);
        font-weight: 800;
      }

      .row {
        display: grid;
        grid-template-columns: minmax(180px, 1fr) auto;
        gap: 14px;
        align-items: center;
        padding: 16px;
        border: 1px solid var(--line);
        border-radius: 8px;
        background: var(--white);
      }

      .row strong,
      .row span {
        display: block;
      }

      .status {
        display: inline-flex;
        width: fit-content;
        margin-top: 8px;
        padding: 5px 9px;
        border-radius: 999px;
        color: var(--muted);
        background: #eee6dc;
        font-size: 0.94rem;
        font-weight: 850;
      }

      .status.sold {
        color: var(--white);
        background: var(--clay);
      }

      .status.available {
        color: var(--white);
        background: var(--green);
      }

      .actions {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        justify-content: flex-end;
      }

      input {
        width: 180px;
        min-height: 42px;
        border: 1px solid var(--line);
        border-radius: 8px;
        padding: 0 12px;
        font: inherit;
      }

      button,
      a.button {
        min-height: 42px;
        border: 0;
        border-radius: 999px;
        padding: 0 14px;
        color: var(--white);
        background: var(--charcoal);
        font: inherit;
        font-weight: 850;
        text-decoration: none;
        cursor: pointer;
      }

      button.available {
        color: var(--white);
        background: var(--green);
      }

      button.sold {
        background: var(--clay);
      }

      .footer-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-top: 22px;
      }

      .logout-form {
        margin: 0;
      }

      @media (max-width: 720px) {
        .hero {
          grid-template-columns: 1fr;
          padding: 20px;
        }

        .row {
          grid-template-columns: 1fr;
        }

        .actions {
          justify-content: flex-start;
        }

        input {
          width: 100%;
        }
      }
    </style>
  </head>
  <body>
    <main>
      <section class="hero" aria-label="Admin Kopfbereich">
        <div>
          <h1>Taschenstatus</h1>
          <p>Hier markierst du Taschen als verkauft oder wieder verfügbar. Nach dem Speichern sehen Besucher den Status direkt auf der Shopseite.</p>
        </div>
        <?php if ($isAdmin): ?>
          <form class="logout-form" method="post">
            <input name="action" type="hidden" value="logout">
            <button type="submit">Ausloggen</button>
          </form>
        <?php endif; ?>
      </section>

      <?php if ($message !== ''): ?>
        <div class="notice"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></div>
      <?php endif; ?>

      <?php if ($error !== ''): ?>
        <div class="error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
      <?php endif; ?>

      <?php if (!$isAdmin): ?>
        <form class="login-card" method="post">
          <input name="action" type="hidden" value="login">
          <label>
            Passwort
            <input name="password" type="password" inputmode="numeric" autocomplete="current-password" placeholder="9856" required autofocus>
          </label>
          <button type="submit">Einloggen</button>
        </form>
      <?php else: ?>
        <div class="grid">
          <?php foreach ($products as $product): ?>
            <?php $status = $statuses[$product] ?? 'available'; ?>
            <form class="row" method="post">
              <div>
                <strong><?= htmlspecialchars($product, ENT_QUOTES, 'UTF-8') ?></strong>
                <span class="status <?= $status === 'sold-out' ? 'sold' : 'available' ?>"><?= $status === 'sold-out' ? 'Aktuell: Verkauft' : 'Aktuell: Verfügbar' ?></span>
              </div>
              <div class="actions">
                <input name="action" type="hidden" value="update">
                <input name="product" type="hidden" value="<?= htmlspecialchars($product, ENT_QUOTES, 'UTF-8') ?>">
                <button class="available" name="status" type="submit" value="available">Verfügbar</button>
                <button class="sold" name="status" type="submit" value="sold-out">Verkauft</button>
              </div>
            </form>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <div class="footer-actions">
        <a class="button" href="index.html#shop">Zur Shopseite</a>
      </div>
    </main>
  </body>
</html>
