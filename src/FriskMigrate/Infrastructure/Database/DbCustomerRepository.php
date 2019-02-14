<?php

namespace FriskMigrate\Infrastructure\Database;

use DateTime;
use Doctrine\DBAL\Connection;
use FriskMigrate\Domain\Customer\Customer;
use FriskMigrate\Domain\Customer\CustomerName;
use FriskMigrate\Domain\Customer\LockerItem;
use FriskMigrate\Domain\Customer\Repository\CustomerRepository;
use FriskMigrate\Domain\Product\Product;
use FriskMigrate\Domain\Product\Publisher;
use FriskMigrate\Domain\Product\Repository\ProductRepository;
use FriskMigrate\Domain\Voucher\Voucher;
use PDO;

class DbCustomerRepository implements CustomerRepository
{
    /**
     * @var Connection
     */
    private $db;

    /**
     * @var ProductRepository
     */
    private $productRepository;

    /**
     * @param Connection $db
     * @param ProductRepository $productRepository
     */
    public function __construct(Connection $db, ProductRepository $productRepository)
    {
        $this->db = $db;
        $this->productRepository = $productRepository;
    }

    /**
     * @param integer $id
     *
     * @return Customer
     */
    public function getById($id)
    {
        return $this->getCustomer('id', $id);
    }

    /**
     * @param string $email
     *
     * @return Customer
     */
    public function getByEmail($email)
    {
        return $this->getCustomer('email', $email);
    }

    /**
     * @param string $lockerId
     *
     * @return Customer
     */
    public function getByLockerId($lockerId)
    {
        return $this->getCustomer('locker_id', $lockerId);
    }

    /**
     * @param $field
     * @param $value
     *
     * @return Customer
     *
     * @throws \Exception
     */
    private function getCustomer($field, $value)
    {
        $row = $this->db->createQueryBuilder()
            ->select('c.*', 'v.*')
            ->from('customers', 'c')
            ->leftJoin('c', 'vouchers', 'v', 'c.id = v.customer_id')
            ->where('c.' . $field . ' = ?')
            ->setParameter(0, $value)
            ->execute()
            ->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        return $this->buildCustomer($row);
    }

