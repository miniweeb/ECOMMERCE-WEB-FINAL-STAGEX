<?php
namespace PHPMailer\PHPMailer;

class PHPMailer
{
    /** @var bool Whether to use SMTP (no‑op for stub) */
    protected $smtp = false;

    /** @var string SMTP host (ignored in stub) */
    public $Host = '';

    /** @var string SMTP username (ignored in stub) */
    public $Username = '';

    /** @var string SMTP password (ignored in stub) */
    public $Password = '';

    /** @var string TLS/SSL encryption (ignored in stub) */
    public $SMTPSecure = '';

    /** @var int SMTP port (ignored in stub) */
    public $Port = 0;

    /** @var bool Whether SMTP authentication is required (ignored in stub) */
    public $SMTPAuth = false;

    /** @var string Sender email address */
    protected $from = '';

    /** @var string Sender name */
    protected $fromName = '';

    /** @var array List of recipient email addresses */
    protected $to = [];

    /** @var string Message subject */
    public $Subject = '';

    /** @var string Message body */
    public $Body = '';

    /** @var bool Whether the message body is HTML */
    protected $html = false;

    /**
     * Enable SMTP mode.  This method exists for API compatibility with
     * the full PHPMailer library but performs no action in this stub
     * implementation.
     *
     * @return void
     */
    public function isSMTP(): void
    {
        $this->smtp = true;
    }

    /**
     * Set the sender address and name.
     *
     * @param string $address The email address messages will be sent from
     * @param string $name    Optional display name
     *
     * @return void
     */
    public function setFrom(string $address, string $name = ''): void
    {
        $this->from = $address;
        $this->fromName = $name;
    }

    /**
     * Add a recipient to the message.
     *
     * @param string $address Recipient email address
     *
     * @return void
     */
    public function addAddress(string $address): void
    {
        $this->to[] = $address;
    }

    /**
     * Specify whether the email body is HTML.  The stub implementation
     * simply sets a flag to emit appropriate headers via mail().
     *
     * @param bool $isHtml Whether to treat the body as HTML
     *
     * @return void
     */
    public function isHTML(bool $isHtml = true): void
    {
        $this->html = $isHtml;
    }

    /**
     * Send the composed email.  Supports two modes:
     *
     * 1. If `isSMTP()` has been called and a Host is provided, this
     *    method will attempt to deliver messages directly via SMTP using
     *    a basic implementation of the SMTP protocol.  It supports
     *    authentication and TLS encryption (STARTTLS) on port 587.
     *
     * 2. Otherwise it falls back to PHP's built‑in mail() function.  It
     *    loops over all recipients and sends individual messages with
     *    appropriate headers.
     *
     * Note: This simplified SMTP implementation is provided as a
     * convenience for development environments.  It does not support
     * advanced features like attachments or custom headers.  For
     * production deployments you should install the full PHPMailer
     * library.
     *
     * @return bool True if all messages were accepted for delivery
     */
    public function send(): bool
    {
        // Build headers common to both transport methods
        $headers = [];
        if ($this->from) {
            $fromName = $this->fromName ?: $this->from;
            $headers[] = 'From: ' . $fromName . ' <' . $this->from . '>';
            $headers[] = 'Reply-To: ' . $this->from;
        }
        if ($this->html) {
            $headers[] = 'MIME-Version: 1.0';
            $headers[] = 'Content-Type: text/html; charset=UTF-8';
        }
        $headerStr = implode("\r\n", $headers);

        // Decide transport: use SMTP if requested and host provided
        $useSmtp = $this->smtp && !empty($this->Host);
        $allOk = true;
        foreach ($this->to as $recipient) {
            $result = false;
            if ($useSmtp) {
                $result = $this->sendViaSmtp($recipient, $headerStr);
            } else {
                // Fallback to mail() for environments without SMTP
                $result = @mail($recipient, $this->Subject, $this->Body, $headerStr);
            }
            if (!$result) {
                $allOk = false;
            }
        }
        return $allOk;
    }

    /**
     * Send a single message via SMTP using a very basic protocol
     * implementation.  Supports STARTTLS on port 587 and PLAIN
     * authentication.  This method is intentionally limited and is
     * suitable for simple transactional messages only.
     *
     * @param string $recipient Recipient email address
     * @param string $headerStr Precompiled header string
     *
     * @return bool True on success
     */
    protected function sendViaSmtp(string $recipient, string $headerStr): bool
    {
        $host = $this->Host;
        $port = $this->Port ?: 587;
        $username = $this->Username;
        $password = $this->Password;
        $timeout = 30;
        $errno = 0;
        $errstr = '';
        $fp = stream_socket_client("tcp://{$host}:{$port}", $errno, $errstr, $timeout, STREAM_CLIENT_CONNECT);
        if (!$fp) {
            return false;
        }
        $lines = function () use ($fp): string {
            $data = '';
            while (($line = fgets($fp)) !== false) {
                $data .= $line;
                if (preg_match('/^\d{3} /', $line)) {
                    break;
                }
            }
            return $data;
        };
        // Read server greeting
        $lines();
        // Send EHLO
        fwrite($fp, "EHLO localhost\r\n");
        $lines();
        // Start TLS if requested
        fwrite($fp, "STARTTLS\r\n");
        $resp = $lines();
        if (strpos($resp, '220') === 0) {
            stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            // Send EHLO again after TLS
            fwrite($fp, "EHLO localhost\r\n");
            $lines();
        }
        // Authenticate if credentials provided
        if ($username && $password) {
            fwrite($fp, "AUTH LOGIN\r\n");
            $lines();
            fwrite($fp, base64_encode($username) . "\r\n");
            $lines();
            fwrite($fp, base64_encode($password) . "\r\n");
            $lines();
        }
        // MAIL FROM
        $from = $this->from ?: $username;
        fwrite($fp, "MAIL FROM:<{$from}>\r\n");
        $lines();
        // RCPT TO
        fwrite($fp, "RCPT TO:<{$recipient}>\r\n");
        $lines();
        // DATA
        fwrite($fp, "DATA\r\n");
        $lines();
        // Build message
        $headersForSmtp = $headerStr ? $headerStr . "\r\n" : '';
        $message = $headersForSmtp . "\r\n" . $this->Body;
        fwrite($fp, $message . "\r\n.\r\n");
        $lines();
        // QUIT
        fwrite($fp, "QUIT\r\n");
        fclose($fp);
        return true;
    }
}