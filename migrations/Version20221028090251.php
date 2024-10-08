<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20221028090251 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX order_reserves_uniq');
        $this->addSql('CREATE INDEX order_idx ON order_reserves (order_id)');
        $this->addSql('CREATE UNIQUE INDEX order_reserves_uniq ON order_reserves (order_id, store_id, article)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX order_idx');
        $this->addSql('DROP INDEX order_reserves_uniq');
        $this->addSql('CREATE UNIQUE INDEX order_reserves_uniq ON order_reserves (order_id)');
    }
}
