<?php

namespace FriskMigrate\Domain\Product;

class Product
{
    /**
     * @var string
     */
    private $isbn;

    /**
     * @var string
     */
    private $title;

    /**
     * @var Publisher
     */
    private $publisher;

    /**
     * @var boolean
     */
    private $isAvailable;

    /**
     * @var boolean
     */
    private $isBlacklisted;

    /**
     * @var string
     */
    private $mappedIsbn;

    /**
     * @param string $isbn
     * @param string $title
     * @param Publisher $publisher
     * @param bool $isAvailable
     * @param bool $isBlacklisted
     * @param string $mappedIsbn
     */
    public function __construct(
        $isbn,
        $title,
        Publisher $publisher,
        $isAvailable = false,
        $isBlacklisted = false,
        $mappedIsbn = null
    ) {
        $this->isbn = $isbn;
        $this->title = $title;
        $this->publisher = $publisher;
        $this->isAvailable = $isAvailable;
        $this->isBlacklisted = $isBlacklisted;
        $this->mappedIsbn = $mappedIsbn;
    }

    /**
     * @return string
     */
    public function getIsbn()
    {
        return $this->isbn;
    }

    /**
     * @return string
     */
    public function getMappedIsbn()
    {
        return $this->mappedIsbn ?: $this->isbn;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @return Publisher
     */
    public function getPublisher()
    {
        return $this->publisher;
    }

    /**
     * @return bool
     */
    public function isPending()
    {
        return !$this->isBlacklisted && !$this->isAvailable;
    }

    /**
     * @return bool
     */
    public function isBlacklisted()
    {
        return $this->isBlacklisted;
    }

    /**
     * @return bool
     */
    public function isAvailable()
    {
        return !$this->isBlacklisted() && $this->isAvailable;
    }
}
