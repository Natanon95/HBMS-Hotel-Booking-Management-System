<?php
// Lightweight mailer wrapper (uses PHP mail() in demo; swap for PHPMailer/SMTP in prod)
class Mailer {
    private static array $log = [];

    public static function send(string $to, string $subject, string $htmlBody): bool {
        if (DEMO_MODE) {
            // In demo mode just log instead of sending
            self::$log[] = compact('to', 'subject', 'htmlBody');
            return true;
        }
        $headers  = "MIME-Version: 1.0\r\n";
        $headers .= "Content-type: text/html; charset=UTF-8\r\n";
        $headers .= "From: " . APP_NAME . " <noreply@hotel.local>\r\n";
        return mail($to, $subject, $htmlBody, $headers);
    }

    public static function sendBookingConfirmation(array $booking, array $guest): bool {
        $subject = APP_NAME . ' – Booking Confirmation ' . e($booking['booking_ref']);
        $body = "
        <h2>Booking Confirmation</h2>
        <p>Dear " . e($guest['first_name']) . " " . e($guest['last_name']) . ",</p>
        <p>Your booking <strong>" . e($booking['booking_ref']) . "</strong> is confirmed.</p>
        <ul>
          <li>Check-in:  " . e($booking['check_in']) . "</li>
          <li>Check-out: " . e($booking['check_out']) . "</li>
          <li>Room:      " . e($booking['room_number']) . "</li>
        </ul>
        <p>Thank you for choosing " . e(APP_NAME) . ".</p>";
        return self::send($guest['email'] ?? '', $subject, $body);
    }

    public static function getLog(): array { return self::$log; }
}
