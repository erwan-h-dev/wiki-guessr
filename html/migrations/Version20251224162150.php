<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251224162150 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE game_session CHANGE updated_at updated_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE multiplayer_participant DROP FOREIGN KEY `FK_C6C7AAA58FE32B32`');
        $this->addSql('DROP INDEX UNIQ_C6C7AAA58FE32B32 ON multiplayer_participant');
        $this->addSql('ALTER TABLE multiplayer_participant DROP game_session_id');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE game_session CHANGE updated_at updated_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL');
        $this->addSql('ALTER TABLE multiplayer_participant ADD game_session_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE multiplayer_participant ADD CONSTRAINT `FK_C6C7AAA58FE32B32` FOREIGN KEY (game_session_id) REFERENCES game_session (id) ON UPDATE NO ACTION ON DELETE SET NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_C6C7AAA58FE32B32 ON multiplayer_participant (game_session_id)');
    }
}
