<?php
declare(strict_types = 1);

namespace noirapi\helpers;

use Latte;
use noirapi\Exceptions\FileNotFoundException;

class Template {

    private string $template;
    private Latte\Engine $latte;
    private const latte_ext = '.latte';

    public function __construct() {

        $this->latte = new Latte\Engine;
        $this->latte->setAutoRefresh();
        /** @psalm-suppress UndefinedConstant */
        $this->latte->setTempDirectory(ROOT . '/temp');
        $this->latte->addFilterLoader('\\noirapi\\helpers\\Filters::init');

    }

    /**
     * @param array $params
     * @return string
     */
    public function print(array $params = []): string {
        return $this->latte->renderToString($this->template, $params);
    }

    /**
     * @param string $template
     * @return $this
     * @throws FileNotFoundException
     * @noinspection PhpUnused
     */
    public function setTemplate(string $template): Template {
        /** @psalm-suppress UndefinedConstant */
        $file = PATH_TEMPLATES . DIRECTORY_SEPARATOR . $template . self::latte_ext;

        if(is_readable($file)) {
            $this->template = $file;
            return $this;
        }

        throw new FileNotFoundException('Unable to find template: ' . $file);
    }

}
