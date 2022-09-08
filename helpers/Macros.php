<?php /** @noinspection UnknownInspectionInspection */
declare(strict_types=1);

namespace noirapi\helpers;

use JetBrains\PhpStorm\Pure;
use Latte\Compiler\Node;
use Latte\Compiler\Nodes\AuxiliaryNode;
use Latte\Compiler\PrintContext;
use Latte\Compiler\Tag;
use Latte\Extension;

class Macros extends Extension {

    public function getTags(): array {
        return [
            'pager'         => [$this, 'pager'],
            'breadcrumb'    => [$this, 'breadCrumb'],
            'topCss'        => [$this, 'topCss'],
            'bottomCss'     => [$this, 'bottomCss'],
            'topJs'         => [$this, 'topJs'],
            'bottomJs'      => [$this, 'bottomJs'],
        ];
    }

    /**
     * @param Tag $tag
     * @return Node
     * @noinspection PhpUnused
     */
    public function pager(Tag $tag): Node {

        $subject = $tag->parser->parseUnquotedStringOrExpression();

        return new AuxiliaryNode(
            fn (PrintContext $context) => $context->format('
                $latte = new Latte\Engine;
                $latte->setTempDirectory(dirname(__DIR__) . \'/temp\');
                $latte->addExtension(new Latte\Essential\RawPhpExtension);
                echo $latte->renderToString(dirname(__DIR__) . \'/noirapi/templates/pager.latte\', [%node]);',
            $subject)
        );

    }

    /**
     * @param Tag $tag
     * @return Node
     * @noinspection PhpUnusedParameterInspection
     */
    public function breadcrumb(Tag $tag): Node {

        $file = ROOT . '/app/layouts/BreadCrumb.latte';

        if(!is_readable($file)) {
            $file = ROOT . '/noirapi/templates/BreadCrumb.latte';
        }

        return new AuxiliaryNode(
            fn(PrintContext $context) => $context->format('
                $latte = new Latte\Engine;
                $latte->setTempDirectory(ROOT . \'/temp\');
                $latte->addExtension(new Latte\Essential\RawPhpExtension);
                $items = [\'items\' => noirapi\helpers\View\BreadCrumb::getItems()];
                echo $latte->renderToString(\'%raw\', $items);', $file
            )
        );

    }

    /**
     * @param Tag $tag
     * @return Node
     * @noinspection PhpUnusedParameterInspection
     * @noinspection HtmlUnknownTarget
     */
    #[Pure]
    public function topCss(Tag $tag): Node {
        return new AuxiliaryNode(
            fn (PrintContext $context) => $context->format('
                foreach($topCss as $css) {
                    echo \'<link rel="stylesheet" href="\' . $css . \'" />\' . PHP_EOL;
                }'
            )
        );
    }

    /**
     * @param Tag $tag
     * @return Node
     * @noinspection PhpUnusedParameterInspection
     * @noinspection HtmlUnknownTarget
     */
    #[Pure]
    public function bottomCss(Tag $tag): Node {
        return new AuxiliaryNode(
            fn (PrintContext $context) => $context->format('
                foreach($bottomCss as $css) {
                    echo \'<link rel="stylesheet" href="\' . $css . \'" />\' . PHP_EOL;
                }'
            )
        );
    }

    /**
     * @param Tag $tag
     * @return Node
     * @noinspection PhpUnusedParameterInspection
     * @noinspection HtmlUnknownTarget
     */
    #[Pure]
    public function topJs(Tag $tag): Node {
        return new AuxiliaryNode(
            fn (PrintContext $context) => $context->format('
                foreach($topJs as $js) {
                    echo \'<script type="text/javascript" src="\' . $js . \'"></script>\' . PHP_EOL;
                }'
            )
        );
    }

    /**
     * @param Tag $tag
     * @return Node
     * @noinspection PhpUnusedParameterInspection
     * @noinspection HtmlUnknownTarget
     */
    #[Pure]
    public function bottomJs(Tag $tag): Node {
        return new AuxiliaryNode(
            fn (PrintContext $context) => $context->format('
                foreach($bottomJs as $js) {
                    echo \'<script type="text/javascript" src="\' . $js . \'"></script>\' . PHP_EOL;
                }'
            )
        );
    }

}
