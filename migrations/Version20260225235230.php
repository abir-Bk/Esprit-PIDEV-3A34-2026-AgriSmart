<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260225235230 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        if ($schema->hasTable('admin_notification')) {
            return;
        }

        $this->addSql('CREATE TABLE admin_notification (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, message LONGTEXT NOT NULL, type VARCHAR(50) NOT NULL, link VARCHAR(255) DEFAULT NULL, is_read TINYINT NOT NULL, created_at DATETIME NOT NULL, related_user_id INT DEFAULT NULL, INDEX IDX_C615D42798771930 (related_user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE admin_notification ADD CONSTRAINT FK_C615D42798771930 FOREIGN KEY (related_user_id) REFERENCES users (id)');
    }

    public function down(Schema $schema): void
    {
        if (!$schema->hasTable('admin_notification')) {
            return;
        }

        $this->addSql('ALTER TABLE admin_notification DROP FOREIGN KEY FK_C615D42798771930');
        $this->addSql('DROP TABLE admin_notification');
    }
}
