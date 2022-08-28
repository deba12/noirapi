<?php
declare(strict_types=1);

namespace noirapi\Tracy;

use Tracy\IBarPanel;

class GenericPanel implements IBarPanel{

    /**
     * Base64 icon for Tracy panel.
     * @var string
     * @see https://www.flaticon.com/free-icons/barrier
     * @author Freepik.com
     * @license http://file000.flaticon.com/downloads/license/license.pdf
     */
    public string $icon = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAYAAAAf8/9hAAAABHNCSVQICAgIfAhkiAAAAAlwSFlzAAAAbwAAAG8B8aLcQwAAABl0RVh0U29mdHdhcmUAd3d3Lmlua3NjYXBlLm9yZ5vuPBoAAACiSURBVDiN7dI9aoJhEATgZ1+rVEHQLs1XfTYewNKcwltYpEvlFTxD7iEIEm9hZZUqZQgJa+EK9jYWLgwMw+zA/kRmuqXaTd2PgHPVFSaZeeE9WvERRsUb+ivf5HLBFb7xjgUO+MAc28K8tEN5lljjreEPz/jFER2G2GNa2JfWladhh0GU4R+BL7zgKTM/I+K1xtxExAw/FTCuDQzi8Yl3EHACaEg66Cf0iAkAAAAASUVORK5CYII=';

    /**
     * Title
     * @var string
     */
    public string $title = 'Generic Panel';

    /**
     * Title HTML attributes
     * @var string
     */
    public string $title_attributes = 'style="font-size:1.6em"';

    /**
     * Key table cell HTML attributes
     * @var string
     */
    public string $key_attributes = 'style="font-weight:bold;color:#333;font-family:Courier New;font-size:1.1em"';

    /**
     * Key table cell HTML attributes
     * @var string
     */
    public string $value_attributes = 'style="font-weight:bold;color:#555;font-family:Courier New;font-size:1.1em"';

    public array $data;

    public function __construct(string $title, array $data) {
        $this->title = $title;
        $this->data = $data;
    }

    /**
     * @return string
     */
    public function getTab(): string {

        $html = "<img src=\"$this->icon\" alt=\"$this->title\" />&nbsp";
        $html .= $this->title . ' (' . count($this->data) . ')';

        return $html;

    }

    /**
     * @return string
     */
    public function getPanel(): string {

        $html = '<h1 ' . $this->title_attributes . '>' . $this->title . '</h1>';
        $html .= '<div class="tracy-inner tracy-InfoPanel">';
        $html .= '<table class="tracy-sortable">';

        foreach($this->data as $key => $value) {
            $html .= '<tr>';
            $html .= "<td><span $this->key_attributes>$key</span></td>";
            $html .= "<td $this->value_attributes>$value</td>";
            $html .= '</tr>';
        }

        return $html;

    }

}
