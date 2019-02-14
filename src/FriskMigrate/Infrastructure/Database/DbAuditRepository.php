<?php

namespace FriskMigrate\Infrastructure\Database;

use DateTime;
use Doctrine\DBAL\Connection;
use FriskMigrate\Domain\Audit\AuditItem;
use FriskMigrate\Domain\Audit\AuditRepository;
use FriskMigrate\Domain\Customer\Customer;
use PDO;

class DbAuditRepository implements AuditRepository
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
     * @param Customer $customer
     */
    public function createFrom(Customer $customer)
    {
        $now = new DateTime();

        $this->db->insert('audit', [
            'locker_id' => $customer->getLockerId(),
            'added'     => $now->format('Y-m-d H:i:s')
        ]);
    }

    /**
     * @return AuditItem[]
     */
    public function getAll()
    {
        $rows = $this->db->createQueryBuilder()
            ->select('a.locker_id, c.added as opened, a.added as closed, count(*), sum(price_paid) as value')
            ->from('audit', 'a')
            ->innerJoin('a', 'customers', 'c', 'a.locker_id = c.locker_id')
            ->innerJoin('a', 'outcomes', 'o', 'o.locker_id = a.locker_id')
            ->innerJoin('o', 'locker_items', 'li', 'o.locker_id = li.locker_id and o.isbn = li.isbn')
            ->where("o.outcome = 'blacklisted'")
            ->groupBy('a.locker_id, c.added')
            ->execute()
            ->fetchAll(PDO::FETCH_ASSOC);

        $items = [];
        foreach ($rows as $row) {
            $items[] = new AuditItem(
                $row['locker_id'],
                new DateTime($row['opened']),
                new DateTime($row['closed']),
                $row['count'],
                $row['value']
            );
        }

        return $items;
    }
}
