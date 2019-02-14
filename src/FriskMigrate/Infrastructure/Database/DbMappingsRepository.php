<?php

namespace FriskMigrate\Infrastructure\Database;

use Doctrine\DBAL\Connection;
use FriskMigrate\Domain\Product\Repository\MappingsRepository;
use PDO;

class DbMappingsRepository implements MappingsRepository
{
    /**
     * @var Connection
     */
    private $db;

    /**
     * @param Connection $db
     */
    public function __construct(Connection $db)
    {
        $this->db = $db;
    }

    /**
     * @return array
     */
    public function getAll()
    {
        return $this->db->createQueryBuilder()
            ->select('m.*', 'c.title', 'p.name')
            ->from('mappings', 'm')
            ->innerJoin('m', 'catalogue', 'c', 'c.isbn = m."from"')
            ->leftJoin('c', 'publishers', 'p', 'c.publisher_id = p.id')
            ->execute()
            ->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * @param array $mappings
     */
    public function bulkSave(array $mappings)
    {
        $sql = 'SELECT "from" FROM mappings WHERE "from" = ?';

        $values = [];
        foreach ($mappings as $from => $to) {
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(1, $from);
            $stmt->execute();
            if (!$stmt->rowCount()) {
                $values[$from] = $to;
            }
        }

        foreach ($values as $from => $to) {
            $sql = 'INSERT INTO mappings ("from", "to") VALUES (?, ?)';
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(1, $from);
            $stmt->bindValue(2, $to);
            $stmt->execute();
        }
    }
}