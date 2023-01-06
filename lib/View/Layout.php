<?php
declare(strict_types=1);

namespace noirapi\lib\View;

class Layout {

    public string $title        = '';
    public array $breadcrumbs   = [];
    public array $topJs         = [];
    public array $bottomJs      = [];
    public array $topCss        = [];
    public array $bottomCss     = [];
    public array $params        = [];

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
     * @noinspection PhpUnused
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

    /**
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function addParam(string $key, mixed $value): void {
        $this->params[$key] = $value;
    }

    /**
     * @return array
     * @noinspection PhpUnused
     */
    public function getParams(): array {
        return $this->params;
    }

    /**
     * @param string $key
     * @param mixed|null $default
     * @return mixed
     * @noinspection PhpUnused
     */
    public function getParam(string $key, mixed $default = null): mixed {
        return $this->params[$key] ?? $default ?? null;
    }

    /**
     * @param string $key
     * @return bool
     * @noinspection PhpUnused
     */
    public function existParam(string $key): bool {
        return isset($this->params[$key]);
    }

}
