<?php
/**
 * @noinspection TypoSafeNamingInspection
 * @noinspection TransitiveDependenciesUsageInspection
 * @noinspection PhpUnused
 * @noinspection UnknownInspectionInspection
 */
declare(strict_types = 1);

namespace noirapi\helpers;

use Latte\Engine;
use RuntimeException;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport;

use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

class Mail {

    public string $message_id;
    private Mailer $mailer;
    private Email $message;
    private string $body;


    public function __construct(string $dsn) {
        $this->mailer = new Mailer(Transport::fromDsn($dsn));
        $this->message = new Email();
    }

    /**
     * @param string|array $from
     * @param array|string $to
     * @param string $subject
     * @return Mail
     */
    public function new(string|array $from, array|string $to, string $subject): Mail {

        if(is_string($to)) {

            $this->message->to($to);

        } else {

            foreach($to as $address) {
                $this->message->addTo($address);
            }

        }

        if(is_string($from)) {
            $this->message->from($from);
        } else {
            $this->message->from(new Address($from[0], $from[1]));
        }

        $this->message->subject($subject);
        $this->message->priority(Email::PRIORITY_HIGHEST);

        return $this;

    }

    /**
     * @param array $cc
     * @return $this
     */
    public function setCC(array $cc): Mail {

        foreach($cc as $address) {
            $this->message->addCc($address);
        }

        return $this;

    }

    /**
     * @param array $bcc
     * @return $this
     */
    public function setBCC(array $bcc): Mail {

        foreach($bcc as $address) {
            $this->message->addBcc($address);
        }

        return $this;

    }

    /**
     * @param string $template
     * @param array $params
     * @return Mail
     */
    public function setTemplate(string $template, array $params): Mail {

        $file = ROOT  . "/app/templates/$template.latte";
        if(!is_readable($file)){
            throw new RuntimeException('Unable to load template: ' . $file);
        }

        $latte = new Engine();
        $latte->setTempDirectory(ROOT . '/temp');
        $this->body = $latte->renderToString($template, $params);

        return $this;

    }

    /**
     * @param string $body
     * @return $this
     */
    public function setBody(string $body): Mail {

        $this->body = $body;

        return $this;

    }

    /**
     * @param string $key
     * @param string $value
     * @return Mail
     */
    public function addHeader(string $key, string $value): Mail {

        $this->message->getHeaders()->addTextHeader($key, $value);

        return $this;

    }

    /**
     * @param string $data
     * @param string $filename
     * @param string|null $mime_type
     * @return Mail
     */
    public function attach(string $data, string $filename, ?string $mime_type = null): Mail {

        $this->message->attach($data, $filename, $mime_type);

        return $this;

    }

    /**
     * @param string $file
     * @param string $name
     * @param string|null $mime_type
     * @return Mail
     */
    public function attachFile(string $file, string $name, ?string $mime_type = null): Mail {

        if(!is_readable($file)) {
            throw new RuntimeException("Unable to open $file");
        }

        $this->message->attachFromPath($file, $name, $mime_type);

        return $this;

    }

    /**
     * @param string $data
     * @param string $filename
     * @param string|null $mime_type
     * @return Mail
     */
    public function embed(string $data, string $filename, ?string $mime_type = null): Mail {

        $this->message->embed($data, $filename, $mime_type);

        return $this;

    }

    /**
     * @param string $file
     * @param string $name
     * @param string|null $mime_type
     * @return Mail
     */
    public function embedFile(string $file, string $name, ?string $mime_type = null): Mail {

        if(!is_readable($file)) {
            throw new RuntimeException("Unable to open $file");
        }

        $this->message->embedFromPath($file, $name, $mime_type);

        return $this;

    }

    /**
     * @param string $error
     * @return bool
     * @noinspection PhpUnusedParameterInspection
     */
    public function send(string $error): bool {

        $this->message->html($this->body);
        $this->message->text(strip_tags($this->body));

        try {
            $this->mailer->send($this->message);
        } catch (TransportExceptionInterface $e) {
            /** @noinspection PhpUnusedLocalVariableInspection */
            $error = $e->getMessage();
            return false;
        }

        return true;

    }

    /**
     * @return $this
     */
    public function noResponders(): Mail {

        $this->addHeader('X-Auto-Response-Suppress', 'OOF, DR, RN, NRN, AutoReply');

        return $this;

    }

}
