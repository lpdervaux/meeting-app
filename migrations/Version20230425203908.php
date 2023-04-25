<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230425203908 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Initial migration';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE campus (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_9D0968115E237E06 (name), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE city (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, postal_code VARCHAR(50) NOT NULL, UNIQUE INDEX UNIQ_2D5B02345E237E06 (name), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE location (id INT AUTO_INCREMENT NOT NULL, city_id INT NOT NULL, name VARCHAR(255) NOT NULL, address VARCHAR(255) NOT NULL, latitude DOUBLE PRECISION DEFAULT NULL, longitude DOUBLE PRECISION DEFAULT NULL, UNIQUE INDEX UNIQ_5E9E89CB5E237E06 (name), INDEX IDX_5E9E89CB8BAC62AF (city_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE meetup (id INT AUTO_INCREMENT NOT NULL, location_id INT NOT NULL, campus_id INT NOT NULL, coordinator_id INT NOT NULL, name VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, capacity INT UNSIGNED NOT NULL, start DATETIME NOT NULL COMMENT \'(DC2Type:datetimetz_immutable)\', end DATETIME NOT NULL COMMENT \'(DC2Type:datetimetz_immutable)\', registration_start DATETIME NOT NULL COMMENT \'(DC2Type:datetimetz_immutable)\', registration_end DATETIME NOT NULL COMMENT \'(DC2Type:datetimetz_immutable)\', cancelled TINYINT(1) NOT NULL, cancellation_date DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetimetz_immutable)\', cancellation_reason LONGTEXT DEFAULT NULL, UNIQUE INDEX UNIQ_9377E285E237E06 (name), INDEX IDX_9377E2864D218E (location_id), INDEX IDX_9377E28AF5D55E1 (campus_id), INDEX IDX_9377E28E7877946 (coordinator_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE meetup_user (meetup_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_5A015D64591E2316 (meetup_id), INDEX IDX_5A015D64A76ED395 (user_id), PRIMARY KEY(meetup_id, user_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE role (id INT AUTO_INCREMENT NOT NULL, role VARCHAR(100) NOT NULL, label VARCHAR(100) NOT NULL, description VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_57698A6A57698A6A (role), UNIQUE INDEX UNIQ_57698A6AEA750E8 (label), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, campus_id INT NOT NULL, email VARCHAR(180) NOT NULL, password VARCHAR(255) NOT NULL, nickname VARCHAR(100) NOT NULL, surname VARCHAR(100) NOT NULL, name VARCHAR(100) NOT NULL, phone_number VARCHAR(50) NOT NULL, UNIQUE INDEX UNIQ_8D93D649E7927C74 (email), UNIQUE INDEX UNIQ_8D93D649A188FE64 (nickname), INDEX IDX_8D93D649AF5D55E1 (campus_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user_role (user_id INT NOT NULL, role_id INT NOT NULL, INDEX IDX_2DE8C6A3A76ED395 (user_id), INDEX IDX_2DE8C6A3D60322AC (role_id), PRIMARY KEY(user_id, role_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0FB7336F0 (queue_name), INDEX IDX_75EA56E0E3BD61CE (available_at), INDEX IDX_75EA56E016BA31DB (delivered_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE location ADD CONSTRAINT FK_5E9E89CB8BAC62AF FOREIGN KEY (city_id) REFERENCES city (id)');
        $this->addSql('ALTER TABLE meetup ADD CONSTRAINT FK_9377E2864D218E FOREIGN KEY (location_id) REFERENCES location (id)');
        $this->addSql('ALTER TABLE meetup ADD CONSTRAINT FK_9377E28AF5D55E1 FOREIGN KEY (campus_id) REFERENCES campus (id)');
        $this->addSql('ALTER TABLE meetup ADD CONSTRAINT FK_9377E28E7877946 FOREIGN KEY (coordinator_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE meetup_user ADD CONSTRAINT FK_5A015D64591E2316 FOREIGN KEY (meetup_id) REFERENCES meetup (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE meetup_user ADD CONSTRAINT FK_5A015D64A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user ADD CONSTRAINT FK_8D93D649AF5D55E1 FOREIGN KEY (campus_id) REFERENCES campus (id)');
        $this->addSql('ALTER TABLE user_role ADD CONSTRAINT FK_2DE8C6A3A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_role ADD CONSTRAINT FK_2DE8C6A3D60322AC FOREIGN KEY (role_id) REFERENCES role (id) ON DELETE CASCADE');

        $this->addSql('INSERT INTO role (id, role, label, description) VALUES (NULL, \'ROLE_USER\', \'User\', \'Regular user\')');
        $this->addSql('INSERT INTO role (id, role, label, description) VALUES (NULL, \'ROLE_ADMINISTRATOR\', \'Administrator\', \'Site administrator\')');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE location DROP FOREIGN KEY FK_5E9E89CB8BAC62AF');
        $this->addSql('ALTER TABLE meetup DROP FOREIGN KEY FK_9377E2864D218E');
        $this->addSql('ALTER TABLE meetup DROP FOREIGN KEY FK_9377E28AF5D55E1');
        $this->addSql('ALTER TABLE meetup DROP FOREIGN KEY FK_9377E28E7877946');
        $this->addSql('ALTER TABLE meetup_user DROP FOREIGN KEY FK_5A015D64591E2316');
        $this->addSql('ALTER TABLE meetup_user DROP FOREIGN KEY FK_5A015D64A76ED395');
        $this->addSql('ALTER TABLE user DROP FOREIGN KEY FK_8D93D649AF5D55E1');
        $this->addSql('ALTER TABLE user_role DROP FOREIGN KEY FK_2DE8C6A3A76ED395');
        $this->addSql('ALTER TABLE user_role DROP FOREIGN KEY FK_2DE8C6A3D60322AC');
        $this->addSql('DROP TABLE campus');
        $this->addSql('DROP TABLE city');
        $this->addSql('DROP TABLE location');
        $this->addSql('DROP TABLE meetup');
        $this->addSql('DROP TABLE meetup_user');
        $this->addSql('DROP TABLE role');
        $this->addSql('DROP TABLE user');
        $this->addSql('DROP TABLE user_role');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
