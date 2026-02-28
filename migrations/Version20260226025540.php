<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260226025540 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE location_reservation (id INT AUTO_INCREMENT NOT NULL, start_date DATE NOT NULL, end_date DATE NOT NULL, days INT NOT NULL, unit_price DOUBLE PRECISION NOT NULL, total_price DOUBLE PRECISION NOT NULL, status VARCHAR(20) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, produit_id INT NOT NULL, locataire_id INT NOT NULL, INDEX IDX_541DAE11F347EFB (produit_id), INDEX IDX_541DAE11D8A38199 (locataire_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE marketplace_conversation (id INT AUTO_INCREMENT NOT NULL, created_at DATETIME NOT NULL, last_message_at DATETIME NOT NULL, produit_id INT NOT NULL, buyer_id INT NOT NULL, seller_id INT NOT NULL, INDEX IDX_8C02D0E7F347EFB (produit_id), INDEX IDX_8C02D0E76C755722 (buyer_id), INDEX IDX_8C02D0E78DE820D9 (seller_id), UNIQUE INDEX uniq_marketplace_conversation (produit_id, buyer_id, seller_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE marketplace_message (id INT AUTO_INCREMENT NOT NULL, content LONGTEXT DEFAULT NULL, audio_path VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL, is_read TINYINT DEFAULT 0 NOT NULL, read_at DATETIME DEFAULT NULL, conversation_id INT NOT NULL, sender_id INT NOT NULL, INDEX IDX_525BE3639AC0396 (conversation_id), INDEX IDX_525BE363F624B39D (sender_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE suivi_tache (id_suivi INT AUTO_INCREMENT NOT NULL, date DATETIME NOT NULL, rendement VARCHAR(255) NOT NULL, problemes LONGTEXT NOT NULL, solution LONGTEXT NOT NULL, image VARCHAR(255) DEFAULT NULL, updated_at DATETIME DEFAULT NULL, id_tache INT NOT NULL, INDEX IDX_308B00567D026145 (id_tache), PRIMARY KEY (id_suivi)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE wishlist_item (id INT AUTO_INCREMENT NOT NULL, created_at DATETIME NOT NULL, user_id INT NOT NULL, produit_id INT NOT NULL, INDEX IDX_6424F4E8A76ED395 (user_id), INDEX IDX_6424F4E8F347EFB (produit_id), UNIQUE INDEX uniq_wishlist_user_product (user_id, produit_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE location_reservation ADD CONSTRAINT FK_541DAE11F347EFB FOREIGN KEY (produit_id) REFERENCES produit (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE location_reservation ADD CONSTRAINT FK_541DAE11D8A38199 FOREIGN KEY (locataire_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE marketplace_conversation ADD CONSTRAINT FK_8C02D0E7F347EFB FOREIGN KEY (produit_id) REFERENCES produit (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE marketplace_conversation ADD CONSTRAINT FK_8C02D0E76C755722 FOREIGN KEY (buyer_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE marketplace_conversation ADD CONSTRAINT FK_8C02D0E78DE820D9 FOREIGN KEY (seller_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE marketplace_message ADD CONSTRAINT FK_525BE3639AC0396 FOREIGN KEY (conversation_id) REFERENCES marketplace_conversation (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE marketplace_message ADD CONSTRAINT FK_525BE363F624B39D FOREIGN KEY (sender_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE suivi_tache ADD CONSTRAINT FK_308B00567D026145 FOREIGN KEY (id_tache) REFERENCES task (id_task) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE wishlist_item ADD CONSTRAINT FK_6424F4E8A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE wishlist_item ADD CONSTRAINT FK_6424F4E8F347EFB FOREIGN KEY (produit_id) REFERENCES produit (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE demande ADD score INT DEFAULT NULL');
        $this->addSql('ALTER TABLE task ADD resume LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE task_assignment ADD CONSTRAINT FK_2CD60F156B20BA36 FOREIGN KEY (worker_id) REFERENCES users (id)');
        $this->addSql('CREATE INDEX IDX_2CD60F156B20BA36 ON task_assignment (worker_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE location_reservation DROP FOREIGN KEY FK_541DAE11F347EFB');
        $this->addSql('ALTER TABLE location_reservation DROP FOREIGN KEY FK_541DAE11D8A38199');
        $this->addSql('ALTER TABLE marketplace_conversation DROP FOREIGN KEY FK_8C02D0E7F347EFB');
        $this->addSql('ALTER TABLE marketplace_conversation DROP FOREIGN KEY FK_8C02D0E76C755722');
        $this->addSql('ALTER TABLE marketplace_conversation DROP FOREIGN KEY FK_8C02D0E78DE820D9');
        $this->addSql('ALTER TABLE marketplace_message DROP FOREIGN KEY FK_525BE3639AC0396');
        $this->addSql('ALTER TABLE marketplace_message DROP FOREIGN KEY FK_525BE363F624B39D');
        $this->addSql('ALTER TABLE suivi_tache DROP FOREIGN KEY FK_308B00567D026145');
        $this->addSql('ALTER TABLE wishlist_item DROP FOREIGN KEY FK_6424F4E8A76ED395');
        $this->addSql('ALTER TABLE wishlist_item DROP FOREIGN KEY FK_6424F4E8F347EFB');
        $this->addSql('DROP TABLE location_reservation');
        $this->addSql('DROP TABLE marketplace_conversation');
        $this->addSql('DROP TABLE marketplace_message');
        $this->addSql('DROP TABLE suivi_tache');
        $this->addSql('DROP TABLE wishlist_item');
        $this->addSql('ALTER TABLE demande DROP score');
        $this->addSql('ALTER TABLE task DROP resume');
        $this->addSql('ALTER TABLE task_assignment DROP FOREIGN KEY FK_2CD60F156B20BA36');
        $this->addSql('DROP INDEX IDX_2CD60F156B20BA36 ON task_assignment');
    }
}
