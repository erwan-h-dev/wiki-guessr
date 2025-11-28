<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251127171444 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE challenge (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, start_page VARCHAR(255) NOT NULL, end_page VARCHAR(255) NOT NULL, difficulty VARCHAR(50) NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE game_session (id INT AUTO_INCREMENT NOT NULL, start_time DATETIME NOT NULL, end_time DATETIME DEFAULT NULL, duration_seconds INT DEFAULT NULL, path JSON NOT NULL, events JSON NOT NULL, completed TINYINT(1) NOT NULL, challenge_id INT NOT NULL, INDEX IDX_4586AAFB98A21AC6 (challenge_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE game_session ADD CONSTRAINT FK_4586AAFB98A21AC6 FOREIGN KEY (challenge_id) REFERENCES challenge (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE game_session DROP FOREIGN KEY FK_4586AAFB98A21AC6');
        $this->addSql('DROP TABLE challenge');
        $this->addSql('DROP TABLE game_session');
    }
}
