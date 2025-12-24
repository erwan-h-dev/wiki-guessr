<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251224083543 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add custom start and end page fields to multiplayer_game table for custom challenges';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE multiplayer_game ADD custom_start_page VARCHAR(255) DEFAULT NULL, ADD custom_end_page VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE multiplayer_game DROP custom_start_page, DROP custom_end_page');
    }
}
