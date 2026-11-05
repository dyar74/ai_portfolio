<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240102000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Drop site_content table (admin feature removed)';
    }

    public function up(Schema $schema): void
    {
        if ($schema->hasTable('site_content')) {
            $schema->dropTable('site_content');
        }
    }

    public function down(Schema $schema): void
    {
        if (!$schema->hasTable('site_content')) {
            $table = $schema->createTable('site_content');
            $table->addColumn('id', 'integer', ['autoincrement' => true]);
            $table->addColumn('locale', 'string', ['length' => 2]);
            $table->addColumn('key', 'string', ['length' => 50]);
            $table->addColumn('value', 'text');
            $updated = $table->addColumn('updated_at', 'datetime_immutable');
            $updated->setNotnull(false);
            $table->setPrimaryKey(['id']);
            $table->addUniqueIndex(['locale', 'key'], 'uniq_site_content_locale_key');
        }
    }
}
