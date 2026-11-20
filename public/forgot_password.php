<?php

declare(strict_types=1);
require_once dirname(__DIR__) . '/src/helpers.php';
page_start('Forgot password', null);
?>
<section class="card"><p>For account security, password recovery is handled by your administrator.</p>
<p>Ask your admin to reset your password from the <strong>MRs</strong> page. You can then log in with the new password.</p>
<a class="button" href="index.php">Back to login</a></section>
<?php page_end(null); ?>
