<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title ?? 'QMC Hospital Management System'; ?></title>
    <?php
    // Determine CSS path based on directory level
    $css_base_path = isset($css_path_prefix) ? $css_path_prefix : '';
    ?>
    <link rel="stylesheet" href="<?php echo $css_base_path; ?>assets/css/style.css">
    <?php if (isset($extra_css)): ?>
        <?php foreach ($extra_css as $css_file): ?>
            <link rel="stylesheet" href="<?php echo $css_base_path; ?>assets/css/<?php echo $css_file; ?>">
        <?php endforeach; ?>
    <?php endif; ?>
</head>
<body>

