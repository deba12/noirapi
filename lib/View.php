<?php
/** @noinspection UnknownInspectionInspection */
declare(strict_types = 1);

namespace noirapi\lib;

use JetBrains\PhpStorm\ArrayShape;
use Latte\Bridges\Tracy\BlueScreenPanel;
use Latte\Engine;
use noirapi\Config;
use noirapi\Exceptions\FileNotFoundException;
use noirapi\helpers\Macros;
use function count;

class View {

    /** @var Request */
    public Request $request;

    /** @var string|null */
    private ?string $template = null;

    /** @var Engine */
    public Engine $latte;

    /** @var string|null */
    private ?string $layout = null;

    /** @var Response */
    private Response $response;

    /** @var string */
    private const latte_ext = '.latte';

    /** @var array */
    private array $extra_params = [];

    private array $topCss = [];
    private array $topJs = [];
    private array $bottomCss = [];
    private array $bottomJs = [];

    // used in systempanel
    private array $params_readonly = [];

    /**
     * View constructor.
     * @param Request $request
     * @param response $response
     * @throws FileNotFoundException
     */
    public function __construct(Request $request, Response $response) {

        $this->request = $request;

        $this->latte = new Engine;
        $this->latte->setTempDirectory(ROOT . '/temp');

        //enable regeneration of the template files
        $this->latte->setAutoRefresh();
        $this->latte->addFilterLoader('\\noirapi\\helpers\\Filters::init');

        $this->response = $response;

        $this->latte->addExtension(new Macros());
        if(class_exists(\app\lib\Macros::class)) {
            $this->latte->addExtension(new \app\lib\Macros());
        }

        $layout = Config::get('layout');

        if($layout) {

            $this->setLayout($layout);

        }

        BlueScreenPanel::initialize();

    }

    /**
     * @param array $params
     * @return response
     * @throws FileNotFoundException
     */
    public function display(array $params = []): Response {

        $this->params_readonly = $params;

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
        $params['extra_params'] = $this->extra_params;
        $params['topCss'] = $this->topCss;
        $params['bottomCss'] = $this->bottomCss;
        $params['topJs'] = $this->topJs;
        $params['bottomJs'] = $this->bottomJs;

        return $this->response->setBody($this->latte->renderToString($layout, array_merge((array)$this->request, $params)));

    }

    /**
     * @param string|null $layout
     * @param string $view
     * @param array $params
     * @return string
     * @throws FileNotFoundException
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
     * @return View
     * @noinspection PhpUnused
     * @throws FileNotFoundException
     */
    public function setTemplate(string $template, string $controller = null): View {

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
     * @return View
     * @throws FileNotFoundException
     * @noinspection PhpUnused
     */
    public function setLayout(?string $layout = null): View {
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

    /**
     * @param array $params
     * @return void
     * @noinspection PhpUnused
     */
    public function setLayoutExtraParams(array $params): void {
        $this->extra_params = $params;
    }

    /**
     * @param string $key
     * @param string|array|null $value
     * @return void
     * @noinspection PhpUnused
     */
    public function addLayoutExtraParam(string $key, null|string|array $value): void {
        $this->extra_params[$key] = $value;
    }

    /**
     * @param string $file
     * @return $this
     * @noinspection PhpUnused
     */
    public function addTopCss(string $file): View {
        $this->topCss[] = $file;
        return $this;
    }

    /**
     * @param string $file
     * @return $this
     * @noinspection PhpUnused
     */
    public function addBottomCss(string $file): View {
        $this->bottomCss[] = $file;
        return $this;
    }

    /**
     * @param string $file
     * @return $this
     * @noinspection PhpUnused
     */
    public function addTopJs(string $file): View {
        $this->topJs[] = $file;
        return $this;
    }

    /**
     * @param string $file
     * @return $this
     * @noinspection PhpUnused
     */
    public function addBottomJs(string $file): View {
        $this->bottomJs[] = $file;
        return $this;
    }

    /**
     * @return array this is used by system panel
     *
     * this is used by system panel
     */
    #[ArrayShape(['layout' => "string", 'view' => "string"])]
    public function gerRenderInfo(): array {

        $layout = !empty($this->layout) ? basename($this->layout) : 'No layout';
        $view = !empty($this->template) ? basename($this->template) : 'No view';
        return [
            'layout' => $layout,
            'view' => $view,
        ];

    }

    /**
     * @return array
     * @noinspection PhpUnused
     *
     * this is used by system panel
     */
    #[ArrayShape(['params' => "array", 'extra_params' => "array", 'topCss' => "array", 'bottomCss' => "array", 'topJs' => "array", 'bottomJs' => "array"])]
    public function getParams(): array {

        return [
            'params'        => $this->params_readonly,
            'extra_params'  => $this->extra_params,
            'topCss'        => $this->topCss,
            'bottomCss'     => $this->bottomCss,
            'topJs'         => $this->topJs,
            'bottomJs'      => $this->bottomJs,
        ];

    }

}
