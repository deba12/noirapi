<?php declare(strict_types = 1);

namespace noirapi\helpers;

use core\Exceptions\FileNotFoundException;
use Latte;

class Template {

    private $template;
    private $latte;
    private const latte_ext = '.latte';

    public function __construct() {

        $this->latte = new Latte\Engine;
        $this->latte->setAutoRefresh(true);
        $this->latte->setTempDirectory(ROOT . '/temp');

    }

    /**
     * @param array $params
     * @return string
     */
    public function print(array $params = []): string {
        return $this->latte->renderToString(PATH_TEMPLATES . DIRECTORY_SEPARATOR . $this->template . self::latte_ext, $params);
    }

    /**
     * @param string $template
     * @return $this
     * @throws \core\Exceptions\FileNotFoundException
     */
    public function setTemplate(string $template): Template {

        $file = PATH_TEMPLATES . DIRECTORY_SEPARATOR . $template . self::latte_ext;

        if(is_readable($file)) {
            $this->template = $file;
            return $this;
        }

        throw new FileNotFoundException('Unable to find template: ' . $file);

    }

}
