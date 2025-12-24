<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251224085111 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Migrate mode field to modes JSON array to support multiple modes per challenge';
    }

    public function up(Schema $schema): void
    {
        // Add new modes column
        $this->addSql('ALTER TABLE challenge ADD modes JSON NOT NULL');

        // Migrate existing mode values to modes array
        $this->addSql("UPDATE challenge SET modes = JSON_ARRAY(mode)");

        // Drop old mode column
        $this->addSql('ALTER TABLE challenge DROP mode');
    }

    public function down(Schema $schema): void
    {
        // Add back old mode column
        $this->addSql('ALTER TABLE challenge ADD mode VARCHAR(255) NOT NULL');

        // Migrate first mode from modes array back to mode field
        $this->addSql("UPDATE challenge SET mode = JSON_UNQUOTE(JSON_EXTRACT(modes, '$[0]'))");

        // Drop new modes column
        $this->addSql('ALTER TABLE challenge DROP modes');
    }
}
