<?php

declare(strict_types=1);

namespace CarmeloSantana\CoquiToolkitDokployDatabase\Tool;

use CarmeloSantana\PHPAgents\Contract\ToolInterface;
use CarmeloSantana\PHPAgents\Tool\Tool;
use CarmeloSantana\PHPAgents\Tool\ToolResult;
use CarmeloSantana\PHPAgents\Tool\Parameter\EnumParameter;
use CarmeloSantana\PHPAgents\Tool\Parameter\StringParameter;
use CarmeloSantana\CoquiToolkitDokploy\Runtime\DokployClient;

/**
 * Database-specific backup management for Dokploy.
 *
 * Handles backup policies, manual triggers, and file listing for
 * Postgres, MySQL, MariaDB, and MongoDB databases.
 */
final readonly class DatabaseBackupTool
{
    public function __construct(
        private DokployClient $client,
    ) {}

    public function build(): ToolInterface
    {
        return new Tool(
            name: 'dokploy_db_backup',
            description: 'Manage Dokploy database backups — create backup policies with cron schedules, trigger manual backups, list available backup files, and manage retention for Postgres, MySQL, MariaDB, and MongoDB.',
            parameters: [
                new EnumParameter(
                    'action',
                    'Operation to perform.',
                    values: [
                        'get', 'create', 'update', 'delete',
                        'run', 'list_files',
                    ],
                    required: true,
                ),
                new StringParameter(
                    'backupId',
                    'Backup policy ID (required for get, update, delete, run).',
                    required: false,
                ),
                new StringParameter(
                    'destinationId',
                    'Backup destination ID (required for create; required for list_files).',
                    required: false,
                ),
                new EnumParameter(
                    'databaseType',
                    'Database engine type (required for create, run).',
                    values: ['postgres', 'mariadb', 'mysql', 'mongo'],
                    required: false,
                ),
                new StringParameter(
                    'schedule',
                    'Cron expression for automated backups (e.g. "0 3 * * *" for daily at 3am). Required for create.',
                    required: false,
                ),
                new StringParameter(
                    'prefix',
                    'Backup filename prefix. Required for create.',
                    required: false,
                ),
                new StringParameter(
                    'database',
                    'Specific database name to back up. Required for create.',
                    required: false,
                ),
                new StringParameter(
                    'postgresId',
                    'Postgres database ID (for create — associates backup with a Postgres instance).',
                    required: false,
                ),
                new StringParameter(
                    'mysqlId',
                    'MySQL database ID (for create — associates backup with a MySQL instance).',
                    required: false,
                ),
                new StringParameter(
                    'mariadbId',
                    'MariaDB database ID (for create — associates backup with a MariaDB instance).',
                    required: false,
                ),
                new StringParameter(
                    'mongoId',
                    'MongoDB database ID (for create — associates backup with a MongoDB instance).',
                    required: false,
                ),
                new StringParameter(
                    'serverId',
                    'Server ID (for multi-server setups).',
                    required: false,
                ),
                new StringParameter(
                    'enabled',
                    'Whether the backup schedule is enabled (true/false).',
                    required: false,
                ),
                new StringParameter(
                    'keepLatestCount',
                    'Number of latest backups to retain (for create/update).',
                    required: false,
                ),
                new StringParameter(
                    'serviceName',
                    'Service name (for compose-based database backups).',
                    required: false,
                ),
                new StringParameter(
                    'search',
                    'Search filter for list_files (required for list_files).',
                    required: false,
                ),
            ],
            callback: fn(array $args) => $this->execute($args),
        );
    }

    /** @param array<string, mixed> $args */
    private function execute(array $args): ToolResult
    {
        $action = trim((string) ($args['action'] ?? ''));

        return match ($action) {
            'get' => $this->getBackup($args),
            'create' => $this->createBackup($args),
            'update' => $this->updateBackup($args),
            'delete' => $this->deleteBackup($args),
            'run' => $this->runBackup($args),
            'list_files' => $this->listFiles($args),
            default => ToolResult::error("Unknown action: {$action}"),
        };
    }

    /** @param array<string, mixed> $args */
    private function getBackup(array $args): ToolResult
    {
        $backupId = $this->requireString($args, 'backupId');
        if ($backupId === null) {
            return ToolResult::error('backupId is required for the "get" action.');
        }

        return $this->client->get('backup.one', ['backupId' => $backupId])
            ->toToolResultWith('Database backup policy details:');
    }

    /** @param array<string, mixed> $args */
    private function createBackup(array $args): ToolResult
    {
        $destinationId = $this->requireString($args, 'destinationId');
        if ($destinationId === null) {
            return ToolResult::error('destinationId is required for the "create" action. Use dokploy_destination(action: "list") to find available destinations.');
        }

        $databaseType = $this->requireString($args, 'databaseType');
        if ($databaseType === null) {
            return ToolResult::error('databaseType is required for the "create" action (postgres, mysql, mariadb, or mongo).');
        }

        $schedule = $this->requireString($args, 'schedule');
        if ($schedule === null) {
            return ToolResult::error('schedule is required for the "create" action (cron expression, e.g. "0 3 * * *").');
        }

        $prefix = $this->requireString($args, 'prefix');
        if ($prefix === null) {
            return ToolResult::error('prefix is required for the "create" action (backup filename prefix).');
        }

        $database = $this->requireString($args, 'database');
        if ($database === null) {
            return ToolResult::error('database is required for the "create" action (name of the database to back up).');
        }

        $body = [
            'destinationId' => $destinationId,
            'databaseType' => $databaseType,
            'schedule' => $schedule,
            'prefix' => $prefix,
            'database' => $database,
        ];

        // Database ID association
        foreach (['postgresId', 'mysqlId', 'mariadbId', 'mongoId'] as $idField) {
            $value = $this->optionalString($args, $idField);
            if ($value !== null) {
                $body[$idField] = $value;
            }
        }

        foreach (['serverId', 'serviceName'] as $field) {
            $value = $this->optionalString($args, $field);
            if ($value !== null) {
                $body[$field] = $value;
            }
        }

        $enabled = $this->optionalString($args, 'enabled');
        if ($enabled !== null) {
            $body['enabled'] = ($enabled === 'true' || $enabled === '1');
        }

        $keepLatestCount = $this->optionalString($args, 'keepLatestCount');
        if ($keepLatestCount !== null && is_numeric($keepLatestCount)) {
            $body['keepLatestCount'] = (int) $keepLatestCount;
        }

        return $this->client->post('backup.create', $body)
            ->toToolResultWith('Database backup policy created.');
    }

    /** @param array<string, mixed> $args */
    private function updateBackup(array $args): ToolResult
    {
        $backupId = $this->requireString($args, 'backupId');
        if ($backupId === null) {
            return ToolResult::error('backupId is required for the "update" action.');
        }

        $body = ['backupId' => $backupId];

        foreach (['schedule', 'prefix', 'database', 'destinationId', 'databaseType', 'serviceName'] as $field) {
            $value = $this->optionalString($args, $field);
            if ($value !== null) {
                $body[$field] = $value;
            }
        }

        $enabled = $this->optionalString($args, 'enabled');
        if ($enabled !== null) {
            $body['enabled'] = ($enabled === 'true' || $enabled === '1');
        }

        $keepLatestCount = $this->optionalString($args, 'keepLatestCount');
        if ($keepLatestCount !== null && is_numeric($keepLatestCount)) {
            $body['keepLatestCount'] = (int) $keepLatestCount;
        }

        return $this->client->post('backup.update', $body)
            ->toToolResultWith('Database backup policy updated.');
    }

    /** @param array<string, mixed> $args */
    private function deleteBackup(array $args): ToolResult
    {
        $backupId = $this->requireString($args, 'backupId');
        if ($backupId === null) {
            return ToolResult::error('backupId is required for the "delete" action.');
        }

        return $this->client->post('backup.remove', ['backupId' => $backupId])
            ->toToolResultWith('Database backup policy deleted.');
    }

    /** @param array<string, mixed> $args */
    private function runBackup(array $args): ToolResult
    {
        $backupId = $this->requireString($args, 'backupId');
        if ($backupId === null) {
            return ToolResult::error('backupId is required for the "run" action.');
        }

        $databaseType = $this->requireString($args, 'databaseType');
        if ($databaseType === null) {
            return ToolResult::error('databaseType is required for the "run" action (postgres, mysql, mariadb, or mongo).');
        }

        $endpointMap = [
            'postgres' => 'backup.manualBackupPostgres',
            'mariadb' => 'backup.manualBackupMariadb',
            'mysql' => 'backup.manualBackupMySql',
            'mongo' => 'backup.manualBackupMongo',
        ];

        $endpoint = $endpointMap[$databaseType] ?? null;
        if ($endpoint === null) {
            return ToolResult::error("Unsupported database type for backup: {$databaseType}. Supported: postgres, mysql, mariadb, mongo.");
        }

        return $this->client->post($endpoint, ['backupId' => $backupId])
            ->toToolResultWith("Manual {$databaseType} backup triggered.");
    }

    /** @param array<string, mixed> $args */
    private function listFiles(array $args): ToolResult
    {
        $destinationId = $this->requireString($args, 'destinationId');
        if ($destinationId === null) {
            return ToolResult::error('destinationId is required for the "list_files" action. Use dokploy_destination(action: "list") to find destination IDs.');
        }

        $search = $this->optionalString($args, 'search') ?? '';

        $query = [
            'destinationId' => $destinationId,
            'search' => $search,
        ];

        $serverId = $this->optionalString($args, 'serverId');
        if ($serverId !== null) {
            $query['serverId'] = $serverId;
        }

        return $this->client->get('backup.listBackupFiles', $query)
            ->toToolResultWith('Available backup files:');
    }

    // -- Helpers ----------------------------------------------------------

    /** @param array<string, mixed> $args */
    private function requireString(array $args, string $key): ?string
    {
        $value = trim((string) ($args[$key] ?? ''));
        return $value !== '' ? $value : null;
    }

    /** @param array<string, mixed> $args */
    private function optionalString(array $args, string $key): ?string
    {
        if (!isset($args[$key])) {
            return null;
        }
        $value = trim((string) $args[$key]);
        return $value !== '' ? $value : null;
    }
}
