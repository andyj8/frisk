<?php

namespace DoctrineMigrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160215162432 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $mappings = $schema->createTable('mappings');
        $mappings->addColumn('from', 'string');
        $mappings->addColumn('to', 'string');
        $mappings->setPrimaryKey(['from']);

        $mappings->addForeignKeyConstraint('catalogue', ['from'], ['isbn']);
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $schema->dropTable('mappings');
    }
}
