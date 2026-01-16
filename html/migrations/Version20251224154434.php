<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251224154434 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE game_session ADD updated_at DATETIME DEFAULT CURRENT_TIMESTAMP');
        // Populate existing records with start_time
        $this->addSql('UPDATE game_session SET updated_at = COALESCE(start_time, NOW())');
        // Alter to NOT NULL after populating data
        $this->addSql('ALTER TABLE game_session MODIFY updated_at DATETIME NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE game_session DROP updated_at');
    }
}
