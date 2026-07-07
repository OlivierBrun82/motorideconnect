<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260707114556 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE user ADD pseudo VARCHAR(100) NOT NULL, ADD avatar VARCHAR(255) DEFAULT NULL, ADD birthdate DATE DEFAULT NULL, ADD about LONGTEXT DEFAULT NULL, ADD phone_number VARCHAR(20) DEFAULT NULL, ADD banned_date DATE DEFAULT NULL, ADD driver_lvl VARCHAR(20) DEFAULT NULL, ADD created_at DATETIME NOT NULL, ADD updated_at DATETIME NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D64986CC499D ON user (pseudo)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX UNIQ_8D93D64986CC499D ON user');
        $this->addSql('ALTER TABLE user DROP pseudo, DROP avatar, DROP birthdate, DROP about, DROP phone_number, DROP banned_date, DROP driver_lvl, DROP created_at, DROP updated_at');
    }
}
