<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class VersionDev extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('
            CREATE TABLE user (
                id UUID NOT NULL,
                email VARCHAR(255) NOT NULL,
                password VARCHAR(255) NOT NULL,
                roles JSON NOT NULL,
                authorization_key UUID NOT NULL,
                PRIMARY KEY (id),
                UNIQUE INDEX unique_email (email),
                UNIQUE INDEX unique_authorization_key (authorization_key)
            )
        ');

        $this->addSql('
            CREATE TABLE user_configuration (
                user_id UUID NOT NULL,
                enabled_conversion_formats JSON NOT NULL,
                snap_expiration_days_interval INT UNSIGNED NULL,
                PRIMARY KEY (user_id),
                FOREIGN KEY (user_id) REFERENCES user (id)
            )
        ');

        $this->addSql('
            CREATE TABLE snap (
                id UUID NOT NULL,
                user_id UUID NOT NULL,
                original_filename VARCHAR(255) NOT NULL,
                mime_type VARCHAR(50) NOT NULL,
                creation_date DATETIME NOT NULL,
                last_seen_date DATETIME NULL,
                PRIMARY KEY (id),
                FOREIGN KEY (user_id) REFERENCES user(id),
                INDEX index_user_id (user_id),
                INDEX index_creation_date (creation_date),
                INDEX index_last_seen_date (last_seen_date)
            );
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE snap');
        $this->addSql('DROP TABLE user');
    }
}
