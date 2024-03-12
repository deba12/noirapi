<?php
declare(strict_types=1);

namespace noirapi\Tracy;

use noirapi\lib\View;
use Tracy\IBarPanel;

class SystemBarPanel implements IBarPanel {

    /**
     * Base64 icon for the Tracy panel.
     * @var string
     * @see https://www.flaticon.com/free-icons/barrier
     * @author Freepik.com
     * @license http://file000.flaticon.com/downloads/license/license.pdf
     * @noinspection SpellCheckingInspection
     */
    public string $icon = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAYAAAAf8/9hAAAABHNCSVQICAgIfAhkiAAAAAlwSFlzAAAAbwAAAG8B8aLcQwAAABl0RVh0U29mdHdhcmUAd3d3Lmlua3NjYXBlLm9yZ5vuPBoAAACiSURBVDiN7dI9aoJhEATgZ1+rVEHQLs1XfTYewNKcwltYpEvlFTxD7iEIEm9hZZUqZQgJa+EK9jYWLgwMw+zA/kRmuqXaTd2PgHPVFSaZeeE9WvERRsUb+ivf5HLBFb7xjgUO+MAc28K8tEN5lljjreEPz/jFER2G2GNa2JfWladhh0GU4R+BL7zgKTM/I+K1xtxExAw/FTCuDQzi8Yl3EHACaEg66Cf0iAkAAAAASUVORK5CYII=';
    public string $title = 'System Panel';

    private View $view;

    public function __construct(View $view) {

        $this->view = $view;

    }

    /**
     * @return string
     */
    public function getTab(): string {

        $html = "<img src=\"$this->icon\" alt=\"$this->title\" />&nbsp";
        $template = $this->view->getTemplate() === null ? 'None' : substr($this->view->getTemplate(), strpos($this->view->getTemplate(), '/app'));
        $called =  $this->view->request->controller . '::' . $this->view->request->function;
        if(isset($this->view->getResponse()->initiator_line)) {
            $called .= '::<strong>' . $this->view->getResponse()->initiator_line . '</strong>';
        }
        $html .= $this->view->request->method . '[' . $called . '][' . basename($this->view->getLayout() ?? 'None') . '][' . $template . ']';

        return $html;

    }

    public function getPanel():? string {

        return null;

    }

}
