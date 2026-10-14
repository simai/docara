<?php

declare(strict_types=1);

namespace Simai\Docara\Application;

use Composer\InstalledVersions;
use Simai\Docara\Console\ApplicationFactory;
use Simai\Docara\Portable\CanonicalJson;
use Simai\Docara\Portable\SchemaRepository;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;

final readonly class CapabilitiesService
{
    public function __construct(
        private string $packageRoot = __DIR__ . '/../..',
        private ?string $version = null,
        private ?string $revision = null,
    ) {}

    public function capabilities(string $root, ?Application $application = null): OperationResult
    {
        $application ??= ApplicationFactory::create($root);
        $contractPath = $this->packageRoot . '/resources/ai-contract.json';
        $contractBytes = is_file($contractPath) && ! is_link($contractPath)
            ? (string) file_get_contents($contractPath)
            : throw new \RuntimeException('DOCARA_AI_CONTRACT_MISSING');
        $contract = json_decode($contractBytes, true, 512, JSON_THROW_ON_ERROR);
        if (! is_array($contract) || ($contract['schema'] ?? null) !== 'docara.ai_contract.v1') {
            throw new \RuntimeException('DOCARA_AI_CONTRACT_INVALID');
        }

        $schemas = [];
        $repository = new SchemaRepository($this->packageRoot . '/resources/schemas');
        foreach ($repository->names() as $name) {
            $path = $this->packageRoot . '/resources/schemas/' . $name;
            $schemas[] = [
                'name' => $name,
                'id' => $repository->get($name)['$id'] ?? null,
                'sha256' => hash_file('sha256', $path),
            ];
        }

        $commands = [];
        $seen = [];
        foreach ($application->all() as $command) {
            $name = $command->getName();
            if ($name === null || isset($seen[$name]) || $command->isHidden()) {
                continue;
            }
            $seen[$name] = true;
            $commands[] = $this->command($command);
        }
        usort($commands, static fn (array $left, array $right): int => $left['name'] <=> $right['name']);

        $data = [
            'schema' => 'docara.capabilities.v1',
            'docara' => [
                'version' => $this->packageVersion(),
                'revision' => $this->packageRevision(),
            ],
            'ai_contract' => $contract + ['sha256' => hash('sha256', $contractBytes)],
            'commands' => $commands,
            'schemas' => $schemas,
            'operation_types' => $this->operationTypes($commands),
            'tracking' => ['translations', 'documentation'],
            'receipts' => $this->receiptSchemas($schemas),
            'lifecycle' => [
                'engine_update' => $this->hasCommand($commands, 'update'),
                'project_upgrade' => $this->hasCommand($commands, 'upgrade'),
                'rollback' => $this->hasOption($commands, 'update', 'rollback') || $this->hasOption($commands, 'upgrade', 'rollback'),
                'network_during_build' => false,
                'network_during_explicit_upgrade' => $this->hasCommand($commands, 'upgrade'),
            ],
            'provenance' => [
                'package' => 'simai/docara',
                'ai_contract' => 'resources/ai-contract.json',
                'schema_root' => 'resources/schemas',
                'command_registry' => ApplicationFactory::class,
            ],
        ];
        $unsigned = $data;
        $data['contract_sha256'] = hash('sha256', CanonicalJson::encode($unsigned));
        (new SchemaRepository($this->packageRoot . '/resources/schemas'))->assertValid($data, 'capabilities.schema.json');

        return OperationResult::success('capabilities', 'simai/docara', $data, $data['provenance']);
    }

    /** @return array<string, mixed> */
    private function command(Command $command): array
    {
        $arguments = [];
        foreach ($command->getDefinition()->getArguments() as $argument) {
            $arguments[] = [
                'name' => $argument->getName(),
                'required' => $argument->isRequired(),
                'array' => $argument->isArray(),
                'description' => $argument->getDescription(),
                'default' => $argument->getDefault(),
            ];
        }
        $options = [];
        foreach ($command->getDefinition()->getOptions() as $option) {
            $options[] = [
                'name' => $option->getName(),
                'shortcut' => $option->getShortcut(),
                'accepts_value' => $option->acceptValue(),
                'value_required' => $option->isValueRequired(),
                'value_optional' => $option->isValueOptional(),
                'array' => $option->isArray(),
                'description' => $option->getDescription(),
                'default' => $option->getDefault(),
            ];
        }

        return [
            'name' => (string) $command->getName(),
            'aliases' => $command->getAliases(),
            'description' => $command->getDescription(),
            'arguments' => $arguments,
            'options' => $options,
        ];
    }

    /** @param list<array<string, mixed>> $commands @return array<string, list<string>> */
    private function operationTypes(array $commands): array
    {
        $types = [];
        foreach (['list', 'inspect', 'schema', 'scaffold', 'validate', 'test', 'qa'] as $name) {
            $command = $this->oneCommand($commands, $name);
            if ($command === null) {
                continue;
            }
            $description = '';
            foreach ($command['arguments'] as $argument) {
                if (($argument['name'] ?? null) === 'kind') {
                    $description = (string) ($argument['description'] ?? '');
                    break;
                }
            }
            if ($name === 'inspect' && $description === '') {
                $types[$name] = $types['list'] ?? [];

                continue;
            }
            $values = array_values(array_filter(array_map('trim', explode('|', $description)), static fn (string $value): bool => preg_match('/^[a-z][a-z0-9_-]*$/', $value) === 1));
            $types[$name] = $values;
        }

        return $types;
    }

    /** @param list<array<string, mixed>> $schemas @return list<string> */
    private function receiptSchemas(array $schemas): array
    {
        $receipts = [];
        foreach ($schemas as $schema) {
            $name = (string) $schema['name'];
            if (str_contains($name, 'receipt') || str_contains($name, 'report') || str_contains($name, 'status')) {
                $receipts[] = $name;
            }
        }

        return $receipts;
    }

    /** @param list<array<string, mixed>> $commands */
    private function hasCommand(array $commands, string $name): bool
    {
        return $this->oneCommand($commands, $name) !== null;
    }

    /** @param list<array<string, mixed>> $commands */
    private function hasOption(array $commands, string $commandName, string $optionName): bool
    {
        $command = $this->oneCommand($commands, $commandName);
        if ($command === null) {
            return false;
        }

        return in_array($optionName, array_column($command['options'], 'name'), true);
    }

    /** @param list<array<string, mixed>> $commands @return array<string, mixed>|null */
    private function oneCommand(array $commands, string $name): ?array
    {
        foreach ($commands as $command) {
            if (($command['name'] ?? null) === $name) {
                return $command;
            }
        }

        return null;
    }

    private function packageVersion(): string
    {
        if ($this->version !== null) {
            return $this->version;
        }

        return InstalledVersions::isInstalled('simai/docara')
            ? (InstalledVersions::getPrettyVersion('simai/docara') ?? 'dev')
            : 'dev';
    }

    private function packageRevision(): ?string
    {
        if ($this->revision !== null) {
            return $this->revision;
        }

        return InstalledVersions::isInstalled('simai/docara') ? InstalledVersions::getReference('simai/docara') : null;
    }
}
