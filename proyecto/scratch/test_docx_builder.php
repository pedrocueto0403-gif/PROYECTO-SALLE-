<?php
function createSampleDocx($filename, $title, $bodyParagraphs) {
    $zip = new ZipArchive();
    if ($zip->open($filename, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
        return false;
    }

    // [Content_Types].xml
    $contentTypes = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
  <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
  <Default Extension="xml" ContentType="application/xml"/>
  <Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>
  <Override PartName="/word/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.styles+xml"/>
</Types>';
    $zip->addFromString('[Content_Types].xml', $contentTypes);

    // _rels/.rels
    $rels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/>
</Relationships>';
    $zip->addFromString('_rels/.rels', $rels);

    // word/styles.xml
    $styles = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:styles xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">
  <w:docDefaults>
    <w:rPrDefault>
      <w:rPr>
        <w:rFonts w:ascii="Calibri" w:hAnsi="Calibri"/>
        <w:sz w:val="22"/>
        <w:szCs w:val="22"/>
        <w:lang w:val="es-CO"/>
      </w:rPr>
    </w:rPrDefault>
  </w:docDefaults>
</w:styles>';
    $zip->addFromString('word/styles.xml', $styles);

    // word/document.xml
    $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">
  <w:body>
    <w:p>
      <w:pPr><w:jc w:val="center"/><w:spacing w:after="200"/></w:pPr>
      <w:r><w:rPr><w:b/><w:sz w:val="28"/><w:color w:val="0B3C68"/></w:rPr><w:t>' . htmlspecialchars($title) . '</w:t></w:r>
    </w:p>';

    foreach ($bodyParagraphs as $p) {
        $xml .= '<w:p>
          <w:pPr><w:jc w:val="both"/><w:spacing w:after="160" w:line="300" w:lineRule="auto"/></w:pPr>
          <w:r><w:t>' . htmlspecialchars($p) . '</w:t></w:r>
        </w:p>';
    }

    $xml .= '<w:sectPr><w:pgSz w:w="12240" w:h="15840"/><w:pgMar w:top="1440" w:right="1440" w:bottom="1440" w:left="1440"/></w:sectPr>
  </w:body>
</w:document>';
    $zip->addFromString('word/document.xml', $xml);

    $zip->close();
    return true;
}

$testFile = __DIR__ . '/test_built.docx';
if (createSampleDocx($testFile, "INSTITUTO LA SALLE - CIÉNAGA", ["Párrafo de prueba con tildes: Ciénaga, Educación, Bogotá."])) {
    echo "Docx built successfully! Size: " . filesize($testFile) . " bytes\n";
} else {
    echo "Failed to build docx\n";
}
