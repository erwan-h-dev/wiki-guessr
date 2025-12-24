<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251212160059 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE multiplayer_game (id INT AUTO_INCREMENT NOT NULL, code VARCHAR(10) NOT NULL, is_public TINYINT(1) NOT NULL, max_players INT NOT NULL, state VARCHAR(255) NOT NULL, countdown_started_at DATETIME DEFAULT NULL, game_started_at DATETIME DEFAULT NULL, game_ended_at DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, challenge_id INT DEFAULT NULL, creator_id INT DEFAULT NULL, UNIQUE INDEX UNIQ_6A436E7077153098 (code), INDEX IDX_6A436E7098A21AC6 (challenge_id), INDEX IDX_6A436E7061220EA6 (creator_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE multiplayer_participant (id INT AUTO_INCREMENT NOT NULL, is_ready TINYINT(1) NOT NULL, has_finished TINYINT(1) NOT NULL, finish_position INT DEFAULT NULL, finished_at DATETIME DEFAULT NULL, joined_at DATETIME NOT NULL, multiplayer_game_id INT NOT NULL, player_id INT NOT NULL, game_session_id INT DEFAULT NULL, INDEX IDX_C6C7AAA5261BFB09 (multiplayer_game_id), INDEX IDX_C6C7AAA599E6F5DF (player_id), UNIQUE INDEX UNIQ_C6C7AAA58FE32B32 (game_session_id), UNIQUE INDEX unique_player_per_game (multiplayer_game_id, player_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE multiplayer_game ADD CONSTRAINT FK_6A436E7098A21AC6 FOREIGN KEY (challenge_id) REFERENCES challenge (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE multiplayer_game ADD CONSTRAINT FK_6A436E7061220EA6 FOREIGN KEY (creator_id) REFERENCES player (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE multiplayer_participant ADD CONSTRAINT FK_C6C7AAA5261BFB09 FOREIGN KEY (multiplayer_game_id) REFERENCES multiplayer_game (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE multiplayer_participant ADD CONSTRAINT FK_C6C7AAA599E6F5DF FOREIGN KEY (player_id) REFERENCES player (id)');
        $this->addSql('ALTER TABLE multiplayer_participant ADD CONSTRAINT FK_C6C7AAA58FE32B32 FOREIGN KEY (game_session_id) REFERENCES game_session (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE game_session ADD multiplayer_participant_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE game_session ADD CONSTRAINT FK_4586AAFBE81A590D FOREIGN KEY (multiplayer_participant_id) REFERENCES multiplayer_participant (id) ON DELETE SET NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_4586AAFBE81A590D ON game_session (multiplayer_participant_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE multiplayer_game DROP FOREIGN KEY FK_6A436E7098A21AC6');
        $this->addSql('ALTER TABLE multiplayer_game DROP FOREIGN KEY FK_6A436E7061220EA6');
        $this->addSql('ALTER TABLE multiplayer_participant DROP FOREIGN KEY FK_C6C7AAA5261BFB09');
        $this->addSql('ALTER TABLE multiplayer_participant DROP FOREIGN KEY FK_C6C7AAA599E6F5DF');
        $this->addSql('ALTER TABLE multiplayer_participant DROP FOREIGN KEY FK_C6C7AAA58FE32B32');
        $this->addSql('DROP TABLE multiplayer_game');
        $this->addSql('DROP TABLE multiplayer_participant');
        $this->addSql('ALTER TABLE game_session DROP FOREIGN KEY FK_4586AAFBE81A590D');
        $this->addSql('DROP INDEX UNIQ_4586AAFBE81A590D ON game_session');
        $this->addSql('ALTER TABLE game_session DROP multiplayer_participant_id');
    }
}
