# Dokploy Database Toolkit

Manage Dokploy databases, database backups, and S3-compatible backup destinations through [Coqui](https://github.com/coquibot/coqui).

Supports **PostgreSQL**, **MySQL**, **MariaDB**, **MongoDB**, and **Redis** — all five Dokploy-managed database types via a unified interface.

## Requirements

- PHP 8.4+
- [coquibot/coqui-toolkit-dokploy](https://github.com/coquibot/coqui-toolkit-dokploy) ^1.0 (provides `DokployClient`)

## Installation

```bash
composer require coquibot/coqui-toolkit-dokploy-database
```

The toolkit is auto-discovered by Coqui — no additional configuration needed.

## Credentials

The toolkit requires the same Dokploy API credentials as the parent toolkit:

| Key | Description |
|-----|-------------|
| `DOKPLOY_API_URL` | Base URL of your Dokploy instance (e.g. `https://dokploy.example.com`) |
| `DOKPLOY_API_TOKEN` | API token — generate one in **Dokploy → Settings → API / Tokens** |

Coqui will prompt for these automatically if they're missing.

## Tools

### `dokploy_database`

Unified management for all five Dokploy database types.

| Action | Description |
|--------|-------------|
| `create` | Create a new database instance |
| `get` | Get database details by ID |
| `search` | Search databases by name |
| `start` | Start a stopped database |
| `stop` | Stop a running database |
| `deploy` | Trigger a deployment |
| `change_status` | Change database status |
| `update` | Update database configuration |
| `reload` | Reload database settings |
| `rebuild` | Rebuild database container |
| `move` | Move to a different environment |
| `delete` | Remove a database |
| `save_ports` | Configure external port |
| `save_env` | Update environment variables |

**Type parameter:** `postgres`, `mysql`, `mariadb`, `mongo`, `redis`

**Create parameters by type:**

| Parameter | postgres | mysql | mariadb | mongo | redis |
|-----------|:--------:|:-----:|:-------:|:-----:|:-----:|
| `name` | ✓ | ✓ | ✓ | ✓ | ✓ |
| `environmentId` | ✓ | ✓ | ✓ | ✓ | ✓ |
| `databasePassword` | ✓ | ✓ | ✓ | ✓ | ✓ |
| `databaseName` | ✓ | ✓ | ✓ | | |
| `databaseUser` | ✓ | ✓ | ✓ | ✓ | |
| `databaseRootPassword` | | ✓ | ✓ | | |
| `dockerImage` | ✓ | ✓ | ✓ | ✓ | ✓ |
| `description` | ✓ | ✓ | ✓ | ✓ | ✓ |

### `dokploy_db_backup`

Database-specific backup policies and manual triggers.

| Action | Description |
|--------|-------------|
| `get` | Get backup policy details |
| `create` | Create a backup policy |
| `update` | Update a backup policy |
| `delete` | Remove a backup policy |
| `run` | Trigger a manual backup |
| `list_files` | List available backup files |

**Supported database types:** `postgres`, `mysql`, `mariadb`, `mongo`

> Redis does not support Dokploy-managed backups.

### `dokploy_destination`

Manage S3-compatible backup storage destinations.

| Action | Description |
|--------|-------------|
| `list` | List all destinations |
| `get` | Get destination details |
| `create` | Create a new S3 destination |
| `update` | Update destination config |
| `delete` | Remove a destination |
| `test_connection` | Verify S3 connectivity |

## Example Workflows

### Provision a PostgreSQL database

```
dokploy_database(type: "postgres", action: "create", name: "app-db",
    environmentId: "env123", databaseName: "myapp",
    databaseUser: "appuser", databasePassword: "s3cret")
```

### Set up automated backups

```
# 1. Create an S3 destination
dokploy_destination(action: "create", name: "s3-prod",
    accessKey: "AKIA...", secretAccessKey: "...",
    bucket: "backups", region: "us-east-1",
    endpoint: "https://s3.amazonaws.com")

# 2. Test connectivity
dokploy_destination(action: "test_connection", destinationId: "dest123")

# 3. Create a daily backup policy
dokploy_db_backup(action: "create", databaseType: "postgres",
    postgresId: "pg123", destinationId: "dest123",
    schedule: "0 3 * * *", prefix: "prod-daily")

# 4. Trigger a manual backup
dokploy_db_backup(action: "run", backupId: "bk123", databaseType: "postgres")
```

### Manage database lifecycle

```
# Stop for maintenance
dokploy_database(type: "mysql", action: "stop", databaseId: "mysql456")

# Update environment
dokploy_database(type: "mysql", action: "save_env",
    databaseId: "mysql456", env: "MYSQL_MAX_CONNECTIONS=200")

# Start and deploy
dokploy_database(type: "mysql", action: "start", databaseId: "mysql456")
dokploy_database(type: "mysql", action: "deploy", databaseId: "mysql456")
```

## Gated Operations

The following operations require user confirmation (unless `--auto-approve` is active):

| Tool | Gated Actions |
|------|---------------|
| `dokploy_database` | `delete`, `rebuild`, `stop` |
| `dokploy_db_backup` | `delete`, `run` |
| `dokploy_destination` | `delete` |

## Relationship to `coqui-toolkit-dokploy`

This package extends the base Dokploy toolkit. The base toolkit handles:
- Projects, applications, compose services
- Deployments, Docker containers, domains
- **Web server and compose** backups

This package adds:
- All five database types (postgres, mysql, mariadb, mongo, redis)
- **Database-specific** backups
- S3 backup destinations

Both toolkits share the same `DokployClient` and credentials — install both for full Dokploy coverage.

## Development

```bash
composer install
composer analyse   # PHPStan level 8
composer test      # Pest tests
```

## License

MIT
