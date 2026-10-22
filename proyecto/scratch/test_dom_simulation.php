<?php
// Test DOM simulation
$selectHtml = '
<select class="form-select border-primary mb-3" name="curso_id" id="regCursoSelect">
    <option value="">-- Seleccione el Curso Oficial --</option>
    <optgroup label="☀️ JORNADA MAÑANA (24 Cursos)">
        <option value="5" data-jornada="Mañana">☀️ Mañana — Transición A (2026)</option>
    </optgroup>
    <optgroup label="⛅ JORNADA TARDE (24 Cursos)">
        <option value="25" data-jornada="Tarde">⛅ Tarde — Transición A (2026)</option>
        <option value="26" data-jornada="Tarde">⛅ Tarde — Transición B (2026)</option>
    </optgroup>
</select>';

$doc = new DOMDocument();
libxml_use_internal_errors(true);
$doc->loadHTML($selectHtml);

$optgroups = $doc->getElementsByTagName('optgroup');
$tardeFound = 0;
foreach ($optgroups as $og) {
    $label = strtolower($og->getAttribute('label'));
    if (strpos($label, 'tarde') !== false) {
        $options = $og->getElementsByTagName('option');
        $tardeFound = $options->length;
    }
}

echo "Tarde options extracted: $tardeFound\n";
if ($tardeFound > 0) echo "SUCCESS: DOM simulation passed!\n";
else echo "FAIL: No tarde options found.\n";
