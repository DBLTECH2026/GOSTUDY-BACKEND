<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Trilce — Datos de pago de matrícula</title>
</head>
<body style="margin:0;padding:0;background-color:#FFF8F2;font-family:'Inter','Helvetica Neue',Arial,sans-serif;color:#1F2A44;">

  <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background-color:#FFF8F2;padding:32px 16px;">
    <tr>
      <td align="center">

        <!-- Contenedor principal -->
        <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="600" style="max-width:600px;width:100%;background-color:#FFFFFF;border-radius:12px;overflow:hidden;box-shadow:0 4px 16px rgba(31,42,68,0.08);">

          <!-- HEADER con logo -->
          <tr>
            <td style="background:linear-gradient(135deg,#F26C21 0%,#D85912 100%);padding:32px 40px;text-align:left;">
              <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                <tr>
                  <td style="vertical-align:middle;">
                    <!-- Logo en círculo blanco -->
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

          <!-- BANNER -->
          <tr>
            <td style="background-color:#FFF1E5;padding:14px 40px;border-bottom:1px solid #FFE7D6;">
              <span style="display:inline-block;background-color:#F26C21;color:#FFFFFF;font-size:10px;font-weight:800;letter-spacing:2px;padding:4px 10px;border-radius:4px;text-transform:uppercase;">Inscripción 2026</span>
              <span style="color:#6B7280;font-size:12px;margin-left:10px;">Datos de pago de matrícula</span>
            </td>
          </tr>

          <!-- CUERPO -->
          <tr>
            <td style="padding:36px 40px 24px 40px;">
              <h1 style="margin:0 0 8px 0;font-size:24px;font-weight:800;color:#1F2A44;">¡Hola! 👋</h1>
              <p style="margin:0 0 24px 0;font-size:15px;line-height:1.6;color:#4B5563;">
                Recibimos la solicitud de inscripción para el alumno:
                <br><strong style="color:#1F2A44;font-size:16px;">{{ $alumno }}</strong>
              </p>

              <p style="margin:0 0 20px 0;font-size:14px;line-height:1.6;color:#4B5563;">
                Para completar el proceso de matrícula, realiza el pago utilizando cualquiera de los siguientes medios:
              </p>

              <!-- Card del monto -->
              <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background:linear-gradient(135deg,#FFF1E5 0%,#FFE7D6 100%);border:2px solid #F26C21;border-radius:12px;margin-bottom:24px;">
                <tr>
                  <td style="padding:24px;text-align:center;">
                    <div style="font-size:11px;font-weight:700;letter-spacing:2px;color:#D85912;text-transform:uppercase;margin-bottom:6px;">Monto a pagar</div>
                    <div style="font-size:38px;font-weight:900;color:#1F2A44;letter-spacing:-1px;line-height:1;">S/ {{ $monto }}</div>
                    <div style="font-size:12px;color:#6B7280;margin-top:6px;">Matrícula año escolar 2026</div>
                  </td>
                </tr>
              </table>

              <!-- Métodos de pago -->
              <h2 style="margin:0 0 14px 0;font-size:13px;font-weight:800;letter-spacing:2px;color:#9CA3AF;text-transform:uppercase;">Medios de pago</h2>

              <!-- Banco -->
              <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background-color:#FFFFFF;border:1px solid #E8E2D9;border-radius:8px;margin-bottom:10px;">
                <tr>
                  <td style="padding:16px 20px;">
                    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
                      <tr>
                        <td style="vertical-align:middle;width:44px;">
                          <div style="width:36px;height:36px;background-color:#FFF1E5;border-radius:8px;text-align:center;line-height:36px;color:#F26C21;font-weight:900;font-size:14px;">🏦</div>
                        </td>
                        <td style="vertical-align:middle;padding-left:14px;">
                          <div style="font-size:11px;color:#9CA3AF;font-weight:700;letter-spacing:1px;text-transform:uppercase;">Transferencia BCP</div>
                          <div style="font-size:15px;font-weight:700;color:#1F2A44;margin-top:2px;font-family:'Courier New',monospace;">191-2345678-0-21</div>
                          <div style="font-size:11px;color:#6B7280;margin-top:2px;">Cuenta Corriente Soles · Titular: Trilce SA</div>
                        </td>
                      </tr>
                    </table>
                  </td>
                </tr>
              </table>

              <!-- Yape / Plin -->
              <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background-color:#FFFFFF;border:1px solid #E8E2D9;border-radius:8px;margin-bottom:24px;">
                <tr>
                  <td style="padding:16px 20px;">
                    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
                      <tr>
                        <td style="vertical-align:middle;width:44px;">
                          <div style="width:36px;height:36px;background-color:#FFF1E5;border-radius:8px;text-align:center;line-height:36px;color:#F26C21;font-weight:900;font-size:14px;">📱</div>
                        </td>
                        <td style="vertical-align:middle;padding-left:14px;">
                          <div style="font-size:11px;color:#9CA3AF;font-weight:700;letter-spacing:1px;text-transform:uppercase;">Yape / Plin</div>
                          <div style="font-size:15px;font-weight:700;color:#1F2A44;margin-top:2px;font-family:'Courier New',monospace;">999 444 777</div>
                          <div style="font-size:11px;color:#6B7280;margin-top:2px;">Titular: Trilce SA</div>
                        </td>
                      </tr>
                    </table>
                  </td>
                </tr>
              </table>

              @if($codigo)
              <!-- Código de inscripción -->
              <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background-color:#1F2A44;border-radius:8px;margin-bottom:24px;">
                <tr>
                  <td style="padding:16px 20px;text-align:center;">
                    <div style="font-size:10px;color:rgba(255,255,255,0.6);font-weight:700;letter-spacing:2px;text-transform:uppercase;">Código de inscripción</div>
                    <div style="font-size:18px;color:#FFFFFF;font-weight:800;margin-top:4px;font-family:'Courier New',monospace;letter-spacing:2px;">{{ $codigo }}</div>
                  </td>
                </tr>
              </table>
              @endif

              <!-- CTA -->
              <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background-color:#FFF8F2;border-left:4px solid #F26C21;border-radius:0 8px 8px 0;margin-bottom:8px;">
                <tr>
                  <td style="padding:16px 20px;">
                    <div style="font-size:13px;font-weight:700;color:#1F2A44;margin-bottom:4px;">📎 Siguiente paso</div>
                    <div style="font-size:13px;line-height:1.5;color:#4B5563;">
                      Una vez realizado el pago, sube el voucher en el formulario de inscripción para que validemos tu matrícula.
                    </div>
                  </td>
                </tr>
              </table>
            </td>
          </tr>

          <!-- FOOTER -->
          <tr>
            <td style="background-color:#1F2A44;padding:24px 40px;text-align:center;">
              <div style="color:#FFFFFF;font-size:13px;font-weight:700;margin-bottom:6px;">Colegio Trilce</div>
              <div style="color:rgba(255,255,255,0.6);font-size:11px;line-height:1.6;">
                Av. Trilce 1234, Lima · informes@trilce.edu.pe · (01) 555-0123<br>
                Este correo fue generado automáticamente por <strong style="color:#F26C21;">GOSTUDY</strong> — Sistema de matrícula digital
              </div>
            </td>
          </tr>

        </table>

        <!-- Nota legal -->
        <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="600" style="max-width:600px;width:100%;margin-top:16px;">
          <tr>
            <td style="text-align:center;color:#9CA3AF;font-size:11px;line-height:1.5;padding:0 16px;">
              Si no solicitaste esta inscripción, puedes ignorar este mensaje.<br>
              © {{ date('Y') }} Colegio Trilce · Todos los derechos reservados.
            </td>
          </tr>
        </table>

      </td>
    </tr>
  </table>

</body>
</html>
