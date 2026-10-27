<?php
declare(strict_types=1);

use PHPMailer\PHPMailer\PHPMailer;

function passwordResetEnv(string $name, ?string $default = null): ?string
{
    $value = getenv($name);
    return $value === false || $value === '' ? $default : $value;
}

function sendPasswordResetEmail(string $recipient, string $resetUrl): void
{
    $autoload = __DIR__ . '/../vendor/autoload.php';
    if (!is_file($autoload)) {
        throw new RuntimeException('Composer dependencies are not installed.');
    }

    require_once $autoload;

    $host = passwordResetEnv('MEMPULSE_SMTP_HOST');
    $fromEmail = passwordResetEnv('MEMPULSE_SMTP_FROM_EMAIL');
    $fromName = passwordResetEnv('MEMPULSE_SMTP_FROM_NAME', 'MemPulse');
    $username = passwordResetEnv('MEMPULSE_SMTP_USERNAME', '');
    $password = passwordResetEnv('MEMPULSE_SMTP_PASSWORD', '');
    $encryption = strtolower((string) passwordResetEnv('MEMPULSE_SMTP_ENCRYPTION', 'tls'));
    $port = (int) passwordResetEnv('MEMPULSE_SMTP_PORT', $encryption === 'ssl' ? '465' : '587');

    if (!$host || !$fromEmail || !filter_var($fromEmail, FILTER_VALIDATE_EMAIL) || $port < 1 || $port > 65535) {
        throw new RuntimeException('Password reset SMTP settings are incomplete.');
    }

    $mailer = new PHPMailer(true);
    $mailer->isSMTP();
    $mailer->Host = $host;
    $mailer->Port = $port;
    $mailer->SMTPAuth = $username !== '';
    $mailer->Username = $username;
    $mailer->Password = $password;
    if ($encryption === 'tls') {
        $mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    } elseif ($encryption === 'ssl') {
        $mailer->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    } elseif ($encryption !== 'none') {
        throw new RuntimeException('MEMPULSE_SMTP_ENCRYPTION must be tls, ssl, or none.');
    }

    $mailer->CharSet = 'UTF-8';
    $mailer->setFrom($fromEmail, $fromName);
    $mailer->addAddress($recipient);
    $mailer->isHTML(true);
    $mailer->Subject = 'Reset your MemPulse password';
    $safeUrl = htmlspecialchars($resetUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $mailer->Body = '<p>We received a request to reset your MemPulse password.</p>'
        . '<p><a href="' . $safeUrl . '">Reset your password</a></p>'
        . '<p>This link expires in 30 minutes and can only be used once. If you did not request a reset, you can ignore this email.</p>';
    $mailer->AltBody = "We received a request to reset your MemPulse password.\n\n"
        . "Open this link within 30 minutes to choose a new password:\n" . $resetUrl
        . "\n\nIf you did not request a reset, you can ignore this email.";
    $mailer->send();
}

function passwordResetPublicUrl(string $path): string
{
    $baseUrl = rtrim((string) passwordResetEnv('MEMPULSE_PUBLIC_URL'), '/');
    if ($baseUrl === '' || !filter_var($baseUrl, FILTER_VALIDATE_URL)) {
        throw new RuntimeException('MEMPULSE_PUBLIC_URL is not configured as an absolute URL.');
    }

    $scheme = strtolower((string) parse_url($baseUrl, PHP_URL_SCHEME));
    if (!in_array($scheme, ['http', 'https'], true)) {
        throw new RuntimeException('MEMPULSE_PUBLIC_URL must use HTTP or HTTPS.');
    }

    return $baseUrl . '/authentication/' . ltrim($path, '/');
}
