<?php
declare(strict_types=1);

namespace noirapi\Tracy;

use noirapi\helpers\Curl;
use noirapi\PDO\PDO;
use SqlFormatter;
use Tracy\IBarPanel;

/**
 * @codeCoverageIgnore
 */
class CurlBarPanel implements IBarPanel {
    /**
     * Base64 icon for Tracy panel.
     * @var string
     * @see https://www.flaticon.com/free-icon/http-search-symbol_37387
     * @author Freepik.com
     * @license http://file000.flaticon.com/downloads/license/license.pdf
     */
    public string $icon = '';

    /**
     * Title
     * @var string
     */
    public string $title = 'Curl logger';

    /**
     * Title HTML attributes
     * @var string
     */
    public string $title_attributes = 'style="font-size:1.6em"';

    /**
     * Time table cell HTML attributes
     * @var string
     */
    public string $time_attributes = 'style="font-weight:bold;color:#333;font-family:Courier New;font-size:1.1em"';

    /**
     * Query table cell HTML attributes
     * @var string
     */
    public string $query_attributes = '';

    /**
     * @var Curl
     */
    private string $curl;

    public function __construct() {
        $this->curl = Curl::class;
    }

    /**
     * Get total queries execution time
     * @return string
     */
    protected function getTotalTime(): string {
        return (string) round(array_sum(array_column($this->curl::getLog(), 'time')), 4);
    }

    /**
     * @return string
     */
    public function getTab(): string {

        //$html = '<img src="'.$this->icon.'" alt="Curl Request logger" /> ';
        $html = '';
        $queries = count($this->curl::getLog());

        if ($queries === 0) {
            $html .= 'no requests!';
            return $html;
        }

        if ($queries === 1) {
            $html .= '1 request';
        } else {
            $html .= $queries . ' requests';
        }

        $html .= ' / '.$this->getTotalTime().' ms';

        return $html;

    }

    /**
     * Renders HTML code for custom panel.
     * @return string
     */
    public function getPanel(): string {

        $html = '<h1 '.$this->title_attributes.'>'.$this->title.'</h1>';

        $html .= '<div class="tracy-inner tracy-InfoPanel">';
        if (count($this->curl::getLog()) > 0) {
            $html .= '<table class="tracy-sortable">';
            $html .= '<tr>';
            $html .= '<th>Time(ms)</td>';
            $html .= '<th>Statement</td>';
            $html .= '</tr>';
            foreach ($this->curl::getLog() as $request) {
                $html .= '<tr>';
                $html .= '<td><span '.$this->time_attributes.'>'.round($request['time'], 4).'</span></td>';
                $html .= '<td><i class="fa fa-chevron-right"></i> '. $request['request'] . ' <i class="fa fa-chevron-left"></i> ' . $request['info'] . '</td>';
                $html .= '</tr>';
            }
            $html .= '</table>';
        } else {
            $html .= '<p style="font-size:1.2em;font-weight:bold;padding:10px">No curl requests were executed!</p>';
        }
        $html .= '</div>';

        return $html;

    }

}
