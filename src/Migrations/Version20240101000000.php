<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240101000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Initial schema: lead and demo_run tables';
    }

    public function up(Schema $schema): void
    {
        // Create lead table
        $lead = $schema->createTable('lead');
        $lead->addColumn('id', 'string', ['length' => 36]);
        $lead->addColumn('name', 'string', ['length' => 255]);
        $lead->addColumn('email', 'string', ['length' => 255]);
        $lead->addColumn('message', 'text');
        $lead->addColumn('locale', 'string', ['length' => 5]);
        $cat = $lead->addColumn('category', 'string', ['length' => 20]);
        $cat->setNotnull(false);
        $pri = $lead->addColumn('priority', 'integer');
        $pri->setNotnull(false);
        $lead->addColumn('ip_hash', 'string', ['length' => 64]);
        $lead->addColumn('created_at', 'datetime_immutable');
        $del = $lead->addColumn('delete_after', 'datetime_immutable');
        $del->setNotnull(false);
        $lead->setPrimaryKey(['id']);
        $lead->addIndex(['created_at'], 'idx_lead_created_at');

        // Create demo_run table
        $demoRun = $schema->createTable('demo_run');
        $demoRun->addColumn('id', 'string', ['length' => 36]);
        $demoRun->addColumn('slug', 'string', ['length' => 100]);
        $demoRun->addColumn('status', 'string', ['length' => 20]);
        $dur = $demoRun->addColumn('duration_ms', 'integer');
        $dur->setNotnull(false);
        $demoRun->addColumn('locale', 'string', ['length' => 5]);
        $demoRun->addColumn('request_id', 'string', ['length' => 36]);
        $demoRun->addColumn('created_at', 'datetime_immutable');
        $demoRun->setPrimaryKey(['id']);
        $demoRun->addIndex(['slug', 'created_at'], 'idx_demo_run_slug_created_at');
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable('demo_run');
        $schema->dropTable('lead');
    }
}
