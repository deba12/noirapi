<?php /** @noinspection PhpUnnecessaryFullyQualifiedNameInspection */
/** @noinspection PhpFullyQualifiedNameUsageInspection */
declare(strict_types=1);

namespace noirapi\lib;

use noirapi\helpers\Curl;
use noirapi\Tracy\CurlBarPanel;
use noirapi\Tracy\PDOBarPanel;
use Tracy\Debugger;

class TracyExtras {

    private static array $panels = [];

    public function __construct() {

        /** @noinspection PhpUndefinedClassInspection */
        if(class_exists(GitVersionPanel::class) && !isset(self::$panels[ 'git' ])) {
            self::$panels['git'] = true;
            Debugger::getBar()->addPanel(GitVersionPanel::createDefault());
        }

        foreach(\noirapi\lib\Model::tracyGetPdo() as $driver => $pdo) {
            if(!isset(self::$panels[ $driver ])) {
                self::$panels[ $driver ] = true;

                $panel = new PDOBarPanel($pdo);
                $panel->title = $driver;
                Debugger::getBar()->addPanel($panel);
            }
        }

        if(!isset(self::$panels[ 'curl' ])) {
            self::$panels[ 'curl' ] = true;

            $panel = new CurlBarPanel();
            $panel->title = 'Curl';

            Debugger::getBar()->addPanel($panel);
        }

    }

}
