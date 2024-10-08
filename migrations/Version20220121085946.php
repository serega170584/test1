<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220121085946 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE remains (id INT NOT NULL, store_id VARCHAR(32) NOT NULL, article VARCHAR(32) NOT NULL, code_mf VARCHAR(32) DEFAULT NULL, quantity INT DEFAULT NULL, price DOUBLE PRECISION DEFAULT NULL, vital BOOLEAN DEFAULT NULL, barcode VARCHAR(255) DEFAULT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX remains_store ON remains (store_id)');
        $this->addSql('CREATE INDEX remains_updated ON remains (updated_at)');
        $this->addSql('CREATE UNIQUE INDEX unique_remains ON remains (store_id, article)');
        $this->addSql('COMMENT ON COLUMN remains.updated_at IS \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE remains');
    }
}
