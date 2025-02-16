<?php

/**
 * @noinspection TypoSafeNamingInspection
 * @noinspection TransitiveDependenciesUsageInspection
 * @noinspection PhpUnused
 * @noinspection PhpUndefinedClassInspection
 * @noinspection PhpUndefinedNamespaceInspection
 */

declare(strict_types=1);

namespace Noirapi\Helpers;

use Html2Text\Html2Text;
use Latte\Engine;
use Noirapi\Config;
use RuntimeException;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

use function is_string;

/**
 * @psalm-api
 * @psalm-suppress PropertyNotSetInConstructor
 */
class Mail
{
    private Email $message;
    private string $body = '';
    /** @noinspection PhpGetterAndSetterCanBeReplacedWithPropertyHooksInspection */
    private string $error;
    private bool $debug;
    private string $debug_data;
    private Transport\TransportInterface $transport;
    private string $dsn;

    public function __construct(string $dsn, bool $debug = false)
    {

        $this->transport = Transport::fromDsn($dsn);
        $this->debug = $debug;
        $this->message = new Email();
        $this->dsn = $dsn;
    }

    /**
     * @param string|array $from
     * @param array|string $to
     * @param string $subject
     * @return Mail
     */
    public function new(string|array $from, array|string $to, string $subject): Mail
    {

        if (is_string($from)) {
            $this->message->from($from);
        } else {
            $this->message->from(new Address($from[0], $from[1]));
        }

        if (is_string($to)) {
            $this->message->to($to);
        } else {
            foreach ($to as $address) {
                $this->message->addTo($address);
            }
        }

        $this->message->subject($subject);
        $this->message->priority(Email::PRIORITY_HIGHEST);

        return $this;
    }

    /**
     * @param array $cc
     * @return $this
     */
    public function setCC(array $cc): Mail
    {

        foreach ($cc as $address) {
            $this->message->addCc($address);
        }

        return $this;
    }

    /**
     * @param array $bcc
     * @return $this
     */
    public function setBCC(array $bcc): Mail
    {

        foreach ($bcc as $address) {
            $this->message->addBcc($address);
        }

        return $this;
    }

    /**
     * @param string $template
     * @param array $params
     * @return Mail
     */
    public function setTemplate(string $template, array $params): Mail
    {

        /** @psalm-suppress UndefinedConstant */
        $file = ROOT  . "/app/templates/$template.latte";
        if (! is_readable($file)) {
            throw new RuntimeException('Unable to load template: ' . $file);
        }

        $latte = new Engine();

        /** @psalm-suppress UndefinedConstant */
        $latte->setTempDirectory(ROOT . '/temp');
        $this->body = $latte->renderToString($file, $params);

        return $this;
    }

    /**
     * @param string $email
     * @return $this
     */
    public function setReplyTo(string $email): Mail
    {
        $this->message->replyTo($email);

        return $this;
    }

    /**
     * @param string $body
     * @return $this
     */
    public function setBody(string $body): Mail
    {

        $this->body = $body;

        return $this;
    }

    /**
     * @param string $key
     * @param string $value
     * @return Mail
     */
    public function addHeader(string $key, string $value): Mail
    {

        $this->message->getHeaders()->addTextHeader($key, $value);

        return $this;
    }

    /**
     * @param string $data
     * @param string $filename
     * @param string|null $mime_type
     * @return Mail
     */
    public function attach(string $data, string $filename, ?string $mime_type = null): Mail
    {

        $this->message->attach($data, $filename, $mime_type);

        return $this;
    }

    /**
     * @param string $file
     * @param string $name
     * @param string|null $mime_type
     * @return Mail
     */
    public function attachFile(string $file, string $name, ?string $mime_type = null): Mail
    {

        if (! is_readable($file)) {
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
    public function embed(string $data, string $filename, ?string $mime_type = null): Mail
    {

        $this->message->embed($data, $filename, $mime_type);

        return $this;
    }

    /**
     * @param string $file
     * @param string $name
     * @param string|null $mime_type
     * @return Mail
     */
    public function embedFile(string $file, string $name, ?string $mime_type = null): Mail
    {

        if (! is_readable($file)) {
            throw new RuntimeException("Unable to open $file");
        }

        $this->message->embedFromPath($file, $name, $mime_type);

        return $this;
    }

    /**
     * @return bool
     * @throws TransportExceptionInterface
     */
    public function send(): bool
    {

        $this->message->html($this->body);
        /** @noinspection PhpUndefinedClassInspection */
        $text = new Html2Text($this->body);

        $search = [
            '/^\t+/m',
            '/^\s+/m',
        ];

        $replace = [
            '',
            '',
        ];

        $this->message->text(preg_replace($search, $replace, $text->getText()));

        // This is used for testing!!!
        if (str_starts_with($this->dsn, 'null://')) {

            /** @noinspection PhpUnhandledExceptionInspection */
            $res = $this->transport->send($this->message);
            /** @noinspection NullPointerExceptionInspection */
            $message_id = $res->getMessageId();

            file_put_contents(Config::getTemp() . "/mail-$message_id.eml", $this->message->toString());
            file_put_contents(Config::getTemp() . "/mail-$message_id.txt", $this->message->getTextBody());

            return true;
        }

        try {
            $res = $this->transport->send($this->message);
        } catch (TransportExceptionInterface $e) {
            $this->error = $e->getMessage();
            $this->debug_data = $e->getDebug();

            return false;
        }

        if ($res === null) {
            $this->error = 'No response from server';

            return false;
        }

        if ($this->debug) {
            $this->debug_data = $res->getDebug();
        }

        return true;
    }

    /**
     * @return $this
     */
    public function noResponders(): Mail
    {

        $this->addHeader('X-Auto-Response-Suppress', 'OOF, DR, RN, NRN, AutoReply');

        return $this;
    }

    public function replyTo(string $email): Mail
    {

        $this->message->replyTo($email);

        return $this;
    }
    /**
     * @return string
     */
    public function getBody(): string
    {
        return $this->body;
    }

    /**
     * @return string
     */
    public function getError(): string
    {
        return $this->error;
    }

    /**
     * @return string
     * @noinspection GetSetMethodCorrectnessInspection
     */
    public function getDebug(): string
    {
        return $this->debug_data;
    }
}
