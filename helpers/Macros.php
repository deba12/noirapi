<?php /** @noinspection UnknownInspectionInspection */
declare(strict_types=1);

namespace noirapi\helpers;

use Latte\CompileException;
use Latte\Engine;
use Latte\MacroNode;
use Latte\Macros\MacroSet;
use Latte\PhpWriter;
use RuntimeException;

class Macros {

    public function __construct(Engine $latte) {

        $compiler = new MacroSet($latte->getCompiler());
        $compiler->addMacro('pager', [$this, 'pager']);

        /** @noinspection PhpUndefinedNamespaceInspection */
        /** @noinspection PhpUndefinedClassInspection */
        if(class_exists(\app\lib\Macros::class)) {
            /** @noinspection PhpUndefinedNamespaceInspection */
            new \app\lib\Macros($latte);
        }

    }

    /**
     * @param MacroNode $node
     * @param PhpWriter $writer
     * @return string
     * @throws CompileException
     * @noinspection PhpUnused
     */
    public function pager(MacroNode $node, PhpWriter $writer): string {

        if ($node->empty = ($node->args !== '')) {

            return $writer->write('
				$latte = new Latte\Engine;
				$latte->setTempDirectory(dirname(__DIR__) . \'/temp\');
				echo %modify(($latte->render(dirname(__DIR__) . \'/noirapi/templates/pager.latte\', [%node.args])))');

        }

        throw new RuntimeException('No arguments provided');

    }

}
