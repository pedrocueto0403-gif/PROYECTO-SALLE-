<?php
$content = file_get_contents(__DIR__ . '/../views/admin/index.php');
preg_match_all('/<form[^>]*action=["\']?([^"\'>\s]*)[^>]*>.*?name=["\']action["\']\s+value=["\']([^"\']+)["\']/is', $content, $m);
for ($i = 0; $i < count($m[0]); $i++) {
    echo "Action: " . $m[2][$i] . "\n";
}

// Also check any if ($action === ...) or $_POST['action']
preg_match_all('/(?:action\s*===|\$_POST\[[\'"]action[\'"]\]\s*===)\s*[\'"]([^\'"]+)[\'"]/i', $content, $m2);
echo "\nPOST actions handled in admin:\n";
print_r(array_unique($m2[1]));
