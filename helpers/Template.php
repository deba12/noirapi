<?php declare(strict_types = 1);

namespace noirapi\helpers;

use Latte;

class Template {

	private $template;
	private $latte;
	private const latte_ext = '.latte';

	public function __construct(string $template = null) {

		$this->latte = new Latte\Engine;
		new \noirapi\Latte($this->latte, []);
		$this->latte->setAutoRefresh(true);
		$this->latte->setTempDirectory(DOCROOT . '/temp');

		if($template !== null) {
			$this->setTemplate($template);
		}

	}

	public function print(array $params = []): string {
		return $this->latte->renderToString(PATH_TEMPLATES . DIRECTORY_SEPARATOR . $this->template . self::latte_ext, $params);
	}

	public function setTemplate(string $name): void {
		$this->template = $name;
	}

}
