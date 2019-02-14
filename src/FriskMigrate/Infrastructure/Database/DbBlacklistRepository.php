<?php

namespace FriskMigrate\Infrastructure\Database;

use Doctrine\DBAL\Connection;
use FriskMigrate\Domain\Product\Repository\BlacklistRepository;
use PDO;

class DbBlacklistRepository implements BlacklistRepository
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
            ->select('b.isbn', 'c.title', 'p.name', 'r.reason', 'b.added')
            ->from('blacklist', 'b')
            ->innerJoin('b', 'blacklist_reasons', 'r', 'b.reason_id = r.id')
            ->innerJoin('b', 'catalogue', 'c', 'c.isbn = b.isbn')
            ->leftJoin('c', 'publishers', 'p', 'c.publisher_id = p.id')
            ->execute()
            ->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * @param array $mappings
     */
    public function bulkSave(array $mappings)
    {
        $values = [];
        foreach ($mappings as $params) {
            $stmt = $this->db->prepare('SELECT isbn FROM blacklist WHERE isbn = ?');
            $stmt->bindValue(1, $params['isbn']);
            $stmt->execute();
            if (!$stmt->rowCount()) {
                $values[] = $params;
            }
        }

        foreach ($values as $insert) {
            $sql = 'INSERT INTO blacklist (isbn, reason_id, added) VALUES (?, ?, ?)';
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(1, $insert['isbn']);
            $stmt->bindValue(2, $insert['reason_id']);
            $stmt->bindValue(3, $insert['added']);
            $stmt->execute();
        }
    }
}
