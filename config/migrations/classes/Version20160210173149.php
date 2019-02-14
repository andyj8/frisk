<?php

namespace DoctrineMigrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160210173149 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $customers = $schema->createTable('customers');
        $customers->addColumn('id', 'integer');
        $customers->addColumn('locker_id', 'string');
        $customers->addColumn('name', 'string');
        $customers->addColumn('email', 'string');
        $customers->addColumn('added', 'datetime');
        $customers->addColumn('existing_ents_customer', 'boolean');
        $customers->setPrimaryKey(['id']);
        $customers->addUniqueIndex(['locker_id']);
        $customers->addUniqueIndex(['email']);
        $customers->addIndex(['added']);

        $lockerItems = $schema->createTable('locker_items');
        $lockerItems->addColumn('locker_id', 'string');
        $lockerItems->addColumn('isbn', 'string');
        $lockerItems->addColumn('frisk_order_id', 'string');
        $lockerItems->addColumn('purchase_date', 'date');
        $lockerItems->addColumn('price_paid', 'decimal', [
            'precision' => 12,
            'scale' => 2,
            'default' => 0.00
        ]);
        $lockerItems->setPrimaryKey(['locker_id', 'isbn']);

        $outcomes = $schema->createTable('outcomes');
        $outcomes->addColumn('locker_id', 'string');
        $outcomes->addColumn('isbn', 'string');
        $outcomes->addColumn('processed_at', 'datetime', ['NotNull' => false]);
        $outcomes->addColumn('outcome', 'string', ['NotNull' => false]);
        $outcomes->setPrimaryKey(['locker_id', 'isbn']);

        $vouchers = $schema->createTable('vouchers');
        $vouchers->addColumn('customer_id', 'integer');
        $vouchers->addColumn('generated_at', 'datetime');
        $vouchers->addColumn('code', 'string');
        $vouchers->addColumn('value', 'decimal', [
            'precision' => 12,
            'scale' => 2,
            'default' => 0.00
        ]);
        $vouchers->setPrimaryKey(['customer_id']);

        $outcomes->addForeignKeyConstraint('locker_items', ['locker_id', 'isbn'], ['locker_id', 'isbn']);
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $schema->dropTable('customers');
        $schema->dropTable('locker_items');
        $schema->dropTable('outcomes');
        $schema->dropTable('vouchers');
    }
}
