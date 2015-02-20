<?php
namespace Bookdown\Content;

class ContentList
{
    protected $items = array();
    protected $contentFactory;

    public function __construct(
        ContentFactory $contentFactory
    ) {
        $this->contentFactory = $contentFactory;
    }

    public function fill($bookdownFile, $name = '', $parent = null, $count = 0)
    {
        $base = $this->getBase($bookdownFile);
        $json = $this->getJson($bookdownFile);

        $content = $this->addContentIndex($json, $base, $name, $parent, $count);
        $parent = $this->getLast();

        $count = 0;
        foreach ($content as $name => $origin) {
            $count ++;
            $origin = $this->fixOrigin($origin, $base);
            if ($this->isJson($origin)) {
                $this->fill($origin, $name, $parent, $count);
            } else {
                $this->addContentItem($name, $origin, $parent, $count);
            }
        }
    }

    public function getItems()
    {
        return $this->items;
    }

    public function getFirst()
    {
        return reset($this->items);
    }

    public function getLast()
    {
        return end($this->items);
    }

    protected function getJson($bookdown)
    {
        $data = file_get_contents($bookdown);
        $json = json_decode($data);

        if (! $json->content) {
            echo "$bookdownFile malformed.";
            exit(1);
        }

        return $json;
    }

    protected function getBase($bookdown)
    {
        return dirname($bookdown) . DIRECTORY_SEPARATOR;
    }

    protected function fixOrigin($origin, $base)
    {
        if (strpos($origin, '://' !== false)) {
            return;
        }

        if ($origin{0} === DIRECTORY_SEPARATOR) {
            return;
        }

        return $base . ltrim($origin, DIRECTORY_SEPARATOR);
    }

    protected function isJson($origin)
    {
        return substr($origin, -5) == '.json';
    }

    protected function addContentItem($name, $origin, $parent, $count)
    {
        $item = $this->contentFactory->newContentItem($name, $origin, null, $parent, $count);
        $this->append($item);
    }

    protected function addContentIndex($json, $base, $name, $parent, $count)
    {
        $content = $json->content;

        $origin = $base . 'index.md';
        if (isset($content->index)) {
            $origin = $content->index;
            unset($content->index);
        }

        $item = $this->contentFactory->newContentIndex($name, $origin, $json->title, $parent, $count);
        $this->append($item);

        return $content;
    }

    protected function append(ContentItem $item)
    {
        $prev = $this->getLast();
        if ($prev) {
            $prev->setNext($item);
            $item->setPrev($prev);
        }

        $this->items[] = $item;
    }
}
