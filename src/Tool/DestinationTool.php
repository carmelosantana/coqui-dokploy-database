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
 * Manage Dokploy backup destinations — S3-compatible storage
 * targets used for database backup policies.
 */
final readonly class DestinationTool
{
    public function __construct(
        private DokployClient $client,
    ) {}

    public function build(): ToolInterface
    {
        return new Tool(
            name: 'dokploy_destination',
            description: 'Manage Dokploy backup destinations — S3-compatible storage targets for database backups. List, create, update, delete destinations, and test connectivity.',
            parameters: [
                new EnumParameter(
                    'action',
                    'Operation to perform.',
                    values: [
                        'list', 'get', 'create', 'update',
                        'delete', 'test_connection',
                    ],
                    required: true,
                ),
                new StringParameter(
                    'destinationId',
                    'Destination ID (required for get, update, delete).',
                    required: false,
                ),
                new StringParameter(
                    'name',
                    'Display name for the destination (required for create, update, test_connection).',
                    required: false,
                ),
                new StringParameter(
                    'provider',
                    'Storage provider identifier (e.g. "aws", "minio", "backblaze").',
                    required: false,
                ),
                new StringParameter(
                    'accessKey',
                    'S3 access key ID (required for create, update, test_connection).',
                    required: false,
                ),
                new StringParameter(
                    'secretAccessKey',
                    'S3 secret access key (required for create, update, test_connection).',
                    required: false,
                ),
                new StringParameter(
                    'bucket',
                    'S3 bucket name (required for create, update, test_connection).',
                    required: false,
                ),
                new StringParameter(
                    'region',
                    'S3 region (e.g. "us-east-1") (required for create, update, test_connection).',
                    required: false,
                ),
                new StringParameter(
                    'endpoint',
                    'S3-compatible endpoint URL (e.g. "https://s3.amazonaws.com") (required for create, update, test_connection).',
                    required: false,
                ),
                new StringParameter(
                    'serverId',
                    'Server ID (for multi-server setups).',
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
            'list' => $this->listDestinations(),
            'get' => $this->getDestination($args),
            'create' => $this->createDestination($args),
            'update' => $this->updateDestination($args),
            'delete' => $this->deleteDestination($args),
            'test_connection' => $this->testConnection($args),
            default => ToolResult::error("Unknown action: {$action}"),
        };
    }

    private function listDestinations(): ToolResult
    {
        return $this->client->get('destination.all')
            ->toToolResultWith('Backup destinations:');
    }

    /** @param array<string, mixed> $args */
    private function getDestination(array $args): ToolResult
    {
        $destinationId = $this->requireString($args, 'destinationId');
        if ($destinationId === null) {
            return ToolResult::error('destinationId is required for the "get" action.');
        }

        return $this->client->get('destination.one', ['destinationId' => $destinationId])
            ->toToolResultWith('Destination details:');
    }

    /** @param array<string, mixed> $args */
    private function createDestination(array $args): ToolResult
    {
        $body = $this->buildS3Body($args);
        if ($body === null) {
            return ToolResult::error(
                'Required fields for create: name, accessKey, secretAccessKey, bucket, region, endpoint.',
            );
        }

        return $this->client->post('destination.create', $body)
            ->toToolResultWith('Backup destination created.');
    }

    /** @param array<string, mixed> $args */
    private function updateDestination(array $args): ToolResult
    {
        $destinationId = $this->requireString($args, 'destinationId');
        if ($destinationId === null) {
            return ToolResult::error('destinationId is required for the "update" action.');
        }

        $body = $this->buildS3Body($args);
        if ($body === null) {
            return ToolResult::error(
                'Required fields for update: name, accessKey, secretAccessKey, bucket, region, endpoint.',
            );
        }

        $body['destinationId'] = $destinationId;

        return $this->client->post('destination.update', $body)
            ->toToolResultWith('Backup destination updated.');
    }

    /** @param array<string, mixed> $args */
    private function deleteDestination(array $args): ToolResult
    {
        $destinationId = $this->requireString($args, 'destinationId');
        if ($destinationId === null) {
            return ToolResult::error('destinationId is required for the "delete" action.');
        }

        return $this->client->post('destination.remove', ['destinationId' => $destinationId])
            ->toToolResultWith('Backup destination deleted.');
    }

    /** @param array<string, mixed> $args */
    private function testConnection(array $args): ToolResult
    {
        $body = $this->buildS3Body($args);
        if ($body === null) {
            return ToolResult::error(
                'Required fields for test_connection: name, accessKey, secretAccessKey, bucket, region, endpoint.',
            );
        }

        return $this->client->post('destination.testConnection', $body)
            ->toToolResultWith('Connection test result:');
    }

    // -- Helpers ----------------------------------------------------------

    /**
     * Build the S3 configuration body from arguments.
     *
     * @param array<string, mixed> $args
     * @return array<string, mixed>|null Null if any required field is missing.
     */
    private function buildS3Body(array $args): ?array
    {
        $required = ['name', 'accessKey', 'secretAccessKey', 'bucket', 'region', 'endpoint'];
        $body = [];

        foreach ($required as $field) {
            $value = $this->requireString($args, $field);
            if ($value === null) {
                return null;
            }
            $body[$field] = $value;
        }

        // Optional fields
        $provider = $this->optionalString($args, 'provider');
        $body['provider'] = $provider;

        $serverId = $this->optionalString($args, 'serverId');
        if ($serverId !== null) {
            $body['serverId'] = $serverId;
        }

        return $body;
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
