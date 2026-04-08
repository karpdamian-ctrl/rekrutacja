<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251212134104 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create users and photos tables';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE users (
            id SERIAL PRIMARY KEY,
            username VARCHAR(180) NOT NULL UNIQUE,
            email VARCHAR(255) NOT NULL,
            name VARCHAR(255) DEFAULT NULL,
            last_name VARCHAR(255) DEFAULT NULL,
            age INTEGER DEFAULT NULL,
            bio TEXT DEFAULT NULL
        )');

        $this->addSql('CREATE TABLE photos (
            id SERIAL PRIMARY KEY,
            user_id INTEGER NOT NULL,
            image_url TEXT NOT NULL,
            location VARCHAR(255) DEFAULT NULL,
            description TEXT DEFAULT NULL,
            camera VARCHAR(255) DEFAULT NULL,
            CONSTRAINT fk_photos_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )');

        $this->addSql('CREATE INDEX idx_photos_user_id ON photos(user_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE photos');
        $this->addSql('DROP TABLE users');
    }
}
