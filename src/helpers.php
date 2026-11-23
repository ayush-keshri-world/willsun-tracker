<?php

declare(strict_types=1);

require_once __DIR__ . '/auth.php';

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function is_valid_mobile(string $value): bool
{
    return (bool) preg_match('/^\d{10}$/', $value);
}

function is_valid_pincode(string $value): bool
{
    return (bool) preg_match('/^\d{6}$/', $value);
}

function redirect(string $path): never
{
    header('Location: ' . $path);
    exit;
}

function app_config(): array
{
    static $config;
    if ($config === null) {
        $configFile = dirname(__DIR__) . '/config.php';
        $config = is_file($configFile) ? require $configFile : [];
    }
    return $config;
}

function send_password_reset_email(string $email, string $name, string $token): bool
{
    $config = app_config();
    $apiKey = (string) ($config['resend_api_key'] ?? '');
    $appUrl = rtrim((string) ($config['app_url'] ?? ''), '/');
    if ($apiKey === '' || $appUrl === '') {
        error_log('Password reset email is not configured.');
        return false;
    }
    $url = $appUrl . '/reset_password.php?token=' . rawurlencode($token);
    $safeName = e($name);
    $body = [
        'from' => (string) ($config['email_from'] ?? 'Willsun ERP <no-reply@willsunpharma.com>'),
        'to' => [$email],
        'subject' => 'Reset your Willsun ERP password',
        'html' => '<p>Hello ' . $safeName . ',</p><p>Use this link to reset your Willsun ERP password. It expires in 30 minutes.</p><p><a href="' . e($url) . '">Reset password</a></p><p>If you did not request this, you can ignore this email.</p>',
    ];
    $payload = json_encode($body, JSON_THROW_ON_ERROR);
    if (function_exists('curl_init')) {
        $request = curl_init('https://api.resend.com/emails');
        curl_setopt_array($request, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => ["Authorization: Bearer {$apiKey}", 'Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 20,
        ]);
        $result = curl_exec($request);
        $statusCode = (int) curl_getinfo($request, CURLINFO_HTTP_CODE);
        $curlError = curl_error($request);
        curl_close($request);
        if ($result !== false && in_array($statusCode, [200, 201], true)) {
            return true;
        }
        error_log("Password reset email delivery failed. HTTP {$statusCode}. {$curlError}");
        return false;
    }

    $context = stream_context_create(['http' => [
        'method' => 'POST',
        'header' => "Authorization: Bearer {$apiKey}\r\nContent-Type: application/json\r\n",
        'content' => $payload,
        'timeout' => 20,
        'ignore_errors' => true,
    ]]);
    $result = @file_get_contents('https://api.resend.com/emails', false, $context);
    $status = $http_response_header[0] ?? '';
    if ($result !== false && (str_contains($status, ' 200 ') || str_contains($status, ' 201 '))) {
        return true;
    }
    error_log('Password reset email delivery failed. ' . $status);
    return false;
}

function page_start(string $title, ?array $user = null, string $active = ''): void
{
    $user ??= current_user();
    $assetPrefix = str_contains($_SERVER['SCRIPT_NAME'] ?? '', '/admin/') ? '../' : '';
    echo '<!doctype html><html lang="en-IN"><head><meta charset="utf-8">';
    echo '<meta name="viewport" content="width=device-width, initial-scale=1">';
    echo '<title>' . e($title) . '</title><link rel="stylesheet" href="' . $assetPrefix . 'assets/style.css">';
    echo '</head><body><main class="page"><header><h1>' . e($title) . '</h1>';
    if ($user) {
        echo '<p class="muted">Signed in as ' . e($user['name']) . '</p>';
        if (in_array($user['role'], ['owner', 'admin'], true)) {
            $adminPrefix = str_contains($_SERVER['SCRIPT_NAME'] ?? '', '/admin/') ? '' : 'admin/';
            $logoutPath = $adminPrefix === '' ? '../logout.php' : 'logout.php';
            echo '<nav class="top-nav"><a href="' . $adminPrefix . 'index.php">Dashboard</a><a href="' . $adminPrefix . 'mrs.php">MRs</a><a href="' . $adminPrefix . 'products.php">Products</a><a href="' . $adminPrefix . 'parties.php">Parties</a>'
                . '<a href="' . $adminPrefix . 'visits.php">Visits</a><a href="' . $adminPrefix . 'report.php">Reports</a>'
                . '<a href="' . $logoutPath . '">Logout</a></nav>';
        }
        elseif ($user['role'] === 'employee') {
            echo '<nav class="top-nav employee-nav"><a class="' . ($active === 'today' ? 'active' : '')
                . '" href="today.php">Today</a><a class="' . ($active === 'parties' ? 'active' : '')
                . '" href="parties.php">Parties</a><a class="' . ($active === 'password' ? 'active' : '')
                . '" href="password.php">Password</a><a href="logout.php">Logout</a></nav>';
        }
    }
    echo '</header>';
}

