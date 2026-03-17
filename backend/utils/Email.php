<?php
// ─── utils/Email.php ─────────────────────────────────────────
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;

class Email {

    private static function mailer(): PHPMailer {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host        = SMTP_HOST;
        $mail->SMTPAuth    = true;
        $mail->Username    = SMTP_USER;
        $mail->Password    = SMTP_PASSWORD;
        $mail->SMTPSecure  = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port        = SMTP_PORT;
        $mail->SMTPOptions = [
            'ssl' => [
                'verify_peer'       => false,
                'verify_peer_name'  => false,
                'allow_self_signed' => true
            ]
        ];
        $mail->setFrom(FROM_EMAIL, FROM_NAME);
        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        return $mail;
    }

    public static function send(string $to, string $subject, string $html): bool {
        try {
            $mail = self::mailer();
            $mail->addAddress($to);
            $mail->Subject = $subject;
            $mail->Body    = $html;
            $mail->AltBody = strip_tags($html);
            $mail->send();
            return true;
        } catch (\Exception $e) {
            error_log("Email failed to $to: " . $e->getMessage());
            return false;
        }
    }

    public static function sendBulk(array $emails, string $subject, string $html): void {
        foreach ($emails as $email) self::send($email, $subject, $html);
    }

    // ── Templates ──────────────────────────────────────────────

    public static function articleSubmitted(string $adminEmail, string $facultyName, string $articleTitle, string $newsletterTitle): void {
        $subject = "📝 New Article Submitted: $articleTitle";
        $html = self::baseTemplate("New Article for Review",
            "<p><strong>$facultyName</strong> submitted an article for review.</p>
             <table style='width:100%;border-collapse:collapse'>
               <tr><td style='padding:8px;background:#f5f5f5'><strong>Article</strong></td><td style='padding:8px'>$articleTitle</td></tr>
               <tr><td style='padding:8px;background:#f5f5f5'><strong>Newsletter</strong></td><td style='padding:8px'>$newsletterTitle</td></tr>
             </table>
             <p style='margin-top:16px'><a href='" . FRONTEND_URL . "/frontend/admin/review.html' style='background:#1a237e;color:#fff;padding:10px 20px;text-decoration:none;border-radius:6px'>Review Now</a></p>"
        );
        self::send($adminEmail, $subject, $html);
    }

    public static function articleApproved(string $facultyEmail, string $facultyName, string $articleTitle): void {
        $subject = "✅ Article Approved: $articleTitle";
        $html = self::baseTemplate("Article Approved! ✅",
            "<p>Dear <strong>$facultyName</strong>,</p>
             <p>Your article <strong>\"$articleTitle\"</strong> has been approved and will be included in the upcoming newsletter.</p>"
        );
        self::send($facultyEmail, $subject, $html);
    }

    public static function articleRejected(string $facultyEmail, string $facultyName, string $articleTitle, string $reason): void {
        $subject = "❌ Article Needs Revision: $articleTitle";
        $html = self::baseTemplate("Article Needs Revision",
            "<p>Dear <strong>$facultyName</strong>,</p>
             <p>Your article <strong>\"$articleTitle\"</strong> was rejected:</p>
             <blockquote style='border-left:4px solid #c62828;padding:12px;background:#ffebee;margin:16px 0'>$reason</blockquote>
             <p><a href='" . FRONTEND_URL . "/frontend/faculty/my-articles.html' style='background:#1a237e;color:#fff;padding:10px 20px;text-decoration:none;border-radius:6px'>View My Articles</a></p>"
        );
        self::send($facultyEmail, $subject, $html);
    }

    public static function newsletterPublished(array $emails, string $title, string $pdfString, string $filename): void {
        $subject = "📰 New Newsletter: $title";
        $html = self::baseTemplate("New Newsletter Published! 📰",
            "<p>Dear Subscriber,</p>
             <p>A new newsletter has been published: <strong>$title</strong></p>
             <p style='margin-top:8px'>Please find the newsletter attached as a PDF to this email.</p>
             <p style='font-size:12px;color:#999;margin-top:20px'>You received this because you subscribed to our newsletter updates.</p>"
        );
        foreach ($emails as $email) {
            self::sendWithPdf($email, $subject, $html, $pdfString, $filename);
        }
    }

    public static function sendWithPdf(string $to, string $subject, string $html, string $pdfString, string $filename): bool {
        try {
            $mail = self::mailer();
            $mail->addAddress($to);
            $mail->Subject = $subject;
            $mail->Body    = $html;
            $mail->AltBody = strip_tags($html);
            // Attach PDF from string (no temp file needed)
            $mail->addStringAttachment($pdfString, $filename, 'base64', 'application/pdf');
            $mail->send();
            return true;
        } catch (\Exception $e) {
            error_log("Email with PDF failed to $to: " . $e->getMessage());
            return false;
        }
    }

    private static function baseTemplate(string $heading, string $body): string {
        return "
        <div style='font-family:Arial,sans-serif;max-width:600px;margin:auto;padding:20px;border:1px solid #e0e0e0;border-radius:8px'>
            <div style='background:#1a237e;color:#fff;padding:20px;border-radius:6px 6px 0 0;margin:-20px -20px 20px'>
                <h2 style='margin:0;font-size:20px'>📰 $heading</h2>
            </div>
            $body
            <hr style='border:none;border-top:1px solid #eee;margin:20px 0'>
            <p style='color:#999;font-size:12px;text-align:center'>Newsletter Management System</p>
        </div>";
    }
}