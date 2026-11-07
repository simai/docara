<?php

declare(strict_types=1);

namespace Simai\Docara\Application;

use Simai\Docara\Portable\FilesystemPath;
use Simai\Docara\Portable\PortableConfigurationException;
use Simai\Docara\Portable\SourceLocatedException;
use Simai\Docara\PortableSite\PortableRuntimeMetadata;
use Simai\Docara\Smart\Provider\SmartProviderException;
use Throwable;

final readonly class OperationFailureMapper
{
    /** @param array<string, mixed> $arguments */
    public function map(
        string $root,
        string $operation,
        array $arguments,
        Throwable $exception,
        int $exitCode = 2,
    ): OperationResult {
        $code = $this->code($exception);
        $subject = $this->subject($operation, $arguments);
        $provenance = $this->provenance($root);
        $located = $this->location($exception);
        $pointer = $located['pointer'] ?? $this->pointer($code, $operation, $arguments);
        $source = $located['path'] ?? 'command';
        $diagnosticProvenance = [
            'operation' => $operation,
            'subject' => $subject ?? 'project',
            'engine_revision' => $provenance['engine_revision'],
            'input_sha256' => $provenance['input_sha256'],
        ];

        return OperationResult::failure(
            $operation,
            $subject,
            new Diagnostic(
                $code,
                'error',
                $this->safeMessage($exception->getMessage(), $root),
                $source,
                $pointer,
                $located['line'] ?? null,
                $located['column'] ?? null,
                owner: 'docara.application',
                provenance: $diagnosticProvenance,
                suggestion: $this->suggestion($code),
            ),
            $exitCode,
            $provenance,
        );
    }

    /** @param array<string, mixed> $arguments */
    public function subject(string $operation, array $arguments): ?string
    {
        if (in_array($operation, ['scaffold.apply', 'qa.finalize_reference', 'qa.verify'], true)) {
            return $this->scalar($arguments['plan_id'] ?? null) ?? 'plan';
        }
        $kind = $this->scalar($arguments['kind'] ?? null);
        $id = $this->scalar($arguments['id'] ?? null);
        if ($kind !== null && $id !== null) {
            return $kind . ':' . $id;
        }

        return $id ?? $kind ?? ($operation === 'doctor' ? 'project' : null);
    }

    private function code(Throwable $exception): string
    {
        if ($exception instanceof PortableConfigurationException) {
            return $exception->errorCode;
        }
        if (preg_match('/\b([A-Z][A-Z0-9_]+):/', $exception->getMessage(), $matches) === 1) {
            return $matches[1];
        }

        return $exception instanceof \InvalidArgumentException ? 'SDK_INPUT_INVALID' : 'SDK_OPERATION_FAILED';
    }

    /** @param array<string, mixed> $arguments */
    private function pointer(string $code, string $operation, array $arguments): string
    {
        if (preg_match('/(?:ARGUMENT_REQUIRED|PLAN_ID|PLAN_STALE|PLAN_HASH|REPORT_BINDING)/', $code) === 1) {
            return '/arguments/' . (array_key_exists('plan_id', $arguments) ? 'plan_id' : 'input');
        }
        if (str_contains($code, 'KIND')) {
            return '/arguments/kind';
        }
        if (array_key_exists('id', $arguments)) {
            return '/arguments/id';
        }

        return '/operations/' . str_replace('.', '/', $operation);
    }

    /** @return array{path:string,pointer:string,line:int,column:int}|null */
    private function location(Throwable $exception): ?array
    {
        if ($exception instanceof PortableConfigurationException && ! $exception->hasFileLocation()) {
            return null;
        }
        if ($exception instanceof SmartProviderException && ! $exception->hasFileLocation()) {
            return null;
        }
        if ($exception instanceof SourceLocatedException) {
            return [
                'path' => $exception->sourcePath(),
                'pointer' => $exception->sourcePointer(),
                'line' => $exception->sourceLine(),
                'column' => $exception->sourceColumn(),
            ];
        }

        return null;
    }

    /** @return array{engine_revision:string,project_root:string,input_sha256:string} */
    private function provenance(string $root): array
    {
        $package = (new PortableRuntimeMetadata(dirname(__DIR__, 2)))->package();
        $engine = (string) ($package['source_revision'] ?? $package['tree_sha256'] ?? '');
        if ($engine === '' || $engine === 'unresolved') {
            $engine = 'sha256:' . hash('sha256', dirname(__DIR__, 2));
        }
        $real = realpath($root);
        $config = $real === false ? null : $real . '/docara.json';
        $input = is_string($config) && is_file($config) && ! is_link($config)
            ? (hash_file('sha256', $config) ?: hash('sha256', 'docara.json:unreadable'))
            : hash('sha256', 'docara:uninitialized-project');

        return ['engine_revision' => $engine, 'project_root' => '.', 'input_sha256' => $input];
    }

    private function safeMessage(string $message, string $root): string
    {
        $real = realpath($root);
        if ($real !== false) {
            $message = str_replace([FilesystemPath::normalize($real), $real], '.', $message);
        }

        return preg_replace('#/(?:Users|home|private|tmp)/[^\s\]]+#', '[private-path]', $message) ?? $message;
    }

    private function suggestion(string $code): string
    {
        return match ($code) {
            'SDK_PROJECT_CONFIG_MISSING' => 'Run the command from an initialized Docara project root containing docara.json.',
            'SDK_DISCOVERY_KIND_UNKNOWN', 'SDK_SCHEMA_KIND_UNKNOWN' => 'Use a kind returned by docara list or shown in command help.',
            'SMART_REGISTRY_COMPONENT_NOT_FOUND', 'DESIGN_ARTIFACT_NOT_FOUND' => 'Inspect the registered artifacts and retry with an exact owned identifier.',
            'SDK_WRITE_PATH_UNSAFE' => 'Remove the unsafe symlink, hardlink or path collision inside the project and retry.',
            'LOCALE_ACTION_INVALID' => 'Use docara locale add <code>.',
            'LOCALE_ADD_CODE_INVALID' => 'Give a BCP 47 code with a two- or three-letter language, such as en, de, pt-BR or zh-Hans.',
            'LOCALE_ADD_EXISTS' => 'The locale is already there: translate its pages, or change default_locale in docara.json by hand.',
            'LOCALE_ADD_SITE_INVALID' => 'Fix docara.json until docara validate project passes, then add the locale again.',
            'LOCALE_ADD_PORTAL_SINGLE_LOCALE' => 'Keep one locale per portal build: make a separate site for the other language.',
            'LOCALE_ADD_CONTENT_LAYOUT_UNSUPPORTED' => 'Move the pages into content/<locale>/ (for example content/ru/), point the locale content_root there, and retry.',
            'LOCALE_ADD_CONTENT_ROOT_UNSAFE' => 'Replace the file or symbolic link named in the message with an ordinary folder, or remove it, and retry.',
            'LOCALE_ADD_DIRECTION_INVALID' => 'Use --direction=ltr or --direction=rtl.',
            'LOCALE_ADD_ROUTE_COLLISION' => 'Rename or move the page named in the message, so no page address starts with the new locale prefix, and retry.',
            'LOCALE_DEFAULT_HOME_MISSING' => 'Write the home page content/<locale>/index.md first, or add the locale without --default.',
            'LOCALE_DEFAULT_REDIRECTS_INVALID' => 'Make the redirects file valid (docara.redirects.v1) or an ordinary file, and retry.',
            'LOCALE_DEFAULT_REDIRECT_CONFLICT' => 'Remove or correct the redirect named in the message, and retry.',
            'LOCALE_ADD_CHANGED_DURING_WRITE' => 'Another program changed the file; nothing was kept. Run the command again.',
            'LOCALE_ADD_WRITE_FAILED' => 'Check write permissions for the project folder; nothing was kept. Run the command again.',
            'MCP_WRITE_CAPABILITY_REQUIRED' => 'Review the exact dry-run plan, then restart the local MCP adapter with explicit write capability.',
            default => 'Correct the referenced argument or project contract and retry the same operation.',
        };
    }

    private function scalar(mixed $value): ?string
    {
        return is_string($value) && trim($value) !== '' ? $value : null;
    }
}
