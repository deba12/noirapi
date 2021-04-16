<?php
/** @noinspection PhpUnused
 * @noinspection UnknownInspectionInspection
 */
declare(strict_types = 1);

namespace noirapi\helpers;

use Latte\Engine;
use RuntimeException;
use Swift_Attachment;
use Swift_Mailer;
use Swift_Message;
use Swift_NullTransport;
use Swift_SendmailTransport;
use Swift_SmtpTransport;

class Mail {

    private $message;
    private $template;
    private $mail;

    public function __construct(array $smtp, string $template) {

        if($smtp['transport'] === 'smtp_ssl') {
            $transport = (new Swift_SmtpTransport($smtp['host'], $smtp['port'], 'ssl'))
                ->setUsername($smtp['user'])
                ->setPassword($smtp['pass']);
        } elseif($smtp['transport'] === 'sendmail') {
            $transport = new Swift_SendmailTransport();
        } elseif($smtp['transport'] === 'null') {
            $transport = new Swift_NullTransport();
        } else {
            throw new RuntimeException('Unable to find transport: ' . $smtp['transport']);
        }

        $this->mail = new Swift_Mailer($transport);
        $this->message = (new Swift_Message())->setCharset('UTF-8');

        $template = APPROOT . '/templates/' . $template . '.latte';
        if(file_exists($template)) {
            $this->template = $template;
        } else {
            throw new RuntimeException('Unable to find template: ' . $template);
        }

    }

    /**
     * @param string $from
     * @param array $to
     * @param string $subject
     * @param string $charset
     * @return void
     * @noinspection UnusedFunctionResultInspection
     */
    public function new(string $from, array $to, string $subject, string $charset = 'UTF-8'): void {

        $this->message->setSubject($subject)
            ->setFrom($from)
            ->setTo($to)
            ->setCharset($charset);

    }

    /**
     * @param array $params
     * @param string $type
     * @return void
     * @noinspection UnusedFunctionResultInspection
     */
    public function setBody(array $params, string $type = 'text/html'): void {

        $latte = new Engine();
        $latte->setTempDirectory(DOCROOT . '/temp');
        $content = $latte->renderToString($this->template, $params);

        $this->message->setBody($content, $type);

        $this->message->addPart(strip_tags($content), 'text/plain');

    }

    /**
     * @param string $file
     * @param string $name
     * @noinspection UnusedFunctionResultInspection
     */
    public function attachFile(string $file, string $name): void {
        $this->message->attach(Swift_Attachment::fromPath($file)->setFilename($name));
    }

    /**
     * @param string $data
     * @param string $filename
     * @param string $mime_type
     * @noinspection UnusedFunctionResultInspection
     */
    public function attach(string $data, string $filename, string $mime_type): void {
        $this->message->attach(new Swift_Attachment($data, $filename, $mime_type));
    }

    /**
     * @return int
     */
    public function send(): int {
        return $this->mail->send($this->message);
    }

}
