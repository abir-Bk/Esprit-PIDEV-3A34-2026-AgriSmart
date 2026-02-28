<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260221103000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create marketplace messaging conversation and message tables';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("CREATE TABLE marketplace_conversation (id INT AUTO_INCREMENT NOT NULL, produit_id INT NOT NULL, buyer_id INT NOT NULL, seller_id INT NOT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', last_message_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', UNIQUE INDEX uniq_marketplace_conversation (produit_id, buyer_id, seller_id), INDEX IDX_7CD6386DF347EFB (produit_id), INDEX IDX_7CD6386558FBEB14 (buyer_id), INDEX IDX_7CD63866DE9ED98 (seller_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");
        $this->addSql("CREATE TABLE marketplace_message (id INT AUTO_INCREMENT NOT NULL, conversation_id INT NOT NULL, sender_id INT NOT NULL, content LONGTEXT NOT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', INDEX IDX_F71566A38EFD64D0 (conversation_id), INDEX IDX_F71566A3F624B39D (sender_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");
        $this->addSql('ALTER TABLE marketplace_conversation ADD CONSTRAINT FK_7CD6386DF347EFB FOREIGN KEY (produit_id) REFERENCES produit (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE marketplace_conversation ADD CONSTRAINT FK_7CD6386558FBEB14 FOREIGN KEY (buyer_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE marketplace_conversation ADD CONSTRAINT FK_7CD63866DE9ED98 FOREIGN KEY (seller_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE marketplace_message ADD CONSTRAINT FK_F71566A38EFD64D0 FOREIGN KEY (conversation_id) REFERENCES marketplace_conversation (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE marketplace_message ADD CONSTRAINT FK_F71566A3F624B39D FOREIGN KEY (sender_id) REFERENCES users (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE marketplace_message DROP FOREIGN KEY FK_F71566A38EFD64D0');
        $this->addSql('ALTER TABLE marketplace_message DROP FOREIGN KEY FK_F71566A3F624B39D');
        $this->addSql('ALTER TABLE marketplace_conversation DROP FOREIGN KEY FK_7CD6386DF347EFB');
        $this->addSql('ALTER TABLE marketplace_conversation DROP FOREIGN KEY FK_7CD6386558FBEB14');
        $this->addSql('ALTER TABLE marketplace_conversation DROP FOREIGN KEY FK_7CD63866DE9ED98');
        $this->addSql('DROP TABLE marketplace_message');
        $this->addSql('DROP TABLE marketplace_conversation');
    }
}
