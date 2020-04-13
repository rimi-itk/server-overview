<?php

declare(strict_types=1);

/*
 * This file is part of ITK Sites.
 *
 * (c) 2018–2020 ITK Development
 *
 * This source file is subject to the MIT license.
 */

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200408102747 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE website_audience (website_id INT NOT NULL, audience_id VARCHAR(255) NOT NULL, INDEX IDX_2CDADD9518F45C82 (website_id), INDEX IDX_2CDADD95848CC616 (audience_id), PRIMARY KEY(website_id, audience_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE audience (id VARCHAR(255) NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE website_audience ADD CONSTRAINT FK_2CDADD9518F45C82 FOREIGN KEY (website_id) REFERENCES website (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE website_audience ADD CONSTRAINT FK_2CDADD95848CC616 FOREIGN KEY (audience_id) REFERENCES audience (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE website ADD active TINYINT(1) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE website_audience DROP FOREIGN KEY FK_2CDADD95848CC616');
        $this->addSql('DROP TABLE website_audience');
        $this->addSql('DROP TABLE audience');
        $this->addSql('ALTER TABLE website DROP active');
    }
}
