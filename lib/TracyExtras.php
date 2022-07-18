<?php /** @noinspection PhpUnnecessaryFullyQualifiedNameInspection */
/** @noinspection PhpFullyQualifiedNameUsageInspection */
declare(strict_types=1);

namespace noirapi\lib;

use Tracy\Debugger;

class TracyExtras {

    public function __construct() {

        /** @noinspection PhpUndefinedClassInspection */
        if(class_exists(GitVersionPanel::class)) {
            Debugger::getBar()->addPanel(GitVersionPanel::createDefault());
        }

        if(class_exists(\Filisko\PDOplus\PDO::class) && class_exists(\Filisko\PDOplus\Tracy\BarPanel::class)) {
            foreach(\noirapi\lib\Model::tracyGetPdo() as $driver => $pdo) {
                $panel = new \Filisko\PDOplus\Tracy\BarPanel($pdo);
                $panel->title = $driver;
                Debugger::getBar()->addPanel($panel);
            }
        }


    }

}
