<?php

namespace FriskMigrate\Infrastructure\Database;

use DateTime;
use Doctrine\DBAL\Connection;
use PDO;

class ReportQueryRunner
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
     * @dashboard
     *
     * @return mixed
     */
    public function getTotals()
    {
        $queryString = "
            select
              count(*) as count,
              sum(li.price_paid) as value
            from locker_items li
            inner join customers c on li.locker_id = c.locker_id
        ";

        $query = $this->db->query($queryString);

        return $query->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * @dashboard
     *
     * @return array
     */
    public function getProcessedTotals()
    {
        $queryString = "
            select
              sum(case when o.outcome = 'migrated' then 1 else 0 end) as migrated,
              sum(case when o.outcome = 'migrated' then li.price_paid else 0 end) as migrated_value,
              sum(case when o.outcome = 'blacklisted' then 1 else 0 end) as blacklisted,
              sum(case when o.outcome = 'blacklisted' then li.price_paid else 0 end) as blacklisted_value
            from outcomes o
            inner join locker_items li on o.locker_id = li.locker_id and o.isbn = li.isbn
        ";

        $query = $this->db->query($queryString);

        return $query->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * @csv
     *
     * @return array
     */
    public function getPending()
    {
        $queryString = "
            select li.locker_id, li.isbn, li.price_paid, cat.title, p.name
            from locker_items li
              left join catalogue cat on li.isbn = cat.isbn
              left join publishers p on p.id = cat.publisher_id
            where li.locker_id in (
              select locker_id from customers
            )
            and not exists (
              select * from outcomes o where o.locker_id = li.locker_id and o.isbn = li.isbn
            );
        ";

        $query = $this->db->query($queryString);

        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * @csv
     *
     * @param $outcome
     *
     * @return array
     */
    public function getProcessed($outcome)
    {
        $queryString = "
            select o.processed_at, li.locker_id, li.isbn, li.price_paid, c.title, p.name
            from locker_items li
            inner join outcomes o on o.locker_id = li.locker_id and o.isbn = li.isbn
            left join catalogue c on li.isbn = c.isbn
            left join publishers p on p.id = c.publisher_id
            where o.outcome = ?
        ";

        $stmt = $this->db->prepare($queryString);
        $stmt->bindValue(1, $outcome);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * @dashboard
     *
     * @return array
     */
    public function getCustomers()
    {
        $queryString = 'select count(*) from customers';
        $totalCustomers = $this->db->query($queryString)->fetchColumn();

        $queryString = "
          select count(*)
          from customers c
          where (
            (select count(*) from locker_items where locker_items.locker_id = c.locker_id)
            =
            (select count(*) from outcomes o where o.locker_id = c.locker_id)
          )
        ";

        $completedCustomers = $this->db->query($queryString)->fetchColumn();

        return [
            'total'     => $totalCustomers,
            'completed' => $completedCustomers
        ];
    }

    /**
     * @dashboard
     *
     * @return array
     */
    public function getDailyRegistrations()
    {
        $queryString = "
            select CAST(c.added AS DATE), count(distinct(c.id)), count(*) as items, sum(li.price_paid)
            from locker_items li
            inner join customers c on li.locker_id = c.locker_id
            group by CAST(c.added AS DATE)
            order by CAST(c.added AS DATE) DESC
        ";

        $query = $this->db->query($queryString);

        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * @dashboard
     *
     * @return array
     */
    public function getDailyVouchers()
    {
        $queryString = "
            select CAST(v.generated_at AS DATE), count(*), sum(v.value)
            from vouchers v
            group by CAST(v.generated_at AS DATE)
            order by CAST(v.generated_at AS DATE) DESC
        ";

        $query = $this->db->query($queryString);

        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * @csv
     *
     * @param $day
     *
     * @return array
     */
    public function getRegistrationsByDay($day)
    {
        $queryString = "
            select
              CAST(c.added AS DATE),
               c.locker_id as registrations,
               count(*) as items,
               sum(li.price_paid)
            from customers c
            inner join locker_items li on c.locker_id = li.locker_id
            where CAST(c.added AS DATE) = ?
            group by CAST(c.added AS DATE), c.locker_id
        ";

        $stmt = $this->db->prepare($queryString);
        $stmt->bindValue(1, $day);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * @csv
     *
     * @param DateTime $day
     *
     * @return array
     */
    public function getVouchersByDay($day)
    {
        $queryString = "
            select CAST(v.generated_at AS DATE), c.locker_id, v.value
            from vouchers v
            inner join customers c on c.id = v.customer_id
            where CAST(v.generated_at AS DATE) = ?;
        ";

        $stmt = $this->db->prepare($queryString);
        $stmt->bindValue(1, $day);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * @csv
     *
     * @return array
     */
    public function getUnOnboarded()
    {
        $queryString = "
            select distinct(li.locker_id)
            from locker_items li
            left join customers c on li.locker_id = c.locker_id
            where c.id is null
        ";

        $query = $this->db->query($queryString);

        return $query->fetchAll(PDO::FETCH_ASSOC);
    }
}
