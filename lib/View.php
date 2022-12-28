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

    public Request $request;
    private Response $response;

    private array $params;
    private ?string $template = null;

    public Engine $latte;
    private ?string $layout_file = null;
    private const latte_ext = '.latte';

    private array $extra_params = [];
    private array $topCss = [];
    private array $topJs = [];
    private array $bottomCss = [];
    private array $bottomJs = [];

    // used in system-panel
    private array $params_readonly = [];

    private static string $uri;

    /**
     * View constructor.
     * @param Request $request
     * @param response $response
     * @param array|null $params
     * @throws FileNotFoundException
     */
    public function __construct(Request $request, Response $response, ?array $params = []) {

        $this->request = $request;
        $this->response = $response;
        $this->params = $params;

        $this->latte = new Engine;
        $this->latte->setTempDirectory(ROOT . '/temp');

        //enable regeneration of the template files
        $this->latte->setAutoRefresh();
        $this->latte->addFilterLoader('\\noirapi\\helpers\\Filters::init');

        $this->latte->addExtension(new Macros());
        /** @noinspection PhpUndefinedClassInspection */
        if(class_exists(\app\lib\Macros::class)) {
            /** @noinspection PhpParamsInspection */
            $this->latte->addExtension(new \app\lib\Macros());
        }

        $layout = Config::get('layout');

        if($layout) {
            $this->setLayout($layout);
        }

        BlueScreenPanel::initialize();

        self::$uri = $request->uri;

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

        $layout = $this->layout_file ?? $this->template;

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

        $params = array_merge($this->params, $params);
        $params = array_merge((array)$this->request, $params);

        return $this->response->setBody($this->latte->renderToString($layout, $params));

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
            $params = array_merge($this->params, $params);
            return $this->latte->renderToString($this->layout_file, $params);
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
     * @param string|null $layout_file
     * @return View
     * @throws FileNotFoundException
     * @noinspection PhpUnused
     */
    public function setLayout(?string $layout_file = null): View {
        if($layout_file === null) {
            $this->layout_file = null;
            return $this;
        }

        $file = PATH_LAYOUTS . $layout_file . self::latte_ext;

        if(is_readable($file)) {
            $this->layout_file = $file;
            return $this;
        }

        throw new FileNotFoundException('Unable to find layout_file: ' . $file);

    }

    /**
     * @param $param
     * @param $page
     * @return string
     * @noinspection PhpUnused
     */
    public static function add_url_var($param, $page): string {

        $params = parse_url(self::$uri, PHP_URL_QUERY);

        if(!empty($params)) {

            parse_str($params, $array);

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
    #[ArrayShape(['layout_file' => "string", 'view' => "string"])]
    public function getRenderInfo(): array {

        return [
            'layout_file'   => !empty($this->layout_file) ? basename($this->layout_file) : 'No layout',
            'view'          => !empty($this->template) ? basename($this->template) : 'No view',
        ];

    }

    /**
     * @return array
     * @noinspection PhpUnused
     *
     * this is used by system panel
     * @noinspection GetSetMethodCorrectnessInspection
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
