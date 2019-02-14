<?php

namespace FriskMigrate\Infrastructure\Slapi;

use DateTime;
use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use FriskMigrate\Domain\Customer\Customer;
use FriskMigrate\Domain\Customer\CustomerName;
use FriskMigrate\Domain\Product\Product;
use FriskMigrate\Domain\Product\Publisher;
use PDO;
use PHPUnit_Framework_TestCase;
use Mockery as m;

class SlapiLibraryRepositoryTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var Customer
     */
    private $customer;

    public function setUp()
    {
        $params = [
            'host'     => '127.0.0.1',
            'port'     => '5432',
            'user'     => 'vagrant',
            'password' => 'vagrant',
            'dbname'   => 'testdb',
            'driver'   => 'pdo_pgsql',
        ];

        $this->connection = DriverManager::getConnection($params, new Configuration());

        $dsn = sprintf(
            "pgsql:host=%s;user=%s;password=%s;dbname=%s;port=%s",
            $params['host'],
            $params['user'],
            $params['password'],
            $params['dbname'],
            $params['port']
        );

        $pdo = new PDO($dsn);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $sql = file_get_contents(__DIR__ . '/../../../slapi_db.sql');
        $pdo->exec($sql);

        $this->customer = new Customer(1, new CustomerName('f', 'l'), 'email', 'locker_id', new DateTime());

        $publisher = new Publisher('pubid', 'pubname');
        $product = new Product('isbn', 'title', $publisher);

        $repo = new SlapiLibraryRepository($this->connection);
        $repo->addProduct($this->customer, $product);
    }

    public function testAddsItemToNewOrder()
    {
        $order = $this->connection->query("SELECT * FROM orders")->fetch(PDO::FETCH_ASSOC);

        $this->assertEquals(1, $order['person_id']);
        $this->assertEquals(20, $order['vat_rate']);
        $this->assertEquals(0, $order['discount_inc_vat']);
        $this->assertEquals(0, $order['subtotal_inc_vat']);
        $this->assertEquals(0, $order['total_inc_vat']);
        $this->assertEquals(1, $order['from_frisk']);

        $stmt = $this->connection->prepare("SELECT * FROM line_item WHERE basket_id = ?");
        $stmt->bindValue(1, $order['id']);
        $stmt->execute();
        $lineItem = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assertEquals('isbn', $lineItem['product_sku']);
    }

    public function testAddsItemToExistingOrder()
    {
        $publisher = new Publisher('pubid', 'pubname');
        $product = new Product('isbn222', 'title222', $publisher);

        $repo = new SlapiLibraryRepository($this->connection);
        $repo->addProduct($this->customer, $product);

        $order = $this->connection->query("SELECT * FROM orders")->fetch(PDO::FETCH_ASSOC);

        $stmt = $this->connection->prepare("SELECT * FROM line_item WHERE basket_id = ?");
        $stmt->bindValue(1, $order['id']);
        $stmt->execute();
        $lineItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $this->assertCount(2, $lineItems);
        foreach ($lineItems as $lineItem) {
            $this->assertEquals($order['id'], $lineItem['basket_id']);
        }
    }

    public function testAddsItemToLibrary()
    {
        $stmt = $this->connection->prepare("SELECT * FROM library_item WHERE person_id = ?");
        $stmt->bindValue(1, 1);
        $stmt->execute();
        $libraryItem = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assertEquals('isbn', $libraryItem['product_sku']);
        $this->assertEquals(true, $libraryItem['from_frisk']);
    }
}
