<?php
$content = file_get_contents(__DIR__ . '/../views/administrativo/index.php');
preg_match_all('/<script[\s>]/i', $content, $opens);
preg_match_all('/<\/script>/i', $content, $closes);
echo "administrativo/index.php -> Script open tags: " . count($opens[0]) . " | close tags: " . count($closes[0]) . "\n";

$contentAdmin = file_get_contents(__DIR__ . '/../views/admin/index.php');
preg_match_all('/<script[\s>]/i', $contentAdmin, $opensAdmin);
preg_match_all('/<\/script>/i', $contentAdmin, $closesAdmin);
echo "admin/index.php -> Script open tags: " . count($opensAdmin[0]) . " | close tags: " . count($closesAdmin[0]) . "\n";
