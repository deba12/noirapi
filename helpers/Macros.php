<?php /** @noinspection UnknownInspectionInspection */
declare(strict_types=1);

namespace noirapi\helpers;

use JetBrains\PhpStorm\ArrayShape;
use Latte\Compiler\Node;
use Latte\Compiler\Nodes\AuxiliaryNode;
use Latte\Compiler\PrintContext;
use Latte\Compiler\Tag;
use Latte\Extension;

class Macros extends Extension {

    #[ArrayShape(['pager' => "array"])]
    public function getTags(): array {
        return [
            'pager' => [$this, 'pager']
        ];
    }

    /**
     * @param Tag $tag
     * @return Node
     * @noinspection PhpUnused
     * @noinspection PhpUnusedParameterInspection
     */
    public function pager(Tag $tag): Node {

        return new AuxiliaryNode(
            fn(PrintContext $context) => $context->format('
                $latte = new Latte\Engine;
				$latte->setTempDirectory(dirname(__DIR__) . \'/temp\');
				echo %modify(($latte->render(dirname(__DIR__) . \'/noirapi/templates/pager.latte\', [%node.args])))'
            )
        );

    }

}
