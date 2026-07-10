<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260710093257 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE brand (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(100) NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE comment (id INT AUTO_INCREMENT NOT NULL, message LONGTEXT NOT NULL, created_at DATETIME NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE motorcycle (id INT AUTO_INCREMENT NOT NULL, type VARCHAR(20) NOT NULL, displacement INT NOT NULL, autonomy INT DEFAULT NULL, photo VARCHAR(255) DEFAULT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE ride (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(100) NOT NULL, description_message LONGTEXT DEFAULT NULL, department_code VARCHAR(5) NOT NULL, meeting_datetime DATETIME NOT NULL, start_time TIME NOT NULL, end_time TIME DEFAULT NULL, meeting_place VARCHAR(255) NOT NULL, end_point VARCHAR(255) DEFAULT NULL, distance_km INT DEFAULT NULL, ride_type VARCHAR(20) NOT NULL, pilot_level VARCHAR(20) NOT NULL, capacity INT NOT NULL, statut VARCHAR(20) NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE strikes (id INT AUTO_INCREMENT NOT NULL, reason VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE brand');
        $this->addSql('DROP TABLE comment');
        $this->addSql('DROP TABLE motorcycle');
        $this->addSql('DROP TABLE ride');
        $this->addSql('DROP TABLE strikes');
    }
}
