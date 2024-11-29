<?php

declare(strict_types=1);

namespace Noirapi\Lib\Tracy;

use Noirapi\Config;
use RateLimit\ApcuRateLimiter;
use RateLimit\Rate;
use RateLimit\Status;
use Tracy\IBarPanel;

class RateLimitBarPanel implements IBarPanel
{
    /**
     * Base64 icon for a Tracy panel.
     * @var string
     * @see https://www.flaticon.com/free-icons/barrier
     * @author Freepik.com
     * @license http://file000.flaticon.com/downloads/license/license.pdf
     */
    // phpcs:ignore
    public string $icon = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAYAAAAf8/9hAAAABHNCSVQICAgIfAhkiAAAAAlwSFlzAAAAbwAAAG8B8aLcQwAAABl0RVh0U29mdHdhcmUAd3d3Lmlua3NjYXBlLm9yZ5vuPBoAAAE6SURBVDiNxdM7a1RRFAXgb+feyhkEC2WYFE7hMEFQQgIRn9griNha+BMsxT6V/oDB0jYklW1IkUEQBiJioWLho7JQBB9EFLbFPYEUyTgwhQcOp1l7rbX3Xicy0yxnbqbqPYKI6EfEvYgY/KsgIgYF29/voIvz6E0h2ivYLtQRcQtL2MWnKQg+F+GbETFfYxGv0MKDiNhGYoRnBbyMr7iNFbzBFq5UOIEzuIAKD3EJZ4uzVZzGezwv2JOosVHhAzq4iI84gihvH4/xHZfxE1dLKzsYVxjiD14U5h+l6DjW8aUIdPAbR7GJt7gTuI9jOIdHWMvMXwdNLyICN3AX7/BSSeJCcbKYmSbdMvQhFjJz9iQGrmk2MCh9jTLzySEtXNds6BRe42mt2f83jAuuPUGwrcnDHrYV//03/gUvL14HlOkQcQAAAABJRU5ErkJggg==';

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

    private Status $limiter;

    public function __construct(string $title, string $keyPrefix, string $identifier)
    {

        $this->title = $title;

        // phpcs:ignore
        $this->limiter = (new ApcuRateLimiter(Rate::perMinute(Config::get('limits.login_per_minute')), $keyPrefix))->current($identifier);
    }

    public function getTab(): string
    {

        $html = '<img src="' . $this->icon . '" alt="' . $this->title . '" /> ';
        // phpcs:ignore
        $html .= $this->limiter->getIdentifier() . ' => ' . $this->limiter->getRemainingAttempts() . '/' . $this->limiter->getLimit();

        return $html;
    }

    public function getPanel(): string
    {

        $html = '<h1 ' . $this->title_attributes . '>' . $this->title . '</h1>';
        $html .= '<div class="tracy-inner tracy-InfoPanel">';
        $html .= '<table class="tracy-sortable">';

        $html .= '<tr>';
        $html .= '<td><span ' . $this->key_attributes . '>Remain</span></td>';
        $html .= '<td ' . $this->value_attributes . '>' . $this->limiter->getRemainingAttempts() . '</td>';
        $html .= '</tr>';

        $html .= '<tr>';
        $html .= '<td><span ' . $this->key_attributes . '>Limit</span></td>';
        $html .= '<td ' . $this->value_attributes . '>' . $this->limiter->getLimit() . '</td>';
        $html .= '</tr>';

        $html .= '<tr>';
        $html .= '<td><span ' . $this->key_attributes . '>resetAt</span></td>';
        $html .= '<td ' . $this->value_attributes . '>' . $this->limiter->getResetAt()->format('Y-m-d h:i:s') . '</td>';
        $html .= '</tr>';

        $html .= '</table>';

        $html .= '</div>';
        $html .= '</div>';

        return $html;
    }
}
