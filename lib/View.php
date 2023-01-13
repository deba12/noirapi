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
use noirapi\lib\View\Layout;
use noirapi\Tracy\SystemBarPanel;
use RuntimeException;
use stdClass;
use Tracy\Debugger;
use function count;

class View {

    public Request $request;
    private Response $response;

    private stdClass $params;
    private ?string $template = null;

    public Engine $latte;
    private ?string $layout_file = null;
    private const latte_ext = '.latte';

    private static string $uri;

    public Layout $layout;

    /**
     * View constructor.
     * @param Request $request
     * @param response $response
     * @throws FileNotFoundException
     */
    public function __construct(Request $request, Response $response) {

        $this->request = $request;
        $this->response = $response;
        $this->params = new stdClass();

        $this->latte = new Engine;
        $this->latte->setTempDirectory(ROOT . '/temp');

        //enable regeneration of the template files
        $this->latte->setAutoRefresh();
        $this->latte->addFilterLoader('\\noirapi\\helpers\\Filters::init');

        $this->latte->addExtension(new Macros());
        /**
         * @noinspection PhpUndefinedClassInspection
         * @noinspection RedundantSuppression
         */
        if(class_exists(\app\lib\Macros::class)) {
            /**
             * @noinspection PhpParamsInspection
             * @noinspection RedundantSuppression
             */
            $this->latte->addExtension(new \app\lib\Macros());
        }

        $layout = Config::get('layout');

        if($layout) {
            $this->setLayout($layout);
        }

        BlueScreenPanel::initialize();

        $panel = new SystemBarPanel($this->request, $this);
        Debugger::getBar()->addPanel($panel);

        self::$uri = $request->uri;

        $this->layout = new Layout();

    }

    /**
     * @param array $params
     * @return response
     * @throws FileNotFoundException
     */
    public function display(array $params = []): Response {

        if($this->template === null) {
            $this->setTemplate($this->request->function);
        }

        $layout = $this->layout_file ?? $this->template;

        if(isset($_SERVER['HTTP_X_PJAX'])) {
            $layout = $params['view'];
        }

        $this->mergeParams($this->request, 'request');

        $this->mergeParams($params);

        if(isset($_SESSION['message'])) {
            $this->mergeParams(['message' => $_SESSION['message']]);
            unset($_SESSION['message']);
        }

        $this->mergeParams([
            'template'  => $this->template
        ]);

        $this->mergeParams($this->layout, 'layout');

        return $this->response->setBody($this->latte->renderToString($layout, $this->params));

    }

    /**
     * @param string|null $layout
     * @param string $view
     * @param array $params
     * @return string
     * @throws FileNotFoundException
     */
    public function print(?string $layout, string $view, array $params = []): string {

        $this->setTemplate($view);
        $params['template'] = $this->template;
        $this->mergeParams($params);

        if($layout !== null) {
            $this->setLayout($layout);
            $this->setTemplate($view);

            return $this->latte->renderToString($this->layout_file, $this->params);
        }

        $this->setTemplate($view);

        return $this->latte->renderToString($this->template, $this->params);

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
     * @noinspection GetSetMethodCorrectnessInspection
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
     * @param string $template
     * @param string|null $controller
     * @return bool
     * @noinspection PhpUnused
     */
    public function templateExists(string $template, string $controller = null): bool {
        if($controller === null) {
            $controller = $this->request->controller;
        }

        $file = PATH_VIEWS . $controller . DIRECTORY_SEPARATOR . $template . self::latte_ext;

        return is_readable($file);
    }

    /**
     * @param string $layout
     * @return bool
     * @noinspection PhpUnused
     */
    public function layoutExists(string $layout): bool {
        $file = PATH_LAYOUTS . $layout . self::latte_ext;

        return is_readable($file);
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
    public function getParams(): array {

        return get_object_vars($this);

    }

    /**
     * @param array|object $params
     * @param string|null $namespace
     * @return void
     */
    public function mergeParams(array|object $params, ?string $namespace = null): void {

        if($namespace === null) {

            foreach($params as $key => $value) {
                if(!isset($this->params->$key)) {
                    $this->params->$key = $value;
                } else {
                    throw new RuntimeException("Duplicate key ain view params: $key");
                }
            }

        } else if(!isset($this->params->$namespace)) {
            $this->params->$namespace = $params;
        } else {
            throw new RuntimeException("Duplicate key ain view params: $namespace");
        }

    }

}
