<?php

namespace DoctrineMigrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160329131058 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $customers = $schema->createTable('completed_customers');
        $customers->addColumn('locker_id', 'string');
        $customers->addColumn('completed_at', 'datetime');
        $customers->addColumn('handler', 'string');

        $customers->setPrimaryKey(['locker_id']);
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $schema->dropTable('completed_customers');
    }
}
