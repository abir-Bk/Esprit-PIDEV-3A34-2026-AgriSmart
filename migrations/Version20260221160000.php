<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260221160000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create wishlist item table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("CREATE TABLE wishlist_item (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, produit_id INT NOT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', INDEX IDX_5B182E4CA76ED395 (user_id), INDEX IDX_5B182E4CF347EFB (produit_id), UNIQUE INDEX uniq_wishlist_user_product (user_id, produit_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");
        $this->addSql('ALTER TABLE wishlist_item ADD CONSTRAINT FK_5B182E4CA76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE wishlist_item ADD CONSTRAINT FK_5B182E4CF347EFB FOREIGN KEY (produit_id) REFERENCES produit (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE wishlist_item DROP FOREIGN KEY FK_5B182E4CA76ED395');
        $this->addSql('ALTER TABLE wishlist_item DROP FOREIGN KEY FK_5B182E4CF347EFB');
        $this->addSql('DROP TABLE wishlist_item');
    }
}
