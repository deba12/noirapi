<?php
/** @noinspection UnknownInspectionInspection */
declare(strict_types = 1);

namespace noirapi\lib;

use core\Exceptions\FileNotFoundException;
use Latte\Engine;
use stdClass;

class View {

    /** @var stdClass */
    public  $request;
    /** @var string */
    private $template;
    /** @var Engine */
    public  $latte;
    /** @var string */
    private $layout;
    /** @var response */
    private $response;
    /** @var string */
    private const latte_ext = '.latte';

    /**
     * View constructor.
     * @param \stdClass $request
     * @param \noirapi\lib\Response $response
     * @throws \core\Exceptions\FileNotFoundException
     */
    public function __construct(stdClass $request, Response $response) {

        $this->request = $request;

        $this->latte = new Engine;
        $this->latte->setTempDirectory(ROOT . '/temp');

        //disable regenerate of template files
        $this->latte->setAutoRefresh(true);

        $this->response = $response;

        if(defined('DEFAULT_LAYOUT')) {
            $this->setLayout(DEFAULT_LAYOUT)
                ->showHeader()
                ->showFooter();
        } else {
            $this->hideHeader()
                ->hideFooter();
        }

    }

    /**
     * @param array $params
     * @return \noirapi\lib\response
     * @throws \core\Exceptions\FileNotFoundException
     */
    public function display(array $params = []): response {

        if($this->template === null) {
            $this->setTemplate($this->request->function);
        }

        $layout = $this->layout ?? $this->template;

        if(isset($_SERVER['HTTP_X_PJAX'])) {
            $layout = $params['view'];
        }

        if(isset($_SESSION['message'])) {
            $params['message'] = $_SESSION['message'];
            unset($_SESSION['message']);
        }

        $params['view'] = $this->template;

        if(isset($_SESSION['message'])) {
            $params['message'] = $_SESSION['message'];
            unset($_SESSION['message']);
        }

        return $this->response->setBody($this->latte->renderToString($layout, array_merge((array)$this->request, $params)));

    }

    /**
     * @param string|null $layout
     * @param string $view
     * @param array $params
     * @return string
     * @throws \core\Exceptions\FileNotFoundException
     */
    public function print(?string $layout, string $view, array $params = []): string {

        if($layout !== null) {
            $this->setLayout($layout);
            $this->setTemplate($view);
            $params['view'] = $this->template;
            return $this->latte->renderToString($this->layout, $params);
        }

        $this->setTemplate($view);

        return $this->latte->renderToString($this->template, $params);

    }

    /**
     * @param string $template
     * @param string|null $controller
     * @return view
     * @noinspection PhpUnused
     * @throws \core\Exceptions\FileNotFoundException
     */
    public function setTemplate(string $template, string $controller = null): view {

        if($controller === null) {
            $controller = $this->request->controller;
        }

        $file = PATH_VIEWS . $controller . DIRECTORY_SEPARATOR . $template . self::latte_ext;

        if(is_readable($file)) {
            $this->template = $file;
            return $this;
        }

        throw new FileNotFoundException('Unable to find template: ' . $file);

    }

    /**
     * @param string|null $layout
     * @return view
     * @throws \core\Exceptions\FileNotFoundException
     * @noinspection PhpUnused
     */
    public function setLayout(?string $layout = null): view {

        if($layout === null) {
            $this->layout = null;
            return $this;
        }

        $file = PATH_LAYOUTS . $layout . self::latte_ext;

        if(is_readable($file)) {
            $this->layout = $file;
            return $this;
        }

        throw new FileNotFoundException('Unable to find layout: ' . $file);

    }

    /**
     * @return $this
     * @noinspection PhpUnused
     */
    public function hideHeader(): view {
        $this->request->header = false;
        return $this;
    }

    /**
     * @return $this
     */
    public function showHeader(): view {
        $this->request->header = true;
        return $this;
    }

    /**
     * @return $this
     * @noinspection PhpUnused
     */
    public function hideFooter(): view {
        $this->request->footer = false;
        return $this;
    }

    /**
     * @return $this
     */
    public function showFooter(): view {
        $this->request->footer = true;
        return $this;
    }

    public function message(string $text, string $type, ?array $post): self {

        if (isset($_SESSION['message'])) {
            unset($_SESSION['message']);
        }

        if (!empty($text) && !empty($type)) {
            $_SESSION['message'] = array('text' => $text, 'type' => $type);
        }

        if (!empty($post)) {
            $_SESSION['message']['post'] = $post;
        }

        return $this;

    }

    /**
     * @param $param
     * @param $page
     * @return string
     * @noinspection PhpUnused
     */
    public static function add_url_var($param, $page): string {

        /** @noinspection UriPartExtractionInspection */
        $url = parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY);

        if(isset($url['query'])) {

            parse_str($url['query'], $array);

            if(count($array) > 0) {
                $array[$param] = $page;
                return '?' . http_build_query($array);
            }
            return '?' . $param . '=' . $page;
        }
        return '?' . $param . '=' . $page;
    }

}
