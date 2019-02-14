<?php

namespace DoctrineMigrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160308121137 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $customers = $schema->getTable('customers');
        $customers->addColumn('first_name', 'string');
        $customers->addColumn('last_name', 'string');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $customers = $schema->getTable('customers');
        $customers->dropColumn('first_name');
        $customers->dropColumn('last_name');
    }
}
