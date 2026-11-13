<?php
require_once __DIR__ . '/auth.php';
$pageTitle = $pageTitle ?? 'BoardMate';
$bodyClass = $bodyClass ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?> | BoardMate - Find. Compare. Stay.</title>
    <meta name="description" content="BoardMate helps University of Moratuwa students find, compare and review boarding places near campus.">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Poppins:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="<?= url('assets/css/style.css') ?>">
</head>
<body class="<?= e($bodyClass) ?>">
<?php include __DIR__ . '/navbar.php'; ?>
<?php render_flash(); ?>
<main class="site-main">
