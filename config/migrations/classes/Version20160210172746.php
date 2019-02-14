<?php

namespace DoctrineMigrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160210172746 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $catalogue = $schema->createTable('catalogue');
        $catalogue->addColumn('isbn', 'string');
        $catalogue->addColumn('title', 'string');
        $catalogue->addColumn('publisher_id', 'integer');
        $catalogue->setPrimaryKey(['isbn']);

        $publishers = $schema->createTable('publishers');
        $publishers->addColumn('id', 'integer', ['unsigned' => true, 'autoincrement' => true]);
        $publishers->addColumn('name', 'string');
        $publishers->setPrimaryKey(['id']);

        $blacklist = $schema->createTable('blacklist');
        $blacklist->addColumn('isbn', 'string');
        $blacklist->addColumn('reason_id', 'integer');
        $blacklist->addColumn('added', 'datetime');
        $blacklist->setPrimaryKey(['isbn']);

        $blacklistReasons = $schema->createTable('blacklist_reasons');
        $blacklistReasons->addColumn('id', 'integer');
        $blacklistReasons->addColumn('reason', 'string');
        $blacklistReasons->setPrimaryKey(['id']);

        $catalogue->addForeignKeyConstraint('publishers', ['publisher_id'], ['id']);
        $blacklist->addForeignKeyConstraint('catalogue', ['isbn'], ['isbn']);
        $blacklist->addForeignKeyConstraint('blacklist_reasons', ['reason_id'], ['id']);
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $schema->dropTable('catalogue');
        $schema->dropTable('publishers');
        $schema->dropTable('blacklist');
        $schema->dropTable('blacklist_reasons');
    }
}
