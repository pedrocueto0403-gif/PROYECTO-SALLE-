<?php
$c = file_get_contents(__DIR__ . '/../views/auth/inscripcion.php');
preg_match_all('/<form[^>]*>/i', $c, $forms);
preg_match_all('/<\/form>/i', $c, $closeForms);
echo "Forms count: " . count($forms[0]) . " | Close forms count: " . count($closeForms[0]) . "\n";
foreach ($forms[0] as $f) {
    echo "Form: " . htmlspecialchars($f) . "\n";
}
