<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Trilce — Inscripción aprobada</title>
</head>
<body style="margin:0;padding:0;background-color:#FFF8F2;font-family:'Inter','Helvetica Neue',Arial,sans-serif;color:#1F2A44;">

  <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background-color:#FFF8F2;padding:32px 16px;">
    <tr>
      <td align="center">

        <!-- Contenedor principal -->
        <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="620" style="max-width:620px;width:100%;background-color:#FFFFFF;border-radius:12px;overflow:hidden;box-shadow:0 4px 16px rgba(31,42,68,0.08);">

          <!-- HEADER -->
          <tr>
            <td style="background:linear-gradient(135deg,#F26C21 0%,#D85912 100%);padding:32px 40px;text-align:left;">
              <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                <tr>
                  <td style="vertical-align:middle;">
                    <div style="display:inline-block;width:56px;height:56px;background-color:#FFFFFF;border-radius:50%;text-align:center;line-height:56px;font-size:28px;font-weight:900;color:#F26C21;letter-spacing:-1px;vertical-align:middle;">T</div>
                  </td>
                  <td style="vertical-align:middle;padding-left:16px;">
                    <div style="color:#FFFFFF;font-size:22px;font-weight:800;letter-spacing:1px;line-height:1;">TRILCE</div>
                    <div style="color:rgba(255,255,255,0.85);font-size:12px;font-weight:600;letter-spacing:3px;margin-top:4px;text-transform:uppercase;">Colegio · GOSTUDY</div>
                  </td>
                </tr>
              </table>
            </td>
          </tr>

          <!-- BANNER éxito -->
          <tr>
            <td style="background:linear-gradient(135deg,#10B981 0%,#059669 100%);padding:24px 40px;text-align:center;">
              <div style="font-size:36px;line-height:1;margin-bottom:8px;">🎉</div>
              <div style="color:#FFFFFF;font-size:22px;font-weight:800;letter-spacing:0.5px;">¡Inscripción aprobada!</div>
              <div style="color:rgba(255,255,255,0.9);font-size:13px;margin-top:6px;">Bienvenido a la familia Trilce</div>
            </td>
          </tr>

          <!-- CUERPO -->
          <tr>
            <td style="padding:36px 40px 24px 40px;">
              <p style="margin:0 0 8px 0;font-size:15px;color:#4B5563;">Estimado(a),</p>
              <h1 style="margin:0 0 20px 0;font-size:20px;font-weight:800;color:#1F2A44;">{{ $apoderado_nombres }}</h1>

              <p style="margin:0 0 16px 0;font-size:15px;line-height:1.7;color:#4B5563;">
                Nos complace informarle que la inscripción de su menor hijo(a)
                <strong style="color:#1F2A44;">{{ $alumno_nombres }} {{ $alumno_apellidos }}</strong>
                ha sido <strong style="color:#10B981;">aprobada satisfactoriamente</strong>.
              </p>

              <p style="margin:0 0 24px 0;font-size:14px;line-height:1.7;color:#4B5563;">
                Su pago de matrícula fue verificado y registrado. El estudiante ya forma parte oficial del año escolar
                <strong>{{ $periodo }}</strong> en el Colegio Trilce.
              </p>

              <!-- Card del alumno -->
              <h2 style="margin:0 0 12px 0;font-size:13px;font-weight:800;letter-spacing:2px;color:#9CA3AF;text-transform:uppercase;">📋 Datos de matrícula</h2>

              <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background-color:#FFF1E5;border:2px solid #F26C21;border-radius:12px;margin-bottom:24px;">
                <tr>
                  <td style="padding:20px 24px;">

                    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
                      <tr>
                        <td style="padding:6px 0;border-bottom:1px solid #FFE7D6;">
                          <div style="font-size:10px;font-weight:700;letter-spacing:2px;color:#D85912;text-transform:uppercase;">Estudiante</div>
                          <div style="font-size:16px;font-weight:800;color:#1F2A44;margin-top:2px;">{{ $alumno_nombres }} {{ $alumno_apellidos }}</div>
                        </td>
                      </tr>
                      <tr>
                        <td style="padding:10px 0;border-bottom:1px solid #FFE7D6;">
                          <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
                            <tr>
                              <td width="50%" style="vertical-align:top;">
                                <div style="font-size:10px;font-weight:700;letter-spacing:2px;color:#D85912;text-transform:uppercase;">Nivel</div>
                                <div style="font-size:14px;font-weight:700;color:#1F2A44;margin-top:2px;">{{ $nivel }}</div>
                              </td>
                              <td width="50%" style="vertical-align:top;">
                                <div style="font-size:10px;font-weight:700;letter-spacing:2px;color:#D85912;text-transform:uppercase;">Grado</div>
                                <div style="font-size:14px;font-weight:700;color:#1F2A44;margin-top:2px;">{{ $grado }}</div>
                              </td>
                            </tr>
                          </table>
                        </td>
                      </tr>
                      <tr>
                        <td style="padding:10px 0;border-bottom:1px solid #FFE7D6;">
                          <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
                            <tr>
                              <td width="50%" style="vertical-align:top;">
                                <div style="font-size:10px;font-weight:700;letter-spacing:2px;color:#D85912;text-transform:uppercase;">Sección</div>
                                <div style="font-size:14px;font-weight:700;color:#1F2A44;margin-top:2px;">{{ $seccion }}</div>
                              </td>
                              <td width="50%" style="vertical-align:top;">
                                <div style="font-size:10px;font-weight:700;letter-spacing:2px;color:#D85912;text-transform:uppercase;">Periodo</div>
                                <div style="font-size:14px;font-weight:700;color:#1F2A44;margin-top:2px;">{{ $periodo }}</div>
                              </td>
                            </tr>
                          </table>
                        </td>
                      </tr>
                      <tr>
                        <td style="padding:10px 0;">
                          <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
                            <tr>
                              <td width="50%" style="vertical-align:top;">
                                <div style="font-size:10px;font-weight:700;letter-spacing:2px;color:#D85912;text-transform:uppercase;">Código alumno</div>
                                <div style="font-size:13px;font-weight:700;color:#1F2A44;margin-top:2px;font-family:'Courier New',monospace;">{{ $codigo_estudiante }}</div>
                              </td>
                              <td width="50%" style="vertical-align:top;">
                                <div style="font-size:10px;font-weight:700;letter-spacing:2px;color:#D85912;text-transform:uppercase;">Fecha matrícula</div>
                                <div style="font-size:13px;font-weight:700;color:#1F2A44;margin-top:2px;">{{ $fecha_matricula }}</div>
                              </td>
                            </tr>
                          </table>
                        </td>
                      </tr>
                    </table>

                  </td>
                </tr>
              </table>

              <!-- Acceso al portal -->
              <h2 style="margin:0 0 12px 0;font-size:13px;font-weight:800;letter-spacing:2px;color:#9CA3AF;text-transform:uppercase;">🔐 Acceso al portal del alumno</h2>

              <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background-color:#1F2A44;border-radius:12px;margin-bottom:24px;">
                <tr>
                  <td style="padding:24px;">

                    <p style="margin:0 0 16px 0;color:rgba(255,255,255,0.85);font-size:13px;line-height:1.5;">
                      A partir de ahora, su hijo(a) puede acceder al portal estudiantil de GOSTUDY con sus credenciales:
                    </p>

                    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
                      <tr>
                        <td width="50%" style="padding:8px 12px 8px 0;">
                          <div style="font-size:10px;font-weight:700;letter-spacing:2px;color:rgba(255,255,255,0.5);text-transform:uppercase;">Usuario (DNI)</div>
                          <div style="font-size:16px;font-weight:800;color:#FFFFFF;margin-top:4px;font-family:'Courier New',monospace;">{{ $pin_acceso }}</div>
                        </td>
                        <td width="50%" style="padding:8px 0 8px 12px;border-left:1px solid rgba(255,255,255,0.15);">
                          <div style="font-size:10px;font-weight:700;letter-spacing:2px;color:rgba(255,255,255,0.5);text-transform:uppercase;">PIN</div>
                          <div style="font-size:14px;font-weight:700;color:#F26C21;margin-top:4px;">El que registró en inscripción</div>
                        </td>
                      </tr>
                    </table>

                    <div style="background-color:rgba(242,108,33,0.15);border-left:3px solid #F26C21;padding:10px 14px;margin-top:16px;border-radius:0 6px 6px 0;">
                      <div style="font-size:11px;color:#FFE7D6;line-height:1.5;">
                        ⚠️ Mantenga el PIN en un lugar seguro. Si lo olvida, contáctenos para restablecerlo.
                      </div>
                    </div>

                  </td>
                </tr>
              </table>

              <!-- Información de pagos -->
              <h2 style="margin:0 0 12px 0;font-size:13px;font-weight:800;letter-spacing:2px;color:#9CA3AF;text-transform:uppercase;">💳 Cronograma de pagos</h2>

              <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background-color:#F5F2EE;border-radius:8px;margin-bottom:24px;">
                <tr>
                  <td style="padding:16px 20px;">
                    <p style="margin:0;font-size:13px;line-height:1.6;color:#4B5563;">
                      ✅ <strong style="color:#10B981;">Matrícula:</strong> Pagada y verificada<br>
                      📅 <strong>10 pensiones mensuales</strong> generadas (Marzo - Diciembre {{ $periodo }})<br>
                      📲 Puede subir los comprobantes de pago desde el <strong>portal del alumno</strong> en cada vencimiento.
                    </p>
                  </td>
                </tr>
              </table>

              <!-- Próximos pasos -->
              <h2 style="margin:0 0 12px 0;font-size:13px;font-weight:800;letter-spacing:2px;color:#9CA3AF;text-transform:uppercase;">📌 Próximos pasos</h2>

              <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="margin-bottom:16px;">
                <tr>
                  <td style="padding:0;">
                    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background-color:#FFFFFF;border:1px solid #E8E2D9;border-radius:8px;margin-bottom:8px;">
                      <tr>
                        <td width="40" style="padding:14px 0 14px 16px;vertical-align:top;">
                          <div style="width:28px;height:28px;background-color:#FFF1E5;color:#F26C21;border-radius:50%;text-align:center;line-height:28px;font-weight:900;font-size:13px;">1</div>
                        </td>
                        <td style="padding:14px 16px 14px 8px;">
                          <div style="font-size:13px;font-weight:700;color:#1F2A44;">Reunión de bienvenida</div>
                          <div style="font-size:12px;color:#6B7280;margin-top:2px;line-height:1.5;">Recibirá la convocatoria con fecha y hora durante la próxima semana.</div>
                        </td>
                      </tr>
                    </table>

                    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background-color:#FFFFFF;border:1px solid #E8E2D9;border-radius:8px;margin-bottom:8px;">
                      <tr>
                        <td width="40" style="padding:14px 0 14px 16px;vertical-align:top;">
                          <div style="width:28px;height:28px;background-color:#FFF1E5;color:#F26C21;border-radius:50%;text-align:center;line-height:28px;font-weight:900;font-size:13px;">2</div>
                        </td>
                        <td style="padding:14px 16px 14px 8px;">
                          <div style="font-size:13px;font-weight:700;color:#1F2A44;">Lista de útiles y uniforme</div>
                          <div style="font-size:12px;color:#6B7280;margin-top:2px;line-height:1.5;">Disponible en el portal del alumno antes del inicio de clases.</div>
                        </td>
                      </tr>
                    </table>

                    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background-color:#FFFFFF;border:1px solid #E8E2D9;border-radius:8px;">
                      <tr>
                        <td width="40" style="padding:14px 0 14px 16px;vertical-align:top;">
                          <div style="width:28px;height:28px;background-color:#FFF1E5;color:#F26C21;border-radius:50%;text-align:center;line-height:28px;font-weight:900;font-size:13px;">3</div>
                        </td>
                        <td style="padding:14px 16px 14px 8px;">
                          <div style="font-size:13px;font-weight:700;color:#1F2A44;">Pago de pensiones</div>
                          <div style="font-size:12px;color:#6B7280;margin-top:2px;line-height:1.5;">El primer vencimiento de pensión es en marzo. Recibirá recordatorios automáticos.</div>
                        </td>
                      </tr>
                    </table>
                  </td>
                </tr>
              </table>

              <!-- CTA -->
              <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="margin-top:24px;margin-bottom:8px;">
                <tr>
                  <td align="center">
                    <a href="http://localhost:3000/portal/login" style="display:inline-block;background:linear-gradient(135deg,#F26C21 0%,#D85912 100%);color:#FFFFFF;text-decoration:none;font-weight:800;font-size:14px;padding:14px 32px;border-radius:8px;letter-spacing:0.5px;">
                      Acceder al portal del alumno →
                    </a>
                  </td>
                </tr>
              </table>

              <!-- Código de inscripción referencia -->
              <p style="margin:24px 0 0 0;text-align:center;font-size:11px;color:#9CA3AF;">
                Código de inscripción de referencia: <strong style="color:#6B7280;font-family:'Courier New',monospace;">{{ $codigo_inscripcion }}</strong>
              </p>
            </td>
          </tr>

          <!-- Mensaje institucional -->
          <tr>
            <td style="background-color:#FFF8F2;border-top:1px solid #FFE7D6;padding:20px 40px;">
              <p style="margin:0;font-size:12px;line-height:1.6;color:#6B7280;text-align:center;font-style:italic;">
                "Formamos a los líderes del mañana con disciplina, valores y excelencia académica."
              </p>
            </td>
          </tr>

          <!-- FOOTER -->
          <tr>
            <td style="background-color:#1F2A44;padding:24px 40px;text-align:center;">
              <div style="color:#FFFFFF;font-size:13px;font-weight:700;margin-bottom:6px;">Colegio Trilce</div>
              <div style="color:rgba(255,255,255,0.6);font-size:11px;line-height:1.6;">
                Av. Trilce 1234, Lima · informes@trilce.edu.pe · (01) 555-0123<br>
                Lunes a Viernes 8:00 a.m. - 5:00 p.m.<br>
                <span style="color:#F26C21;">GOSTUDY</span> — Sistema de matrícula digital
              </div>
            </td>
          </tr>

        </table>

        <!-- Nota legal -->
        <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="620" style="max-width:620px;width:100%;margin-top:16px;">
          <tr>
            <td style="text-align:center;color:#9CA3AF;font-size:11px;line-height:1.5;padding:0 16px;">
              Este es un mensaje automático generado por GOSTUDY. Para consultas, contacte directamente a administración.<br>
              © {{ date('Y') }} Colegio Trilce · Todos los derechos reservados.
            </td>
          </tr>
        </table>

      </td>
    </tr>
  </table>

</body>
</html>
