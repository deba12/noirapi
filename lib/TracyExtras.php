<?php /** @noinspection PhpUnnecessaryFullyQualifiedNameInspection */
/** @noinspection PhpFullyQualifiedNameUsageInspection */
declare(strict_types=1);

namespace noirapi\lib;

use noirapi\helpers\Curl;
use noirapi\Tracy\CurlBarPanel;
use noirapi\Tracy\PDOBarPanel;
use Tracy\Debugger;

class TracyExtras {

    public function __construct() {

        /** @noinspection PhpUndefinedClassInspection */
        if(class_exists(GitVersionPanel::class)) {
            Debugger::getBar()->addPanel(GitVersionPanel::createDefault());
        }

        foreach(\noirapi\lib\Model::tracyGetPdo() as $driver => $pdo) {
            $panel = new PDOBarPanel($pdo);
            $panel->title = $driver;
            Debugger::getBar()->addPanel($panel);
        }

        Debugger::getBar()->addPanel(new CurlBarPanel());

    }

}
