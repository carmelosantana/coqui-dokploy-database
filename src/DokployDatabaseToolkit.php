<?php

declare(strict_types=1);

namespace CarmeloSantana\CoquiToolkitDokployDatabase;

use CarmeloSantana\PHPAgents\Contract\ToolkitInterface;
use CarmeloSantana\CoquiToolkitDokploy\Runtime\DokployClient;
use CarmeloSantana\CoquiToolkitDokployDatabase\Tool\DatabaseBackupTool;
use CarmeloSantana\CoquiToolkitDokployDatabase\Tool\DatabaseTool;
use CarmeloSantana\CoquiToolkitDokployDatabase\Tool\DestinationTool;

/**
 * Dokploy database management toolkit for Coqui.
 *
 * Provides full lifecycle management for Dokploy-managed databases
 * (Postgres, MySQL, MariaDB, MongoDB, Redis), database-specific
 * backup policies, and S3-compatible backup destinations.
 */
final class DokployDatabaseToolkit implements ToolkitInterface
{
    private readonly DokployClient $client;

    public function __construct(
        ?DokployClient $client = null,
    ) {
        $this->client = $client ?? DokployClient::fromEnv();
    }

    /**
     * @return array<\CarmeloSantana\PHPAgents\Contract\ToolInterface>
     */
    public function tools(): array
    {
        return [
            (new DatabaseTool($this->client))->build(),
            (new DatabaseBackupTool($this->client))->build(),
            (new DestinationTool($this->client))->build(),
        ];
    }

    public function guidelines(): string
    {
        return <<<'GUIDELINES'
        <DOKPLOY-DATABASE-GUIDELINES>
        ## Dokploy Database Toolkit

        You have full access to Dokploy-managed databases through the following tools:

        ### Tool Overview
        | Tool | Purpose |
        |------|---------|
        | **dokploy_database** | Unified management for Postgres, MySQL, MariaDB, MongoDB, and Redis — CRUD, deploy, start/stop, env vars, external ports, resource limits |
        | **dokploy_db_backup** | Database-specific backup policies — create schedules, trigger manual backups, list backup files |
        | **dokploy_destination** | Manage S3-compatible backup storage destinations — CRUD, test connectivity |

        ### Supported Database Types
        | Type | `type` value | Create requires |
        |------|-------------|-----------------|
        | PostgreSQL | `postgres` | name, environmentId, databaseName, databaseUser, databasePassword |
        | MySQL | `mysql` | name, environmentId, databaseName, databaseUser, databasePassword |
        | MariaDB | `mariadb` | name, environmentId, databaseName, databaseUser, databasePassword |
        | MongoDB | `mongo` | name, environmentId, databaseUser, databasePassword |
        | Redis | `redis` | name, environmentId, databasePassword |

        ### Resource Hierarchy
        ```
        Project
        └── Environment
            ├── Application
            ├── Compose
            ├── Postgres
            ├── MySQL
            ├── MariaDB
            ├── MongoDB
            └── Redis
        ```

        Every database belongs to a project environment. Use `dokploy_project(action: "get", projectId: "...")` from the Dokploy toolkit to discover environment IDs.

        ### Common Workflows

        **Create a new Postgres database:**
        1. `dokploy_project(action: "list")` — find the target project
        2. `dokploy_database(type: "postgres", action: "create", name: "my-db", environmentId: "...", databaseName: "myapp", databaseUser: "myapp", databasePassword: "secure-pass")`
        3. `dokploy_database(type: "postgres", action: "port", databaseId: "...", externalPort: "5432")` — expose externally
        4. `dokploy_database(type: "postgres", action: "deploy", databaseId: "...")`

        **Set up automated database backups:**
        1. `dokploy_destination(action: "list")` — find or create a backup destination
        2. `dokploy_destination(action: "create", name: "s3-backups", accessKey: "...", secretAccessKey: "...", bucket: "backups", region: "us-east-1", endpoint: "https://s3.amazonaws.com")` — if none exist
        3. `dokploy_db_backup(action: "create", databaseType: "postgres", postgresId: "...", destinationId: "...", schedule: "0 3 * * *", prefix: "mydb", database: "myapp")`
        4. `dokploy_db_backup(action: "run", backupId: "...", databaseType: "postgres")` — trigger manually

        **Search across all databases of a type:**
        `dokploy_database(type: "postgres", action: "search", q: "production")`

        **Move a database to another environment:**
        `dokploy_database(type: "mysql", action: "move", databaseId: "...", targetEnvironmentId: "...")`

        **Update database resource limits:**
        `dokploy_database(type: "postgres", action: "update", databaseId: "...", memoryLimit: "1G", cpuLimit: "1.0")`

        ### Important Notes
        - The `databaseId` parameter maps internally to the type-specific ID (postgresId, mysqlId, etc.) — just pass the ID you received from `create` or `get`.
        - MySQL and MariaDB support `databaseRootPassword` in addition to `databasePassword`.
        - MongoDB supports `replicaSets` (true/false) during creation.
        - Redis does not have `databaseName` or `databaseUser` — only `databasePassword`.
        - Use `env` action to set custom environment variables (newline-separated KEY=VALUE pairs).
        - The `deploy` action applies configuration changes to the running container.
        - The `rebuild` action rebuilds the container from scratch — use when Docker image changes.
        - Database backups (`dokploy_db_backup`) are separate from compose/web-server backups in the Dokploy toolkit.
        - Always create a `dokploy_destination` first before setting up backup policies.
        </DOKPLOY-DATABASE-GUIDELINES>
        GUIDELINES;
    }
}