    /**
     * @param Customer $customer
     */
    private function getCustomerLocker(Customer $customer)
    {
        $rows = $this->db->createQueryBuilder()
            ->select(
                'l.locker_id', 'l.isbn', 'l.frisk_order_id', 'l.purchase_date', 'l.price_paid',
                'out.processed_at', 'out.outcome',
                'cat.title',
                'pub.id AS publisher_id', 'pub.name AS publisher_name',
                'b.isbn AS blacklisted',
                'm.to AS mapped_isbn'
            )
            ->from('locker_items', 'l')
            ->leftJoin('l', 'outcomes', 'out', 'out.locker_id = l.locker_id AND out.isbn = l.isbn')
            ->leftJoin('l', 'catalogue', 'cat', 'cat.isbn = l.isbn')
            ->leftJoin('cat', 'publishers', 'pub', 'pub.id = cat.publisher_id')
            ->leftJoin('l', 'blacklist', 'b', 'b.isbn = l.isbn')
            ->leftJoin('l', 'mappings', 'm', 'm.from = l.isbn')
            ->where('l.locker_id = ?')
            ->setParameter(0, $customer->getLockerId())
            ->execute()
            ->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as $row) {
            $product = new Product(
                $row['isbn'],
                $row['title'],
                new Publisher($row['publisher_id'], $row['publisher_name']),
                $this->productRepository->isAvailable($row['mapped_isbn'] ?: $row['isbn']),
                $row['blacklisted'] !== null,
                $row['mapped_isbn']
            );
            $customer->addLockerItem(new LockerItem(
                $row['locker_id'],
                $product,
                $row['frisk_order_id'],
                new DateTime($row['purchase_date']),
                $row['price_paid'],
                ($row['processed_at']) ? new DateTime($row['processed_at']) : null,
                $row['outcome']
            ));
        }
    }

    /**
     * @return array
     */
    public function getAllUnfinished()
    {
        $rows = $this->db->createQueryBuilder()
            ->select('c.*, li.*, o.outcome')
            ->from('customers', 'c')
            ->innerJoin('c', 'locker_items', 'li', 'li.locker_id = c.locker_id')
            ->leftJoin('c', 'outcomes', 'o', 'o.locker_id = c.locker_id and o.isbn = li.isbn')
            ->where('o.outcome is null')
            ->execute()
            ->fetchAll(PDO::FETCH_ASSOC);

        return $rows;
    }

    /**
     * @return Customer
     */
    public function getNextUncompletedCustomer()
    {
        $queryString = '
            select c.*, v.*
            from customers c
            left join vouchers v on v.customer_id = c.id
            where c.locker_id not in (
              select locker_id from completed_customers
            )
            and (
              (select count(*) from locker_items li where c.locker_id = li.locker_id)
                =
              (select count(*) from outcomes o where c.locker_id = o.locker_id)
            )
            order by c.id ASC
            limit 1
        ';

        $stmt = $this->db->prepare($queryString);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        return $this->buildCustomer($row);
    }

    /**
     * @param array $row
     *
     * @return Customer
     */
    private function buildCustomer(array $row)
    {
        $voucher = null;
        if ($row['code']) {
            $voucher = new Voucher(
                new DateTime($row['generated_at']),
                $row['code'],
                $row['value']
            );
        }

        $customer = new Customer(
            $row['id'],
            new CustomerName($row['first_name'], $row['last_name']),
            $row['email'],
            $row['locker_id'],
            $row['existing_ents_customer'],
            new DateTime($row['added']),
            $voucher
        );

        $this->getCustomerLocker($customer);

        return $customer;
    }
    /**
     * @param Customer $customer
     *
     * @return bool
     */
    public function save(Customer $customer)
    {
        $affected = $this->db->insert('customers', [
            'id'         => $customer->getId(),
            'email'      => $customer->getEmail(),
            'locker_id'  => $customer->getLockerId(),
            'first_name' => $customer->getFirstName(),
            'last_name'  => $customer->getLastName(),
            'added'      => date('Y-m-d H:i:s'),
            'existing_ents_customer' => $customer->isExistingEntsCustomer() ? 't' : 'f'
        ]);

        $this->getCustomerLocker($customer);

        return !empty($affected);
    }

    /**
     * @param Customer $customer
     * @param string $handler
     */
    public function setCompleted(Customer $customer, $handler)
    {
        $this->db->insert('completed_customers', [
            'locker_id'    => $customer->getLockerId(),
            'completed_at' => date('Y-m-d H:i:s'),
            'handler'      => $handler
        ]);
    }

    /**
     * @param LockerItem $lockerItem
     *
     * @return bool
     */
    public function saveItemOutcome(LockerItem $lockerItem)
    {
        $affected = $this->db->insert('outcomes', [
            'locker_id'    => $lockerItem->getLockerId(),
            'isbn'         => $lockerItem->getProduct()->getIsbn(),
            'processed_at' => $lockerItem->getProcessedAt()->format('Y-m-d H:i:s'),
            'outcome'      => $lockerItem->getOutcome()
        ]);

        return !empty($affected);
    }

    /**
     * @param Customer $customer
     *
     * @return bool
     */
    public function saveVoucher(Customer $customer)
    {
        $voucher = $customer->getVoucher();

        $affected = $this->db->insert('vouchers', [
            'customer_id'  => $customer->getId(),
            'generated_at' => $voucher->getGeneratedAt()->format('Y-m-d H:i:s'),
            'code'         => $voucher->getCode(),
            'value'        => $voucher->getValue()
        ]);

        return !empty($affected);
    }
}