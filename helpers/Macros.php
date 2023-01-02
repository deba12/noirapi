<?php /** @noinspection UnknownInspectionInspection */
declare(strict_types=1);

namespace noirapi\helpers;

use JetBrains\PhpStorm\Pure;
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
            'breadcrumb'    => [$this, 'breadCrumb'],
            'topCss'        => [$this, 'topCss'],
            'bottomCss'     => [$this, 'bottomCss'],
            'topJs'         => [$this, 'topJs'],
            'bottomJs'      => [$this, 'bottomJs'],
            'active'        => [$this, 'active'],
            'title'         => [$this, 'title'],
        ];
    }

    /**
     * @param Tag $tag
     * @return Node
     * @noinspection PhpUnused
     * @noinspection PhpUnusedParameterInspection
     */
    public function title(Tag $tag): Node {

        return new AuxiliaryNode(
            fn (PrintContext $context) => $context->format('
            if(isset($this->params[\'layout\']->title)) {
                echo " - " . $this->params[\'layout\']->title;
            }')
        );

    }

    /**
     * @param Tag $tag
     * @return Node
     * @noinspection PhpUnused
     */
    public function pager(Tag $tag): Node {

        $file = ROOT . '/app/layouts/pager.latte';

        if(!is_readable($file)) {
            $file = ROOT . '/noirapi/templates/pager.latte';
        }

        $subject = $tag->parser->parseUnquotedStringOrExpression();

        return new AuxiliaryNode(
            fn (PrintContext $context) => $context->format('
                $latte = new Latte\Engine;
                $latte->setTempDirectory(dirname(__DIR__) . \'/temp\');
                $latte->addExtension(new Latte\Essential\RawPhpExtension);
                echo $latte->renderToString(\'%raw\', [%node]);',
                $file,
                $subject
            )
        );

    }

    /**
     * @param Tag $tag
     * @return Node
     * @noinspection PhpUnusedParameterInspection
     */
    public function breadcrumb(Tag $tag): Node {

        $file = ROOT . '/app/layouts/BreadCrumbs.latte';

        if(!is_readable($file)) {
            $file = ROOT . '/noirapi/templates/BreadCrumbs.latte';
        }

        return new AuxiliaryNode(
            fn(PrintContext $context) => $context->format(
                '$this->createTemplate(\'%raw\', [ \'breadcrumbs\' => $this->params[\'layout\']->breadcrumbs ], \'include\')->renderToContentType(\'html\');', $file
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
     * @noinspection JSUnresolvedVariable
     */
    #[Pure]
    public function topJs(Tag $tag): Node {
        return new AuxiliaryNode(
            fn (PrintContext $context) => $context->format('
                foreach($topJs as $js) {
                    if(\str_starts_with($js, \'/\')) {
                        echo "<script type=\"text/javascript\" src=\"$js\"></script>" . PHP_EOL;
                    } else {
                        echo "<script type=\"text/javascript\">$js</script>" . PHP_EOL;
                    }
                }'
            )
        );
    }

    /**
     * @param Tag $tag
     * @return Node
     * @noinspection PhpUnusedParameterInspection
     * @noinspection HtmlUnknownTarget
     * @noinspection JSUnresolvedVariable
     */
    #[Pure]
    public function bottomJs(Tag $tag): Node {
        return new AuxiliaryNode(
            fn (PrintContext $context) => $context->format('
                foreach($bottomJs as $js) {
                    if(\str_starts_with($js, \'/\')) {
                        echo "<script type=\"text/javascript\" src=\"$js\"></script>" . PHP_EOL;
                    } else {
                        echo "<script type=\"text/javascript\">$js</script>" . PHP_EOL;
                    }
                }'
            )
        );
    }

    /**
     * @param Tag $tag
     * @return Node
     * @throws CompileException
     */
    public function active(Tag $tag): Node {

        $tag->expectArguments();
        $res = $tag->parser->parseArguments();

        return new AuxiliaryNode(
            fn (PrintContext $context) => $context->format('

                $active = %node;

                if(count($active) === 1) {
                    if($this->params[\'controller\'] === $active[0]) {
                        echo \'active\';
                    }
                } else {
                    if($this->params[\'controller\'] === $active[0] && $this->params[\'function\'] === $active[1]) {
                        echo \'active\';
                    }
                }',
                $res
            )
        );

    }

}
