<?php

require_once dirname(__DIR__) . '/config/database.php';

class EmailService {
    /**
     * Send transactional email via Brevo or fallback to internal simulation
     */
    public static function sendEmail(string $toEmail, string $toName, string $subject, string $htmlContent): bool {
        // If Brevo API key is available, send via Brevo free plan
        if (EMAIL_PROVIDER === 'brevo' && !empty(BREVO_API_KEY)) {
            return self::sendViaBrevo($toEmail, $toName, $subject, $htmlContent);
        }

        // Otherwise, Mock Provider: log email locally
        error_log("[PayBen Email Sandbox] To: {$toName} <{$toEmail}> | Subject: {$subject}");
        return true;
    }

    private static function sendViaBrevo(string $toEmail, string $toName, string $subject, string $htmlContent): bool {
        $url = 'https://api.brevo.com/v3/smtp/email';
        $payload = [
            'sender' => [
                'name' => APP_NAME,
                'email' => BREVO_SENDER_EMAIL
            ],
            'to' => [
                [
                    'email' => $toEmail,
                    'name' => $toName
                ]
            ],
            'subject' => $subject,
            'htmlContent' => $htmlContent
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'accept: application/json',
            'api-key: ' . BREVO_API_KEY,
            'content-type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return ($httpCode >= 200 && $httpCode < 300);
    }

    /**
     * Generate HTML email template for transactions
     */
    public static function renderTransactionTemplate(
        string $userName,
        string $txId,
        string $serviceName,
        float $amount,
        string $status = 'SUCCESS'
    ): string {
        $amountFormatted = formatCurrency($amount);
        $dateFormatted = date('d M Y, h:i A');
        $statusColor = $status === 'SUCCESS' ? '#10b981' : '#ef4444';

        return "
        <!DOCTYPE html>
        <html>
        <head>
          <meta charset='utf-8'>
          <title>PayBen Transaction Receipt</title>
        </head>
        <body style='font-family: -apple-system, BlinkMacSystemFont, \"Segoe UI\", Roboto, sans-serif; background: #f8fafc; margin: 0; padding: 24px; color: #1e293b;'>
          <div style='max-width: 520px; margin: 0 auto; background: #ffffff; border-radius: 16px; overflow: hidden; border: 1px solid #e2e8f0; box-shadow: 0 4px 12px rgba(0,0,0,0.05);'>
            <div style='background: linear-gradient(135deg, #0047cc, #ff6200); padding: 24px; text-align: center;'>
              <h1 style='color: #ffffff; margin: 0; font-size: 24px; font-weight: 800; letter-spacing: -0.5px;'>PayBen</h1>
              <p style='color: #fed7aa; margin: 4px 0 0 0; font-size: 12px;'>Recharge • Pay • Simplify</p>
            </div>
            <div style='padding: 24px;'>
              <p style='font-size: 15px; margin: 0 0 16px 0;'>Hello <strong>{$userName}</strong>,</p>
              <p style='font-size: 13px; color: #64748b; margin: 0 0 20px 0;'>Your payment transaction has been processed in our sandbox environment.</p>
              
              <div style='background: #f1f5f9; border-radius: 12px; padding: 16px; margin-bottom: 20px;'>
                <div style='display: flex; justify-content: space-between; margin-bottom: 8px;'>
                  <span style='font-size: 12px; color: #64748b;'>Transaction ID</span>
                  <span style='font-size: 12px; font-weight: 700; font-family: monospace;'>{$txId}</span>
                </div>
                <div style='display: flex; justify-content: space-between; margin-bottom: 8px;'>
                  <span style='font-size: 12px; color: #64748b;'>Service</span>
                  <span style='font-size: 12px; font-weight: 600;'>{$serviceName}</span>
                </div>
                <div style='display: flex; justify-content: space-between; margin-bottom: 8px;'>
                  <span style='font-size: 12px; color: #64748b;'>Date & Time</span>
                  <span style='font-size: 12px; color: #334155;'>{$dateFormatted}</span>
                </div>
                <div style='display: flex; justify-content: space-between; border-top: 1px solid #e2e8f0; padding-top: 8px;'>
                  <span style='font-size: 14px; font-weight: 700;'>Amount</span>
                  <span style='font-size: 16px; font-weight: 800; color: #0047cc;'>{$amountFormatted}</span>
                </div>
              </div>

              <div style='text-align: center; margin-bottom: 20px;'>
                <span style='display: inline-block; padding: 6px 16px; border-radius: 9999px; background: {$statusColor}20; color: {$statusColor}; font-size: 12px; font-weight: 800; text-transform: uppercase;'>
                  {$status}
                </span>
              </div>

              <p style='font-size: 11px; color: #94a3b8; text-align: center; margin: 0;'>
                This is a simulated PayBen Fintech Sandbox receipt. No actual funds were deducted.
              </p>
            </div>
          </div>
        </body>
        </html>
        ";
    }
}
