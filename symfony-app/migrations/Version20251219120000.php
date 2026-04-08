<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251219120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add taken_at field to photos table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE photos ADD taken_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('COMMENT ON COLUMN photos.taken_at IS \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE photos DROP taken_at');
    }
}
