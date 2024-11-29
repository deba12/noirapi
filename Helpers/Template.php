<?php

declare(strict_types=1);

namespace Noirapi\Helpers;

use Latte;
use Noirapi\Exceptions\FileNotFoundException;

/**
 * @psalm-api
 * @psalm-suppress PropertyNotSetInConstructor
 */
class Template
{
    private string $template;
    private Latte\Engine $latte;
    private const string LATTE_EXT = '.latte';

    public function __construct()
    {

        $this->latte = new Latte\Engine();
        $this->latte->setAutoRefresh();
        /** @psalm-suppress UndefinedConstant */
        $this->latte->setTempDirectory(ROOT . '/temp');
        /** @psalm-suppress UndefinedClass */
        $this->latte->addFilterLoader('\\noirapi\\helpers\\Filters::init');
    }

    /**
     * @param array $params
     * @return string
     */
    public function print(array $params = []): string
    {
        return $this->latte->renderToString($this->template, $params);
    }

    /**
     * @param string $template
     * @return $this
     * @throws FileNotFoundException
     * @noinspection PhpUnused
     */
    public function setTemplate(string $template): Template
    {
        /** @psalm-suppress UndefinedConstant */
        $file = PATH_TEMPLATES . DIRECTORY_SEPARATOR . $template . self::LATTE_EXT;

        if (is_readable($file)) {
            $this->template = $file;

            return $this;
        }

        throw new FileNotFoundException('Unable to find template: ' . $file);
    }
}
