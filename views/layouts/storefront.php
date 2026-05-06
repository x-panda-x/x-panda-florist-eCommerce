<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars((string) ($pageTitle ?? settings('store_name', 'Lily and Rose')), ENT_QUOTES, 'UTF-8'); ?></title>
    <!-- Removed inline CSS vars to let storefront.css govern purely -->
    <link rel="stylesheet" href="/assets/css/storefront.css">
</head>
<body>
    <div class="site-shell">
        <?php require BASE_PATH . '/views/components/storefront-header.php'; ?>
        <main class="site-main">
            <!-- Container removed from shell to allow full width pages, like home hero -->
            <?php echo $content ?? ''; ?>
        </main>
        <?php require BASE_PATH . '/views/components/storefront-footer.php'; ?>
    </div>
</body>
</html>