function page_end(?array $user = null, string $active = ''): void
{
    echo '<script>(function(){var input=document.getElementById("order-date"),display=document.getElementById("order-date-display");if(!input||!display)return;function show(){var p=input.value.split("-");display.value=p.length===3?p[2]+"/"+p[1]+"/"+p[0]:"";}show();display.form.addEventListener("submit",function(e){var m=display.value.match(/^(\d{2})\/(\d{2})\/(\d{4})$/);if(!m){e.preventDefault();display.setCustomValidity("Use DD/MM/YYYY");display.reportValidity();return;}var iso=m[3]+"-"+m[2]+"-"+m[1];var d=new Date(iso+"T00:00:00");if(isNaN(d)||d.getFullYear()!=m[3]||d.getMonth()+1!=+m[2]||d.getDate()!=+m[1]){e.preventDefault();display.setCustomValidity("Enter a valid date");display.reportValidity();return;}display.setCustomValidity("");input.value=iso;});})();</script>';
    echo '<script>document.querySelectorAll("input[type=password]").forEach(function(input){if(input.dataset.eye)return;input.dataset.eye="1";var button=document.createElement("button");button.type="button";button.className="password-eye secondary";button.textContent="Show password";button.onclick=function(){var show=input.type==="password";input.type=show?"text":"password";button.textContent=show?"Hide password":"Show password";};input.insertAdjacentElement("afterend",button);});function syncRate(select){var custom=select.closest(".card").querySelector(".custom");if(custom){var hidden=select.value==="default";custom.hidden=hidden;custom.style.display=hidden?"none":"block";}}function syncRates(node){if(node.nodeType!==1)return;if(node.matches&&node.matches(".m"))syncRate(node);if(node.querySelectorAll)node.querySelectorAll(".m").forEach(syncRate);}document.querySelectorAll(".m").forEach(syncRate);document.addEventListener("change",function(e){if(e.target.classList.contains("m"))syncRate(e.target);});new MutationObserver(function(records){records.forEach(function(record){record.addedNodes.forEach(syncRates);});}).observe(document.body,{childList:true,subtree:true});</script></main></body></html>';
}

function visit_day_options(): array
{
    return ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
}

/** Prevent spreadsheet formula execution when opening an exported CSV. */
function csv_safe(mixed $value): string
{
    $text = (string) $value;
    if ($text !== '' && in_array($text[0], ['=', '+', '-', '@'], true)) {
        $text = "'" . $text;
    }
    return $text;
}

function party_for_user(int $partyId, array $user): ?array
{
    $sql = 'SELECT p.*, u.name AS mr_name FROM parties p LEFT JOIN users u ON u.id = p.mr_id WHERE p.id = ?';
    if (!in_array($user['role'], ['owner', 'admin'], true)) {
        $sql .= ' AND p.mr_id = ' . (int) $user['id'] . ' AND p.active = 1';
    }
    $stmt = db()->prepare($sql);
    $stmt->execute([$partyId]);
    return $stmt->fetch() ?: null;
}
