<?php

namespace DoctrineMigrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160607125828 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $blacklist = $schema->getTable('blacklist');
        $blacklist->removeForeignKey('fk_3b175385cc1cf4e6');

    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $blacklist = $schema->getTable('blacklist');
        $blacklist->addForeignKeyConstraint('catalogue', ['isbn'], ['isbn'], [], 'fk_3b175385cc1cf4e6');
    }
}