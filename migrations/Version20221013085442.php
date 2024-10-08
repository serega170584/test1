<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20221013085442 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SEQUENCE order_reserves_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE remain_reserves_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        //$this->addSql('CREATE SEQUENCE remains_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE order_reserves (id INT NOT NULL, store_id VARCHAR(32) NOT NULL, article VARCHAR(32) NOT NULL, order_id INT NOT NULL, quantity INT NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX search_idx ON order_reserves (store_id, article, order_id)');
        $this->addSql('CREATE INDEX created_at_idx ON order_reserves (created_at)');
        $this->addSql('CREATE UNIQUE INDEX order_reserves_uniq ON order_reserves (order_id)');
        $this->addSql('CREATE TABLE remain_reserves (id INT NOT NULL, store_id VARCHAR(32) NOT NULL, article VARCHAR(32) NOT NULL, quantity INT NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX updated_at_idx ON remain_reserves (updated_at)');
        $this->addSql('CREATE UNIQUE INDEX remain_reserves_uniq ON remain_reserves (store_id, article)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP SEQUENCE order_reserves_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE remain_reserves_id_seq CASCADE');
        //$this->addSql('DROP SEQUENCE remains_id_seq CASCADE');
        $this->addSql('DROP TABLE order_reserves');
        $this->addSql('DROP TABLE remain_reserves');
    }
}
