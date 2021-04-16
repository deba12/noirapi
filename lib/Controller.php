<?php declare(strict_types = 1);

namespace noirapi\lib;
use core\Exceptions\UnableToForwardException;
use stdClass;

class Controller {

    /** @var stdClass $request */
	public $request;
    /** @var array $server */
    public $server;
    /** @var Model $model */
    public $model;
	/** @var Response $response */
	public $response;
	/** @var View $view */
	public $view;

    /**
     * Controller constructor.
     * @param stdClass $request
     * @param array $server
     */
	public function __construct(stdClass $request, array $server) {

		$this->request = $request;
		$this->server = $server;

		if(defined('DB')) {
			$model = 'app\\models\\' . self::getClassName(get_class($this));
			if(class_exists($model)) {
				$this->model = new $model($this->request);
			} else {
				$this->model = new Model();
			}
		}


		$this->response = new Response();

	}

	/**
	 * @param string $class
	 * @return string
	 */
	public static function getClassName(string $class):string {
		$path = explode('\\', $class);
		return array_pop($path);
	}

    /**
     * @param string $location
     * @param int $status
     * @return Response
     * @throws UnableToForwardException
     * @noinspection PhpUnused
     * @noinspection UnknownInspectionInspection
     */
	public function forward(string $location, int $status = 302): Response {

		if($status !== 302 && $status !== 301) {
			throw new UnableToForwardException('Unable to forward with status code: ' . $status);
		}

		return $this->response->withStatus($status)->withLocation($location);
	}

}
