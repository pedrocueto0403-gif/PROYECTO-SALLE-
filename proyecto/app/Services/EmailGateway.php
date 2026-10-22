<?php
/**
 * SERVICIO AUTOMATIZADO DE NOTIFICACIONES (EMAIL GATEWAY)
 * Instituto La Salle Ciénaga Magdalena
 * Manejo de citaciones de psicología y alertas de cartera/cobro con plantillas HTML profesionales
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/Database.php';

class EmailGateway {
    
    /**
     * Envía citación de orientación o psicología escolar al acudiente
     */
    public static function enviarCitacionPsicologia(
        string $destinatarioCorreo,
        string $destinatarioNombre,
        string $estudianteNombre,
        string $fechaHoraCitacion,
        string $modalidad,
        string $motivo,
        string $psicologoNombre,
        ?int $referenciaId = null
    ): array {
        $timestamp = strtotime($fechaHoraCitacion);
        $fechaFormato = $timestamp ? date('d/m/Y', $timestamp) : $fechaHoraCitacion;
        $horaFormato  = $timestamp ? date('h:i A', $timestamp) : 'Hora pactada';

        $asunto = "CITACIÓN ORIENTACIÓN ESCOLAR — Instituto La Salle: Estudiante {$estudianteNombre}";

        $cuerpoHtml = "
        <!DOCTYPE html>
        <html lang='es'>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f4f6f9; margin: 0; padding: 20px; color: #333; }
                .email-container { max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.08); border: 1px solid #e1e7ec; }
                .email-header { background: #1E3A8A; color: #ffffff; padding: 25px 20px; text-align: center; }
                .email-header h2 { margin: 0; font-size: 20px; text-transform: uppercase; letter-spacing: 0.5px; }
                .email-header p { margin: 5px 0 0; font-size: 13px; color: #e2e8f0; }
                .email-body { padding: 30px 25px; }
                .badge-citacion { display: inline-block; background: #FEF3C7; color: #92400E; padding: 4px 12px; border-radius: 20px; font-weight: bold; font-size: 12px; margin-bottom: 15px; border: 1px solid #FCD34D; }
                .data-card { background: #F8FAFC; border-left: 4px solid #3B82F6; padding: 18px; margin: 20px 0; border-radius: 0 6px 6px 0; }
                .data-row { margin-bottom: 10px; font-size: 14px; }
                .data-label { font-weight: bold; color: #475569; width: 140px; display: inline-block; }
                .motivo-box { background: #FFFBEB; border: 1px solid #FDE68A; padding: 14px; border-radius: 6px; font-size: 13px; color: #78350F; margin-top: 15px; }
                .email-footer { background: #f8fafc; padding: 18px 25px; text-align: center; font-size: 11px; color: #64748b; border-top: 1px solid #e2e8f0; }
            </style>
        </head>
        <body>
            <div class='email-container'>
                <div class='email-header'>
                    <h2>INSTITUTO LA SALLE</h2>
                    <p>Departamento de Bienestar Estudiantil & Orientación Escolar</p>
                </div>
                <div class='email-body'>
                    <div class='badge-citacion'>NOTIFICACIÓN OFICIAL DE CITACIÓN</div>
                    <p>Estimado(a) Acudiente / Tutor legal <strong>" . htmlspecialchars($destinatarioNombre) . "</strong>,</p>
                    <p>Por medio de la presente, nos dirigimos a usted para convocarlo(a) de manera formal a una sesión de atención individual de orientación escolar correspondiente al estudiante:</p>
                    
                    <div class='data-card'>
                        <div class='data-row'><span class='data-label'>Estudiante:</span> <strong>" . htmlspecialchars($estudianteNombre) . "</strong></div>
                        <div class='data-row'><span class='data-label'>Fecha:</span> <strong>{$fechaFormato}</strong></div>
                        <div class='data-row'><span class='data-label'>Hora:</span> <strong>{$horaFormato}</strong></div>
                        <div class='data-row'><span class='data-label'>Modalidad:</span> <span style='color:#1E3A8A; font-weight:bold;'>" . htmlspecialchars($modalidad) . "</span></div>
                        <div class='data-row'><span class='data-label'>Profesional a cargo:</span> <strong>" . htmlspecialchars($psicologoNombre) . "</strong> (Psicología / Orientación)</div>
                    </div>

                    <p><strong>Motivo / Asunto de la Citación:</strong></p>
                    <div class='motivo-box'>
                        " . nl2br(htmlspecialchars($motivo)) . "
                    </div>

                    <p style='margin-top:20px; font-size:13px; color:#475569;'>
                        Su asistencia puntual es fundamental para el acompañamiento y desarrollo integral de su acudido(a). En caso de fuerza mayor que impida su asistencia, agradecemos comunicarse previamente con la secretaría o departamento de psicología.
                    </p>
                </div>
                <div class='email-footer'>
                    <strong>Instituto La Salle Ciénaga Magdalena</strong><br>
                    Carrera 31 N° 18-70 • Teléfono: 5 4101084 • Correo: psicologia@lasallecienaga.edu.co<br>
                    Este mensaje ha sido generado automáticamente por el sistema AulaVirtual Salle.
                </div>
            </div>
        </body>
        </html>
        ";

        return self::despacharCorreo(
            $destinatarioCorreo,
            $destinatarioNombre,
            'psicologia_citacion',
            $asunto,
            $cuerpoHtml,
            $referenciaId
        );
    }

    /**
     * Envía notificación de cobro / comprobante de cartera financiera al acudiente
     * Estructura obligatoria: Desglose de conceptos, Monto total a cancelar, Fecha límite de pago
     */
    public static function enviarComprobanteCobroCartera(
        string $destinatarioCorreo,
        string $destinatarioNombre,
        string $estudianteNombre,
        array $desgloseConceptos,
        float $montoTotal,
        string $fechaLimitePago,
        string $reciboNumero = '',
        ?int $referenciaId = null
    ): array {
        $timestamp = strtotime($fechaLimitePago);
        $fechaLimiteFormato = $timestamp ? date('d/m/Y', $timestamp) : $fechaLimitePago;

        $asunto = "ESTADO DE CUENTA Y AVISO DE COBRO — Instituto La Salle [Estudiante: {$estudianteNombre}]";

        // Armar tabla de desglose de conceptos
        $filasConceptos = '';
        foreach ($desgloseConceptos as $c) {
            $conceptoNombre = htmlspecialchars($c['concepto'] ?? 'Obligación Académica');
            $periodoMes     = htmlspecialchars($c['periodo'] ?? 'Periodo Regular');
            $submonto       = number_format(floatval($c['monto'] ?? 0), 0, ',', '.');
            $filasConceptos .= "
                <tr style='border-bottom: 1px solid #E2E8F0;'>
                    <td style='padding: 10px; font-size: 13px;'><strong>{$conceptoNombre}</strong><br><span style='color:#64748B; font-size: 11px;'>Ref: {$periodoMes}</span></td>
                    <td style='padding: 10px; font-size: 13px; text-align: right; font-weight: 600;'>$ {$submonto} COP</td>
                </tr>
            ";
        }

        $totalFormato = number_format($montoTotal, 0, ',', '.');
        $numComprobante = $reciboNumero ? htmlspecialchars($reciboNumero) : ('REC-' . date('Ym') . '-' . rand(1000, 9999));

        $cuerpoHtml = "
        <!DOCTYPE html>
        <html lang='es'>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #F1F5F9; margin: 0; padding: 20px; color: #1E293B; }
                .email-container { max-width: 620px; margin: 0 auto; background: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 14px rgba(0,0,0,0.07); border: 1px solid #CBD5E1; }
                .email-header { background: #0F172A; color: #ffffff; padding: 25px 20px; text-align: center; border-bottom: 3px solid #F59E0B; }
                .email-header h2 { margin: 0; font-size: 20px; letter-spacing: 0.5px; }
                .email-header p { margin: 4px 0 0; font-size: 13px; color: #94A3B8; }
                .email-body { padding: 25px 30px; }
                .aviso-alerta { background: #FEF3C7; border: 1px solid #F59E0B; color: #92400E; padding: 12px 16px; border-radius: 6px; font-size: 13px; margin-bottom: 20px; }
                .comprobante-meta { background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 6px; padding: 14px 18px; margin-bottom: 22px; font-size: 13px; }
                .tabla-conceptos { width: 100%; border-collapse: collapse; margin: 15px 0 20px 0; }
                .tabla-conceptos th { background: #1E3A8A; color: #ffffff; text-align: left; padding: 10px; font-size: 12px; text-transform: uppercase; }
                .total-box { background: #ECFDF5; border: 2px dashed #10B981; padding: 15px 20px; border-radius: 6px; text-align: right; margin-bottom: 20px; }
                .fecha-limite-box { background: #EFF6FF; border-left: 4px solid #2563EB; padding: 12px 16px; border-radius: 0 6px 6px 0; font-size: 13px; color: #1E3A8A; margin-bottom: 20px; }
                .metodos-pago { font-size: 12px; color: #475569; background: #F8FAFC; padding: 14px; border-radius: 6px; border: 1px solid #E2E8F0; }
                .email-footer { background: #F8FAFC; padding: 18px; text-align: center; font-size: 11px; color: #64748B; border-top: 1px solid #E2E8F0; }
            </style>
        </head>
        <body>
            <div class='email-container'>
                <div class='email-header'>
                    <h2>INSTITUTO LA SALLE</h2>
                    <p>Departamento Administrativo & Tesorería Escolar</p>
                </div>
                <div class='email-body'>
                    <div class='aviso-alerta'>
                        <strong>⚠️ AVISO DE OBLIGACIÓN FINANCIERA / CARTERA INSTITUCIONAL</strong><br>
                        Le recordamos mantener sus obligaciones al día para evitar recargos o suspensión de servicios académicos.
                    </div>

                    <p>Apreciado(a) Padre de Familia / Acudiente: <strong>" . htmlspecialchars($destinatarioNombre) . "</strong>,</p>
                    <p>Adjuntamos el detalle del estado de cuenta y comprobante de cobro emitido a nombre de su acudido(a):</p>

                    <div class='comprobante-meta'>
                        <table style='width:100%; font-size:13px;'>
                            <tr>
                                <td><strong>Estudiante:</strong> " . htmlspecialchars($estudianteNombre) . "</td>
                                <td style='text-align:right;'><strong>N° Comprobante:</strong> {$numComprobante}</td>
                            </tr>
                            <tr>
                                <td><strong>Fecha de Emisión:</strong> " . date('d/m/Y') . "</td>
                                <td style='text-align:right;'><strong>Moneda:</strong> Pesos Colombianos (COP)</td>
                            </tr>
                        </table>
                    </div>

                    <h4 style='margin: 0 0 8px 0; color: #1E3A8A; font-size: 14px; text-transform: uppercase;'>1. Desglose de Conceptos Adeudados</h4>
                    <table class='tabla-conceptos'>
                        <thead>
                            <tr>
                                <th>Concepto / Rubro</th>
                                <th style='text-align: right;'>Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            {$filasConceptos}
                        </tbody>
                    </table>

                    <h4 style='margin: 0 0 8px 0; color: #1E3A8A; font-size: 14px; text-transform: uppercase;'>2. Monto Total a Cancelar</h4>
                    <div class='total-box'>
                        <span style='font-size: 13px; color: #047857; font-weight: bold;'>TOTAL A PAGAR:</span><br>
                        <span style='font-size: 24px; font-weight: 900; color: #065F46;'>$ {$totalFormato} COP</span>
                    </div>

                    <h4 style='margin: 0 0 8px 0; color: #1E3A8A; font-size: 14px; text-transform: uppercase;'>3. Plazo y Fecha Límite de Pago</h4>
                    <div class='fecha-limite-box'>
                        <strong>📅 FECHA LÍMITE DE PAGO: {$fechaLimiteFormato}</strong><br>
                        Plazo máximo de recepción de fondos antes de incurrir en intereses moratorios, recargos reglamentarios o restricción administrativa en plataforma.
                    </div>

                    <div class='metodos-pago'>
                        <strong>Canales de Pago Habilitados:</strong><br>
                        • <strong>Caja de Tesorería Escolar:</strong> Efectivo o Datáfono en ventanilla institucional (Lunes a Viernes de 7:00 AM a 2:00 PM).<br>
                        • <strong>Transferencia / Consignación Bancaria:</strong> Cuenta de Ahorros Bancolombia N° 819-004984-10 a nombre de <em>Instituto La Salle</em> (NIT: 8190049841-0). Enviar comprobante con nombre completo y grado al correo tesoreria@lasallecienaga.edu.co.
                    </div>
                </div>
                <div class='email-footer'>
                    <strong>Instituto La Salle Ciénaga Magdalena</strong><br>
                    Carrera 31 N° 18-70 • Teléfono: 5 4101084 • Correo: tesoreria@lasallecienaga.edu.co<br>
                    Este es un comprobante oficial de cobranza emitido automáticamente por el sistema de cartera.
                </div>
            </div>
        </body>
        </html>
        ";

        return self::despacharCorreo(
            $destinatarioCorreo,
            $destinatarioNombre,
            'cartera_cobro',
            $asunto,
            $cuerpoHtml,
            $referenciaId
        );
    }

    /**
     * Notificación de Admisión Aprobada al Acudiente con instrucciones de pago en Caja
     */
    public static function enviarAprobacionAdmision(
        string $destinatarioCorreo,
        string $destinatarioNombre,
        string $estudianteNombre,
        string $codigoSolicitud,
        string $gradoNombre,
        float $montoMatricula,
        ?int $referenciaId = null
    ): array {
        $montoFmt = number_format($montoMatricula, 0, ',', '.');
        $asunto = "¡SOLICITUD DE ADMISIÓN APROBADA! — Instituto La Salle [Radicado: {$codigoSolicitud}]";

        $cuerpoHtml = "
        <!DOCTYPE html>
        <html lang='es'>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f1f5f9; margin: 0; padding: 20px; color: #1e293b; }
                .email-container { max-width: 620px; margin: 0 auto; background: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 10px 25px rgba(0,0,0,0.08); border: 1px solid #cbd5e1; }
                .email-header { background: linear-gradient(135deg, #0F172A 0%, #1E3A8A 100%); color: #ffffff; padding: 30px 25px; text-align: center; border-bottom: 4px solid #D4AF37; }
                .email-header h2 { margin: 0; font-size: 22px; letter-spacing: 0.5px; font-weight: 700; }
                .email-header p { margin: 6px 0 0; font-size: 13px; color: #cbd5e1; }
                .email-body { padding: 32px 28px; }
                .badge-status { display: inline-block; background: #DCFCE7; color: #15803D; padding: 6px 14px; border-radius: 20px; font-weight: 700; font-size: 12px; margin-bottom: 18px; border: 1px solid #86EFAC; }
                .data-card { background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 8px; padding: 20px; margin: 20px 0; }
                .data-row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px dashed #E2E8F0; font-size: 14px; }
                .data-row:last-child { border-bottom: none; }
                .data-label { font-weight: 600; color: #475569; }
                .data-val { font-weight: 700; color: #0F172A; text-align: right; }
                .monto-box { background: #EFF6FF; border: 2px solid #3B82F6; border-radius: 8px; padding: 18px; text-align: center; margin: 20px 0; }
                .monto-title { font-size: 13px; text-transform: uppercase; color: #1E40AF; font-weight: 700; letter-spacing: 0.5px; }
                .monto-val { font-size: 26px; font-weight: 800; color: #1E3A8A; margin: 5px 0; }
                .instructions-box { background: #FFFBEB; border-left: 4px solid #F59E0B; padding: 16px; border-radius: 0 8px 8px 0; margin-top: 20px; font-size: 13px; color: #78350F; line-height: 1.5; }
                .email-footer { background: #f8fafc; padding: 20px; text-align: center; font-size: 12px; color: #64748b; border-top: 1px solid #e2e8f0; }
            </style>
        </head>
        <body>
            <div class='email-container'>
                <div class='email-header'>
                    <h2>INSTITUTO LA SALLE</h2>
                    <p>Admisiones & Registro Académico • Ciénaga Magdalena</p>
                </div>
                <div class='email-body'>
                    <div class='badge-status'>✓ ADMISIÓN APROBADA</div>
                    <p style='font-size: 15px;'>Apreciado(a) Acudiente <strong>" . htmlspecialchars($destinatarioNombre) . "</strong>,</p>
                    <p style='font-size: 14px; line-height: 1.6; color: #334155;'>
                        Nos complace informarle que la solicitud de admisión para el aspirante <strong>" . htmlspecialchars($estudianteNombre) . "</strong> ha sido formalmente <strong>APROBADA</strong> por el comité institucional para el año lectivo 2026.
                    </p>

                    <div class='data-card'>
                        <div class='data-row'>
                            <span class='data-label'>N° de Radicado:</span>
                            <span class='data-val' style='color: #1E3A8A;'>" . htmlspecialchars($codigoSolicitud) . "</span>
                        </div>
                        <div class='data-row'>
                            <span class='data-label'>Aspirante:</span>
                            <span class='data-val'>" . htmlspecialchars($estudianteNombre) . "</span>
                        </div>
                        <div class='data-row'>
                            <span class='data-label'>Grado Asignado:</span>
                            <span class='data-val'>" . htmlspecialchars($gradoNombre) . "</span>
                        </div>
                        <div class='data-row'>
                            <span class='data-label'>Año Académico:</span>
                            <span class='data-val'>2026</span>
                        </div>
                    </div>

                    <div class='monto-box'>
                        <div class='monto-title'>Valor de Matrícula Institucional</div>
                        <div class='monto-val'>$ {$montoFmt} COP</div>
                        <small style='color: #64748B;'>Concepto: Matrícula Oficial Salle 2026</small>
                    </div>

                    <div class='instructions-box'>
                        <strong>📌 Siguiente paso para formalizar la matrícula:</strong><br>
                        Por favor acérquese a la ventanilla de <strong>Tesorería y Caja</strong> del Instituto La Salle indicando el número de radicado <strong>" . htmlspecialchars($codigoSolicitud) . "</strong> o documento del aspirante.<br><br>
                        Tan pronto se registre el pago en Caja, <em>el sistema activará automáticamente la matrícula y creará las credenciales del Aula Virtual</em>, las cuales recibirá en este correo electrónico.
                    </div>
                </div>
                <div class='email-footer'>
                    <strong>Instituto La Salle Ciénaga Magdalena</strong><br>
                    \"Dios, Ciencia y Virtud\" • Teléfono Secretaría: (605) 485-0000<br>
                    Este es un mensaje institucional automático.
                </div>
            </div>
        </body>
        </html>
        ";

        return self::despacharCorreo(
            $destinatarioCorreo,
            $destinatarioNombre,
            'general',
            $asunto,
            $cuerpoHtml,
            $referenciaId
        );
    }

    /**
     * Notificación de Admisión Rechazada con Motivo
     */
    public static function enviarNotificacionRechazoAdmision(
        string $destinatarioCorreo,
        string $destinatarioNombre,
        string $estudianteNombre,
        string $codigoSolicitud,
        string $motivoRechazo,
        ?int $referenciaId = null
    ): array {
        $asunto = "Actualización de Solicitud de Admisión — Instituto La Salle [Radicado: {$codigoSolicitud}]";

        $cuerpoHtml = "
        <!DOCTYPE html>
        <html lang='es'>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f8fafc; margin: 0; padding: 20px; color: #1e293b; }
                .email-container { max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 10px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.06); border: 1px solid #e2e8f0; }
                .email-header { background: #0F172A; color: #ffffff; padding: 25px 20px; text-align: center; border-bottom: 3px solid #EF4444; }
                .email-header h2 { margin: 0; font-size: 20px; }
                .email-body { padding: 30px 25px; }
                .badge-status { display: inline-block; background: #FEE2E2; color: #B91C1C; padding: 5px 12px; border-radius: 20px; font-weight: bold; font-size: 12px; margin-bottom: 15px; }
                .motivo-card { background: #FFF5F5; border-left: 4px solid #EF4444; padding: 16px; border-radius: 0 6px 6px 0; margin: 20px 0; font-size: 14px; color: #7F1D1D; line-height: 1.5; }
                .email-footer { background: #f8fafc; padding: 18px; text-align: center; font-size: 11px; color: #64748b; border-top: 1px solid #e2e8f0; }
            </style>
        </head>
        <body>
            <div class='email-container'>
                <div class='email-header'>
                    <h2>INSTITUTO LA SALLE</h2>
                    <p style='margin: 4px 0 0; font-size: 12px; color: #cbd5e1;'>Comité de Admisiones</p>
                </div>
                <div class='email-body'>
                    <div class='badge-status'>ESTADO: SOLICITUD NO ADMITIDA</div>
                    <p>Estimado(a) Acudiente <strong>" . htmlspecialchars($destinatarioNombre) . "</strong>,</p>
                    <p style='line-height: 1.6; font-size: 14px;'>
                        Le informamos que la solicitud de admisión con radicado <strong>" . htmlspecialchars($codigoSolicitud) . "</strong> para el aspirante <strong>" . htmlspecialchars($estudianteNombre) . "</strong> no pudo ser admitida para el periodo actual.
                    </p>
                    <div class='motivo-card'>
                        <strong>Observaciones del Comité:</strong><br>
                        " . nl2br(htmlspecialchars($motivoRechazo)) . "
                    </div>
                    <p style='font-size: 13px; color: #64748B;'>Para cualquier aclaración adicional, puede comunicarse con la Secretaría Académica institucional.</p>
                </div>
                <div class='email-footer'>
                    Instituto La Salle Ciénaga Magdalena • \"Dios, Ciencia y Virtud\"
                </div>
            </div>
        </body>
        </html>
        ";

        return self::despacharCorreo(
            $destinatarioCorreo,
            $destinatarioNombre,
            'general',
            $asunto,
            $cuerpoHtml,
            $referenciaId
        );
    }

    /**
     * Bienvenida Institucional y Entrega de Credenciales de Acceso al Aula Virtual tras pago en Caja
     */
    public static function enviarBienvenidaMatricula(
        string $destinatarioCorreo,
        string $destinatarioNombre,
        string $estudianteNombre,
        string $codigoMatricula,
        string $gradoNombre,
        string $usuarioCorreo,
        string $passwordTemporal,
        string $reciboNumero,
        ?int $referenciaId = null
    ): array {
        $asunto = "¡BIENVENIDO A LA SALLE! Matrícula Exitosa y Credenciales de Acceso [Estudiante: {$estudianteNombre}]";
        $loginUrl = defined('APP_URL') ? (APP_URL . '/views/auth/login.php') : '#';

        $cuerpoHtml = "
        <!DOCTYPE html>
        <html lang='es'>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f1f5f9; margin: 0; padding: 20px; color: #0f172a; }
                .email-container { max-width: 620px; margin: 0 auto; background: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.08); border: 1px solid #cbd5e1; }
                .email-header { background: linear-gradient(135deg, #0F172A 0%, #1E3A8A 50%, #B89628 100%); color: #ffffff; padding: 32px 24px; text-align: center; }
                .email-header h2 { margin: 0; font-size: 24px; font-weight: 800; letter-spacing: 0.5px; }
                .email-header p { margin: 8px 0 0; font-size: 13px; color: #e2e8f0; }
                .email-body { padding: 32px 28px; }
                .badge-success-pill { display: inline-block; background: #DCFCE7; color: #166534; padding: 6px 16px; border-radius: 30px; font-weight: 700; font-size: 12px; margin-bottom: 20px; border: 1px solid #86EFAC; }
                .creds-box { background: linear-gradient(135deg, #0F172A 0%, #1E3A8A 100%); color: #ffffff; border-radius: 10px; padding: 24px; margin: 24px 0; border: 2px solid #D4AF37; }
                .creds-row { margin-bottom: 12px; font-size: 14px; }
                .creds-label { color: #93C5FD; font-weight: 600; display: inline-block; width: 140px; }
                .creds-val { color: #FFFFFF; font-weight: 700; font-family: 'Courier New', monospace; font-size: 15px; background: rgba(255,255,255,0.1); padding: 3px 8px; border-radius: 4px; }
                .btn-login { display: inline-block; background: #D4AF37; color: #0F172A; text-decoration: none; font-weight: 800; padding: 14px 32px; border-radius: 8px; font-size: 14px; text-transform: uppercase; letter-spacing: 0.5px; margin-top: 15px; box-shadow: 0 4px 12px rgba(212,175,55,0.4); }
                .btn-container { text-align: center; margin: 25px 0 15px; }
                .info-recibo { background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 8px; padding: 16px; margin-top: 20px; font-size: 13px; color: #475569; }
                .email-footer { background: #f8fafc; padding: 20px; text-align: center; font-size: 12px; color: #64748b; border-top: 1px solid #e2e8f0; }
            </style>
        </head>
        <body>
            <div class='email-container'>
                <div class='email-header'>
                    <h2>INSTITUTO LA SALLE</h2>
                    <p>Bienvenido a la Comunidad Educativa Lasallista • Ciénaga Magdalena</p>
                </div>
                <div class='email-body'>
                    <div class='badge-success-pill'>🎓 MATRÍCULA OFICIALIZADA & ESTUDIANTE ACTIVO</div>
                    <p style='font-size: 15px;'>Estimado(a) <strong>" . htmlspecialchars($destinatarioNombre) . "</strong>,</p>
                    <p style='font-size: 14px; line-height: 1.6; color: #334155;'>
                        ¡Es un honor darle la más cordial bienvenida a nuestra familia Lasallista! Se ha confirmado satisfactoriamente en Tesorería el pago de matrícula para el estudiante <strong>" . htmlspecialchars($estudianteNombre) . "</strong> en el curso <strong>" . htmlspecialchars($gradoNombre) . "</strong> para el año lectivo 2026.
                    </p>

                    <div class='creds-box'>
                        <h4 style='margin: 0 0 16px 0; color: #FCD34D; font-size: 16px;'>🔐 Credenciales de Acceso al Aula Virtual</h4>
                        <div class='creds-row'>
                            <span class='creds-label'>Código Alumno:</span>
                            <span class='creds-val'>" . htmlspecialchars($codigoMatricula) . "</span>
                        </div>
                        <div class='creds-row'>
                            <span class='creds-label'>Usuario / Correo:</span>
                            <span class='creds-val'>" . htmlspecialchars($usuarioCorreo) . "</span>
                        </div>
                        <div class='creds-row'>
                            <span class='creds-label'>Clave Temporal:</span>
                            <span class='creds-val'>" . htmlspecialchars($passwordTemporal) . "</span>
                        </div>
                        <small style='display: block; margin-top: 12px; color: #E2E8F0; font-size: 12px;'>
                            * Por motivos de seguridad, el sistema le solicitará cambiar su contraseña en su primer inicio de sesión.
                        </small>
                    </div>

                    <div class='btn-container'>
                        <a href='{$loginUrl}' class='btn-login' target='_blank'>
                            🚀 Ingresar al Aula Virtual La Salle
                        </a>
                    </div>

                    <div class='info-recibo'>
                        <strong>📄 Comprobante de Caja:</strong> Recibo oficial N° <strong>" . htmlspecialchars($reciboNumero) . "</strong> generado en Tesorería.<br>
                        Ya puede consultar notas, observador, horario, material de clase y calendario escolar desde cualquier dispositivo.
                    </div>
                </div>
                <div class='email-footer'>
                    <strong>Instituto La Salle Ciénaga Magdalena</strong><br>
                    \"Dios, Ciencia y Virtud\" • Avenida La Salle, Ciénaga – Magdalena<br>
                    Este es un correo institucional oficial emitido automáticamente por AulaVirtual Salle.
                </div>
            </div>
        </body>
        </html>
        ";

        return self::despacharCorreo(
            $destinatarioCorreo,
            $destinatarioNombre,
            'general',
            $asunto,
            $cuerpoHtml,
            $referenciaId
        );
    }

    /**
     * Notificación general de citación a acudientes (convocada por Docente o Coordinador)
     */
    public static function notificarCitacionAcudiente(array $estudiante, array $datosCita): array {
        $acudienteCorreo = $estudiante['acudiente_correo'] ?? '';
        $acudienteNombre = $estudiante['acudiente_nombre'] ?? 'Padre de Familia / Acudiente';
        $estudianteNombre = trim(($estudiante['primer_nombre'] ?? '') . ' ' . ($estudiante['primer_apellido'] ?? ''));
        $curso = "Grado " . ($estudiante['grado'] ?? '') . ' ' . ($estudiante['grupo'] ?? '');

        if (empty($acudienteCorreo)) {
            return ['status' => 'error', 'message' => 'El estudiante no tiene correo de acudiente registrado'];
        }

        $fechaCita = $datosCita['fecha_citacion'] ?? date('Y-m-d H:i');
        $ts = strtotime($fechaCita);
        $fechaFmt = $ts ? date('d/m/Y h:i A', $ts) : $fechaCita;
        $modalidad = $datosCita['modalidad'] ?? 'Presencial';
        $motivo = $datosCita['motivo'] ?? 'Asuntos formativos y académicos';
        $profesional = $datosCita['profesional'] ?? 'Docente / Coordinación';

        $asunto = "CITACIÓN OFICIAL — Instituto La Salle: Acudiente de {$estudianteNombre}";

        $cuerpoHtml = "
        <!DOCTYPE html>
        <html lang='es'>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: 'Segoe UI', Arial, sans-serif; background-color: #f1f5f9; margin: 0; padding: 20px; color: #1e293b; }
                .email-container { max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.08); border: 1px solid #cbd5e1; }
                .email-header { background: linear-gradient(135deg, #0B3C68 0%, #1D568A 100%); color: #ffffff; padding: 25px 20px; text-align: center; }
                .email-header h2 { margin: 0; font-size: 20px; letter-spacing: 0.5px; }
                .email-body { padding: 28px 24px; font-size: 14px; line-height: 1.6; }
                .data-card { background: #F8FAFC; border-left: 4px solid #0B3C68; padding: 16px; margin: 18px 0; border-radius: 4px; }
                .data-row { margin-bottom: 8px; }
                .data-label { font-weight: bold; color: #475569; width: 130px; display: inline-block; }
                .motivo-box { background: #EFF6FF; border: 1px solid #BFDBFE; padding: 14px; border-radius: 6px; color: #1E3A8A; margin-top: 15px; }
                .email-footer { background: #f8fafc; padding: 16px; text-align: center; font-size: 11px; color: #64748b; border-top: 1px solid #e2e8f0; }
            </style>
        </head>
        <body>
            <div class='email-container'>
                <div class='email-header'>
                    <h2>INSTITUTO LA SALLE</h2>
                    <p style='margin: 4px 0 0 0; font-size: 13px; opacity: 0.9;'>Gestión Académica y Convivencia Escolar • Ciénaga Magdalena</p>
                </div>
                <div class='email-body'>
                    <p>Estimado(a) Sr.(a) <strong>" . htmlspecialchars($acudienteNombre) . "</strong>,</p>
                    <p>Reciba un cordial saludo de la Comunidad Educativa Lasallista. Por medio de la presente, se le convoca de manera formal a una citación institucional:</p>
                    
                    <div class='data-card'>
                        <div class='data-row'><span class='data-label'>Estudiante:</span> <strong>" . htmlspecialchars($estudianteNombre) . "</strong> ({$curso})</div>
                        <div class='data-row'><span class='data-label'>Fecha y Hora:</span> <strong style='color:#0B3C68;'>{$fechaFmt}</strong></div>
                        <div class='data-row'><span class='data-label'>Modalidad:</span> <strong>" . htmlspecialchars($modalidad) . "</strong></div>
                        <div class='data-row'><span class='data-label'>Convocante:</span> <strong>" . htmlspecialchars($profesional) . "</strong></div>
                    </div>

                    <p><strong>Motivo / Asunto de la Citación:</strong></p>
                    <div class='motivo-box'>
                        " . nl2br(htmlspecialchars($motivo)) . "
                    </div>

                    <p style='margin-top: 20px; font-size: 13px; color: #64748B;'>
                        Agradecemos su puntual asistencia y compromiso con el proceso formativo de su acudido(a).
                    </p>
                </div>
                <div class='email-footer'>
                    Instituto La Salle • Ciénaga Magdalena • Notificación Oficial Generada por AulaVirtual Salle
                </div>
            </div>
        </body>
        </html>
        ";

        return self::despacharCorreo($acudienteCorreo, $acudienteNombre, 'citacion_acudiente', $asunto, $cuerpoHtml);
    }

    /**
     * Notificación oficial de Acta de Convivencia Escolar al Acudiente
     */
    public static function notificarActaConvivencia(array $estudiante, array $datosActa): array {
        $acudienteCorreo = $estudiante['acudiente_correo'] ?? '';
        $acudienteNombre = $estudiante['acudiente_nombre'] ?? 'Padre de Familia / Acudiente';
        $estudianteNombre = trim(($estudiante['primer_nombre'] ?? '') . ' ' . ($estudiante['primer_apellido'] ?? ''));
        $curso = "Grado " . ($estudiante['grado'] ?? '') . ' ' . ($estudiante['grupo'] ?? '');

        if (empty($acudienteCorreo)) {
            return ['status' => 'error', 'message' => 'El estudiante no tiene correo de acudiente registrado'];
        }

        $numeroActa = $datosActa['numero_acta'] ?? ('ACTA-' . date('Ymd'));
        $tipoActa = $datosActa['tipo_acta'] ?? 'Tipo I';
        $fechaActa = !empty($datosActa['fecha_acta']) ? date('d/m/Y', strtotime($datosActa['fecha_acta'])) : date('d/m/Y');
        $faltas = $datosActa['faltas'] ?? [];
        $descargos = $datosActa['descargos'] ?? '';
        $compromisos = $datosActa['compromisos'] ?? '';
        $convocante = $datosActa['convocante'] ?? 'Coordinación de Convivencia';

        $asunto = "NOTIFICACIÓN OFICIAL: Acta de Convivencia Escolar ({$tipoActa}) — Estudiante {$estudianteNombre}";

        $tipoColor = ($tipoActa === 'Tipo III') ? '#DC2626' : (($tipoActa === 'Tipo II') ? '#D97706' : '#2563EB');

        $faltasHtml = '<ul style="margin: 0; padding-left: 20px; line-height: 1.6;">';
        if (is_array($faltas) && !empty($faltas)) {
            foreach ($faltas as $f) {
                $faltasHtml .= "<li>" . htmlspecialchars($f) . "</li>";
            }
        } else {
            $faltasHtml .= "<li>Situación descrita en el manual de convivencia escolar.</li>";
        }
        $faltasHtml .= '</ul>';

        $cuerpoHtml = "
        <!DOCTYPE html>
        <html lang='es'>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: 'Segoe UI', Arial, sans-serif; background-color: #f1f5f9; margin: 0; padding: 20px; color: #1e293b; }
                .email-container { max-width: 620px; margin: 0 auto; background: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.08); border: 1px solid #cbd5e1; }
                .email-header { background: linear-gradient(135deg, #0B3C68 0%, #1D568A 100%); color: #ffffff; padding: 25px 20px; text-align: center; }
                .email-body { padding: 28px 24px; font-size: 14px; line-height: 1.6; }
                .acta-badge { display: inline-block; background: {$tipoColor}; color: #ffffff; padding: 4px 12px; border-radius: 20px; font-weight: bold; font-size: 12px; margin-bottom: 15px; }
                .data-card { background: #F8FAFC; border-left: 4px solid {$tipoColor}; padding: 16px; margin: 16px 0; border-radius: 4px; }
                .data-row { margin-bottom: 8px; }
                .data-label { font-weight: bold; color: #475569; width: 140px; display: inline-block; }
                .box-compromisos { background: #F0FDF4; border: 1px solid #BBF7D0; padding: 12px; border-radius: 6px; color: #166534; margin: 10px 0; }
                .box-descargos { background: #F1F5F9; padding: 12px; border-radius: 6px; font-style: italic; color: #334155; margin: 10px 0; }
                .email-footer { background: #f8fafc; padding: 16px; text-align: center; font-size: 11px; color: #64748b; border-top: 1px solid #e2e8f0; }
            </style>
        </head>
        <body>
            <div class='email-container'>
                <div class='email-header'>
                    <h2 style='margin: 0; font-size: 20px;'>INSTITUTO LA SALLE</h2>
                    <p style='margin: 4px 0 0 0; font-size: 13px; opacity: 0.9;'>Comité de Convivencia Escolar • Ley 1620 de 2013</p>
                </div>
                <div class='email-body'>
                    <div class='acta-badge'>ACTA DE CONVIVENCIA N° {$numeroActa} — {$tipoActa}</div>
                    <p>Estimado(a) Acudiente <strong>" . htmlspecialchars($acudienteNombre) . "</strong>,</p>
                    <p>Le notificamos que en el seguimiento formativo del estudiante se ha formalizado la siguiente acta de convivencia escolar:</p>
                    
                    <div class='data-card'>
                        <div class='data-row'><span class='data-label'>Estudiante:</span> <strong>" . htmlspecialchars($estudianteNombre) . "</strong> ({$curso})</div>
                        <div class='data-row'><span class='data-label'>Fecha del Acta:</span> <strong>{$fechaActa}</strong></div>
                        <div class='data-row'><span class='data-label'>Diligenciado por:</span> <strong>" . htmlspecialchars($convocante) . "</strong></div>
                    </div>

                    <p><strong>Falta(s) o Situación(es) Registrada(s):</strong></p>
                    <div style='background: #FFFBEB; border: 1px solid #FDE68A; padding: 12px; border-radius: 6px;'>
                        {$faltasHtml}
                    </div>

                    " . ($descargos ? "<p style='margin-top: 14px;'><strong>Versión / Descargos del Estudiante:</strong></p><div class='box-descargos'>" . nl2br(htmlspecialchars($descargos)) . "</div>" : "") . "

                    " . ($compromisos ? "<p style='margin-top: 14px;'><strong>Acciones Pedagógicas y Compromisos Acordados:</strong></p><div class='box-compromisos'>" . nl2br(htmlspecialchars($compromisos)) . "</div>" : "") . "

                    <p style='margin-top: 18px; font-size: 13px; color: #64748B;'>
                        Este documento reposa en el observador escolar del estudiante. Si requiere dialogar al respecto, puede contactar a Coordinación de Convivencia.
                    </p>
                </div>
                <div class='email-footer'>
                    Instituto La Salle Ciénaga Magdalena • Notificación Oficial del Sistema de Convivencia Escolar
                </div>
            </div>
        </body>
        </html>
        ";

        return self::despacharCorreo($acudienteCorreo, $acudienteNombre, 'acta_convivencia', $asunto, $cuerpoHtml);
    }

    /**
     * Envío de correo genérico
     */
    public static function enviarCorreoGenerico(string $toEmail, string $subject, string $htmlBody, string $toName = 'Acudiente'): array {
        return self::despacharCorreo($toEmail, $toName, 'general', $subject, $htmlBody);
    }

    /**
     * Envío directo por socket SMTP con soporte STARTTLS (ej: smtp.gmail.com:587)
     */
    public static function enviarPorSMTP(
        string $toEmail,
        string $toName,
        string $subject,
        string $htmlBody,
        string $fromEmail,
        string $fromName,
        string $smtpHost = 'smtp.gmail.com',
        int $smtpPort = 587,
        ?string $smtpUser = null,
        ?string $smtpPass = null
    ): array {
        $cfgFile = __DIR__ . '/../../config/smtp_config.json';
        if (file_exists($cfgFile)) {
            $cfg = @json_decode(file_get_contents($cfgFile), true) ?: [];
            $smtpHost = $cfg['smtp_host'] ?? $smtpHost;
            $smtpPort = intval($cfg['smtp_port'] ?? $smtpPort);
            $smtpUser = $cfg['smtp_user'] ?? $smtpUser;
            $smtpPass = $cfg['smtp_pass'] ?? $smtpPass;
        }

        $smtpUser = $smtpUser ?: (defined('SMTP_USER') ? SMTP_USER : $fromEmail);
        $smtpPass = $smtpPass ?: (defined('SMTP_PASS') ? SMTP_PASS : '');

        if (empty($smtpPass)) {
            return [
                'success' => false,
                'error' => 'No se ha configurado la Contraseña de Aplicación de Google para ' . $smtpUser
            ];
        }

        $socket = @stream_socket_client("tcp://{$smtpHost}:{$smtpPort}", $errno, $errstr, 4);
        if (!$socket) {
            // Intentar puerto SSL 465 si falló 587
            $socket = @stream_socket_client("ssl://{$smtpHost}:465", $errno, $errstr, 4);
            if (!$socket) {
                return ['success' => false, 'error' => "No se pudo conectar a {$smtpHost}:{$smtpPort} ni 465: $errstr ($errno). Posible bloqueo de puertos SMTP en servidor de hosting."];
            }
        }

        $getResponse = function($sock) {
            $data = '';
            while ($str = fgets($sock, 515)) {
                $data .= $str;
                if (substr($str, 3, 1) === ' ') break;
            }
            return $data;
        };

        $getResponse($socket); // 220 banner
        fputs($socket, "EHLO localhost\r\n");
        $getResponse($socket);

        // STARTTLS solo si es puerto 587 o no SSL directo
        if ($smtpPort != 465) {
            fputs($socket, "STARTTLS\r\n");
            $starttlsResp = $getResponse($socket);
            if (strpos($starttlsResp, '220') !== false) {
                stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
                fputs($socket, "EHLO localhost\r\n");
                $getResponse($socket);
            }
        }

        // AUTH LOGIN
        fputs($socket, "AUTH LOGIN\r\n");
        $authResp = $getResponse($socket);
        if (strpos($authResp, '334') === false) {
            fclose($socket);
            return ['success' => false, 'error' => 'AUTH LOGIN no aceptado: ' . trim($authResp)];
        }

        fputs($socket, base64_encode($smtpUser) . "\r\n");
        $getResponse($socket);
        fputs($socket, base64_encode(str_replace(' ', '', $smtpPass)) . "\r\n");
        $passResp = $getResponse($socket);
        if (strpos($passResp, '235') === false) {
            fclose($socket);
            return ['success' => false, 'error' => 'Error de autenticación SMTP con Google: ' . trim($passResp)];
        }

        // MAIL FROM
        fputs($socket, "MAIL FROM:<{$fromEmail}>\r\n");
        $getResponse($socket);

        // RCPT TO
        fputs($socket, "RCPT TO:<{$toEmail}>\r\n");
        $rcptResp = $getResponse($socket);
        if (strpos($rcptResp, '250') === false && strpos($rcptResp, '251') === false) {
            fclose($socket);
            return ['success' => false, 'error' => 'Destinatario rechazado: ' . trim($rcptResp)];
        }

        // DATA
        fputs($socket, "DATA\r\n");
        $getResponse($socket);

        $headers = [
            "MIME-Version: 1.0",
            "Content-Type: text/html; charset=UTF-8",
            "From: {$fromName} <{$fromEmail}>",
            "To: {$toName} <{$toEmail}>",
            "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=",
            "Date: " . date('r'),
            "X-Mailer: AulaVirtualSalle/2026 SMTP Gateway"
        ];
        $msgData = implode("\r\n", $headers) . "\r\n\r\n" . $htmlBody . "\r\n.\r\n";
        fputs($socket, $msgData);
        $dataResp = $getResponse($socket);

        fputs($socket, "QUIT\r\n");
        fclose($socket);

        if (strpos($dataResp, '250') !== false) {
            return ['success' => true, 'mensaje' => 'Correo entregado exitosamente por SMTP a los servidores de correo'];
        } else {
            return ['success' => false, 'error' => 'Error al transmitir datos SMTP: ' . trim($dataResp)];
        }
    }

    /**
     * Envío mediante Web Relay HTTPS por puerto 443 (Garantizado para InfinityFree y hosting en línea)
     */
    public static function enviarPorHttpRelay(
        string $toEmail,
        string $toName,
        string $subject,
        string $htmlBody,
        string $fromEmail,
        string $fromName
    ): array {
        $cfgFile = __DIR__ . '/../../config/smtp_config.json';
        $relayUrl = '';
        if (file_exists($cfgFile)) {
            $cfg = @json_decode(file_get_contents($cfgFile), true) ?: [];
            $relayUrl = trim($cfg['relay_url'] ?? '');
        }

        if (empty($relayUrl)) {
            return [
                'success' => false,
                'error' => 'No hay URL de Relay HTTPS configurada en smtp_config.json'
            ];
        }

        $payload = json_encode([
            'to'        => $toEmail,
            'toName'    => $toName,
            'subject'   => $subject,
            'html'      => $htmlBody,
            'from'      => $fromEmail,
            'fromName'  => $fromName
        ]);

        if (function_exists('curl_init')) {
            $ch = curl_init($relayUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            $response = curl_exec($ch);
            $err = curl_error($ch);
            curl_close($ch);

            if ($err) {
                return ['success' => false, 'error' => "Error cURL Relay HTTPS: " . $err];
            }
            $json = @json_decode($response, true);
            if ($json && !empty($json['success'])) {
                return ['success' => true, 'mensaje' => 'Correo entregado exitosamente vía HTTPS Relay 443'];
            }
            return ['success' => false, 'error' => 'Respuesta de Relay HTTPS: ' . substr($response, 0, 150)];
        }

        $opts = [
            'http' => [
                'method'  => 'POST',
                'header'  => "Content-Type: application/json\r\n",
                'content' => $payload,
                'timeout' => 10
            ]
        ];
        $context = stream_context_create($opts);
        $res = @file_get_contents($relayUrl, false, $context);
        if ($res !== false) {
            $json = @json_decode($res, true);
            if ($json && !empty($json['success'])) {
                return ['success' => true, 'mensaje' => 'Correo entregado exitosamente vía HTTPS Relay 443'];
            }
        }

        return ['success' => false, 'error' => 'No se pudo contactar al endpoint de Relay HTTPS'];
    }

    private static function despacharCorreo(
        string $destinatarioCorreo,
        string $destinatarioNombre,
        string $tipo,
        string $asunto,
        string $cuerpoHtml,
        ?int $referenciaId = null
    ): array {
        $estado = 'simulado';
        $metodoEnvio = 'Auditoría Interna';
        $errorMsg = null;

        // Validar dirección de correo
        if (!filter_var($destinatarioCorreo, FILTER_VALIDATE_EMAIL)) {
            return [
                'success' => false,
                'mensaje' => "Dirección de correo inválida: '{$destinatarioCorreo}'."
            ];
        }

        // Configurar cabeceras MIME estándar para correos HTML
        $fromEmail = defined('MAIL_FROM_ADDRESS') ? MAIL_FROM_ADDRESS : 'aulavirtualsalle@gmail.com';
        $fromName  = defined('MAIL_FROM_NAME') ? MAIL_FROM_NAME : 'Instituto La Salle';

        // 1. Verificar si hay un Relay HTTPS configurado (Especialmente para InfinityFree/online)
        $cfgFile = __DIR__ . '/../../config/smtp_config.json';
        $cfg = file_exists($cfgFile) ? (@json_decode(file_get_contents($cfgFile), true) ?: []) : [];
        $hasRelay = !empty($cfg['relay_url']);

        if ($hasRelay) {
            $relayRes = self::enviarPorHttpRelay($destinatarioCorreo, $destinatarioNombre, $asunto, $cuerpoHtml, $fromEmail, $fromName);
            if ($relayRes['success']) {
                $estado = 'enviado';
                $metodoEnvio = 'HTTPS Web Relay (Puerto 443)';
            } else {
                $errorMsg = $relayRes['error'];
            }
        }

        // 2. Si no hay relay o falló, intentar por Socket SMTP Directo (Local o cPanel con puertos abiertos)
        if ($estado !== 'enviado') {
            $smtpRes = self::enviarPorSMTP($destinatarioCorreo, $destinatarioNombre, $asunto, $cuerpoHtml, $fromEmail, $fromName);
            if ($smtpRes['success']) {
                $estado = 'enviado';
                $metodoEnvio = 'SMTP Gmail Directo';
            } else {
                $errorMsg = ($errorMsg ? $errorMsg . ' | ' : '') . ($smtpRes['error'] ?? 'Fallo conexión SMTP');
                // 3. Intentar entrega vía mail() nativa de PHP
                $headers  = "MIME-Version: 1.0\r\n";
                $headers .= "Content-type: text/html; charset=UTF-8\r\n";
                $headers .= "From: {$fromName} <{$fromEmail}>\r\n";
                $headers .= "Reply-To: {$fromEmail}\r\n";
                $headers .= "X-Mailer: AulaVirtualSalle/2026 EmailGateway\r\n";
                try {
                    $sent = @mail($destinatarioCorreo, $asunto, $cuerpoHtml, $headers);
                    if ($sent) {
                        $estado = 'enviado';
                        $metodoEnvio = 'Servicio Mail PHP';
                    }
                } catch (\Throwable $e) {}
            }
        }

        // Registrar siempre en tabla de auditoría notificaciones_correo
        try {
            $pdo = Database::getInstance()->getConnection();
            if ($pdo) {
                $stmt = $pdo->prepare("
                    INSERT INTO notificaciones_correo 
                    (destinatario_correo, destinatario_nombre, tipo_notificacion, asunto, cuerpo_html, referencia_id, estado_envio)
                    VALUES (:email, :nombre, :tipo, :asunto, :html, :ref_id, :estado)
                ");
                $stmt->execute([
                    ':email'   => $destinatarioCorreo,
                    ':nombre'  => $destinatarioNombre,
                    ':tipo'    => $tipo,
                    ':asunto'  => $asunto,
                    ':html'    => $cuerpoHtml,
                    ':ref_id'  => $referenciaId,
                    ':estado'  => $estado
                ]);
            }
        } catch (\Exception $ex) {
            error_log("Error auditando notificación: " . $ex->getMessage());
        }

        return [
            'success' => ($estado === 'enviado'),
            'estado'  => $estado,
            'metodo'  => $metodoEnvio,
            'error_detalle' => $errorMsg,
            'mensaje' => ($estado === 'enviado') 
                ? "Notificación entregada exitosamente a {$destinatarioCorreo} ({$metodoEnvio})."
                : "Notificación registrada en auditoría ({$metodoEnvio}). Diagnóstico: {$errorMsg}"
        ];
    }
}
