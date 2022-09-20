<?php

namespace noirapi\Tracy;

use noirapi\lib\Request;
use noirapi\lib\View;
use Tracy\IBarPanel;

class SystemBarPanel implements IBarPanel {

    /**
     * Base64 icon for Tracy panel.
     * @var string
     * @see https://www.flaticon.com/free-icons/barrier
     * @author Freepik.com
     * @license http://file000.flaticon.com/downloads/license/license.pdf
     */
    public string $icon = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAYAAAAf8/9hAAAABHNCSVQICAgIfAhkiAAAAAlwSFlzAAAAbwAAAG8B8aLcQwAAABl0RVh0U29mdHdhcmUAd3d3Lmlua3NjYXBlLm9yZ5vuPBoAAACiSURBVDiN7dI9aoJhEATgZ1+rVEHQLs1XfTYewNKcwltYpEvlFTxD7iEIEm9hZZUqZQgJa+EK9jYWLgwMw+zA/kRmuqXaTd2PgHPVFSaZeeE9WvERRsUb+ivf5HLBFb7xjgUO+MAc28K8tEN5lljjreEPz/jFER2G2GNa2JfWladhh0GU4R+BL7zgKTM/I+K1xtxExAw/FTCuDQzi8Yl3EHACaEg66Cf0iAkAAAAASUVORK5CYII=';
    public string $title = 'System Panel';
    public string $title_attributes = 'style="font-size:1.6em"';
    public string $key_attributes = 'style="font-weight:bold;color:#333;font-family:Courier New;font-size:1.1em"';
    public string $value_attributes = 'style="font-weight:bold;color:#555;font-family:Courier New;font-size:1.1em"';
    public string $value_mod_attributes = 'style="font-weight:bold;color:#A52A2A;font-family:Courier New;font-size:1.1em"';

    private Request $request;
    private View $view;

    public function __construct(Request $request, View $view) {

        $this->request = $request;
        $this->view = $view;

    }

    /**
     * @return string
     */
    public function getTab(): string {

        $html = "<img src=\"$this->icon\" alt=\"$this->title\" />&nbsp";
        $view = $this->view->getRenderInfo();
        $html .= $this->request->method . ' [ ' . $this->request->controller . '=>' . $this->request->function . ' ] || [ ' . $view['layout'] . '->' . $view['view'] . ' ]';

        return $html;

    }

    public function getPanel(): string {

        $html = '<h1 ' . $this->title_attributes . '>' . $this->title . '</h1>';
        $html .= '<div class="tracy-inner tracy-InfoPanel" style="width: 600px;">';
        $html .= $this->cell([
            'System' => [
                'Method'        => $this->request->method,
                'Controller'    => $this->request->controller,
                'Function'      => $this->request->function,
            ]
        ]);

        $html .= $this->cell([
            'Request' => [
                'GET'           => $this->request->get ?? [],
                'POST'          => $this->request->post ?? [],
            ]
        ]);

        $view = array_merge(['template' => $this->view->getRenderInfo()], $this->view->getParams());

        $html .= $this->cell([
            'View' => $view
        ]);
        $html .= '</div>';

        return $html;

    }

    /**
     * @param array|object $data
     * @return string
     * @noinspection DuplicatedCode
     */
    private function cell(array|object $data): string {

        $hidden = [
            'password',
            'key',
            'token',
            'salt',
            'pass'
        ];

        $return = '<table class="tracy-sortable">';

        foreach ($data as $key => $value) {

            if(is_string($value) && in_array($key, $hidden, true)) {
                $value = "<span $this->value_mod_attributes>" . substr($value, 0, 3) . '...' . substr($value, -3) . "</span>";
            } elseif($value === true) {
                $value = "<span $this->value_mod_attributes>true</span>";
            } elseif(is_bool($value) && $value === false) {
                $value = "<span $this->value_mod_attributes>false</span>";
            } elseif($value === null) {
                $value = "<span $this->value_mod_attributes>null</span>";
            }

            $return .= "<tr><td><span $this->key_attributes>$key</span></td><td $this->value_attributes>";

            if (is_array($value) || is_object($value)) {
                $return .= $this->cell($value);
            } else {
                $return .= $value;
            }

            $return .= "</td><tr>";

        }

        $return .= "</tr></table>";

        return $return;

    }

}
