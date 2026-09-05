<?php
// Arquivo: enviar_lembretes.php

date_default_timezone_set('America/Sao_Paulo');
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/email_service.php';

error_log("------ INICIANDO CRON JOB 'enviar_lembretes.php' EM " . date('Y-m-d H:i:s') . " ------");

try {
    $intervals = [
        1440 => '24h', 720 => '12h', 360 => '6h', 120 => '2h',
        60 => '1h', 30 => '30min', 10 => '10min', 5 => '5min'
    ];

    $sql = "SELECT l.lembrete_id, l.texto, l.data, l.notificacoes_enviadas, u.email, u.usuario
            FROM lembretes l
            JOIN usuarios u ON l.usuario_id = u.user_id
            WHERE l.is_completed = 0";

    $stmt = $pdo->query($sql);
    $reminders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $now = time();

    if (empty($reminders)) {
        error_log("Nenhum lembrete pendente encontrado. Finalizando script.");
        exit;
    }

    error_log(count($reminders) . " lembretes pendentes encontrados para verificação.");

    foreach ($reminders as $reminder) {
        $reminder_time = strtotime($reminder['data']);
        $diff_minutes = round(($reminder_time - $now) / 60);
        $notifications_sent = json_decode($reminder['notificacoes_enviadas'], true) ?: [];
        $needs_update = false;

        if ($diff_minutes >= 0) {
            foreach ($intervals as $interval_minutes => $interval_name) {
                if ($diff_minutes <= $interval_minutes && !isset($notifications_sent[$interval_name])) {
                    $subject = "⏰ Lembrete Próximo: " . htmlspecialchars($reminder['texto']);
                    $content = '<p>Olá, ' . htmlspecialchars($reminder['usuario']) . '!</p>'
                             . '<p>Este é um lembrete amigável sobre sua tarefa que está se aproximando:</p>'
                             . '<p style="font-size: 18px; font-weight: bold; color: #A0522D;">"' . htmlspecialchars($reminder['texto']) . '"</p>'
                             . '<p>Data Limite: <strong>' . date('d/m/Y \à\s H:i', $reminder_time) . '</strong></p>'
                             . '<p>Não se esqueça!</p>';

                    $fullHtmlEmail = createEmailTemplate('Lembrete Próximo', $content);
                    if (sendEmail($reminder['email'], $reminder['usuario'], $subject, $fullHtmlEmail)) {
                        $notifications_sent[$interval_name] = date('Y-m-d H:i:s');
                        $needs_update = true;
                        error_log(">> E-mail de {$interval_name} enviado para o lembrete #{$reminder['lembrete_id']}");
                    }
                }
            }
        }
        else {
            if (!isset($notifications_sent['expired'])) {
                $subject = "⚠️ Lembrete Expirado: " . htmlspecialchars($reminder['texto']);
                $content = '<p>Olá, ' . htmlspecialchars($reminder['usuario']) . '.</p>'
                         . '<p>Parece que o prazo para a sua tarefa expirou:</p>'
                         . '<p style="font-size: 18px; font-weight: bold; color: #e57373;">"' . htmlspecialchars($reminder['texto']) . '"</p>'
                         . '<p>O prazo era para: <strong>' . date('d/m/Y \à\s H:i', $reminder_time) . '</strong>.</p>'
                         . '<p>Por favor, acesse o sistema para marcar a tarefa como concluída ou para excluí-la.</p>';

                $fullHtmlEmail = createEmailTemplate('Lembrete Expirado', $content);
                if (sendEmail($reminder['email'], $reminder['usuario'], $subject, $fullHtmlEmail)) {
                    $notifications_sent['expired'] = date('Y-m-d H:i:s');
                    $needs_update = true;
                    error_log(">> E-mail de EXPIRADO enviado para o lembrete #{$reminder['lembrete_id']}");
                }
            }
        }

        if ($needs_update) {
            $updateSql = "UPDATE lembretes SET notificacoes_enviadas = ? WHERE lembrete_id = ?";
            $updateStmt = $pdo->prepare($updateSql);
            $updateStmt->execute([json_encode($notifications_sent), $reminder['lembrete_id']]);
        }
    }
    error_log("------ FIM DO CRON JOB 'enviar_lembretes.php' ------");
} catch (Exception $e) {
    error_log("!!!!!! ERRO FATAL no script de lembretes: " . $e->getMessage() . " !!!!!!");
    die("Ocorreu um erro durante a execução.");
}
?>