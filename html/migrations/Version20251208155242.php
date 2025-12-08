<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251208155242 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE player (id INT AUTO_INCREMENT NOT NULL, uuid VARCHAR(36) NOT NULL, created_at DATETIME NOT NULL, last_seen_at DATETIME NOT NULL, nickname VARCHAR(100) DEFAULT NULL, UNIQUE INDEX UNIQ_98197A65D17F50A6 (uuid), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE game_session ADD player_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE game_session ADD CONSTRAINT FK_4586AAFB99E6F5DF FOREIGN KEY (player_id) REFERENCES player (id)');
        $this->addSql('CREATE INDEX IDX_4586AAFB99E6F5DF ON game_session (player_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE player');
        $this->addSql('ALTER TABLE game_session DROP FOREIGN KEY FK_4586AAFB99E6F5DF');
        $this->addSql('DROP INDEX IDX_4586AAFB99E6F5DF ON game_session');
        $this->addSql('ALTER TABLE game_session DROP player_id');
    }
}
