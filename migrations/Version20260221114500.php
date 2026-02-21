<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260221114500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add unread fields to marketplace messages';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE marketplace_message ADD is_read TINYINT(1) DEFAULT 0 NOT NULL, ADD read_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)'");
        $this->addSql('CREATE INDEX IDX_F71566A38C4601F4 ON marketplace_message (is_read)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IDX_F71566A38C4601F4 ON marketplace_message');
        $this->addSql('ALTER TABLE marketplace_message DROP is_read, DROP read_at');
    }
}
