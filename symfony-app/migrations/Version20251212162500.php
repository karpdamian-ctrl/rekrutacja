<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251212162500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create auth_tokens table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE auth_tokens (
            id SERIAL PRIMARY KEY,
            user_id INTEGER NOT NULL,
            token VARCHAR(255) NOT NULL,
            created_at TIMESTAMP NOT NULL,
            CONSTRAINT fk_auth_tokens_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )');

        $this->addSql('CREATE INDEX idx_auth_tokens_user_id ON auth_tokens(user_id)');
        $this->addSql('CREATE INDEX idx_auth_tokens_token ON auth_tokens(token)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE auth_tokens');
    }
}
