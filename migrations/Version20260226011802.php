<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260226011802 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE demande ADD score INT DEFAULT NULL');
        $this->addSql('ALTER TABLE location_reservation DROP FOREIGN KEY `FK_6A3180E1CC5668EA`');
        $this->addSql('ALTER TABLE location_reservation DROP FOREIGN KEY `FK_6A3180E1F347EFB`');
        $this->addSql('ALTER TABLE location_reservation CHANGE start_date start_date DATE NOT NULL, CHANGE end_date end_date DATE NOT NULL, CHANGE created_at created_at DATETIME NOT NULL, CHANGE updated_at updated_at DATETIME DEFAULT NULL');
        $this->addSql('DROP INDEX idx_6a3180e1f347efb ON location_reservation');
        $this->addSql('CREATE INDEX IDX_541DAE11F347EFB ON location_reservation (produit_id)');
        $this->addSql('DROP INDEX idx_6a3180e1cc5668ea ON location_reservation');
        $this->addSql('CREATE INDEX IDX_541DAE11D8A38199 ON location_reservation (locataire_id)');
        $this->addSql('ALTER TABLE location_reservation ADD CONSTRAINT `FK_6A3180E1CC5668EA` FOREIGN KEY (locataire_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE location_reservation ADD CONSTRAINT `FK_6A3180E1F347EFB` FOREIGN KEY (produit_id) REFERENCES produit (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE marketplace_conversation DROP FOREIGN KEY `FK_7CD6386558FBEB14`');
        $this->addSql('ALTER TABLE marketplace_conversation DROP FOREIGN KEY `FK_7CD63866DE9ED98`');
        $this->addSql('ALTER TABLE marketplace_conversation DROP FOREIGN KEY `FK_7CD6386DF347EFB`');
        $this->addSql('ALTER TABLE marketplace_conversation CHANGE created_at created_at DATETIME NOT NULL, CHANGE last_message_at last_message_at DATETIME NOT NULL');
        $this->addSql('DROP INDEX idx_7cd6386df347efb ON marketplace_conversation');
        $this->addSql('CREATE INDEX IDX_8C02D0E7F347EFB ON marketplace_conversation (produit_id)');
        $this->addSql('DROP INDEX idx_7cd6386558fbeb14 ON marketplace_conversation');
        $this->addSql('CREATE INDEX IDX_8C02D0E76C755722 ON marketplace_conversation (buyer_id)');
        $this->addSql('DROP INDEX idx_7cd63866de9ed98 ON marketplace_conversation');
        $this->addSql('CREATE INDEX IDX_8C02D0E78DE820D9 ON marketplace_conversation (seller_id)');
        $this->addSql('ALTER TABLE marketplace_conversation ADD CONSTRAINT `FK_7CD6386558FBEB14` FOREIGN KEY (buyer_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE marketplace_conversation ADD CONSTRAINT `FK_7CD63866DE9ED98` FOREIGN KEY (seller_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE marketplace_conversation ADD CONSTRAINT `FK_7CD6386DF347EFB` FOREIGN KEY (produit_id) REFERENCES produit (id) ON DELETE CASCADE');
        $this->addSql('DROP INDEX IDX_F71566A38C4601F4 ON marketplace_message');
        $this->addSql('ALTER TABLE marketplace_message DROP FOREIGN KEY `FK_F71566A38EFD64D0`');
        $this->addSql('ALTER TABLE marketplace_message DROP FOREIGN KEY `FK_F71566A3F624B39D`');
        $this->addSql('ALTER TABLE marketplace_message CHANGE created_at created_at DATETIME NOT NULL, CHANGE read_at read_at DATETIME DEFAULT NULL');
        $this->addSql('DROP INDEX idx_f71566a38efd64d0 ON marketplace_message');
        $this->addSql('CREATE INDEX IDX_525BE3639AC0396 ON marketplace_message (conversation_id)');
        $this->addSql('DROP INDEX idx_f71566a3f624b39d ON marketplace_message');
        $this->addSql('CREATE INDEX IDX_525BE363F624B39D ON marketplace_message (sender_id)');
        $this->addSql('ALTER TABLE marketplace_message ADD CONSTRAINT `FK_F71566A38EFD64D0` FOREIGN KEY (conversation_id) REFERENCES marketplace_conversation (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE marketplace_message ADD CONSTRAINT `FK_F71566A3F624B39D` FOREIGN KEY (sender_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE wishlist_item DROP FOREIGN KEY `FK_5B182E4CA76ED395`');
        $this->addSql('ALTER TABLE wishlist_item DROP FOREIGN KEY `FK_5B182E4CF347EFB`');
        $this->addSql('ALTER TABLE wishlist_item CHANGE created_at created_at DATETIME NOT NULL');
        $this->addSql('DROP INDEX idx_5b182e4ca76ed395 ON wishlist_item');
        $this->addSql('CREATE INDEX IDX_6424F4E8A76ED395 ON wishlist_item (user_id)');
        $this->addSql('DROP INDEX idx_5b182e4cf347efb ON wishlist_item');
        $this->addSql('CREATE INDEX IDX_6424F4E8F347EFB ON wishlist_item (produit_id)');
        $this->addSql('ALTER TABLE wishlist_item ADD CONSTRAINT `FK_5B182E4CA76ED395` FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE wishlist_item ADD CONSTRAINT `FK_5B182E4CF347EFB` FOREIGN KEY (produit_id) REFERENCES produit (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE demande DROP score');
        $this->addSql('ALTER TABLE location_reservation DROP FOREIGN KEY FK_541DAE11F347EFB');
        $this->addSql('ALTER TABLE location_reservation DROP FOREIGN KEY FK_541DAE11D8A38199');
        $this->addSql('ALTER TABLE location_reservation CHANGE start_date start_date DATE NOT NULL COMMENT \'(DC2Type:date_immutable)\', CHANGE end_date end_date DATE NOT NULL COMMENT \'(DC2Type:date_immutable)\', CHANGE created_at created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE updated_at updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('DROP INDEX idx_541dae11f347efb ON location_reservation');
        $this->addSql('CREATE INDEX IDX_6A3180E1F347EFB ON location_reservation (produit_id)');
        $this->addSql('DROP INDEX idx_541dae11d8a38199 ON location_reservation');
        $this->addSql('CREATE INDEX IDX_6A3180E1CC5668EA ON location_reservation (locataire_id)');
        $this->addSql('ALTER TABLE location_reservation ADD CONSTRAINT FK_541DAE11F347EFB FOREIGN KEY (produit_id) REFERENCES produit (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE location_reservation ADD CONSTRAINT FK_541DAE11D8A38199 FOREIGN KEY (locataire_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE marketplace_conversation DROP FOREIGN KEY FK_8C02D0E7F347EFB');
        $this->addSql('ALTER TABLE marketplace_conversation DROP FOREIGN KEY FK_8C02D0E76C755722');
        $this->addSql('ALTER TABLE marketplace_conversation DROP FOREIGN KEY FK_8C02D0E78DE820D9');
        $this->addSql('ALTER TABLE marketplace_conversation CHANGE created_at created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE last_message_at last_message_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('DROP INDEX idx_8c02d0e7f347efb ON marketplace_conversation');
        $this->addSql('CREATE INDEX IDX_7CD6386DF347EFB ON marketplace_conversation (produit_id)');
        $this->addSql('DROP INDEX idx_8c02d0e76c755722 ON marketplace_conversation');
        $this->addSql('CREATE INDEX IDX_7CD6386558FBEB14 ON marketplace_conversation (buyer_id)');
        $this->addSql('DROP INDEX idx_8c02d0e78de820d9 ON marketplace_conversation');
        $this->addSql('CREATE INDEX IDX_7CD63866DE9ED98 ON marketplace_conversation (seller_id)');
        $this->addSql('ALTER TABLE marketplace_conversation ADD CONSTRAINT FK_8C02D0E7F347EFB FOREIGN KEY (produit_id) REFERENCES produit (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE marketplace_conversation ADD CONSTRAINT FK_8C02D0E76C755722 FOREIGN KEY (buyer_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE marketplace_conversation ADD CONSTRAINT FK_8C02D0E78DE820D9 FOREIGN KEY (seller_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE marketplace_message DROP FOREIGN KEY FK_525BE3639AC0396');
        $this->addSql('ALTER TABLE marketplace_message DROP FOREIGN KEY FK_525BE363F624B39D');
        $this->addSql('ALTER TABLE marketplace_message CHANGE created_at created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE read_at read_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE INDEX IDX_F71566A38C4601F4 ON marketplace_message (is_read)');
        $this->addSql('DROP INDEX idx_525be3639ac0396 ON marketplace_message');
        $this->addSql('CREATE INDEX IDX_F71566A38EFD64D0 ON marketplace_message (conversation_id)');
        $this->addSql('DROP INDEX idx_525be363f624b39d ON marketplace_message');
        $this->addSql('CREATE INDEX IDX_F71566A3F624B39D ON marketplace_message (sender_id)');
        $this->addSql('ALTER TABLE marketplace_message ADD CONSTRAINT FK_525BE3639AC0396 FOREIGN KEY (conversation_id) REFERENCES marketplace_conversation (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE marketplace_message ADD CONSTRAINT FK_525BE363F624B39D FOREIGN KEY (sender_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE wishlist_item DROP FOREIGN KEY FK_6424F4E8A76ED395');
        $this->addSql('ALTER TABLE wishlist_item DROP FOREIGN KEY FK_6424F4E8F347EFB');
        $this->addSql('ALTER TABLE wishlist_item CHANGE created_at created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('DROP INDEX idx_6424f4e8a76ed395 ON wishlist_item');
        $this->addSql('CREATE INDEX IDX_5B182E4CA76ED395 ON wishlist_item (user_id)');
        $this->addSql('DROP INDEX idx_6424f4e8f347efb ON wishlist_item');
        $this->addSql('CREATE INDEX IDX_5B182E4CF347EFB ON wishlist_item (produit_id)');
        $this->addSql('ALTER TABLE wishlist_item ADD CONSTRAINT FK_6424F4E8A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE wishlist_item ADD CONSTRAINT FK_6424F4E8F347EFB FOREIGN KEY (produit_id) REFERENCES produit (id) ON DELETE CASCADE');
    }
}
