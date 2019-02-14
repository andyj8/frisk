<?php

namespace FriskMigrate\Infrastructure\Slapi;

use Doctrine\DBAL\Connection;
use FriskMigrate\Domain\Customer\Customer;
use FriskMigrate\Domain\Customer\Repository\LibraryRepository;
use PDO;
use Rhumsaa\Uuid\Uuid;

class SlapiLibraryRepository implements LibraryRepository
{
    /**
     * @var Connection
     */
    private $slapiDb;

    /**
     * @param Connection $slapiDb
     */
    public function __construct(Connection $slapiDb)
    {
        $this->slapiDb = $slapiDb;
    }

    /**
     * @param Customer $customer
     * @param string $isbn
     *
     * @return bool
     */
    public function ownsProduct(Customer $customer, $isbn)
    {
        $row = $this->slapiDb->createQueryBuilder()
            ->select('l.id')
            ->from('library_item', 'l')
            ->where('l.person_id = ?')
            ->andWhere('l.product_sku = ?')
            ->setParameter(0, $customer->getId())
            ->setParameter(1, $isbn)
            ->execute()
            ->fetch(PDO::FETCH_ASSOC);

        return !empty($row);
    }

    /**
     * @param Customer $customer
     * @param string $isbn
     *
     * @return mixed
     */
    public function addProduct(Customer $customer, $isbn)
    {
        $orderId = $this->getFriskOrder($customer);

        if (!$orderId) {
            $orderId = $this->insertOrder($customer);
        }

        $this->slapiDb->insert('line_item', [
            'id'                  => Uuid::uuid4(),
            'basket_id'           => $orderId,
            'product_sku'         => $isbn,
            'cost_price_inc_vat'  => 0,
            'sales_price_inc_vat' => 0,
            'rrp_inc_vat'         => 0,
            'supplier_short_name' => 'frisk',
            'date_added'          => date('Y-m-d H:i:s')
        ]);

        $this->slapiDb->insert('library_item', [
            'person_id'   => $customer->getId(),
            'product_sku' => $isbn,
            'date_added'  => date('Y-m-d H:i:s'),
            'from_frisk'  => 't'
        ]);
    }

    /**
     * @param Customer $customer
     *
     * @return null|integer
     */
    private function getFriskOrder(Customer $customer)
    {
        $row = $this->slapiDb->createQueryBuilder()
            ->select('o.*')
            ->from('orders', 'o')
            ->where('o.person_id = ?')
            ->andWhere('o.from_frisk = true')
            ->setParameter(0, $customer->getId())
            ->execute()
            ->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            return $row['id'];
        }

        return null;
    }

    /**
     * @param Customer $customer
     *
     * @return integer
     */
    private function insertOrder(Customer $customer)
    {
        $orderId = $this->getNextOrderId();

        $this->slapiDb->insert('basket', [
            'id'                        => $orderId,
            'basket_created_time'       => date('Y-m-d H:i:s'),
            'order_state_discriminator' => 'order'
        ]);

        $this->slapiDb->insert('orders', [
            'id'                  => $orderId,
            'person_id'           => $customer->getId(),
            'order_complete_time' => date('Y-m-d H:i:s'),
            'vat_rate'            => 20,
            'discount_inc_vat'    => 0,
            'subtotal_inc_vat'    => 0,
            'total_inc_vat'       => 0,
            'from_frisk'          => 't'
        ]);

        return $orderId;
    }

    /**
     * @return integer
     */
    public function getNextOrderId()
    {
        $sql = "SELECT nextval('order_id_seq')";

        return $this->slapiDb->query($sql)->fetchColumn();
    }
}
