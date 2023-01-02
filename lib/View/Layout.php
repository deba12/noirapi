<?php
declare(strict_types=1);

namespace noirapi\lib\View;

class Layout {

    public string $title = '';
    public array $breadcrumbs = [];
    public array $topJs = [];
    public array $bottomJs = [];
    public array $topCss = [];
    public array $bottomCss = [];

    /**
     * @param string $title
     * @return $this
     * @noinspection PhpUnused
     */
    public function setTitle(string $title): static {
        $this->title = $title;

        return $this;
    }

    /**
     * @param int|string $name
     * @param string|null $url
     * @param bool|null $active
     * @return void
     */
    public function addBreadCrumb(int|string $name, ?string $url = null, ?bool $active = null): void {

        $this->breadcrumbs[] = [
            'name'      => (string)$name,
            'url'       => $url,
            'active'    => $active
        ];

    }

    /**
     * @param string $file
     * @return $this
     * @noinspection PhpUnused
     */
    public function addTopCss(string $file): static {
        $this->topCss[] = $file;

        return $this;
    }

    /**
     * @param string $file
     * @return $this
     * @noinspection PhpUnused
     */
    public function addBottomCss(string $file): static {
        $this->bottomCss[] = $file;

        return $this;
    }

    /**
     * @param string $file
     * @return $this
     * @noinspection PhpUnused
     */
    public function addTopJs(string $file): static {
        $this->topJs[] = $file;
        return $this;
    }

    /**
     * @param string $file
     * @return $this
     * @noinspection PhpUnused
     */
    public function addBottomJs(string $file): static {
        $this->bottomJs[] = $file;
        return $this;
    }

}
