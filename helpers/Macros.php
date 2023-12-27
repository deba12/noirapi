<?php
/**
 * @noinspection HtmlUnknownAttribute
 * @noinspection UnknownInspectionInspection
 */
declare(strict_types=1);

namespace noirapi\helpers;

use Latte\CompileException;
use Latte\Compiler\Node;
use Latte\Compiler\Nodes\AuxiliaryNode;
use Latte\Compiler\PrintContext;
use Latte\Compiler\Tag;
use Latte\Extension;

class Macros extends Extension {

    public function getTags(): array {
        return [
            'pager'         => [$this, 'pager'],
        ];
    }

    /**
     * @param Tag $tag
     * @return Node
     * @noinspection PhpUnused
     * @noinspection PhpUnusedParameterInspection
     */
    public function pager(Tag $tag): Node {

        /** @psalm-suppress UndefinedConstant */
        $file = ROOT . '/app/layouts/pager.latte';

        if(!is_readable($file)) {
            /** @psalm-suppress UndefinedConstant */
            $file = ROOT . '/noirapi/templates/pager.latte';
        }

        return new AuxiliaryNode(
            fn(PrintContext $context) => $context->format('
                if(empty($pager)) {
                    throw new RuntimeException("Pager is not setup");
                }

                if($pager->getPageCount() == 1) { $idxl = 0; $idxr = 0; }
                else if($pager->getPageCount() < 5) {
                    $idxl = round($pager->getPageCount() / $pager->getPage(), 0, PHP_ROUND_HALF_UP) + $pager->getPage();
                    $idxr = $pager->getPageCount()-$pager->getPage();
                }
                else if($pager->getPage() == 1) { $idxl = 0; $idxr = 4; }
                else if($pager->getPage() == 2) { $idxl = 1; $idxr = 3; }
                else if($pager->getPage() == $pager->getLastPage()) { $idxl = 4; $idxr = 0; }
                else if($pager->getPage() == $pager->getLastPage() -1 ) { $idxl = 3; $idxr = 1; }
                else { $idxl = 2; $idxr = 2; }

                $this->createTemplate(\'%raw\', [
                    \'pager\' => $pager,
                    \'idxl\' => $idxl,
                    \'idxr\' => $idxr,
                 ], \'include\')->renderToContentType(\'html\');',
                $file
            )
        );

    }

}
