<?php

namespace FriskMigrate\Infrastructure\Slapi;

use Doctrine\DBAL\Connection;
use FriskMigrate\Domain\Product\Repository\ProductRepository;

class SlapiProductRepository implements ProductRepository
{
    /**
     * @var Connection
     */
    private $slapiProductDb;

    /**
     * @param Connection $slapiProductDb
     */
    public function __construct(Connection $slapiProductDb)
    {
        $this->slapiProductDb = $slapiProductDb;
    }

    /**
     * @param $isbn
     *
     * @return bool
     */
    public function isAvailable($isbn)
    {
        $queryString = "
            select product.id from product
            left join book.book b on product.id = b.id
            where sku = ?
            and b.ebook_type is not null
            and b.ebook_type != 'NONE'
            and b.ebook_filesize is not null
        ";

        $stmt = $this->slapiProductDb->prepare($queryString);

        $stmt->bindValue(1, $isbn);
        $stmt->execute();

        return $stmt->rowCount() > 0;
    }
}
