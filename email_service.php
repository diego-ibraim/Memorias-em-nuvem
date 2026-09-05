<?php
// Arquivo: email_service.php

/**
 * >>> MODIFICAÇÃO: Novo template de e-mail que usa a identidade visual do seu site. <<<
 */
function createEmailTemplate($title, $content) {
    // Cores e fontes baseadas no seu site
    $corDeFundo = '#f7faff'; // Um azul bem claro, similar ao seu gradiente
    $corDoCard = '#ffffff';
    $corDoHeader = '#A0522D'; // A cor principal de títulos do seu site
    $corDoTexto = '#333333';
    $corDoTitulo = '#A0522D';
    $fontePrincipal = 'Arial, Helvetica, sans-serif';

    $logoUrl = SITE_URL . '/img/1.png'; 

    // HTML do e-mail com estilos inline para máxima compatibilidade
    $html = '
    <!DOCTYPE html>
    <html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>' . htmlspecialchars($title) . '</title>
    </head>
    <body style="margin: 0; padding: 0; background-color: ' . $corDeFundo . '; font-family: ' . $fontePrincipal . ';">
        <table width="100%" border="0" cellspacing="0" cellpadding="0" style="background-color: ' . $corDeFundo . ';">
            <tr>
                <td align="center" style="padding: 20px;">
                    <table width="100%" border="0" cellspacing="0" cellpadding="0" style="max-width: 600px; border-radius: 15px; overflow: hidden; box-shadow: 0 8px 32px 0 rgba(149, 173, 194, 0.2); border: 1px solid #e0e0e0;">
                        <tr>
                            <td align="center" style="background-color: ' . $corDoHeader . '; padding: 25px;">
                                <img src="' . $logoUrl . '" alt="' . NOME_FROM . '" style="max-width: 150px; height: auto; display: block;">
                            </td>
                        </tr>
                        <tr>
                            <td align="left" style="background-color: ' . $corDoCard . '; padding: 30px 35px;">
                                <h1 style="color: ' . $corDoTitulo . '; font-size: 24px; margin-top: 0; margin-bottom: 20px;">' . htmlspecialchars($title) . '</h1>
                                <div style="color: ' . $corDoTexto . '; font-size: 16px; line-height: 1.6;">
                                ' . $content . '
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td align="center" style="background-color: #f1f1f1; padding: 20px; font-size: 12px; color: #888;">
                                <p style="margin: 0;">&copy; ' . date('Y') . ' ' . NOME_FROM . '. Todos os direitos reservados.</p>
                                <p style="margin: 5px 0 0 0;">Este é um e-mail automático, por favor, não responda.</p>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </body>
    </html>';

    // Aplica o estilo do botão do seu site a qualquer link com a classe 'button'
    $buttonStyle = 'style="display: inline-block; background-color: ' . $corDoHeader . '; color: #ffffff; padding: 12px 25px; text-decoration: none; border-radius: 10px; font-weight: bold; font-size: 16px;"';
    $html = str_replace('class="button"', $buttonStyle, $html);

    return $html;
}


// O restante do arquivo (funções de envio com fallback) permanece o mesmo.

function _sendViaSendGrid($toEmail, $toName, $subject, $htmlContent) {
    if (!defined('SENDGRID_API_KEY') || empty(SENDGRID_API_KEY)) { throw new Exception("Chave de API do SendGrid não definida."); }
    $emailData = ['personalizations' => [['to' => [['email' => $toEmail, 'name' => $toName]], 'subject' => $subject]], 'from' => ['email' => EMAIL_FROM_SENDGRID, 'name' => NOME_FROM], 'content' => [['type' => 'text/html', 'value' => $htmlContent]]];
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://api.sendgrid.com/v3/mail/send");
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($emailData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . SENDGRID_API_KEY, 'Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($httpcode != 202) { throw new Exception("Falha no SendGrid. HTTP Code: $httpcode. Response: $response"); }
    return true;
}

function _sendViaBrevo($toEmail, $toName, $subject, $htmlContent) {
    if (!defined('BREVO_API_KEY') || empty(BREVO_API_KEY)) { throw new Exception("Chave de API do Brevo não definida."); }
    $emailData = ['sender' => ['name' => NOME_FROM, 'email' => EMAIL_FROM_BREVO], 'to' => [['email' => $toEmail, 'name' => $toName]], 'subject' => $subject, 'htmlContent' => $htmlContent];
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://api.brevo.com/v3/smtp/email");
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($emailData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['api-key: ' . BREVO_API_KEY, 'Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($httpcode != 201) { throw new Exception("Falha no Brevo. HTTP Code: $httpcode. Response: $response"); }
    return true;
}

function sendEmail($toEmail, $toName, $subject, $htmlContent) {
    try {
        error_log("Tentando enviar e-mail via SendGrid para $toEmail...");
        if (_sendViaSendGrid($toEmail, $toName, $subject, $htmlContent)) {
            error_log("E-mail para '$toEmail' aceito pela SendGrid.");
            return true;
        }
    } catch (Exception $e) {
        error_log("FALHA no SendGrid: " . $e->getMessage());
        error_log("Acionando fallback para o Brevo...");
        try {
            if (_sendViaBrevo($toEmail, $toName, $subject, $htmlContent)) {
                error_log("E-mail para '$toEmail' enviado com SUCESSO via Brevo (backup).");
                return true;
            }
        } catch (Exception $e2) {
            error_log("FALHA GERAL: O backup (Brevo) também falhou: " . $e2->getMessage());
            return false;
        }
    }
    return false;
}
?>