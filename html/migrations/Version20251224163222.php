<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251224163222 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs

        // First, create a default "Custom Game" challenge for existing games with NULL challenge
        $this->addSql('INSERT IGNORE INTO challenge (name, start_page, end_page, difficulty, modes)
                      VALUES ("Custom Game", "Unknown", "Unknown", "UNKNOWN", "[]")');

        // Get the default challenge ID
        $this->addSql('SET @challenge_id = (SELECT id FROM challenge WHERE name = "Custom Game" LIMIT 1)');

        // Update all games with NULL challenge to use the default
        $this->addSql('UPDATE multiplayer_game SET challenge_id = @challenge_id WHERE challenge_id IS NULL');

        // Remove custom columns and make challenge_id NOT NULL
        $this->addSql('ALTER TABLE multiplayer_game DROP custom_start_page, DROP custom_end_page, MODIFY challenge_id INT NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE multiplayer_game DROP FOREIGN KEY FK_6A436E7098A21AC6');
        $this->addSql('ALTER TABLE multiplayer_game ADD custom_start_page VARCHAR(255) DEFAULT NULL, ADD custom_end_page VARCHAR(255) DEFAULT NULL, CHANGE challenge_id challenge_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE multiplayer_game ADD CONSTRAINT `FK_6A436E7098A21AC6` FOREIGN KEY (challenge_id) REFERENCES challenge (id) ON UPDATE NO ACTION ON DELETE SET NULL');
    }
}
