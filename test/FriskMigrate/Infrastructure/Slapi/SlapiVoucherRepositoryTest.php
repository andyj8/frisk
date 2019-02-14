<?php

namespace FriskMigrate\Infrastructure\Slapi;

use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use PDO;
use PHPUnit_Framework_TestCase;
use Mockery as m;

class SlapiVoucherRepositoryTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var Connection
     */
    private $connection;

    public function setUp()
    {
        $params = [
            'host' => '127.0.0.1',
            'port' => '5432',
            'user' => 'vagrant',
            'password' => 'vagrant',
            'dbname' => 'testdb',
            'driver' => 'pdo_pgsql',
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

        $codeGenerator = m::mock('FriskMigrate\Domain\Voucher\Service\CodeGenerator');
        $codeGenerator->shouldReceive('generate')->andReturn('ABC');

        $repo = new SlapiVoucherRepository($this->connection, $codeGenerator);
        $repo->createVoucherFor(5.99);
    }

    public function testCreatesPromotion()
    {
        $promotion = $this->connection->query("SELECT * FROM promotion")->fetch(PDO::FETCH_ASSOC);

        $this->assertEquals('nook', $promotion['family']);

        return $promotion;
    }

    /**
     * @depends testCreatesPromotion
     */
    public function testCreatesCriteria($promotion)
    {
        $stmt = $this->connection->prepare("SELECT * FROM promotion_criteria WHERE promotion_id = ?");
        $stmt->bindValue(1, $promotion['id']);
        $stmt->execute();
        $criteria = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assertEquals('voucher_multi', $criteria['type']);

        $stmt = $this->connection->prepare("SELECT * FROM promotion_voucher_multi WHERE id = ?");
        $stmt->bindValue(1, $criteria['id']);
        $stmt->execute();
        $voucher = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assertEquals('ABC', $voucher['code']);
    }

    /**
     * @depends testCreatesPromotion
     */
    public function testCreatesOutcome($promotion)
    {
        $stmt = $this->connection->prepare("SELECT * FROM promotion_outcome WHERE promotion_id = ?");
        $stmt->bindValue(1, $promotion['id']);
        $stmt->execute();
        $outcome = $stmt->fetch(PDO::FETCH_ASSOC);

        $options = json_decode($outcome['options'], true);
        $this->assertEquals(5.99, $options['amount']);
        $this->assertEquals('discount_amount_basket', $outcome['type']);
    }
}
