<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251214202000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add likes table and like_counter to photos';
    }

    public function up(Schema $schema): void
    {
        // Add like_counter to photos table
        $this->addSql('ALTER TABLE photos ADD COLUMN like_counter INTEGER DEFAULT 0 NOT NULL');

        // Create likes table - intentionally NO UNIQUE INDEX to allow duplicate likes
        $this->addSql('CREATE TABLE likes (
            id SERIAL PRIMARY KEY,
            user_id INTEGER NOT NULL,
            photo_id INTEGER NOT NULL,
            created_at TIMESTAMP NOT NULL,
            CONSTRAINT fk_likes_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            CONSTRAINT fk_likes_photo FOREIGN KEY (photo_id) REFERENCES photos(id) ON DELETE CASCADE
        )');

        $this->addSql('CREATE INDEX idx_likes_user_id ON likes(user_id)');
        $this->addSql('CREATE INDEX idx_likes_photo_id ON likes(photo_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE likes');
        $this->addSql('ALTER TABLE photos DROP COLUMN like_counter');
    }
}
