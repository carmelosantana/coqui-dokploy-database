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
 * Unified database management tool for Dokploy.
 *
 * Supports Postgres, MySQL, MariaDB, MongoDB, and Redis through a single
 * tool with a `type` parameter that selects the API prefix.
 */
final readonly class DatabaseTool
{
    public function __construct(
        private DokployClient $client,
    ) {}

    public function build(): ToolInterface
    {
        return new Tool(
            name: 'dokploy_database',
            description: 'Manage Dokploy databases — Postgres, MySQL, MariaDB, MongoDB, and Redis. Create, update, delete, deploy, start/stop, configure environment variables, external ports, and resource limits.',
            parameters: [
                new EnumParameter(
                    'type',
                    'Database engine type.',
                    values: ['postgres', 'mysql', 'mariadb', 'mongo', 'redis'],
                    required: true,
                ),
                new EnumParameter(
                    'action',
                    'Operation to perform.',
                    values: [
                        'get', 'create', 'update', 'delete',
                        'deploy', 'start', 'stop', 'reload', 'rebuild',
                        'move', 'search', 'env', 'port', 'status',
                    ],
                    required: true,
                ),
                new StringParameter(
                    'databaseId',
                    'Database resource ID (required for all actions except create and search).',
                    required: false,
                ),
                new StringParameter(
                    'name',
                    'Display name for the database (required for create).',
                    required: false,
                ),
                new StringParameter(
                    'appName',
                    'Docker container app name (alphanumeric, dots, hyphens, underscores; max 63 chars). Auto-generated if omitted on create. Required for reload.',
                    required: false,
                ),
                new StringParameter(
                    'environmentId',
                    'Project environment ID (required for create).',
                    required: false,
                ),
                new StringParameter(
                    'databaseName',
                    'Database name within the server (required for create on postgres, mysql, mariadb).',
                    required: false,
                ),
                new StringParameter(
                    'databaseUser',
                    'Database user (required for create on postgres, mysql, mariadb, mongo).',
                    required: false,
                ),
                new StringParameter(
                    'databasePassword',
                    'Database password (required for create).',
                    required: false,
                ),
                new StringParameter(
                    'databaseRootPassword',
                    'Root password (mysql and mariadb only).',
                    required: false,
                ),
                new StringParameter(
                    'dockerImage',
                    'Docker image with tag (e.g. "postgres:18", "mysql:8", "redis:8"). Defaults to engine default.',
                    required: false,
                ),
                new StringParameter(
                    'description',
                    'Human-readable description.',
                    required: false,
                ),
                new StringParameter(
                    'serverId',
                    'Target server ID (multi-server setups).',
                    required: false,
                ),
                new StringParameter(
                    'replicaSets',
                    'Enable MongoDB replica sets (true/false, mongo only).',
                    required: false,
                ),
                new StringParameter(
                    'env',
                    'Environment variables as newline-separated KEY=VALUE pairs (for env action).',
                    required: false,
                ),
                new StringParameter(
                    'externalPort',
                    'External port number to expose, or empty to remove (for port action).',
                    required: false,
                ),
                new EnumParameter(
                    'applicationStatus',
                    'Application status to set (for status action).',
                    values: ['idle', 'running', 'done', 'error'],
                    required: false,
                ),
                new StringParameter(
                    'targetEnvironmentId',
                    'Target environment ID (for move action).',
                    required: false,
                ),
                new StringParameter(
                    'command',
                    'Custom command override for the container.',
                    required: false,
                ),
                new StringParameter(
                    'memoryReservation',
                    'Memory reservation (e.g. "512M").',
                    required: false,
                ),
                new StringParameter(
                    'memoryLimit',
                    'Memory limit (e.g. "1G").',
                    required: false,
                ),
                new StringParameter(
                    'cpuReservation',
                    'CPU reservation (e.g. "0.5").',
                    required: false,
                ),
                new StringParameter(
                    'cpuLimit',
                    'CPU limit (e.g. "1.0").',
                    required: false,
                ),
                new StringParameter(
                    'q',
                    'Free-text search query (for search action).',
                    required: false,
                ),
                new StringParameter(
                    'projectId',
                    'Filter search by project ID.',
                    required: false,
                ),
                new StringParameter(
                    'limit',
                    'Maximum results to return (search, default 20, max 100).',
                    required: false,
                ),
                new StringParameter(
                    'offset',
                    'Result offset for pagination (search, default 0).',
                    required: false,
                ),
            ],
            callback: fn(array $args) => $this->execute($args),
        );
    }

    /** @param array<string, mixed> $args */
    private function execute(array $args): ToolResult
    {
        $type = trim((string) ($args['type'] ?? ''));
        $action = trim((string) ($args['action'] ?? ''));

        if (!in_array($type, ['postgres', 'mysql', 'mariadb', 'mongo', 'redis'], true)) {
            return ToolResult::error("Unknown database type: {$type}");
        }

        return match ($action) {
            'get' => $this->getDatabase($type, $args),
            'create' => $this->createDatabase($type, $args),
            'update' => $this->updateDatabase($type, $args),
            'delete' => $this->deleteDatabase($type, $args),
            'deploy' => $this->deployDatabase($type, $args),
            'start' => $this->startDatabase($type, $args),
            'stop' => $this->stopDatabase($type, $args),
            'reload' => $this->reloadDatabase($type, $args),
            'rebuild' => $this->rebuildDatabase($type, $args),
            'move' => $this->moveDatabase($type, $args),
            'search' => $this->searchDatabases($type, $args),
            'env' => $this->saveEnvironment($type, $args),
            'port' => $this->saveExternalPort($type, $args),
            'status' => $this->changeStatus($type, $args),
            default => ToolResult::error("Unknown action: {$action}"),
        };
    }

    // -- Action handlers --------------------------------------------------

    /** @param array<string, mixed> $args */
    private function getDatabase(string $type, array $args): ToolResult
    {
        $id = $this->requireDatabaseId($type, $args);
        if ($id === null) {
            return $this->missingIdError($type, 'get');
        }

        return $this->client->get(
            "{$type}.one",
            [$this->idFieldName($type) => $id],
        )->toToolResultWith(ucfirst($type) . ' database details:');
    }

    /** @param array<string, mixed> $args */
    private function createDatabase(string $type, array $args): ToolResult
    {
        $name = $this->requireString($args, 'name');
        if ($name === null) {
            return ToolResult::error('name is required for the "create" action.');
        }

        $environmentId = $this->requireString($args, 'environmentId');
        if ($environmentId === null) {
            return ToolResult::error('environmentId is required for the "create" action.');
        }

        $databasePassword = $this->requireString($args, 'databasePassword');
        if ($databasePassword === null) {
            return ToolResult::error('databasePassword is required for the "create" action.');
        }

        $body = [
            'name' => $name,
            'environmentId' => $environmentId,
            'databasePassword' => $databasePassword,
        ];

        // Relational + Mongo require databaseUser
        if (in_array($type, ['postgres', 'mysql', 'mariadb', 'mongo'], true)) {
            $databaseUser = $this->requireString($args, 'databaseUser');
            if ($databaseUser === null) {
                return ToolResult::error("databaseUser is required for creating a {$type} database.");
            }
            $body['databaseUser'] = $databaseUser;
        }

        // Relational DBs require databaseName
        if (in_array($type, ['postgres', 'mysql', 'mariadb'], true)) {
            $databaseName = $this->requireString($args, 'databaseName');
            if ($databaseName === null) {
                return ToolResult::error("databaseName is required for creating a {$type} database.");
            }
            $body['databaseName'] = $databaseName;
        }

        // MySQL and MariaDB optional root password
        if (in_array($type, ['mysql', 'mariadb'], true)) {
            $rootPassword = $this->optionalString($args, 'databaseRootPassword');
            if ($rootPassword !== null) {
                $body['databaseRootPassword'] = $rootPassword;
            }
        }

        // MongoDB optional replica sets
        if ($type === 'mongo') {
            $replicaSets = $this->optionalString($args, 'replicaSets');
            if ($replicaSets !== null) {
                $body['replicaSets'] = ($replicaSets === 'true' || $replicaSets === '1');
            }
        }

        // Common optional fields
        foreach (['appName', 'dockerImage', 'description', 'serverId'] as $field) {
            $value = $this->optionalString($args, $field);
            if ($value !== null) {
                $body[$field] = $value;
            }
        }

        return $this->client->post("{$type}.create", $body)
            ->toToolResultWith(ucfirst($type) . ' database created.');
    }

    /** @param array<string, mixed> $args */
    private function updateDatabase(string $type, array $args): ToolResult
    {
        $id = $this->requireDatabaseId($type, $args);
        if ($id === null) {
            return $this->missingIdError($type, 'update');
        }

        $body = [$this->idFieldName($type) => $id];

        $optionalFields = [
            'name', 'appName', 'databaseName', 'databaseUser', 'databasePassword',
            'databaseRootPassword', 'dockerImage', 'description', 'command',
            'memoryReservation', 'memoryLimit', 'cpuReservation', 'cpuLimit',
        ];

        foreach ($optionalFields as $field) {
            $value = $this->optionalString($args, $field);
            if ($value !== null) {
                $body[$field] = $value;
            }
        }

        // External port as number|null
        $externalPort = $this->optionalString($args, 'externalPort');
        if ($externalPort !== null) {
            $body['externalPort'] = is_numeric($externalPort) ? (int) $externalPort : null;
        }

        $applicationStatus = $this->optionalString($args, 'applicationStatus');
        if ($applicationStatus !== null) {
            $body['applicationStatus'] = $applicationStatus;
        }

        return $this->client->post("{$type}.update", $body)
            ->toToolResultWith(ucfirst($type) . ' database updated.');
    }

    /** @param array<string, mixed> $args */
    private function deleteDatabase(string $type, array $args): ToolResult
    {
        $id = $this->requireDatabaseId($type, $args);
        if ($id === null) {
            return $this->missingIdError($type, 'delete');
        }

        return $this->client->post(
            "{$type}.remove",
            [$this->idFieldName($type) => $id],
        )->toToolResultWith(ucfirst($type) . ' database deleted.');
    }

    /** @param array<string, mixed> $args */
    private function deployDatabase(string $type, array $args): ToolResult
    {
        $id = $this->requireDatabaseId($type, $args);
        if ($id === null) {
            return $this->missingIdError($type, 'deploy');
        }

        return $this->client->post(
            "{$type}.deploy",
            [$this->idFieldName($type) => $id],
        )->toToolResultWith(ucfirst($type) . ' database deployment triggered.');
    }

    /** @param array<string, mixed> $args */
    private function startDatabase(string $type, array $args): ToolResult
    {
        $id = $this->requireDatabaseId($type, $args);
        if ($id === null) {
            return $this->missingIdError($type, 'start');
        }

        return $this->client->post(
            "{$type}.start",
            [$this->idFieldName($type) => $id],
        )->toToolResultWith(ucfirst($type) . ' database started.');
    }

    /** @param array<string, mixed> $args */
    private function stopDatabase(string $type, array $args): ToolResult
    {
        $id = $this->requireDatabaseId($type, $args);
        if ($id === null) {
            return $this->missingIdError($type, 'stop');
        }

        return $this->client->post(
            "{$type}.stop",
            [$this->idFieldName($type) => $id],
        )->toToolResultWith(ucfirst($type) . ' database stopped.');
    }

    /** @param array<string, mixed> $args */
    private function reloadDatabase(string $type, array $args): ToolResult
    {
        $id = $this->requireDatabaseId($type, $args);
        if ($id === null) {
            return $this->missingIdError($type, 'reload');
        }

        $appName = $this->requireString($args, 'appName');
        if ($appName === null) {
            return ToolResult::error('appName is required for the "reload" action.');
        }

        return $this->client->post("{$type}.reload", [
            $this->idFieldName($type) => $id,
            'appName' => $appName,
        ])->toToolResultWith(ucfirst($type) . ' database reloaded.');
    }

    /** @param array<string, mixed> $args */
    private function rebuildDatabase(string $type, array $args): ToolResult
    {
        $id = $this->requireDatabaseId($type, $args);
        if ($id === null) {
            return $this->missingIdError($type, 'rebuild');
        }

        return $this->client->post(
            "{$type}.rebuild",
            [$this->idFieldName($type) => $id],
        )->toToolResultWith(ucfirst($type) . ' database rebuild triggered.');
    }

    /** @param array<string, mixed> $args */
    private function moveDatabase(string $type, array $args): ToolResult
    {
        $id = $this->requireDatabaseId($type, $args);
        if ($id === null) {
            return $this->missingIdError($type, 'move');
        }

        $targetEnvironmentId = $this->requireString($args, 'targetEnvironmentId');
        if ($targetEnvironmentId === null) {
            return ToolResult::error('targetEnvironmentId is required for the "move" action.');
        }

        return $this->client->post("{$type}.move", [
            $this->idFieldName($type) => $id,
            'targetEnvironmentId' => $targetEnvironmentId,
        ])->toToolResultWith(ucfirst($type) . ' database moved.');
    }

    /** @param array<string, mixed> $args */
    private function searchDatabases(string $type, array $args): ToolResult
    {
        $query = [];

        foreach (['q', 'name', 'appName', 'description', 'projectId', 'environmentId'] as $field) {
            $value = $this->optionalString($args, $field);
            if ($value !== null) {
                $query[$field] = $value;
            }
        }

        $limit = $this->optionalString($args, 'limit');
        if ($limit !== null && is_numeric($limit)) {
            $query['limit'] = (int) $limit;
        }

        $offset = $this->optionalString($args, 'offset');
        if ($offset !== null && is_numeric($offset)) {
            $query['offset'] = (int) $offset;
        }

        return $this->client->get("{$type}.search", $query)
            ->toToolResultWith(ucfirst($type) . ' search results:');
    }

    /** @param array<string, mixed> $args */
    private function saveEnvironment(string $type, array $args): ToolResult
    {
        $id = $this->requireDatabaseId($type, $args);
        if ($id === null) {
            return $this->missingIdError($type, 'env');
        }

        $env = $this->optionalString($args, 'env');

        return $this->client->post("{$type}.saveEnvironment", [
            $this->idFieldName($type) => $id,
            'env' => $env,
        ])->toToolResultWith(ucfirst($type) . ' environment variables saved.');
    }

    /** @param array<string, mixed> $args */
    private function saveExternalPort(string $type, array $args): ToolResult
    {
        $id = $this->requireDatabaseId($type, $args);
        if ($id === null) {
            return $this->missingIdError($type, 'port');
        }

        $externalPort = $this->optionalString($args, 'externalPort');
        $portValue = ($externalPort !== null && is_numeric($externalPort))
            ? (int) $externalPort
            : null;

        return $this->client->post("{$type}.saveExternalPort", [
            $this->idFieldName($type) => $id,
            'externalPort' => $portValue,
        ])->toToolResultWith(ucfirst($type) . ' external port saved.');
    }

    /** @param array<string, mixed> $args */
    private function changeStatus(string $type, array $args): ToolResult
    {
        $id = $this->requireDatabaseId($type, $args);
        if ($id === null) {
            return $this->missingIdError($type, 'status');
        }

        $applicationStatus = $this->requireString($args, 'applicationStatus');
        if ($applicationStatus === null) {
            return ToolResult::error('applicationStatus is required for the "status" action.');
        }

        return $this->client->post("{$type}.changeStatus", [
            $this->idFieldName($type) => $id,
            'applicationStatus' => $applicationStatus,
        ])->toToolResultWith(ucfirst($type) . ' status changed.');
    }

    // -- Helpers ----------------------------------------------------------

    /**
     * Map database type to the API ID field name.
     */
    private function idFieldName(string $type): string
    {
        return match ($type) {
            'postgres' => 'postgresId',
            'mysql' => 'mysqlId',
            'mariadb' => 'mariadbId',
            'mongo' => 'mongoId',
            'redis' => 'redisId',
            default => $type . 'Id',
        };
    }

    /**
     * Resolve the database ID from the generic `databaseId` argument.
     *
     * @param array<string, mixed> $args
     */
    private function requireDatabaseId(string $type, array $args): ?string
    {
        // Accept both `databaseId` (generic) and the type-specific field name
        $id = $this->requireString($args, 'databaseId')
            ?? $this->requireString($args, $this->idFieldName($type));

        return $id;
    }

    private function missingIdError(string $type, string $action): ToolResult
    {
        return ToolResult::error(
            "databaseId is required for the \"{$action}\" action. "
            . "Use dokploy_database(type: \"{$type}\", action: \"search\") to find database IDs.",
        );
    }

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
