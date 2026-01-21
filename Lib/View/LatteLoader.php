<?php

declare(strict_types=1);

namespace Noirapi\Lib\View;

use Latte\Loaders\FileLoader;
use Noirapi\Config;
use Override;

class LatteLoader extends FileLoader
{
    /**
     * @param string $fileName
     * @return string
     * @psalm-suppress ParamNameMismatch
     */
    #[Override]
    public function getContent(string $fileName): string
    {
        if (str_starts_with(basename($fileName), '__')) {
            $fileName = Config::getAppRoot() . '/layouts/' . basename($fileName);
        }

        return parent::getContent($fileName);
    }

    /**
     * @param string $file
     * @param string $referringFile
     * @return string
     * @psalm-suppress ParamNameMismatch
     */
    #[Override]
    public function getReferredName(string $file, string $referringFile): string
    {
        if (str_starts_with(basename($file), '__')) {
            $file = Config::getAppRoot() . '/layouts/' . basename($file);
        }

        return parent::getReferredName($file, $referringFile);
    }

    /**
     * @param string $file
     * @return string
     * @psalm-suppress ParamNameMismatch
     */
    #[Override]
    public function getUniqueId(string $file): string
    {
        if (str_starts_with(basename($file), '__')) {
            $file = Config::getAppRoot() . '/layouts/' . basename($file);
        }

        return parent::getUniqueId($file);
    }
}
