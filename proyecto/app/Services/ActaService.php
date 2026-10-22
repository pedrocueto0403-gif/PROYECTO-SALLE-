<?php
/**
 * SERVICIO DIGITAL DE ACTAS DE CONVIVENCIA ESCOLAR
 * Tipos I (Leves), Tipo II (Graves), Tipo III (Gravísimas)
 * Instituto La Salle Ciénaga Magdalena
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/Database.php';

class ActaService {

    /**
     * Catálogo oficial de faltas extraído del Manual de Convivencia y del documento oficial
     */
    public static function getCatalogoFaltas(): array {
        return [
            'Tipo I' => [
                'Impedir que los demás estudiantes se recreen reteniendo los implementos deportivos.',
                'Rehusarse a efectuar los simulacros y demás ejercicios, derivados del plan de atención y prevención de emergencias de la Institución educativa.',
                'El uso de aparatos electrónicos o elementos que interfieran en el desarrollo de las clases o cualquier acto institucional. Podrán ser utilizados con la autorización del docente, como recurso tecnológico, pero ni el profesor ni la Institución se hacen responsables de la pérdida de dichos elementos.',
                'Interrumpir constantemente el desarrollo de las clases.',
                'Ingerir alimentos durante la clase, en eventos científicos, deportivos y/o culturales.',
                'Permanecer durante los descansos en las aulas de clase, en sitios alejados o lugares determinados como áreas restringidas, sin previa autorización.',
                'Promover el desorden en la clase o en actos comunitarios.',
                'Comer dentro de las aulas de clase.',
                'Impuntualidad o inasistencia repetitiva a las clases.',
                'Falta de cuidado en la higiene personal.',
                'La omisión de compromisos y responsabilidades adquiridos.',
                'Uso de accesorios llamativos o extravagantes.',
                'Utilizar un vocabulario soez.',
                'Discusiones o riñas con sus compañeros(as) que involucran agresiones verbales, relacionales, gestuales, físicas o virtuales sin generar daños al cuerpo o a la salud.',
                'Agresiones verbales, relacionales, gestuales o virtuales con contenido sexual que hagan referencia a comportamientos o comentarios.',
                'Realizar ventas o transacciones comerciales no autorizadas en el plantel.',
                'Incumplir con el porte adecuado del uniforme institucional.',
                'No ingresar a clase estando dentro de las instalaciones de la institución.',
                'Arrojar basuras o desperdicios fuera de las canecas destinadas para tal fin.',
                'Dañar intencionalmente carteleras o decoraciones de las aulas.',
                'Uso indebido de apodos o burlas hacia cualquier miembro de la comunidad educativa.',
                'Desatender las instrucciones y llamados de atención de los docentes.',
                'Falta de materiales o implementos escolares de forma reiterada.',
                'Presentarse a la institución sin el carnet estudiantil.',
                'Permanecer en los pasillos durante las horas de clase sin la debida justificación.'
            ],
            'Tipo II' => [
                'Practicar o fomentar juegos agresivos: físicos y/o verbales, que vayan en contra de la integridad física y la dignidad humana o generen algún tipo de agresión física o mental.',
                'Agresión física, que no genere lesión personal y/o incapacidad, o agresión verbal a cualquier miembro de la comunidad.',
                'Contestar inadecuadamente a algún miembro de la comunidad educativa, usando palabras soeces y gestos inapropiados.',
                'Esconder, dañar, averiar o destruir pertenencias de los compañeros y/o de la Institución Educativa. Situación con la cual el alumno queda obligado a la restitución del objeto o su valor correspondiente además de la sanción a que hubiere lugar.',
                'Encubrir a compañeros o grupos de compañeros frente a hechos que vayan en contra del buen funcionamiento de la institución y de su buen nombre.',
                'Irrespetar el derecho de sus compañeros a la libre expresión, participación y ejercicio académico.',
                'Trato irrespetuoso y descortés a los miembros de la comunidad educativa.',
                'Discriminar a miembros de la comunidad educativa por razón de origen, raza, sexo, creencias o cualquier otra circunstancia personal o social.',
                'Realizar actos en contra de la paz y la tranquilidad de cualquiera de los miembros de la comunidad educativa por medio de calumnia, injuria, mensajes a través de medios electrónicos o telefónicos.',
                'Atentar contra derechos fundamentales como la libertad de conciencia, las creencias religiosas y morales, la dignidad, integridad e intimidad de todos los miembros de la comunidad educativa.',
                'Irrespetar la tranquilidad del vecindario.',
                'Causar daños en bienes de las zonas aledañas al colegio.',
                'Realizar dentro de la institución o un lugar ajeno, cuando está en representación del colegio, expresiones afectivas íntimas que atenten contra la moral y los principios institucionales y sociales señalados en la legislación vigente.',
                'Sacar sin autorización, dañar o extraviar libros de la biblioteca, materiales didácticos, equipos electrónicos y demás recursos pertenecientes a la dotación del establecimiento educativo. Quien incurra en esta falta deberá hacer su restitución.',
                'Incumplir los reglamentos establecidos para uso de los espacios comunes como biblioteca, salas de lectura, sala de sistemas, de danzas, tecnología, laboratorio, canchas, entre otros.',
                'Salir de la Institución sin autorización ni acompañamiento del adulto responsable.',
                'Ausentarse del aula de clase sin la debida autorización.',
                'Fugarse de clase con agravación, cuando ejecuta acciones que ponen en riesgo su vida o su integridad.',
                'Fugas colectivas de clase. Se entiende por fuga colectiva, la inasistencia a clase del 50% o más de los estudiantes pertenecientes a un determinado grupo.',
                'Acoso escolar (bullying) o ciberacoso reiterado hacia compañeros a través de redes sociales o medios digitales.'
            ],
            'Tipo III' => [
                'Agresión física que cause lesión personal y/o incapacidad o amenazas que atenten contra la integridad personal a cualquier miembro de la Institución Educativa.',
                'Lacerar o agredir el cuerpo de uno(s) estudiantes con perforaciones, tatuajes u otras modalidades.',
                'Utilizar mecanismos inducir o hacer que otras personas cometan actos de inmoralidad y que atenten contra su dignidad y buenas costumbres.',
                'Traer a la institución educativa material y videos pornográficos u objetos con evidente connotación sexual que atenten contra la moral y las sanas costumbres.',
                'Utilizar el nombre de la institución educativa para actividades sociales, económicas entre otras, sin la debida autorización del consejo directivo.',
                'Consumir, traficar, comerciar y portar sustancias alucinógenas o psicoactivas tanto dentro como fuera de la institución educativa.',
                'Asistir a la institución educativa bajo efectos de sustancias psicoactivas -SPA- como: alcohol, drogas, marihuana, entre otros.',
                'La amenaza con expresiones verbales o circunstanciales a compañeros dentro o fuera de la institución.',
                'Extorsión o constreñimiento a compañeros dentro o fuera de la institución.',
                'Reincidencia en faltas graves o Tipo II.',
                'Participar y/o ser cómplice en el hurto comprobado de objetos, útiles, dinero, o cualquier otro elemento que sea de la institución, de compañeros, docentes, personal administrativo, de servicios generales y visitantes.',
                'Apropiarse, hurtar, botar o destruir cualquier bien ajeno.',
                'Maltratar, atropellar o agredir físicamente a un compañero (a), docente, personal administrativo y personal de servicio.',
                'Promover o protagonizar desordenes que atenten contra el normal desarrollo de las actividades de la institución.',
                'Participar, incitar y promover peleas ya sea dentro o fuera del establecimiento educativo entre compañeros de la Institución Educativa.',
                'Sustraer, apropiarse, alterar o dañar documentos oficiales de la institución como el diario de clase, registros de asistencia, constancias, certificados, libros y planillas de calificaciones.',
                'Acosar, abusar o agredir sexualmente a cualquier miembro de la comunidad educativa.',
                'Irrespetar su propio cuerpo y el de los demás en todas sus dimensiones, generando la autoagresión, el exhibicionismo, el abuso y/o la pornografía, entre otros.',
                'Portar o usar cualquier tipo de arma dentro de la institución educativa y fuera de la institución educativa.',
                'Utilizar el chantaje o el soborno para conseguir algún beneficio personal o grupal.',
                'Mostrar comportamientos que vayan en contra de la ley, la moral y las buenas costumbres.',
                'Conformar grupos de agresión o pandillas dentro o fuera de la institución educativa.',
                'Ejecutar cualquier acción considerada como presunto delito en el código de procedimiento penal colombiano vigente.',
                'El incumplimiento reiterado de las obligaciones o compromisos académicos y disciplinarios con pérdida de tres o más áreas remitidas al comité de convivencia.'
            ]
        ];
    }

    /**
     * Definición legal del tipo de situación según el Manual
     */
    public static function getDefinicionSituacion(string $tipo): string {
        switch ($tipo) {
            case 'Tipo I':
                return 'SITUACIONES TIPO I: Corresponden a este tipo los conflictos manejados inadecuadamente y aquellas situaciones esporádicas que inciden negativamente en el clima escolar, y que en ningún caso generan daños al cuerpo o a la salud. FALTAS LEVES (TIPO I).';
            case 'Tipo II':
                return 'SITUACIONES TIPO II: Corresponden a este tipo las situaciones de agresión escolar, acoso escolar (bullying) y ciberacoso, que no revistan las características de la comisión de un delito y cumplan con cualquiera de las siguientes características: que se presenten de manera repetida o sistemática, o que causen daños al cuerpo o a la salud sin generar incapacidad médica.';
            case 'Tipo III':
                return 'SITUACIONES TIPO III: Corresponde a este tipo, las situaciones de agresión escolar que sean constitutivas de presuntos delitos contra la libertad, integridad y formación sexual, referidos en el título IV del libro II de la ley 599 de 2000; o cuando constituyen cualquier otro delito establecido en la ley penal colombiana vigente.';
            default:
                return '';
        }
    }

    /**
     * Genera un archivo Word (.docx) formal e institucional del Acta
     */
    public static function generateDocx(array $data, string $outputPath): bool {
        $tipo = $data['tipo_acta'] ?? 'Tipo I';
        $numeroActa = $data['numero_acta'] ?? ('ACTA-' . date('Ymd-His'));

        $zip = new ZipArchive();
        if ($zip->open($outputPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
            return false;
        }

        // 1. Content Types (soporte para imágenes PNG)
        $contentTypes = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
  <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
  <Default Extension="xml" ContentType="application/xml"/>
  <Default Extension="png" ContentType="image/png"/>
  <Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>
  <Override PartName="/word/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.styles+xml"/>
</Types>';
        $zip->addFromString('[Content_Types].xml', $contentTypes);

        // 2. Global Relationships
        $rels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/>
</Relationships>';
        $zip->addFromString('_rels/.rels', $rels);

        // 3. Document Relationships (vinculación de la imagen del Escudo)
        $docRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rIdStyles" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>
  <Relationship Id="rIdLogo" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/image" Target="media/logo.png"/>
</Relationships>';
        $zip->addFromString('word/_rels/document.xml.rels', $docRels);

        // Cargar Escudo Institucional Oficial
        $logoPath = __DIR__ . '/../../public/assets/logo_acta.png';
        if (!file_exists($logoPath)) {
            $logoPath = __DIR__ . '/../../public/assets/image1.png';
        }
        if (file_exists($logoPath)) {
            $zip->addFromString('word/media/logo.png', file_get_contents($logoPath));
        }

        // 4. Styles
        $styles = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:styles xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">
  <w:docDefaults>
    <w:rPrDefault>
      <w:rPr>
        <w:rFonts w:ascii="Calibri" w:hAnsi="Calibri"/>
        <w:sz w:val="21"/>
        <w:szCs w:val="21"/>
        <w:lang w:val="es-CO"/>
      </w:rPr>
    </w:rPrDefault>
  </w:docDefaults>
</w:styles>';
        $zip->addFromString('word/styles.xml', $styles);

        // Helper closures
        $esc = function($v) {
            return htmlspecialchars($v ?? '', ENT_XML1, 'UTF-8');
        };

        $p = function($text, $align = 'left', $bold = false, $size = 21, $spaceAfter = 100, $color = null) use ($esc) {
            $jc = in_array($align, ['center', 'right', 'both']) ? "<w:jc w:val=\"$align\"/>" : "";
            $b = $bold ? "<w:b/>" : "";
            $col = $color ? "<w:color w:val=\"$color\"/>" : "";
            return "<w:p><w:pPr>$jc<w:spacing w:after=\"$spaceAfter\" w:line=\"270\" w:lineRule=\"auto\"/></w:pPr><w:r><w:rPr>$b$col<w:sz w:val=\"$size\"/></w:rPr><w:t>" . $esc($text) . "</w:t></w:r></w:p>";
        };

        // Header institucional con Escudo idéntico al documento original
        $bodyXml = '';

        // Bloque del Escudo centrado
        $bodyXml .= '<w:p><w:pPr><w:jc w:val="center"/><w:spacing w:after="80" w:line="240" w:lineRule="auto"/></w:pPr><w:r><w:drawing><wp:inline distT="0" distB="0" distL="0" distR="0"><wp:extent cx="850000" cy="873743"/><wp:docPr id="1" name="Escudo La Salle"/><wp:cNvGraphicFramePr><a:graphicFrameLocks xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" noChangeAspect="1"/></wp:cNvGraphicFramePr><a:graphic xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main"><a:graphicData uri="http://schemas.openxmlformats.org/drawingml/2006/picture"><pic:pic xmlns:pic="http://schemas.openxmlformats.org/drawingml/2006/picture"><pic:nvPicPr><pic:cNvPr id="1" name="Escudo La Salle"/><pic:cNvPicPr/></pic:nvPicPr><pic:blipFill><a:blip r:embed="rIdLogo" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"/><a:stretch><a:fillRect/></a:stretch></pic:blipFill><pic:spPr><a:xfrm><a:off x="0" y="0"/><a:ext cx="850000" cy="873743"/></a:xfrm><a:prstGeom prst="rect"><a:avLst/></a:prstGeom></pic:spPr></pic:pic></a:graphicData></a:graphic></wp:inline></w:drawing></w:r></w:p>';

        $bodyXml .= $p("INSTITUTO LA SALLE", "center", true, 26, 40, "0B3C68");
        $bodyXml .= $p("Aprobado por Resolución No 1806 de Octubre 08 de 2019", "center", false, 18, 30);
        $bodyXml .= $p("Preescolar, Básica Primaria, Secundaria y Media • DANE N° 347189041870", "center", false, 17, 30);
        $bodyXml .= $p("Ciénaga – Magdalena", "center", true, 18, 140, "333333");

        // Título del Acta
        $bodyXml .= $p("ACTA DE REPORTE", "center", true, 24, 30, "0B3C68");
        $bodyXml .= $p("SITUACIONES DISCIPLINARIAS", "center", true, 22, 30);
        $bodyXml .= $p(strtoupper($tipo), "center", true, 26, 120, ($tipo === 'Tipo III' ? 'C53030' : ($tipo === 'Tipo II' ? 'DD6B20' : '2B6CB0')));
        $bodyXml .= $p("N° DE ACTA: " . $numeroActa, "right", true, 18, 120, "555555");

        // Tabla de Datos del Estudiante
        $estNom = strtoupper($data['estudiante_nombre'] ?? 'ESTUDIANTE');
        $estDoc = $data['estudiante_doc'] ?? 'N/D';
        $estGrado = $data['estudiante_grado'] ?? '11° A';
        $fechaActa = $data['fecha_acta'] ?? date('Y-m-d');

        $bodyXml .= '<w:tbl><w:tblPr><w:tblW w:w="9400" w:type="dxa"/><w:tblBorders><w:top w:val="single" w:sz="6" w:space="0" w:color="0B3C68"/><w:left w:val="single" w:sz="6" w:space="0" w:color="0B3C68"/><w:bottom w:val="single" w:sz="6" w:space="0" w:color="0B3C68"/><w:right w:val="single" w:sz="6" w:space="0" w:color="0B3C68"/><w:insideH w:val="single" w:sz="4" w:space="0" w:color="CCCCCC"/><w:insideV w:val="single" w:sz="4" w:space="0" w:color="CCCCCC"/></w:tblBorders></w:tblPr>';
        $bodyXml .= '<w:tr><w:tc><w:tcPr><w:tcW w:w="6000" w:type="dxa"/><w:shd w:val="clear" w:color="auto" w:fill="F8FAFC"/></w:tcPr><w:p><w:r><w:rPr><w:b/><w:sz w:val="19"/></w:rPr><w:t>Nombre del Estudiante: </w:t></w:r><w:r><w:rPr><w:sz w:val="19"/></w:rPr><w:t>' . $esc($estNom) . '</w:t></w:r></w:p></w:tc><w:tc><w:tcPr><w:tcW w:w="3400" w:type="dxa"/><w:shd w:val="clear" w:color="auto" w:fill="F8FAFC"/></w:tcPr><w:p><w:r><w:rPr><w:b/><w:sz w:val="19"/></w:rPr><w:t>Documento: </w:t></w:r><w:r><w:rPr><w:sz w:val="19"/></w:rPr><w:t>' . $esc($estDoc) . '</w:t></w:r></w:p></w:tc></w:tr>';
        $bodyXml .= '<w:tr><w:tc><w:tcPr><w:tcW w:w="6000" w:type="dxa"/><w:shd w:val="clear" w:color="auto" w:fill="FFFFFF"/></w:tcPr><w:p><w:r><w:rPr><w:b/><w:sz w:val="19"/></w:rPr><w:t>Grado / Curso: </w:t></w:r><w:r><w:rPr><w:sz w:val="19"/></w:rPr><w:t>' . $esc($estGrado) . '</w:t></w:r></w:p></w:tc><w:tc><w:tcPr><w:tcW w:w="3400" w:type="dxa"/><w:shd w:val="clear" w:color="auto" w:fill="FFFFFF"/></w:tcPr><w:p><w:r><w:rPr><w:b/><w:sz w:val="19"/></w:rPr><w:t>Fecha del Reporte: </w:t></w:r><w:r><w:rPr><w:sz w:val="19"/></w:rPr><w:t>' . $esc($fechaActa) . '</w:t></w:r></w:p></w:tc></w:tr>';
        $bodyXml .= '</w:tbl>';
        $bodyXml .= $p("", "left", false, 12, 100);

        // Definición legal
        $defTexto = self::getDefinicionSituacion($tipo);
        $bodyXml .= '<w:p><w:pPr><w:spacing w:after="140" w:line="260" w:lineRule="auto"/><w:pBdr><w:left w:val="single" w:sz="18" w:space="8" w:color="0B3C68"/></w:pBdr><w:shd w:val="clear" w:color="auto" w:fill="F1F5F9"/></w:pPr><w:r><w:rPr><w:i/><w:sz w:val="18"/><w:color w:val="334155"/></w:rPr><w:t>' . $esc($defTexto) . '</w:t></w:r></w:p>';

        // Faltas cometidas / seleccionadas
        $bodyXml .= $p("FALTAS O SITUACIONES REGISTRADAS:", "left", true, 20, 60, "0B3C68");

        $faltasArr = [];
        if (!empty($data['faltas_seleccionadas'])) {
            if (is_array($data['faltas_seleccionadas'])) {
                $faltasArr = $data['faltas_seleccionadas'];
            } else {
                $decoded = json_decode($data['faltas_seleccionadas'], true);
                if (is_array($decoded)) {
                    $faltasArr = $decoded;
                } else {
                    $faltasArr = array_filter(array_map('trim', explode("\n", $data['faltas_seleccionadas'])));
                }
            }
        }

        if (!empty($faltasArr)) {
            foreach ($faltasArr as $idx => $faltaItem) {
                $numF = $idx + 1;
                $bodyXml .= "<w:p><w:pPr><w:spacing w:after=\"60\" w:line=\"240\" w:lineRule=\"auto\"/><w:ind w:left=\"360\" w:hanging=\"360\"/></w:pPr><w:r><w:rPr><w:b/><w:color w:val=\"0B3C68\"/><w:sz w:val=\"19\"/></w:rPr><w:t>☒ [$numF] </w:t></w:r><w:r><w:rPr><w:sz w:val=\"19\"/></w:rPr><w:t>" . $esc($faltaItem) . "</w:t></w:r></w:p>";
            }
        } else {
            $bodyXml .= $p("No se especificaron faltas puntuales del catálogo oficial.", "left", false, 18, 80);
        }
        $bodyXml .= $p("", "left", false, 12, 100);

        // Para Tipo III: Remisión Externa
        if ($tipo === 'Tipo III' && !empty($data['remision_entidad'])) {
            $bodyXml .= '<w:p><w:pPr><w:spacing w:after="120" w:line="260" w:lineRule="auto"/><w:shd w:val="clear" w:color="auto" w:fill="FEE2E2"/></w:pPr><w:r><w:rPr><w:b/><w:sz w:val="20"/><w:color w:val="991B1B"/></w:rPr><w:t>ESTA ACTA DEBE REMITIRSE A: </w:t></w:r><w:r><w:rPr><w:sz w:val="20"/><w:color w:val="991B1B"/></w:rPr><w:t>' . $esc($data['remision_entidad']) . '</w:t></w:r></w:p>';
        }

        // Descargos del estudiante
        $bodyXml .= $p("DESCARGOS DEL ESTUDIANTE QUE HA COMETIDO LA FALTA:", "left", true, 20, 60, "0B3C68");
        $descargos = trim($data['descargos'] ?? '');
        $bodyXml .= $p($descargos ?: 'El estudiante no presenta descargos adicionales en la presente diligencia.', "both", false, 19, 140);

        // Testigos
        $bodyXml .= $p("TESTIGOS:", "left", true, 20, 60, "0B3C68");
        $testigos = trim($data['testigos'] ?? '');
        $bodyXml .= $p($testigos ?: 'No se registran testigos adicionales.', "both", false, 19, 140);

        // Descripción del bien o parte afectada
        $bodyXml .= $p("DESCRIPCIÓN DEL BIEN AFECTADO O DE LA PARTE AFECTADA:", "left", true, 20, 60, "0B3C68");
        $afectados = trim($data['afectados_o_bienes'] ?? '');
        $bodyXml .= $p($afectados ?: 'No se registran daños materiales ni afectación física identificada.', "both", false, 19, 140);

        // Compromisos
        $bodyXml .= $p("COMPROMISOS DEL ALUMNO QUE COMETE LA FALTA:", "left", true, 20, 60, "0B3C68");
        $compromisos = trim($data['compromisos'] ?? '');
        $bodyXml .= $p($compromisos ?: 'El alumno se compromete a respetar las normas de convivencia y acatar las directrices escolares.', "both", false, 19, 140);

        // Otros
        if (!empty($data['otros'])) {
            $bodyXml .= $p("OTROS / OBSERVACIONES ADICIONALES:", "left", true, 20, 60, "0B3C68");
            $bodyXml .= $p(trim($data['otros']), "both", false, 19, 140);
        }

        // Cuadro de Firmas Institucionales (2 filas x 2 columnas = 8 firmas)
        $bodyXml .= $p("", "left", false, 12, 180);
        $bodyXml .= $p("CONSTANCIA DE FIRMAS DE LOS INTERVINIENTES", "center", true, 19, 140, "555555");

        $firmaAlumno   = !empty($data['firma_alumno']) ? $data['firma_alumno'] : $estNom;
        $firmaAfectado = !empty($data['firma_afectado']) ? $data['firma_afectado'] : 'Parte Afectada';
        $firmaTestigo1 = !empty($data['firma_testigo1']) ? $data['firma_testigo1'] : 'Testigo 1';
        $firmaTestigo2 = !empty($data['firma_testigo2']) ? $data['firma_testigo2'] : 'Testigo 2';
        $firmaPadre    = !empty($data['firma_padre']) ? $data['firma_padre'] : (!empty($data['acudiente_nombre']) ? $data['acudiente_nombre'] : 'Padre o Acudiente');
        $firmaDocente  = !empty($data['firma_docente']) ? $data['firma_docente'] : (!empty($data['creador_nombre']) ? $data['creador_nombre'] : 'Docente Responsable');
        $firmaDirGrupo = !empty($data['firma_dir_grupo']) ? $data['firma_dir_grupo'] : 'Director de Grupo';
        $firmaCoord    = !empty($data['firma_coord']) ? $data['firma_coord'] : 'Coordinador de Disciplina';

        $firmaCell = function($cargo, $nombre) use ($esc) {
            return '<w:p><w:pPr><w:jc w:val="center"/><w:spacing w:before="360" w:after="20"/></w:pPr><w:r><w:rPr><w:sz w:val="18"/></w:rPr><w:t>_______________________________________</w:t></w:r></w:p>' .
                   '<w:p><w:pPr><w:jc w:val="center"/><w:spacing w:after="20"/></w:pPr><w:r><w:rPr><w:b/><w:sz w:val="17"/><w:color w:val="0B3C68"/></w:rPr><w:t>' . $esc($cargo) . '</w:t></w:r></w:p>' .
                   '<w:p><w:pPr><w:jc w:val="center"/><w:spacing w:after="160"/></w:pPr><w:r><w:rPr><w:sz w:val="16"/><w:color w:val="475569"/></w:rPr><w:t>' . $esc($nombre) . '</w:t></w:r></w:p>';
        };

        $bodyXml .= '<w:tbl><w:tblPr><w:tblW w:w="9400" w:type="dxa"/><w:tblBorders><w:top w:val="none"/><w:left w:val="none"/><w:bottom w:val="none"/><w:right w:val="none"/><w:insideH w:val="none"/><w:insideV w:val="none"/></w:tblBorders></w:tblPr>';
        
        // Fila 1: Alumno y Parte Afectada
        $bodyXml .= '<w:tr><w:tc><w:tcPr><w:tcW w:w="4700" w:type="dxa"/></w:tcPr>' . $firmaCell('ALUMNO QUE COMETE LA FALTA', $firmaAlumno) . '</w:tc><w:tc><w:tcPr><w:tcW w:w="4700" w:type="dxa"/></w:tcPr>' . $firmaCell('PARTE AFECTADA', $firmaAfectado) . '</w:tc></w:tr>';
        
        // Fila 2: Testigos
        $bodyXml .= '<w:tr><w:tc><w:tcPr><w:tcW w:w="4700" w:type="dxa"/></w:tcPr>' . $firmaCell('TESTIGO', $firmaTestigo1) . '</w:tc><w:tc><w:tcPr><w:tcW w:w="4700" w:type="dxa"/></w:tcPr>' . $firmaCell('TESTIGO', $firmaTestigo2) . '</w:tc></w:tr>';

        // Fila 3: Acudiente y Docente
        $bodyXml .= '<w:tr><w:tc><w:tcPr><w:tcW w:w="4700" w:type="dxa"/></w:tcPr>' . $firmaCell('PADRE O ACUDIENTE', $firmaPadre) . '</w:tc><w:tc><w:tcPr><w:tcW w:w="4700" w:type="dxa"/></w:tcPr>' . $firmaCell('DOCENTE', $firmaDocente) . '</w:tc></w:tr>';

        // Fila 4: Director de Grupo y Coordinador
        $bodyXml .= '<w:tr><w:tc><w:tcPr><w:tcW w:w="4700" w:type="dxa"/></w:tcPr>' . $firmaCell('DIRECTOR DE GRUPO', $firmaDirGrupo) . '</w:tc><w:tc><w:tcPr><w:tcW w:w="4700" w:type="dxa"/></w:tcPr>' . $firmaCell('COORDINADOR DE DISCIPLINA', $firmaCoord) . '</w:tc></w:tr>';

        $bodyXml .= '</w:tbl>';

        // Document.xml wrapper
        $documentXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"
            xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"
            xmlns:wp="http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing"
            xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main"
            xmlns:pic="http://schemas.openxmlformats.org/drawingml/2006/picture">
  <w:body>' . $bodyXml . '
    <w:sectPr>
      <w:pgSz w:w="12240" w:h="15840"/>
      <w:pgMar w:top="1440" w:right="1440" w:bottom="1440" w:left="1440" w:header="720" w:footer="720" w:gutter="0"/>
    </w:sectPr>
  </w:body>
</w:document>';

        $zip->addFromString('word/document.xml', $documentXml);
        $zip->close();
        return true;
    }
}
