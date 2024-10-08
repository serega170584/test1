<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20221227080345 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create table for minimal remains';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
            CREATE TABLE prov_minimal_remains (
                id SERIAL PRIMARY KEY, 
                article VARCHAR(32) NOT NULL, 
                minimal_remain_quantity INT NOT NULL, 
                created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, 
                updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL
            )
        ');
        $this->addSql('CREATE UNIQUE INDEX article_uniq ON prov_minimal_remains (article)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE prov_minimal_remains');
    }
}
