<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260211194656 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE task ADD CONSTRAINT FK_527EDB25B108249D FOREIGN KEY (culture_id) REFERENCES culture (id)');
        $this->addSql('CREATE INDEX IDX_527EDB25B108249D ON task (culture_id)');
        $this->addSql('ALTER TABLE task_assignment DROP FOREIGN KEY `FK_TASK_ASSIGNMENT_TASK_ID`');
        $this->addSql('DROP INDEX idx_task_assignment_task_id ON task_assignment');
        $this->addSql('CREATE INDEX IDX_2CD60F158DB60186 ON task_assignment (task_id)');
        $this->addSql('ALTER TABLE task_assignment ADD CONSTRAINT `FK_TASK_ASSIGNMENT_TASK_ID` FOREIGN KEY (task_id) REFERENCES task (id_task) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE task DROP FOREIGN KEY FK_527EDB25B108249D');
        $this->addSql('DROP INDEX IDX_527EDB25B108249D ON task');
        $this->addSql('ALTER TABLE task_assignment DROP FOREIGN KEY FK_2CD60F158DB60186');
        $this->addSql('DROP INDEX idx_2cd60f158db60186 ON task_assignment');
        $this->addSql('CREATE INDEX IDX_TASK_ASSIGNMENT_TASK_ID ON task_assignment (task_id)');
        $this->addSql('ALTER TABLE task_assignment ADD CONSTRAINT FK_2CD60F158DB60186 FOREIGN KEY (task_id) REFERENCES task (id_task) ON DELETE CASCADE');
    }
}
