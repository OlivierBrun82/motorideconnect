<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260710120846 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE ride_motorcycle (ride_id INT NOT NULL, motorcycle_id INT NOT NULL, INDEX IDX_C167216302A8A70 (ride_id), INDEX IDX_C167216CCE1540F (motorcycle_id), PRIMARY KEY (ride_id, motorcycle_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE ride_participant (ride_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_F46BF95C302A8A70 (ride_id), INDEX IDX_F46BF95CA76ED395 (user_id), PRIMARY KEY (ride_id, user_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE ride_like (ride_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_E75C75C7302A8A70 (ride_id), INDEX IDX_E75C75C7A76ED395 (user_id), PRIMARY KEY (ride_id, user_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE ride_motorcycle ADD CONSTRAINT FK_C167216302A8A70 FOREIGN KEY (ride_id) REFERENCES ride (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE ride_motorcycle ADD CONSTRAINT FK_C167216CCE1540F FOREIGN KEY (motorcycle_id) REFERENCES motorcycle (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE ride_participant ADD CONSTRAINT FK_F46BF95C302A8A70 FOREIGN KEY (ride_id) REFERENCES ride (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE ride_participant ADD CONSTRAINT FK_F46BF95CA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE ride_like ADD CONSTRAINT FK_E75C75C7302A8A70 FOREIGN KEY (ride_id) REFERENCES ride (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE ride_like ADD CONSTRAINT FK_E75C75C7A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE comment ADD user_id INT NOT NULL, ADD ride_id INT NOT NULL');
        $this->addSql('ALTER TABLE comment ADD CONSTRAINT FK_9474526CA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE comment ADD CONSTRAINT FK_9474526C302A8A70 FOREIGN KEY (ride_id) REFERENCES ride (id)');
        $this->addSql('CREATE INDEX IDX_9474526CA76ED395 ON comment (user_id)');
        $this->addSql('CREATE INDEX IDX_9474526C302A8A70 ON comment (ride_id)');
        $this->addSql('ALTER TABLE motorcycle ADD brand_id INT DEFAULT NULL, ADD user_id INT NOT NULL');
        $this->addSql('ALTER TABLE motorcycle ADD CONSTRAINT FK_21E380E144F5D008 FOREIGN KEY (brand_id) REFERENCES brand (id)');
        $this->addSql('ALTER TABLE motorcycle ADD CONSTRAINT FK_21E380E1A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_21E380E144F5D008 ON motorcycle (brand_id)');
        $this->addSql('CREATE INDEX IDX_21E380E1A76ED395 ON motorcycle (user_id)');
        $this->addSql('ALTER TABLE ride ADD user_id INT NOT NULL');
        $this->addSql('ALTER TABLE ride ADD CONSTRAINT FK_9B3D7CD0A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_9B3D7CD0A76ED395 ON ride (user_id)');
        $this->addSql('ALTER TABLE strikes ADD user_id INT NOT NULL');
        $this->addSql('ALTER TABLE strikes ADD CONSTRAINT FK_8067CA9CA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_8067CA9CA76ED395 ON strikes (user_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE ride_motorcycle DROP FOREIGN KEY FK_C167216302A8A70');
        $this->addSql('ALTER TABLE ride_motorcycle DROP FOREIGN KEY FK_C167216CCE1540F');
        $this->addSql('ALTER TABLE ride_participant DROP FOREIGN KEY FK_F46BF95C302A8A70');
        $this->addSql('ALTER TABLE ride_participant DROP FOREIGN KEY FK_F46BF95CA76ED395');
        $this->addSql('ALTER TABLE ride_like DROP FOREIGN KEY FK_E75C75C7302A8A70');
        $this->addSql('ALTER TABLE ride_like DROP FOREIGN KEY FK_E75C75C7A76ED395');
        $this->addSql('DROP TABLE ride_motorcycle');
        $this->addSql('DROP TABLE ride_participant');
        $this->addSql('DROP TABLE ride_like');
        $this->addSql('ALTER TABLE comment DROP FOREIGN KEY FK_9474526CA76ED395');
        $this->addSql('ALTER TABLE comment DROP FOREIGN KEY FK_9474526C302A8A70');
        $this->addSql('DROP INDEX IDX_9474526CA76ED395 ON comment');
        $this->addSql('DROP INDEX IDX_9474526C302A8A70 ON comment');
        $this->addSql('ALTER TABLE comment DROP user_id, DROP ride_id');
        $this->addSql('ALTER TABLE motorcycle DROP FOREIGN KEY FK_21E380E144F5D008');
        $this->addSql('ALTER TABLE motorcycle DROP FOREIGN KEY FK_21E380E1A76ED395');
        $this->addSql('DROP INDEX IDX_21E380E144F5D008 ON motorcycle');
        $this->addSql('DROP INDEX IDX_21E380E1A76ED395 ON motorcycle');
        $this->addSql('ALTER TABLE motorcycle DROP brand_id, DROP user_id');
        $this->addSql('ALTER TABLE ride DROP FOREIGN KEY FK_9B3D7CD0A76ED395');
        $this->addSql('DROP INDEX IDX_9B3D7CD0A76ED395 ON ride');
        $this->addSql('ALTER TABLE ride DROP user_id');
        $this->addSql('ALTER TABLE strikes DROP FOREIGN KEY FK_8067CA9CA76ED395');
        $this->addSql('DROP INDEX IDX_8067CA9CA76ED395 ON strikes');
        $this->addSql('ALTER TABLE strikes DROP user_id');
    }
}
