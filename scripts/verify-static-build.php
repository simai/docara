#!/usr/bin/env php
<?php

declare(strict_types=1);

use League\CommonMark\Environment\Environment;
use Simai\Docara\ComponentCatalog\EffectiveComponentCatalogBuilder;
use Simai\Docara\Framework\FrameworkComponentRuntime;
use Simai\Docara\Framework\FrameworkLock;
use Simai\Docara\Portable\JsonSchemaValidator;
use Simai\Docara\Portable\ResolvedPagePlan;
use Simai\Docara\Portable\SchemaRepository;
use Simai\Docara\PortableSite\PortableComponentCatalogProjector;
use Simai\Docara\PortableSite\PortableHtmlRenderer;
use Simai\Docara\PortableSite\PortableMarkdownRenderer;
use Simai\Docara\PortableSite\PortableRedirectPublisher;

function docaraBootstrapTrustedSource(): void
{
    static $bootstrapped = false;
    if ($bootstrapped) {
        return;
    }

    $packageRoot = dirname(__DIR__);
    $candidates = [
        getenv('DOCARA_COMPOSER_AUTOLOAD') ?: null,
        $packageRoot . '/vendor/autoload.php',
        getcwd() . '/vendor/autoload.php',
        dirname(__DIR__, 3) . '/autoload.php',
    ];
    foreach ($candidates as $candidate) {
        if (is_string($candidate) && is_file($candidate)) {
            require_once $candidate;
            break;
        }
    }
    spl_autoload_register(
        static function (string $class) use ($packageRoot): void {
            $prefix = 'Simai\\Docara\\';
            if (! str_starts_with($class, $prefix)) {
                return;
            }
            $path = $packageRoot . '/src/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
            if (is_file($path)) {
                require_once $path;
            }
        },
        true,
        true,
    );
    if (! class_exists(Environment::class)
        || ! class_exists(JsonSchemaValidator::class)
        || ! class_exists(EffectiveComponentCatalogBuilder::class)
    ) {
        throw new RuntimeException(
            'Trusted Docara source verification requires the project Composer autoloader.',
        );
    }
    $bootstrapped = true;
}

/** @param list<string> $expected */
function docaraExactKeys(array $value, array $expected): bool
{
    $keys = array_keys($value);
    sort($keys, SORT_STRING);
    sort($expected, SORT_STRING);

    return $keys === $expected;
}

function docaraCanonicalValue(mixed $value): mixed
{
    if (! is_array($value)) {
        return $value;
    }
    if (array_is_list($value)) {
        return array_map(docaraCanonicalValue(...), $value);
    }
    ksort($value, SORT_STRING);
    foreach ($value as $key => $item) {
        $value[$key] = docaraCanonicalValue($item);
    }

    return $value;
}

function docaraCanonicalJson(mixed $value): string
{
    return json_encode(
        docaraCanonicalValue($value),
        JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION,
    );
}

/**
 * @param  list<string>  $pageOutputs
 * @param  list<array<string, mixed>>  $pageRecords
 * @return list<string>
 */
function docaraRedirectOutputs(
    string $root,
    string $deploymentBase,
    array $pageOutputs,
    array $pageRecords,
    string $locale,
    string $documentationVersion,
    ?string $expectedSource,
): array {
    $receiptPath = $root . '/.docara/redirects.json';
    if (! file_exists($receiptPath) && ! is_link($receiptPath)) {
        if ($expectedSource !== null) {
            throw new RuntimeException('Configured redirects require a safe redirect receipt.');
        }

        return [];
    }
    if ($expectedSource === null) {
        throw new RuntimeException('A redirect receipt exists while redirects are not configured.');
    }
    if (! docaraSafeRegularFile($receiptPath)) {
        throw new RuntimeException('Redirect receipt is missing or unsafe.');
    }

    docaraBootstrapTrustedSource();
    $receipt = json_decode(
        (string) file_get_contents($receiptPath),
        true,
        512,
        JSON_THROW_ON_ERROR,
    );
    (new SchemaRepository)->assertValid($receipt, 'redirect-receipt.schema.json');
    if (! is_array($receipt)
        || ($receipt['base_url'] ?? null) !== $deploymentBase
        || ($receipt['locale'] ?? null) !== $locale
        || ($receipt['documentation_version'] ?? null) !== $documentationVersion
        || ($receipt['source'] ?? null) !== $expectedSource
    ) {
        throw new RuntimeException('Redirect receipt build identity does not match the resolved page plans.');
    }

    $records = $receipt['redirects'] ?? null;
    if (! is_array($records) || ! array_is_list($records)) {
        throw new RuntimeException('Redirect receipt does not contain a redirect list.');
    }
    if (! hash_equals(
        hash('sha256', docaraCanonicalJson($records)),
        (string) ($receipt['content_sha256'] ?? ''),
    )) {
        throw new RuntimeException('Redirect receipt content_sha256 does not match canonical records.');
    }

    $targets = [];
    foreach ($pageRecords as $page) {
        $output = $page['output'] ?? null;
        $url = $page['url'] ?? null;
        if (is_string($output) && is_string($url)) {
            $targets[$output] = $url;
        }
    }
    $reservedOutputs = array_fill_keys($pageOutputs, true);
    $sourceRoutes = [];
    foreach ($records as $record) {
        if (is_array($record) && is_string($record['from'] ?? null)) {
            $sourceRoutes[$record['from']] = true;
        }
    }

    $outputs = [];
    $sourceDescriptorRecords = [];
    $previousFrom = null;
    foreach ($records as $index => $record) {
        if (! is_array($record)
            || ! docaraExactKeys($record, ['from', 'to', 'url', 'target_url', 'output'])
        ) {
            throw new RuntimeException("Redirect receipt record [$index] has an invalid shape.");
        }
        foreach (['from', 'url', 'target_url', 'output'] as $field) {
            if (! is_string($record[$field] ?? null) || $record[$field] === '') {
                throw new RuntimeException("Redirect receipt record [$index] has an invalid [$field].");
            }
        }
        if (! is_string($record['to'] ?? null)) {
            throw new RuntimeException("Redirect receipt record [$index] has an invalid [to].");
        }
        $from = $record['from'];
        $to = $record['to'];
        $sourceDescriptorRecords[] = [
            'from' => $from,
            'to' => $to,
        ];
        $slugPattern = '/\A[a-z0-9](?:[a-z0-9._-]*[a-z0-9_-])?(?:\/[a-z0-9](?:[a-z0-9._-]*[a-z0-9_-])?)*\z/D';
        $targetSlugPattern = '/\A(?:[a-z0-9](?:[a-z0-9._-]*[a-z0-9_-])?(?:\/[a-z0-9](?:[a-z0-9._-]*[a-z0-9_-])?)*)?\z/D';
        if (preg_match($slugPattern, $from) !== 1
            || preg_match($targetSlugPattern, $to) !== 1
            || ! docaraSearchUrlIsSafe($record['url'], $deploymentBase)
            || ! docaraSearchUrlIsSafe($record['target_url'], $deploymentBase)
        ) {
            throw new RuntimeException("Redirect receipt record [$index] contains an unsafe route.");
        }
        if ($previousFrom !== null && strcmp($previousFrom, $from) >= 0) {
            throw new RuntimeException('Redirect receipt records are not strictly sorted or contain duplicates.');
        }
        $previousFrom = $from;
        if ($from === $to || isset($sourceRoutes[$to])) {
            throw new RuntimeException("Redirect [$from] forms a chain or loop.");
        }

        $output = $from . '/index.html';
        $targetOutput = $to === '' ? 'index.html' : $to . '/index.html';
        $expectedUrl = $deploymentBase . $from . '/';
        $expectedTargetRoute = $to === '' ? $deploymentBase : $deploymentBase . $to . '/';
        $expectedTargetUrl = $targets[$targetOutput] ?? null;
        if ($record['output'] !== $output
            || $record['url'] !== $expectedUrl
            || ! is_string($expectedTargetUrl)
            || $expectedTargetUrl !== $expectedTargetRoute
            || $record['target_url'] !== $expectedTargetUrl
        ) {
            throw new RuntimeException("Redirect [$from] does not match exact generated routes.");
        }
        if (isset($reservedOutputs[$output]) || isset($outputs[$output])) {
            throw new RuntimeException("Redirect output [$output] collides with another generated HTML output.");
        }
        if (! docaraSafeRegularFile($root . '/' . $output)) {
            throw new RuntimeException("Redirect output [$output] is missing or unsafe.");
        }
        $actualBytes = file_get_contents($root . '/' . $output);
        $expectedBytes = PortableRedirectPublisher::renderPage(
            $record,
            $locale,
            $documentationVersion,
        );
        if (! is_string($actualBytes) || ! hash_equals($expectedBytes, $actualBytes)) {
            throw new RuntimeException("Redirect output [$output] does not match its deterministic receipt.");
        }
        $outputs[$output] = true;
    }
    $canonicalSource = [
        'schema' => 'docara.redirects.v1',
        'version' => 1,
        'redirects' => $sourceDescriptorRecords,
    ];
    if (! hash_equals(
        hash('sha256', docaraCanonicalJson($canonicalSource)),
        (string) ($receipt['source_sha256'] ?? ''),
    )) {
        throw new RuntimeException('Redirect receipt source_sha256 does not match canonical source descriptors.');
    }

    $outputs = array_keys($outputs);
    sort($outputs, SORT_STRING);

    return $outputs;
}

function docaraSearchUrlIsSafe(string $url, string $deploymentBase): bool
{
    return preg_match('#\A/(?:(?!\.{1,2}/)[A-Za-z0-9._~-]+/)*\z#D', $url) === 1
        && str_starts_with($url, $deploymentBase);
}

function docaraSafeRegularFile(string $path): bool
{
    $stat = @lstat($path);

    return ! is_link($path)
        && is_array($stat)
        && (($stat['mode'] ?? 0) & 0170000) === 0100000
        && ($stat['nlink'] ?? 1) === 1;
}

function docaraCatalogSafePath(string $path): bool
{
    if ($path === ''
        || str_starts_with($path, '/')
        || preg_match('/\A[A-Za-z]:/', $path) === 1
        || str_contains($path, '\\')
        || str_contains($path, "\0")
    ) {
        return false;
    }
    foreach (explode('/', $path) as $segment) {
        if ($segment === '' || $segment === '.' || $segment === '..') {
            return false;
        }
    }

    return true;
}

function docaraCatalogStringList(mixed $value, bool $nonEmpty = false): bool
{
    if (! is_array($value)
        || ! array_is_list($value)
        || ($nonEmpty && $value === [])
    ) {
        return false;
    }
    foreach ($value as $item) {
        if (! is_string($item) || trim($item) === '') {
            return false;
        }
    }

    return count($value) === count(array_unique($value));
}

function docaraCatalogValues(mixed $value): bool
{
    if (! is_array($value) || ! array_is_list($value)) {
        return false;
    }
    $encoded = [];
    foreach ($value as $item) {
        if (! docaraCatalogScalar($item)) {
            return false;
        }
        $encoded[] = docaraCanonicalJson($item);
    }

    return count($encoded) === count(array_unique($encoded));
}

function docaraCatalogScalar(mixed $value): bool
{
    return is_string($value) || is_bool($value) || is_int($value) || is_float($value);
}

function docaraCatalogLocalizedLabels(mixed $value): bool
{
    if (! is_array($value) || ($value !== [] && array_is_list($value))) {
        return false;
    }
    foreach ($value as $key => $label) {
        if ((! is_string($key) && ! is_int($key))
            || ! is_string($label)
            || trim($label) === ''
        ) {
            return false;
        }
    }

    return true;
}

/** @param array<string, mixed> $entry @param array<string, mixed> $authoring */
function docaraCatalogPresentation(array $entry, array $authoring): bool
{
    $presentation = $entry['presentation'] ?? null;
    if (! is_array($presentation)
        || array_is_list($presentation)
        || ! docaraExactKeys($presentation, ['ru'])
        || ! is_array($presentation['ru'])
        || array_is_list($presentation['ru'])
    ) {
        return false;
    }
    $localized = $presentation['ru'];
    $supported = ($entry['lifecycle'] ?? null) === 'supported';
    $required = ['title', 'description', 'limitations', 'states', 'parameters'];
    if ($supported) {
        $required[] = 'example_ref';
    } else {
        $required[] = 'gap';
    }
    if (! docaraExactKeys($localized, $required)
        || ! is_string($localized['title'])
        || trim($localized['title']) === ''
        || ! is_string($localized['description'])
        || trim($localized['description']) === ''
        || ! docaraCatalogStringList($localized['limitations'])
        || count($localized['limitations']) !== count($entry['limitations'])
        || ! docaraCatalogLocalizedLabels($localized['states'])
        || ! is_array($localized['parameters'])
        || ($localized['parameters'] !== [] && array_is_list($localized['parameters']))
    ) {
        return false;
    }
    if ($supported
        && (! is_string($localized['example_ref'])
            || ! docaraCatalogSafePath($localized['example_ref']))
    ) {
        return false;
    }
    $stateKeys = array_keys($localized['states']);
    $states = array_map('strval', $entry['states']);
    sort($stateKeys, SORT_STRING);
    sort($states, SORT_STRING);
    if ($stateKeys !== $states) {
        return false;
    }

    $parameterKeys = array_keys($localized['parameters']);
    $expectedParameterKeys = array_map(
        static fn (array $parameter): string => (string) $parameter['name'],
        $authoring['parameters'],
    );
    sort($parameterKeys, SORT_STRING);
    sort($expectedParameterKeys, SORT_STRING);
    if ($parameterKeys !== $expectedParameterKeys) {
        return false;
    }
    foreach ($authoring['parameters'] as $parameter) {
        $name = (string) $parameter['name'];
        $localizedParameter = $localized['parameters'][$name] ?? null;
        $hasValues = isset($parameter['values']);
        $expectedKeys = $hasValues ? ['label', 'description', 'values'] : ['label', 'description'];
        if (! is_array($localizedParameter)
            || array_is_list($localizedParameter)
            || ! docaraExactKeys($localizedParameter, $expectedKeys)
            || ! is_string($localizedParameter['label'])
            || trim($localizedParameter['label']) === ''
            || ! is_string($localizedParameter['description'])
            || trim($localizedParameter['description']) === ''
        ) {
            return false;
        }
        if (! $hasValues) {
            continue;
        }
        if (! docaraCatalogLocalizedLabels($localizedParameter['values'])) {
            return false;
        }
        $valueKeys = array_map('strval', array_keys($localizedParameter['values']));
        $expectedValueKeys = array_map('strval', $parameter['values']);
        sort($valueKeys, SORT_STRING);
        sort($expectedValueKeys, SORT_STRING);
        if ($valueKeys !== $expectedValueKeys) {
            return false;
        }
    }

    if ($supported) {
        return true;
    }
    $gap = $localized['gap'];
    if (! is_array($gap)
        || array_is_list($gap)
        || ! docaraExactKeys($gap, ['reason', 'fallback', 'admission_condition'])
    ) {
        return false;
    }
    foreach ($gap as $value) {
        if (! is_string($value) || trim($value) === '') {
            return false;
        }
    }

    return true;
}

function docaraCatalogCondition(mixed $value): bool
{
    if (! is_array($value) || array_is_list($value) || $value === []) {
        return false;
    }
    foreach ($value as $key => $item) {
        if (! is_string($key)
            || preg_match('/\A[a-z][a-z0-9_-]*\z/D', $key) !== 1
            || ! docaraCatalogScalar($item)
        ) {
            return false;
        }
    }

    return true;
}

function docaraCatalogConstraints(mixed $value): bool
{
    if (! is_array($value)
        || array_is_list($value)
        || ! docaraExactKeys($value, ['allowed_combinations', 'requires'])
        || ! is_array($value['allowed_combinations'])
        || ! array_is_list($value['allowed_combinations'])
        || ! is_array($value['requires'])
        || ! array_is_list($value['requires'])
    ) {
        return false;
    }
    foreach ($value['allowed_combinations'] as $combination) {
        if (! is_array($combination)
            || array_is_list($combination)
            || ! docaraExactKeys($combination, ['keys', 'values'])
            || ! docaraCatalogStringList($combination['keys'], true)
            || ! is_array($combination['values'])
            || ! array_is_list($combination['values'])
            || $combination['values'] === []
        ) {
            return false;
        }
        foreach ($combination['keys'] as $key) {
            if (preg_match('/\A[a-z][a-z0-9_-]*\z/D', $key) !== 1) {
                return false;
            }
        }
        foreach ($combination['values'] as $tuple) {
            if (! is_array($tuple)
                || ! array_is_list($tuple)
                || count($tuple) !== count($combination['keys'])
            ) {
                return false;
            }
            foreach ($tuple as $item) {
                if (! docaraCatalogScalar($item)) {
                    return false;
                }
            }
        }
    }
    foreach ($value['requires'] as $requirement) {
        if (! is_array($requirement)
            || array_is_list($requirement)
            || ! docaraExactKeys($requirement, ['when', 'then'])
            || ! docaraCatalogCondition($requirement['when'])
            || ! docaraCatalogCondition($requirement['then'])
        ) {
            return false;
        }
    }

    return true;
}

function docaraCatalogParameterValidation(mixed $value, string $type): bool
{
    if (! is_array($value)
        || array_is_list($value)
        || $value === []
        || array_diff(
            array_keys($value),
            ['min_length', 'max_length', 'pattern', 'minimum', 'maximum'],
        ) !== []
    ) {
        return false;
    }
    foreach (['min_length', 'max_length'] as $key) {
        if (array_key_exists($key, $value)
            && (! in_array($type, ['string', 'enum'], true)
                || ! is_int($value[$key])
                || $value[$key] < 0)
        ) {
            return false;
        }
    }
    if (isset($value['min_length'], $value['max_length'])
        && $value['min_length'] > $value['max_length']
    ) {
        return false;
    }
    if (array_key_exists('pattern', $value)
        && (! in_array($type, ['string', 'enum'], true)
            || ! is_string($value['pattern'])
            || $value['pattern'] === '')
    ) {
        return false;
    }
    foreach (['minimum', 'maximum'] as $key) {
        if (array_key_exists($key, $value)
            && (! in_array($type, ['integer', 'number', 'enum'], true)
                || (! is_int($value[$key]) && ! is_float($value[$key])))
        ) {
            return false;
        }
    }
    if (isset($value['minimum'], $value['maximum'])
        && $value['minimum'] > $value['maximum']
    ) {
        return false;
    }

    return true;
}

function docaraCatalogContainsAbsoluteFilesystemReference(mixed $value): bool
{
    if (is_array($value)) {
        foreach ($value as $item) {
            if (docaraCatalogContainsAbsoluteFilesystemReference($item)) {
                return true;
            }
        }

        return false;
    }
    if (! is_string($value)) {
        return false;
    }

    return preg_match(
        '~(?:^|[\s("\'])'
        . '(?:/(?:'
        . 'Applications|Library|Network|System|Users|Volumes'
        . '|bin|boot|dev|etc|home|lib|lib64|media|mnt|opt|private|proc'
        . '|root|run|sbin|srv|sys|tmp|usr|var'
        . ')/'
        . '|[A-Za-z]:[\\\\/]'
        . '|\\\\\\\\[^\\\\\s]+\\\\)'
        . '~u',
        $value,
    ) === 1;
}

/** @param array<string, mixed> $entry */
function docaraCatalogEntryContractError(
    array $entry,
    string $frameworkPair,
    string $providerRevision,
): ?string {
    $required = [
        'id',
        'family',
        'category',
        'title',
        'description',
        'presentation',
        'lifecycle',
        'authoring',
        'states',
        'limitations',
        'verification',
        'docs_ref',
        'provenance',
    ];
    $allowed = [...$required, 'example_ref', 'consumer_policy', 'gap'];
    if (array_diff($required, array_keys($entry)) !== []
        || array_diff(array_keys($entry), $allowed) !== []
        || ! is_string($entry['id'])
        || preg_match('/\A[a-z][a-z0-9_]*(?:\.[a-z][a-z0-9_]*)+\z/D', $entry['id']) !== 1
        || ! in_array(
            $entry['family'],
            ['native_markdown', 'docara_typed', 'framework_smart', 'requirement'],
            true,
        )
        || ! is_string($entry['category'])
        || trim($entry['category']) === ''
        || ! is_string($entry['title'])
        || trim($entry['title']) === ''
        || ! is_string($entry['description'])
        || trim($entry['description']) === ''
        || ! in_array(
            $entry['lifecycle'],
            ['supported', 'admission_pending', 'framework_gap', 'deferred'],
            true,
        )
        || ! docaraCatalogStringList($entry['states'])
        || ! docaraCatalogStringList($entry['limitations'])
        || ! is_string($entry['docs_ref'])
        || ! docaraCatalogSafePath($entry['docs_ref'])
    ) {
        return 'The effective component catalogue entry shape is invalid.';
    }

    $authoring = $entry['authoring'];
    if (! is_array($authoring)
        || array_is_list($authoring)
        || array_diff(['syntax', 'call', 'jobs', 'parameters'], array_keys($authoring)) !== []
        || array_diff(array_keys($authoring), ['syntax', 'call', 'jobs', 'parameters', 'constraints']) !== []
        || ! in_array(
            $authoring['syntax'],
            ['markdown', 'directive', 'directive_json', 'proposed_directive', 'unavailable'],
            true,
        )
        || (! is_string($authoring['call']) && $authoring['call'] !== null)
        || (is_string($authoring['call']) && trim($authoring['call']) === '')
        || ! docaraCatalogStringList($authoring['jobs'], true)
        || ! is_array($authoring['parameters'])
        || ! array_is_list($authoring['parameters'])
    ) {
        return 'The effective component catalogue authoring contract is invalid.';
    }
    foreach ($authoring['jobs'] as $job) {
        if (preg_match('/\A[a-z][a-z0-9_]*\z/D', $job) !== 1) {
            return 'The effective component catalogue author job is invalid.';
        }
    }
    foreach ($authoring['parameters'] as $parameter) {
        if (! is_array($parameter) || array_is_list($parameter)) {
            return 'The effective component catalogue parameter is invalid.';
        }
        $parameterKeys = array_keys($parameter);
        if (array_diff(['name', 'type', 'required', 'description'], $parameterKeys) !== []
            || array_diff(
                $parameterKeys,
                ['name', 'type', 'required', 'description', 'values', 'default', 'validation', 'mirrors'],
            ) !== []
            || ! is_string($parameter['name'])
            || preg_match('/\A[a-z][a-z0-9_-]*\z/D', $parameter['name']) !== 1
            || ! in_array($parameter['type'], ['string', 'boolean', 'integer', 'number', 'enum', 'markdown'], true)
            || ! is_bool($parameter['required'])
            || ! is_string($parameter['description'])
            || trim($parameter['description']) === ''
            || (isset($parameter['values']) && ! docaraCatalogValues($parameter['values']))
            || (array_key_exists('default', $parameter) && ! docaraCatalogScalar($parameter['default']))
            || (isset($parameter['validation'])
                && ! docaraCatalogParameterValidation($parameter['validation'], (string) $parameter['type']))
            || (isset($parameter['mirrors'])
                && (! docaraCatalogStringList($parameter['mirrors'], true)
                    || array_filter(
                        $parameter['mirrors'],
                        static fn (string $target): bool => preg_match(
                            '/\A[a-z][a-z0-9_-]*\z/D',
                            $target,
                        ) !== 1,
                    ) !== []))
        ) {
            return 'The effective component catalogue parameter is invalid.';
        }
    }
    if (isset($authoring['constraints']) && ! docaraCatalogConstraints($authoring['constraints'])) {
        return 'The effective component catalogue author constraints are invalid.';
    }
    if (! docaraCatalogPresentation($entry, $authoring)) {
        return 'The effective component catalogue presentation contract is invalid.';
    }

    $verification = $entry['verification'];
    if (! is_array($verification)
        || array_is_list($verification)
        || ! docaraExactKeys($verification, ['renderer', 'tests', 'docs', 'demo'])
    ) {
        return 'The effective component catalogue verification contract is invalid.';
    }
    foreach ($verification as $value) {
        if (! is_bool($value)) {
            return 'The effective component catalogue verification contract is invalid.';
        }
    }

    $family = $entry['family'];
    $lifecycle = $entry['lifecycle'];
    $provenance = $entry['provenance'];
    if (! is_array($provenance) || array_is_list($provenance)) {
        return 'The effective component catalogue provenance is invalid.';
    }
    if ($family === 'native_markdown') {
        if ($lifecycle !== 'supported'
            || $authoring['syntax'] !== 'markdown'
            || ! docaraExactKeys($provenance, ['source_kind', 'profile_id'])
            || $provenance['source_kind'] !== 'portable_markdown_profile'
            || $provenance['profile_id'] !== 'docara.portable_markdown_profile.v1'
            || ! isset($entry['example_ref'])
            || isset($entry['consumer_policy'])
            || isset($entry['gap'])
        ) {
            return 'A native Markdown catalogue entry violates its family contract.';
        }
    } elseif ($family === 'docara_typed') {
        $name = str_starts_with($entry['id'], 'docara.')
            ? substr($entry['id'], strlen('docara.'))
            : '';
        if ($lifecycle !== 'supported'
            || $authoring['syntax'] !== 'directive'
            || $authoring['call'] !== ':::' . $name
            || ! docaraExactKeys($provenance, ['source_kind', 'definition_ref'])
            || $provenance['source_kind'] !== 'typed_definition'
            || ! is_string($provenance['definition_ref'])
            || ! docaraCatalogSafePath($provenance['definition_ref'])
            || ! isset($entry['example_ref'])
            || isset($entry['consumer_policy'])
            || isset($entry['gap'])
        ) {
            return 'A typed Docara catalogue entry violates its family contract.';
        }
    } elseif ($family === 'framework_smart') {
        if ($lifecycle !== 'supported'
            || $authoring['syntax'] !== 'directive_json'
            || $authoring['call'] !== ':::' . $entry['id']
            || ! isset($authoring['constraints'])
            || ! docaraCatalogConstraints($authoring['constraints'])
            || ! docaraExactKeys(
                $provenance,
                [
                    'source_kind',
                    'manifest_ref',
                    'provider',
                    'provider_revision',
                    'upstream_revision',
                    'manifest_sha256',
                    'runtime_pair',
                ],
            )
            || $provenance['source_kind'] !== 'smart_consumer_metadata'
            || $provenance['provider'] !== 'larena/ui'
            || $provenance['provider_revision'] !== $providerRevision
            || $provenance['runtime_pair'] !== $frameworkPair
            || ! is_string($provenance['upstream_revision'])
            || preg_match('/\A[a-f0-9]{40}\z/D', $provenance['upstream_revision']) !== 1
            || ! is_string($provenance['manifest_sha256'])
            || preg_match('/\A[a-f0-9]{64}\z/D', $provenance['manifest_sha256']) !== 1
            || ! is_string($provenance['manifest_ref'])
            || ! docaraCatalogSafePath($provenance['manifest_ref'])
            || ! isset($entry['example_ref'])
            || ! is_array($entry['consumer_policy'] ?? null)
            || isset($entry['gap'])
        ) {
            return 'A Smart catalogue entry violates its exact admission contract.';
        }
        $policy = $entry['consumer_policy'];
        if (array_is_list($policy)
            || ! docaraExactKeys(
                $policy,
                [
                    'can_admit',
                    'managed_properties',
                    'forbidden_inputs',
                    'omitted_assets',
                    'excluded_states',
                ],
            )
            || $policy['can_admit'] !== false
            || ! docaraCatalogStringList($policy['managed_properties'])
            || ! docaraCatalogStringList($policy['forbidden_inputs'])
            || ! docaraCatalogStringList($policy['omitted_assets'])
            || ! docaraCatalogStringList($policy['excluded_states'])
        ) {
            return 'A Smart catalogue entry has an invalid narrowing consumer policy.';
        }
    } elseif ($family === 'requirement') {
        if ($lifecycle === 'supported'
            || ! in_array($authoring['syntax'], ['proposed_directive', 'unavailable'], true)
            || ! docaraExactKeys($provenance, ['source_kind', 'framework_owner'])
            || $provenance['source_kind'] !== 'product_requirement'
            || ! is_string($provenance['framework_owner'])
            || trim($provenance['framework_owner']) === ''
            || isset($entry['example_ref'])
            || isset($entry['consumer_policy'])
            || ! is_array($entry['gap'] ?? null)
        ) {
            return 'A requirement catalogue entry is incorrectly executable.';
        }
    }

    if (isset($entry['example_ref'])
        && (! is_string($entry['example_ref']) || ! docaraCatalogSafePath($entry['example_ref']))
    ) {
        return 'The effective component catalogue example reference is unsafe.';
    }
    if ($lifecycle === 'supported') {
        if ($verification['renderer'] !== true
            || $verification['tests'] !== true
            || $verification['docs'] !== true
            || ! isset($entry['example_ref'])
        ) {
            return 'A supported component catalogue entry has incomplete evidence.';
        }
    } else {
        $gap = $entry['gap'] ?? null;
        if (! is_array($gap)
            || array_is_list($gap)
            || ! docaraExactKeys($gap, ['owner', 'reason', 'fallback', 'admission_condition'])
        ) {
            return 'An unavailable component catalogue entry lacks a complete gap contract.';
        }
        foreach ($gap as $value) {
            if (! is_string($value) || trim($value) === '') {
                return 'An unavailable component catalogue entry lacks a complete gap contract.';
            }
        }
    }

    return null;
}

/** @return array{ids: array<string, true>, duplicates: list<string>} */
function docaraHtmlIdInventory(string $html): array
{
    $document = new DOMDocument('1.0', 'UTF-8');
    $previous = libxml_use_internal_errors(true);
    $loaded = $document->loadHTML(
        '<?xml encoding="UTF-8">' . $html,
        LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING | LIBXML_COMPACT,
    );
    libxml_clear_errors();
    libxml_use_internal_errors($previous);
    if ($loaded !== true) {
        throw new RuntimeException('Generated HTML could not be parsed for fragment verification.');
    }

    $ids = [];
    $duplicates = [];
    $xpath = new DOMXPath($document);
    foreach ($xpath->query('//*[@id]') ?: [] as $node) {
        if (! $node instanceof DOMElement) {
            continue;
        }
        $id = $node->getAttribute('id');
        if ($id === '') {
            continue;
        }
        if (isset($ids[$id])) {
            $duplicates[] = $id;
        }
        $ids[$id] = true;
    }
    sort($duplicates, SORT_STRING);

    return ['ids' => $ids, 'duplicates' => array_values(array_unique($duplicates))];
}

function docaraAssertHtmlBuildIdentity(
    string $html,
    string $locale,
    string $documentationVersion,
): void {
    $document = new DOMDocument('1.0', 'UTF-8');
    $previous = libxml_use_internal_errors(true);
    $loaded = $document->loadHTML(
        '<?xml encoding="UTF-8">' . $html,
        LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING | LIBXML_COMPACT,
    );
    libxml_clear_errors();
    libxml_use_internal_errors($previous);
    if ($loaded !== true) {
        throw new RuntimeException('Generated HTML could not be parsed for build identity verification.');
    }

    $root = $document->documentElement;
    if (! $root instanceof DOMElement
        || strtolower($root->tagName) !== 'html'
        || $root->getAttribute('lang') !== $locale
        || $root->getAttribute('data-docara-documentation-version') !== $documentationVersion
    ) {
        throw new RuntimeException(
            'Generated HTML root does not match the resolved locale and documentation version.',
        );
    }

    $nodes = (new DOMXPath($document))->query(
        '//meta[@name="docara:documentation-version"]',
    );
    if ($nodes === false
        || $nodes->length !== 1
        || ! $nodes->item(0) instanceof DOMElement
        || $nodes->item(0)->getAttribute('content') !== $documentationVersion
    ) {
        throw new RuntimeException(
            'Generated HTML requires one documentation-version meta matching the resolved build.',
        );
    }
}

function docaraAssertTrustedPackagedDirectory(string $packageRoot, string $relative): void
{
    if (! docaraCatalogSafePath($relative)) {
        throw new RuntimeException("Packaged source directory [$relative] has an unsafe path.");
    }
    $cursor = rtrim($packageRoot, '/\\');
    $packageStat = @lstat($cursor);
    if (! is_array($packageStat)
        || is_link($cursor)
        || (($packageStat['mode'] ?? 0) & 0170000) !== 0040000
    ) {
        throw new RuntimeException('Packaged source root is missing or unsafe.');
    }
    foreach (explode('/', $relative) as $segment) {
        $cursor .= '/' . $segment;
        $segmentStat = @lstat($cursor);
        if (! is_array($segmentStat)
            || is_link($cursor)
            || (($segmentStat['mode'] ?? 0) & 0170000) !== 0040000
        ) {
            throw new RuntimeException("Packaged source directory [$relative] has an unsafe path segment.");
        }
    }
    $packageReal = realpath($packageRoot);
    $directory = rtrim($packageRoot, '/\\') . '/' . $relative;
    $directoryReal = realpath($directory);
    $directoryStat = @lstat($directory);
    if (! is_string($packageReal)
        || ! is_string($directoryReal)
        || ! is_array($directoryStat)
        || is_link($directory)
        || (($directoryStat['mode'] ?? 0) & 0170000) !== 0040000
        || ! str_starts_with($directoryReal, rtrim($packageReal, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR)
    ) {
        throw new RuntimeException("Packaged source directory [$relative] is missing or unsafe.");
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST,
    );
    foreach ($iterator as $entry) {
        $path = $entry->getPathname();
        $entryRelative = str_replace('\\', '/', substr($path, strlen($packageRoot) + 1));
        $stat = @lstat($path);
        $real = realpath($path);
        if ($entry->isLink()
            || is_link($path)
            || ! is_array($stat)
            || ! is_string($real)
            || ! str_starts_with($real, rtrim($packageReal, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR)
        ) {
            throw new RuntimeException("Packaged source [$entryRelative] is unsafe.");
        }
        $mode = ($stat['mode'] ?? 0) & 0170000;
        if ($entry->isDir()) {
            if ($mode !== 0040000) {
                throw new RuntimeException("Packaged source [$entryRelative] is not a regular directory.");
            }

            continue;
        }
        if (! $entry->isFile() || $mode !== 0100000 || ($stat['nlink'] ?? 0) !== 1) {
            throw new RuntimeException("Packaged source [$entryRelative] is not a safe regular file.");
        }
    }
}

function docaraAssertTrustedPackagedFile(string $packageRoot, string $relative): void
{
    if (! docaraCatalogSafePath($relative)) {
        throw new RuntimeException("Packaged source [$relative] has an unsafe path.");
    }
    $segments = explode('/', $relative);
    $cursor = rtrim($packageRoot, '/\\');
    $packageStat = @lstat($cursor);
    if (! is_array($packageStat)
        || is_link($cursor)
        || (($packageStat['mode'] ?? 0) & 0170000) !== 0040000
    ) {
        throw new RuntimeException('Packaged source root is missing or unsafe.');
    }
    foreach ($segments as $index => $segment) {
        $cursor .= '/' . $segment;
        $segmentStat = @lstat($cursor);
        $expectedMode = $index === count($segments) - 1 ? 0100000 : 0040000;
        if (! is_array($segmentStat)
            || is_link($cursor)
            || (($segmentStat['mode'] ?? 0) & 0170000) !== $expectedMode
        ) {
            throw new RuntimeException("Packaged source [$relative] has an unsafe path segment.");
        }
    }
    $packageReal = realpath($packageRoot);
    $path = rtrim($packageRoot, '/\\') . '/' . $relative;
    $stat = @lstat($path);
    $real = realpath($path);
    if (! is_string($packageReal)
        || ! is_string($real)
        || ! is_array($stat)
        || is_link($path)
        || (($stat['mode'] ?? 0) & 0170000) !== 0100000
        || ($stat['nlink'] ?? 0) !== 1
        || ! str_starts_with($real, rtrim($packageReal, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR)
    ) {
        throw new RuntimeException("Packaged source [$relative] is missing or unsafe.");
    }
}

/** @return list<string> */
function docaraSafeFileInventory(string $root, string $relative): array
{
    if (! docaraCatalogSafePath($relative)) {
        throw new RuntimeException("Generated directory [$relative] has an unsafe path.");
    }
    $directory = rtrim($root, '/\\') . '/' . $relative;
    $directoryStat = @lstat($directory);
    $directoryReal = realpath($directory);
    if (! is_array($directoryStat)
        || is_link($directory)
        || (($directoryStat['mode'] ?? 0) & 0170000) !== 0040000
        || ! is_string($directoryReal)
        || ($directoryReal !== $root && ! str_starts_with($directoryReal, $root . DIRECTORY_SEPARATOR))
    ) {
        throw new RuntimeException("Generated directory [$relative] is missing or unsafe.");
    }

    $files = [];
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST,
    );
    foreach ($iterator as $entry) {
        $path = $entry->getPathname();
        $entryRelative = str_replace('\\', '/', substr($path, strlen($root) + 1));
        $stat = @lstat($path);
        $real = realpath($path);
        if ($entry->isLink()
            || is_link($path)
            || ! is_array($stat)
            || ! is_string($real)
            || ($real !== $root && ! str_starts_with($real, $root . DIRECTORY_SEPARATOR))
        ) {
            throw new RuntimeException("Generated catalogue artifact [$entryRelative] is unsafe.");
        }
        $mode = ($stat['mode'] ?? 0) & 0170000;
        if ($entry->isDir()) {
            if ($mode !== 0040000) {
                throw new RuntimeException("Generated catalogue artifact [$entryRelative] is unsafe.");
            }

            continue;
        }
        if (! $entry->isFile() || $mode !== 0100000 || ($stat['nlink'] ?? 0) !== 1) {
            throw new RuntimeException("Generated catalogue artifact [$entryRelative] is unsafe.");
        }
        $files[] = $entryRelative;
    }
    sort($files, SORT_STRING);

    return $files;
}

/** @return array<string, mixed> */
function docaraCatalogPagesReceipt(string $path): array
{
    if (! docaraSafeRegularFile($path)) {
        throw new RuntimeException('Generated component catalogue page receipt is missing or unsafe.');
    }
    $receipt = json_decode(
        (string) file_get_contents($path),
        true,
        512,
        JSON_THROW_ON_ERROR,
    );
    if (! is_array($receipt)
        || array_is_list($receipt)
        || ! docaraExactKeys(
            $receipt,
            ['schema', 'version', 'catalog_content_sha256', 'content_sha256', 'index', 'pages'],
        )
        || ($receipt['schema'] ?? null) !== 'docara.component_catalog_pages.v1'
        || ($receipt['version'] ?? null) !== 1
        || ! is_string($receipt['catalog_content_sha256'] ?? null)
        || preg_match('/\A[a-f0-9]{64}\z/D', $receipt['catalog_content_sha256']) !== 1
        || ! is_string($receipt['content_sha256'] ?? null)
        || preg_match('/\A[a-f0-9]{64}\z/D', $receipt['content_sha256']) !== 1
        || ! is_array($receipt['index'] ?? null)
        || array_is_list($receipt['index'])
        || ! docaraExactKeys($receipt['index'], ['output', 'route', 'contract_fragment_sha256'])
        || ! is_array($receipt['pages'] ?? null)
        || ! array_is_list($receipt['pages'])
        || $receipt['pages'] === []
    ) {
        throw new RuntimeException('Generated component catalogue page receipt root contract is invalid.');
    }
    if (($receipt['index']['output'] ?? null) !== 'components/catalog/index.html'
        || ! is_string($receipt['index']['route'] ?? null)
        || ! is_string($receipt['index']['contract_fragment_sha256'] ?? null)
        || preg_match('/\A[a-f0-9]{64}\z/D', $receipt['index']['contract_fragment_sha256']) !== 1
    ) {
        throw new RuntimeException('Generated component catalogue index receipt is invalid.');
    }

    $previousId = null;
    foreach ($receipt['pages'] as $index => $page) {
        if (! is_array($page)
            || array_is_list($page)
            || ! docaraExactKeys(
                $page,
                [
                    'id',
                    'family',
                    'output',
                    'route',
                    'example_ref',
                    'catalog_entry_sha256',
                    'example_sha256',
                    'rendered_fragment_sha256',
                    'contract_fragment_sha256',
                ],
            )
            || ! is_string($page['id'] ?? null)
            || preg_match('/\A[a-z][a-z0-9_]*(?:\.[a-z][a-z0-9_]*)+\z/D', $page['id']) !== 1
            || ! in_array($page['family'] ?? null, ['native_markdown', 'docara_typed', 'framework_smart'], true)
            || ! is_string($page['output'] ?? null)
            || ! docaraCatalogSafePath($page['output'])
            || ! str_ends_with($page['output'], '/index.html')
            || ! is_string($page['route'] ?? null)
            || ! is_string($page['example_ref'] ?? null)
            || ! docaraCatalogSafePath($page['example_ref'])
        ) {
            throw new RuntimeException("Generated component catalogue page receipt record [$index] is invalid.");
        }
        foreach ([
            'catalog_entry_sha256',
            'example_sha256',
            'rendered_fragment_sha256',
            'contract_fragment_sha256',
        ] as $hashKey) {
            if (! is_string($page[$hashKey] ?? null)
                || preg_match('/\A[a-f0-9]{64}\z/D', $page[$hashKey]) !== 1
            ) {
                throw new RuntimeException(
                    "Generated component catalogue page receipt record [$index] has an invalid hash.",
                );
            }
        }
        if ($previousId !== null && strcmp($previousId, $page['id']) >= 0) {
            throw new RuntimeException(
                'Generated component catalogue page receipt records are not strictly sorted or contain duplicates.',
            );
        }
        $previousId = $page['id'];
    }

    $calculated = hash('sha256', docaraCanonicalJson([
        'catalog_content_sha256' => $receipt['catalog_content_sha256'],
        'index' => $receipt['index'],
        'pages' => $receipt['pages'],
    ]));
    if (! hash_equals($calculated, $receipt['content_sha256'])) {
        throw new RuntimeException(
            'Generated component catalogue page receipt content_sha256 does not match its canonical content.',
        );
    }

    return $receipt;
}

function docaraCatalogContractFragment(string $html, string $xpathExpression): string
{
    $document = new DOMDocument('1.0', 'UTF-8');
    $previous = libxml_use_internal_errors(true);
    $loaded = $document->loadHTML(
        '<?xml encoding="UTF-8">' . $html,
        LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING | LIBXML_COMPACT,
    );
    libxml_clear_errors();
    libxml_use_internal_errors($previous);
    if ($loaded !== true) {
        throw new RuntimeException('Generated component catalogue HTML could not be parsed.');
    }
    $nodes = (new DOMXPath($document))->query($xpathExpression);
    if ($nodes === false || $nodes->length !== 1) {
        throw new RuntimeException(
            "Generated component catalogue HTML requires exactly one contract node [$xpathExpression].",
        );
    }
    $fragment = $document->saveHTML($nodes->item(0));
    if (! is_string($fragment) || $fragment === '') {
        throw new RuntimeException('Generated component catalogue contract node could not be serialized.');
    }

    return $fragment;
}

function docaraCatalogContractText(string $html, string $xpathExpression): string
{
    $document = new DOMDocument('1.0', 'UTF-8');
    $previous = libxml_use_internal_errors(true);
    $loaded = $document->loadHTML(
        '<?xml encoding="UTF-8">' . $html,
        LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING | LIBXML_COMPACT,
    );
    libxml_clear_errors();
    libxml_use_internal_errors($previous);
    if ($loaded !== true) {
        throw new RuntimeException('Generated component catalogue HTML could not be parsed.');
    }
    $nodes = (new DOMXPath($document))->query($xpathExpression);
    if ($nodes === false || $nodes->length !== 1) {
        throw new RuntimeException(
            "Generated component catalogue HTML requires exactly one source node [$xpathExpression].",
        );
    }

    return str_replace(["\r\n", "\r"], "\n", (string) $nodes->item(0)?->textContent);
}

/** @param array<string, mixed> $expectedPage */
function docaraAssertCatalogShellContract(
    string $html,
    array $expectedPage,
    string $catalogRoute,
): void {
    $document = new DOMDocument('1.0', 'UTF-8');
    $previous = libxml_use_internal_errors(true);
    $loaded = $document->loadHTML(
        '<?xml encoding="UTF-8">' . $html,
        LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING | LIBXML_COMPACT,
    );
    libxml_clear_errors();
    libxml_use_internal_errors($previous);
    if ($loaded !== true) {
        throw new RuntimeException('Generated component catalogue shell could not be parsed.');
    }
    $xpath = new DOMXPath($document);
    $count = static function (string $expression) use ($xpath): int {
        $nodes = $xpath->query($expression);
        if ($nodes === false) {
            throw new RuntimeException(
                "Generated component catalogue shell XPath is invalid [$expression].",
            );
        }

        return $nodes->length;
    };
    $hasClass = static function (DOMElement $element, string $class): bool {
        return in_array(
            $class,
            preg_split('/\s+/', trim($element->getAttribute('class'))) ?: [],
            true,
        );
    };

    if ($count('/html') !== 1
        || $count('/html/head') !== 1
        || $count('/html/body') !== 1
        || $count('//*[@id="docara-main"]') !== 1
        || $count('//*[@id="docara-main"]/article[contains(concat(" ", normalize-space(@class), " "), " docara-prose ")]') !== 1
        || $count('//header[contains(concat(" ", normalize-space(@class), " "), " docara-header ")]') !== 1
        || $count('//*[@id="docara-reader-settings-dialog"]') !== 1
        || $count('//script[@data-docara-shell-controller]') !== 1
    ) {
        throw new RuntimeException(
            'Generated component catalogue shell is missing a required unique landmark.',
        );
    }

    $expectedBody = ['skip', 'header'];
    if (($expectedPage['search_enabled'] ?? false) === true) {
        $expectedBody[] = 'search';
    }
    $expectedBody = [...$expectedBody, 'reader', 'layout', 'controller'];
    $actualBody = [];
    $bodyChildren = $xpath->query('/html/body/*');
    if ($bodyChildren === false) {
        throw new RuntimeException('Generated component catalogue body could not be inspected.');
    }
    foreach ($bodyChildren as $child) {
        if (! $child instanceof DOMElement) {
            continue;
        }
        $actualBody[] = match (true) {
            $child->tagName === 'a' && $hasClass($child, 'docara-skip-link') => 'skip',
            $child->tagName === 'header' && $hasClass($child, 'docara-header') => 'header',
            $child->tagName === 'dialog' && $child->getAttribute('id') === 'docara-search-dialog' => 'search',
            $child->tagName === 'dialog' && $child->getAttribute('id') === 'docara-reader-settings-dialog' => 'reader',
            $child->tagName === 'div' && $hasClass($child, 'docara-docs-layout') => 'layout',
            $child->tagName === 'script' && $child->hasAttribute('data-docara-shell-controller') => 'controller',
            default => 'unexpected:' . $child->tagName,
        };
    }
    if ($actualBody !== $expectedBody) {
        throw new RuntimeException(
            'Generated component catalogue shell body structure does not match the strict contract.',
        );
    }

    $rootSelector = ($expectedPage['component_catalog_kind'] ?? null) === 'index'
        ? '//*[@id="docara-main"]/article/*[@data-docara-component-catalog-index]'
        : '//*[@id="docara-main"]/article/*[@data-docara-component-detail="'
            . (string) ($expectedPage['component_catalog_id'] ?? '') . '"]';
    if ($count('//*[@id="docara-main"]/article/*') !== 1 || $count($rootSelector) !== 1) {
        throw new RuntimeException(
            'Generated component catalogue shell contains missing or injected article content.',
        );
    }
    if ($count('//nav[contains(concat(" ", normalize-space(@class), " "), " docara-navigation ")]') !== 2
        || $count(
            '//nav[contains(concat(" ", normalize-space(@class), " "), " docara-navigation ")]'
            . '//a[@href="' . $catalogRoute . '" and @aria-current="page"]',
        ) !== 2
    ) {
        throw new RuntimeException(
            'Generated component catalogue shell lost its active desktop or mobile navigation context.',
        );
    }
    if ($count('//*[@data-docara-outline]') !== 2) {
        throw new RuntimeException(
            'Generated component catalogue shell requires matching desktop and mobile outlines.',
        );
    }

    $expectedBreadcrumbs = $expectedPage['component_catalog_breadcrumbs'] ?? null;
    if (! is_array($expectedBreadcrumbs) || $expectedBreadcrumbs === []) {
        throw new RuntimeException('Trusted component catalogue breadcrumbs are unavailable.');
    }
    $breadcrumbNodes = $xpath->query(
        '//*[@data-docara-breadcrumbs]/*['
        . 'contains(concat(" ", normalize-space(@class), " "), " sf-breadcrumbs-item ")'
        . ' and not(@data-sf-breadcrumb-separator)]',
    );
    if ($breadcrumbNodes === false || $breadcrumbNodes->length !== count($expectedBreadcrumbs)) {
        throw new RuntimeException(
            'Generated component catalogue breadcrumbs do not match the strict shell contract.',
        );
    }
    foreach ($expectedBreadcrumbs as $index => $expectedBreadcrumb) {
        $node = $breadcrumbNodes->item($index);
        if (! $node instanceof DOMElement
            || trim((string) $node->textContent) !== (string) ($expectedBreadcrumb['title'] ?? '')
        ) {
            throw new RuntimeException(
                'Generated component catalogue breadcrumb labels do not match the trusted projection.',
            );
        }
        $expectedUrl = $expectedBreadcrumb['url'] ?? null;
        if (is_string($expectedUrl)) {
            if ($node->tagName !== 'a' || $node->getAttribute('href') !== $expectedUrl) {
                throw new RuntimeException(
                    'Generated component catalogue breadcrumb links do not match the trusted projection.',
                );
            }
        } elseif ($index !== count($expectedBreadcrumbs) - 1
            || $node->tagName !== 'span'
            || $node->getAttribute('aria-current') !== 'page'
        ) {
            throw new RuntimeException(
                'Generated component catalogue current breadcrumb is invalid.',
            );
        }
    }

    foreach (['previous' => 'prev', 'next' => 'next'] as $field => $relation) {
        $expected = $expectedPage['component_catalog_' . $field] ?? null;
        $links = $xpath->query('//*[@data-docara-previous-next]/a[@rel="' . $relation . '"]');
        if ($links === false) {
            throw new RuntimeException('Generated component catalogue adjacency could not be inspected.');
        }
        if ($expected === null) {
            if ($links->length !== 0) {
                throw new RuntimeException(
                    "Generated component catalogue unexpectedly contains a [$relation] link.",
                );
            }

            continue;
        }
        $link = $links->item(0);
        if ($links->length !== 1
            || ! $link instanceof DOMElement
            || $link->getAttribute('href') !== (string) ($expected['url'] ?? '')
            || ! str_contains(trim((string) $link->textContent), (string) ($expected['title'] ?? ''))
        ) {
            throw new RuntimeException(
                "Generated component catalogue [$relation] link does not match the trusted projection.",
            );
        }
    }
    $expectsAdjacency = ($expectedPage['component_catalog_previous'] ?? null) !== null
        || ($expectedPage['component_catalog_next'] ?? null) !== null;
    if ($count('//*[@data-docara-previous-next]') !== ($expectsAdjacency ? 1 : 0)) {
        throw new RuntimeException(
            'Generated component catalogue adjacency container does not match the strict shell contract.',
        );
    }
}

$rootInput = $argv[1] ?? '';
if ($rootInput === '' || count($argv) !== 2) {
    fwrite(STDERR, "Usage: php scripts/verify-static-build.php <build-directory>\n");

    exit(2);
}

$lexicalInput = str_replace('\\', '/', rtrim($rootInput, '/\\'));
foreach (explode('/', $lexicalInput) as $segment) {
    if ($segment === '.' || $segment === '..') {
        fwrite(STDERR, "Build directory is missing or unsafe.\n");

        exit(1);
    }
}
$ancestor = rtrim($rootInput, '/\\');
while ($ancestor !== '' && $ancestor !== '.' && $ancestor !== DIRECTORY_SEPARATOR) {
    if (is_link($ancestor)) {
        fwrite(STDERR, "Build directory is missing or unsafe.\n");

        exit(1);
    }
    $parent = dirname($ancestor);
    if ($parent === $ancestor) {
        break;
    }
    $ancestor = rtrim($parent, '/\\');
}

$inputStat = @lstat($rootInput);
if (is_link($rootInput)
    || ! is_array($inputStat)
    || (($inputStat['mode'] ?? 0) & 0170000) !== 0040000
) {
    fwrite(STDERR, "Build directory is missing or unsafe.\n");

    exit(1);
}

$root = realpath($rootInput);
if ($root === false || ! is_dir($root)) {
    fwrite(STDERR, "Build directory is missing or unsafe.\n");

    exit(1);
}

$htmlFiles = [];
$unsafeArtifactEntries = [];
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
    RecursiveIteratorIterator::SELF_FIRST,
);
foreach ($iterator as $file) {
    $path = $file->getPathname();
    $relativeEntry = str_replace('\\', '/', substr($path, strlen($root) + 1));
    if ($file->isLink() || is_link($path)) {
        $unsafeArtifactEntries[] = $relativeEntry;

        continue;
    }
    if ($file->isFile()) {
        $stat = @lstat($path);
        if (! is_array($stat)
            || (($stat['mode'] ?? 0) & 0170000) !== 0100000
            || ($stat['nlink'] ?? 1) > 1
        ) {
            $unsafeArtifactEntries[] = $relativeEntry;

            continue;
        }

        if (strtolower($file->getExtension()) === 'html') {
            $htmlFiles[] = $path;
        }

        continue;
    }
    if (! $file->isDir()) {
        $unsafeArtifactEntries[] = $relativeEntry;
    }
}
sort($htmlFiles, SORT_STRING);
sort($unsafeArtifactEntries, SORT_STRING);

$deploymentBase = '/';
$manifestDirectory = $root . '/.docara';
$manifestPath = $root . '/.docara/resolved-page-plans.json';
$manifestError = null;
$manifestOutputs = [];
$manifestPageRecords = [];
$searchEnabled = false;
$expectedSearchSurfaceCount = 0;
$expectedSearchDocuments = [];
$expectedFrameworkPair = null;
$expectedFrameworkProviderRevision = null;
$expectedFrameworkSmartRevision = null;
$expectedSupportedComponents = null;
$expectedConsumerPolicyHash = null;
$expectedFrameworkLock = null;
$expectedBuildLocale = null;
$expectedDocumentationVersion = null;
$expectedRedirectSource = null;
$redirectSourceInitialized = false;
$trustedCatalog = null;
$trustedCatalogVerified = false;
$manifestDirectoryStat = @lstat($manifestDirectory);
if (is_link($manifestDirectory)
    || ! is_array($manifestDirectoryStat)
    || (($manifestDirectoryStat['mode'] ?? 0) & 0170000) !== 0040000
) {
    $manifestError = 'Resolved page-plan manifest is missing or unsafe.';
} else {
    $manifestStat = @lstat($manifestPath);
    if (is_link($manifestPath)
        || ! is_array($manifestStat)
        || (($manifestStat['mode'] ?? 0) & 0170000) !== 0100000
        || ($manifestStat['nlink'] ?? 1) > 1
    ) {
        $manifestError = 'Resolved page-plan manifest is missing or unsafe.';
    } else {
        try {
            $manifest = json_decode((string) file_get_contents($manifestPath), true, 512, JSON_THROW_ON_ERROR);
            if (! is_array($manifest) || ($manifest['schema'] ?? null) !== 'docara.resolved_page_plans.v1') {
                throw new RuntimeException('Resolved page-plan manifest has an unsupported schema.');
            }
            $pages = $manifest['pages'] ?? null;
            if (! is_array($pages) || ! array_is_list($pages) || $pages === []) {
                throw new RuntimeException('Resolved page-plan manifest must contain a non-empty page list.');
            }
            $bases = [];
            $outputs = [];
            foreach ($pages as $index => $page) {
                if (! is_array($page)) {
                    throw new RuntimeException("Resolved page-plan record [$index] must be an object.");
                }
                $configuration = $page['resolved_page_plan']['configuration'] ?? null;
                if (! is_array($configuration)) {
                    throw new RuntimeException("Resolved page-plan record [$index] has no configuration.");
                }
                $base = $configuration['base_url'] ?? null;
                if (! is_string($base) || $base === '') {
                    throw new RuntimeException("Resolved page-plan record [$index] is missing base_url.");
                }
                $bases[$base] = true;
                $pageLocale = $configuration['locale']
                    ?? $configuration['default_locale']
                    ?? 'en';
                $buildLocale = $configuration['default_locale']
                    ?? $configuration['locale']
                    ?? 'en';
                $documentationVersion = $configuration['documentation_version'] ?? 'current';
                if (! is_string($pageLocale)
                    || ! is_string($buildLocale)
                    || ! is_string($documentationVersion)
                    || preg_match('/\A[a-z]{2}(?:-[A-Z]{2})?\z/D', $pageLocale) !== 1
                    || preg_match('/\A[a-z]{2}(?:-[A-Z]{2})?\z/D', $buildLocale) !== 1
                    || preg_match('/\A[A-Za-z0-9](?:[A-Za-z0-9._-]{0,62}[A-Za-z0-9])?\z/D', $documentationVersion) !== 1
                ) {
                    throw new RuntimeException("Resolved page-plan record [$index] has invalid locale or documentation version metadata.");
                }
                if ($pageLocale !== $buildLocale) {
                    throw new RuntimeException("Resolved page-plan record [$index] does not match the one-locale build contract.");
                }
                $redirectSource = $configuration['redirects_file'] ?? null;
                if ($redirectSource !== null
                    && (! is_string($redirectSource) || ! docaraCatalogSafePath($redirectSource))
                ) {
                    throw new RuntimeException("Resolved page-plan record [$index] has an unsafe redirect source.");
                }
                if (! $redirectSourceInitialized) {
                    $expectedRedirectSource = $redirectSource;
                    $redirectSourceInitialized = true;
                } elseif ($redirectSource !== $expectedRedirectSource) {
                    throw new RuntimeException('Resolved pages do not share one redirect source contract.');
                }
                $expectedBuildLocale ??= $buildLocale;
                $expectedDocumentationVersion ??= $documentationVersion;
                if ($buildLocale !== $expectedBuildLocale
                    || $documentationVersion !== $expectedDocumentationVersion
                ) {
                    throw new RuntimeException('Resolved pages do not share one locale and documentation version.');
                }

                $output = $page['output'] ?? null;
                if (! is_string($output)
                    || preg_match('#\A(?:[A-Za-z0-9][A-Za-z0-9._~-]*/)*[A-Za-z0-9][A-Za-z0-9._~-]*\.html\z#', $output) !== 1
                ) {
                    throw new RuntimeException("Resolved page-plan record [$index] has an unsafe output.");
                }
                if (isset($outputs[$output])) {
                    throw new RuntimeException("Resolved page-plan output [$output] is duplicated.");
                }
                $outputs[$output] = true;
                $manifestPageRecords[] = $page;

                $diagnostics = $page['component_runtime']['diagnostics'] ?? null;
                $runtimePair = is_array($diagnostics) ? ($diagnostics['runtime_pair'] ?? null) : null;
                $providerRevision = is_array($diagnostics) ? ($diagnostics['provider_revision'] ?? null) : null;
                $supportedComponents = is_array($diagnostics) ? ($diagnostics['supported_components'] ?? null) : null;
                $consumerPolicyHash = is_array($diagnostics) ? ($diagnostics['consumer_policy_sha256'] ?? null) : null;
                $frameworkLock = $page['resolved_page_plan']['framework_lock'] ?? null;
                $smartRevision = is_array($frameworkLock)
                    ? ($frameworkLock['runtime']['ui_smart']['commit'] ?? null)
                    : null;
                if (! is_string($runtimePair)
                    || preg_match('/\A[a-z0-9][a-z0-9._-]+\z/D', $runtimePair) !== 1
                    || ! is_string($providerRevision)
                    || preg_match('/\A[a-f0-9]{40}\z/D', $providerRevision) !== 1
                    || ! is_array($supportedComponents)
                    || ! array_is_list($supportedComponents)
                    || $supportedComponents === []
                    || ! is_string($consumerPolicyHash)
                    || preg_match('/\A[a-f0-9]{64}\z/D', $consumerPolicyHash) !== 1
                    || ! is_array($frameworkLock)
                    || array_is_list($frameworkLock)
                    || ! is_string($smartRevision)
                    || preg_match('/\A[a-f0-9]{40}\z/D', $smartRevision) !== 1
                ) {
                    throw new RuntimeException("Resolved page-plan record [$index] has an incomplete component runtime contract.");
                }
                foreach ($supportedComponents as $component) {
                    if (! is_string($component)
                        || preg_match('/\Aui(?:\.[a-z][a-z0-9_]*)+\z/D', $component) !== 1
                    ) {
                        throw new RuntimeException("Resolved page-plan record [$index] has an invalid supported component.");
                    }
                }
                $sortedSupportedComponents = array_values(array_unique($supportedComponents));
                sort($sortedSupportedComponents, SORT_STRING);
                if ($supportedComponents !== $sortedSupportedComponents) {
                    throw new RuntimeException("Resolved page-plan record [$index] has unsorted or duplicate supported components.");
                }
                $expectedFrameworkPair ??= $runtimePair;
                $expectedFrameworkProviderRevision ??= $providerRevision;
                $expectedFrameworkSmartRevision ??= $smartRevision;
                $expectedSupportedComponents ??= $supportedComponents;
                $expectedConsumerPolicyHash ??= $consumerPolicyHash;
                $expectedFrameworkLock ??= $frameworkLock;
                if ($runtimePair !== $expectedFrameworkPair
                    || $providerRevision !== $expectedFrameworkProviderRevision
                    || $smartRevision !== $expectedFrameworkSmartRevision
                    || $supportedComponents !== $expectedSupportedComponents
                    || $consumerPolicyHash !== $expectedConsumerPolicyHash
                    || docaraCanonicalJson($frameworkLock) !== docaraCanonicalJson($expectedFrameworkLock)
                ) {
                    throw new RuntimeException('Resolved pages do not share one exact component runtime contract.');
                }

                $outputPath = $root . '/' . $output;
                $outputStat = @lstat($outputPath);
                $realOutput = realpath($outputPath);
                if (is_link($outputPath)
                    || ! is_array($outputStat)
                    || (($outputStat['mode'] ?? 0) & 0170000) !== 0100000
                    || ($outputStat['nlink'] ?? 1) > 1
                    || $realOutput === false
                    || ($realOutput !== $root && ! str_starts_with($realOutput, $root . DIRECTORY_SEPARATOR))
                ) {
                    throw new RuntimeException("Resolved page-plan output [$output] is missing or unsafe.");
                }
            }
            $manifestOutputs = array_keys($outputs);
            sort($manifestOutputs, SORT_STRING);
            if (count($bases) !== 1) {
                throw new RuntimeException('Resolved plans must contain one deployment base.');
            }
            $configuredBase = (string) array_key_first($bases);
            if (preg_match('#\A(?:/|/[A-Za-z0-9._~-]+(?:/[A-Za-z0-9._~-]+)*/?)\z#', $configuredBase) !== 1) {
                throw new RuntimeException('Resolved deployment base is unsafe.');
            }
            foreach (explode('/', trim($configuredBase, '/')) as $segment) {
                if ($segment === '.' || $segment === '..') {
                    throw new RuntimeException('Resolved deployment base is unsafe.');
                }
            }
            $deploymentBase = $configuredBase === '/' ? '/' : '/' . trim($configuredBase, '/') . '/';
            $manifestBuild = $manifest['build'] ?? null;
            if (! is_array($manifestBuild)
                || ! docaraExactKeys($manifestBuild, ['locale', 'documentation_version'])
                || ($manifestBuild['locale'] ?? null) !== $expectedBuildLocale
                || ($manifestBuild['documentation_version'] ?? null) !== $expectedDocumentationVersion
            ) {
                throw new RuntimeException(
                    'Resolved build metadata is required and must match page locale and version metadata.',
                );
            }

            foreach ($manifestPageRecords as $index => $page) {
                $search = $page['resolved_page_plan']['configuration']['search'] ?? null;
                if (is_array($search) && ($search['enabled'] ?? false) === true) {
                    $searchEnabled = true;
                    $expectedSearchSurfaceCount++;
                }
            }
            if ($searchEnabled) {
                foreach ($manifestPageRecords as $index => $page) {
                    $configuration = $page['resolved_page_plan']['configuration'] ?? null;
                    $search = is_array($configuration) ? ($configuration['search'] ?? null) : null;
                    if (! is_array($search)
                        || ! is_bool($search['enabled'] ?? null)
                        || ! is_bool($search['indexed'] ?? null)
                    ) {
                        throw new RuntimeException("Resolved page-plan record [$index] has an incomplete search contract.");
                    }
                    if ($search['indexed'] !== true) {
                        continue;
                    }
                    $url = $page['url'] ?? null;
                    $locale = $configuration['locale'] ?? $configuration['default_locale'] ?? null;
                    if (! is_string($url)
                        || ! docaraSearchUrlIsSafe($url, $deploymentBase)
                        || ! is_string($locale)
                        || preg_match('/\A[a-z]{2}(?:-[A-Z]{2})?\z/D', $locale) !== 1
                    ) {
                        throw new RuntimeException("Resolved page-plan record [$index] has unsafe search identity fields.");
                    }
                    $expectedSearchDocuments[] = $locale . "\0" . $url;
                }
                sort($expectedSearchDocuments, SORT_STRING);
                if ($expectedSearchDocuments === []) {
                    throw new RuntimeException('Search is enabled but the manifest has no indexed page.');
                }
            }
        } catch (Throwable $exception) {
            $manifestError = $exception->getMessage();
        }
    }
}

$redirectError = null;
if ($manifestError === null) {
    try {
        $redirectOutputs = docaraRedirectOutputs(
            $root,
            $deploymentBase,
            $manifestOutputs,
            $manifestPageRecords,
            (string) ($expectedBuildLocale ?? 'en'),
            (string) ($expectedDocumentationVersion ?? 'current'),
            $expectedRedirectSource,
        );
        $manifestOutputs = [...$manifestOutputs, ...$redirectOutputs];
        sort($manifestOutputs, SORT_STRING);
    } catch (Throwable $exception) {
        $redirectError = $exception->getMessage();
    }
}

$htmlBuildIdentityProblems = [];
if ($manifestError === null) {
    foreach ($manifestPageRecords as $page) {
        $output = $page['output'] ?? null;
        $configuration = $page['resolved_page_plan']['configuration'] ?? null;
        if (! is_string($output) || ! is_array($configuration)) {
            continue;
        }
        $locale = $configuration['locale']
            ?? $configuration['default_locale']
            ?? 'en';
        $documentationVersion = $configuration['documentation_version']
            ?? 'current';
        $html = file_get_contents($root . '/' . $output);
        try {
            if (! is_string($locale)
                || ! is_string($documentationVersion)
                || ! is_string($html)
            ) {
                throw new RuntimeException(
                    'Resolved page HTML identity inputs are incomplete.',
                );
            }
            docaraAssertHtmlBuildIdentity($html, $locale, $documentationVersion);
        } catch (Throwable $exception) {
            $htmlBuildIdentityProblems[] = [
                'page' => $output,
                'reference' => '@page-build-identity',
                'target' => $exception->getMessage(),
            ];
        }
    }
}

$htmlIds = [];
$htmlIdProblems = [];
foreach ($htmlFiles as $htmlFile) {
    $relativePage = str_replace('\\', '/', substr($htmlFile, strlen($root) + 1));
    $html = file_get_contents($htmlFile);
    if (! is_string($html)) {
        continue;
    }
    try {
        $inventory = docaraHtmlIdInventory($html);
        $htmlIds[$relativePage] = $inventory['ids'];
        foreach ($inventory['duplicates'] as $duplicate) {
            $htmlIdProblems[] = [
                'page' => $relativePage,
                'reference' => '@duplicate-html-id',
                'target' => $duplicate,
            ];
        }
    } catch (Throwable $exception) {
        $htmlIdProblems[] = [
            'page' => $relativePage,
            'reference' => '@html-fragment-inventory',
            'target' => $exception->getMessage(),
        ];
    }
}

$checked = 0;
$searchIndexReferences = [];
$searchRuntimeReferences = [];
$broken = array_map(
    static fn (string $entry): array => [
        'page' => '@build',
        'reference' => '@unsafe-artifact-entry',
        'target' => $entry,
    ],
    $unsafeArtifactEntries,
);
array_push($broken, ...$htmlIdProblems);
array_push($broken, ...$htmlBuildIdentityProblems);
if ($manifestError !== null) {
    $broken[] = ['page' => '@build', 'reference' => '@resolved-page-plans', 'target' => $manifestError];
} else {
    if ($redirectError !== null) {
        $broken[] = ['page' => '@build', 'reference' => '@redirect-contract', 'target' => $redirectError];
    }
    $actualHtmlOutputs = array_map(
        static fn (string $path): string => str_replace('\\', '/', substr($path, strlen($root) + 1)),
        $htmlFiles,
    );
    sort($actualHtmlOutputs, SORT_STRING);
    if ($actualHtmlOutputs !== $manifestOutputs) {
        $broken[] = [
            'page' => '@build',
            'reference' => '@resolved-page-plans',
            'target' => 'Resolved page-plan outputs do not exactly match generated HTML files.',
        ];
    }

    try {
        docaraBootstrapTrustedSource();
        (new SchemaRepository)->assertValid(
            $expectedFrameworkLock,
            'framework-lock.schema.json',
        );
        $projection = is_array($expectedFrameworkLock)
            ? ($expectedFrameworkLock['asset_projection'] ?? null)
            : null;
        $files = is_array($projection) ? ($projection['files'] ?? null) : null;
        if (! is_array($files) || array_is_list($files) || $files === []) {
            throw new RuntimeException('Exact Framework asset projection is missing.');
        }

        $frameworkRoot = $root . '/_docara/framework';
        $frameworkRootStat = @lstat($frameworkRoot);
        if (is_link($frameworkRoot)
            || ! is_array($frameworkRootStat)
            || (($frameworkRootStat['mode'] ?? 0) & 0170000) !== 0040000
        ) {
            throw new RuntimeException('Materialized Framework asset directory is missing or unsafe.');
        }

        $expectedAssets = [];
        foreach ($files as $relativePath => $record) {
            if (! is_string($relativePath)
                || ! docaraCatalogSafePath($relativePath)
                || ! is_array($record)
                || array_is_list($record)
                || ! is_string($record['sha256'] ?? null)
                || preg_match('/\A[a-f0-9]{64}\z/D', $record['sha256']) !== 1
            ) {
                throw new RuntimeException('Exact Framework asset projection contains an invalid record.');
            }
            $path = $frameworkRoot . '/' . $relativePath;
            if (! docaraSafeRegularFile($path)) {
                throw new RuntimeException("Projected Framework asset [$relativePath] is missing or unsafe.");
            }
            $actualSha = hash_file('sha256', $path);
            if (! is_string($actualSha) || ! hash_equals($record['sha256'], $actualSha)) {
                throw new RuntimeException("Projected Framework asset [$relativePath] has an incorrect SHA-256.");
            }
            $expectedAssets[] = $relativePath;
        }
        sort($expectedAssets, SORT_STRING);

        $actualAssets = [];
        $frameworkIterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($frameworkRoot, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::LEAVES_ONLY,
        );
        foreach ($frameworkIterator as $file) {
            $path = $file->getPathname();
            $relativePath = str_replace('\\', '/', substr($path, strlen($frameworkRoot) + 1));
            if (! $file->isFile() || ! docaraSafeRegularFile($path)) {
                throw new RuntimeException("Materialized Framework asset [$relativePath] is unsafe.");
            }
            $actualAssets[] = $relativePath;
        }
        sort($actualAssets, SORT_STRING);
        if ($actualAssets !== $expectedAssets) {
            throw new RuntimeException('Materialized Framework assets do not exactly match the locked projection.');
        }
    } catch (Throwable $exception) {
        $broken[] = [
            'page' => '@build',
            'reference' => '@framework-asset-projection',
            'target' => $exception->getMessage(),
        ];
    }
}
foreach ($htmlFiles as $htmlFile) {
    $html = file_get_contents($htmlFile);
    if (! is_string($html)) {
        $broken[] = ['page' => $htmlFile, 'reference' => '@unreadable'];

        continue;
    }
    if (preg_match('/<\s*base\b/i', $html) === 1) {
        $broken[] = [
            'page' => str_replace('\\', '/', substr($htmlFile, strlen($root) + 1)),
            'reference' => '@html-base-element',
        ];
    }
    preg_match_all(
        '/\bdata-docara-search-index\s*=\s*(?:"([^"]*)"|\'([^\']*)\')/i',
        $html,
        $searchMatches,
        PREG_SET_ORDER,
    );
    foreach ($searchMatches as $match) {
        $searchIndexReferences[] = html_entity_decode(
            $match[1] !== '' ? $match[1] : $match[2],
            ENT_QUOTES | ENT_HTML5,
        );
    }
    preg_match_all(
        '/<script\b(?=[^>]*\bdata-docara-search-runtime\b)[^>]*\bsrc\s*=\s*(?:"([^"]*)"|\'([^\']*)\')[^>]*>/i',
        $html,
        $runtimeMatches,
        PREG_SET_ORDER,
    );
    foreach ($runtimeMatches as $match) {
        $searchRuntimeReferences[] = html_entity_decode(
            $match[1] !== '' ? $match[1] : $match[2],
            ENT_QUOTES | ENT_HTML5,
        );
    }
    preg_match_all('/\b(?:href|src|data-docara-search-index)\s*=\s*(?:"([^"]*)"|\'([^\']*)\')/i', $html, $matches, PREG_SET_ORDER);
    foreach ($matches as $match) {
        $reference = html_entity_decode($match[1] !== '' ? $match[1] : $match[2], ENT_QUOTES | ENT_HTML5);
        if ($reference === ''
            || str_starts_with($reference, '//')
            || preg_match('/\A[a-z][a-z0-9+.-]*:/i', $reference) === 1
        ) {
            continue;
        }

        $checked++;
        $fragmentOffset = strpos($reference, '#');
        $encodedFragment = $fragmentOffset === false ? null : substr($reference, $fragmentOffset + 1);
        $encodedPath = preg_split('/[?#]/', $reference, 2)[0] ?? '';
        if (str_contains($encodedPath, '\\')
            || preg_match('/%(?![0-9A-Fa-f]{2})/', $encodedPath) === 1
        ) {
            $broken[] = [
                'page' => str_replace('\\', '/', substr($htmlFile, strlen($root) + 1)),
                'reference' => $reference,
                'target' => '@unsafe-percent-encoding',
            ];

            continue;
        }
        $decodedSegments = [];
        $unsafeDecodedSegment = false;
        foreach (explode('/', $encodedPath) as $encodedSegment) {
            $decodedSegment = rawurldecode($encodedSegment);
            if (str_contains($decodedSegment, '/')
                || str_contains($decodedSegment, '\\')
                || str_contains($decodedSegment, "\0")
                || preg_match('//u', $decodedSegment) !== 1
                || (($decodedSegment === '.' || $decodedSegment === '..') && $decodedSegment !== $encodedSegment)
            ) {
                $unsafeDecodedSegment = true;
                break;
            }
            $decodedSegments[] = $decodedSegment;
        }
        if ($unsafeDecodedSegment) {
            $broken[] = [
                'page' => str_replace('\\', '/', substr($htmlFile, strlen($root) + 1)),
                'reference' => $reference,
                'target' => '@unsafe-decoded-path-segment',
            ];

            continue;
        }
        $path = implode('/', $decodedSegments);
        $relativePage = str_replace('\\', '/', substr($htmlFile, strlen($root) + 1));
        if ($path === '') {
            $candidate = $relativePage;
        } elseif (str_starts_with($path, '/')) {
            $baseWithoutSlash = rtrim($deploymentBase, '/');
            if ($deploymentBase === '/') {
                $candidate = ltrim($path, '/');
            } elseif ($path === $baseWithoutSlash) {
                $candidate = '';
            } elseif (str_starts_with($path, $deploymentBase)) {
                $candidate = substr($path, strlen($deploymentBase));
            } else {
                $broken[] = [
                    'page' => $relativePage,
                    'reference' => $reference,
                    'target' => '@outside-deployment-base',
                ];

                continue;
            }
        } else {
            $candidate = dirname($relativePage) . '/' . $path;
        }
        $segments = [];
        foreach (explode('/', str_replace('\\', '/', $candidate)) as $segment) {
            if ($segment === '' || $segment === '.') {
                continue;
            }
            if ($segment === '..') {
                if ($segments === []) {
                    $segments = ['@outside-root'];
                    break;
                }
                array_pop($segments);

                continue;
            }
            $segments[] = $segment;
        }
        $relativeTarget = implode('/', $segments);
        if ($relativeTarget === '' || str_ends_with($path, '/')) {
            $relativeTarget = rtrim($relativeTarget, '/') . '/index.html';
            $relativeTarget = ltrim($relativeTarget, '/');
        }
        $target = $root . '/' . $relativeTarget;
        if (is_dir($target) && is_file($target . '/index.html')) {
            $relativeTarget = rtrim($relativeTarget, '/') . '/index.html';
            $target = $root . '/' . $relativeTarget;
        }
        $realTarget = realpath($target);
        if ($realTarget === false
            || ! is_file($realTarget)
            || ($realTarget !== $root && ! str_starts_with($realTarget, $root . DIRECTORY_SEPARATOR))
        ) {
            $broken[] = [
                'page' => $relativePage,
                'reference' => $reference,
                'target' => $relativeTarget,
            ];

            continue;
        }

        if ($encodedFragment !== null && $encodedFragment !== '' && str_ends_with($relativeTarget, '.html')) {
            if (preg_match('/%(?![0-9A-Fa-f]{2})/', $encodedFragment) === 1) {
                $broken[] = [
                    'page' => $relativePage,
                    'reference' => $reference,
                    'target' => '@unsafe-fragment-encoding',
                ];

                continue;
            }
            $fragment = rawurldecode($encodedFragment);
            if (str_contains($fragment, "\0") || preg_match('//u', $fragment) !== 1) {
                $broken[] = [
                    'page' => $relativePage,
                    'reference' => $reference,
                    'target' => '@unsafe-fragment-encoding',
                ];

                continue;
            }
            if (! isset($htmlIds[$relativeTarget][$fragment])) {
                $broken[] = [
                    'page' => $relativePage,
                    'reference' => $reference,
                    'target' => '@missing-fragment:' . $fragment,
                ];
            }
        }
    }
}

if ($manifestError === null) {
    $searchIndexPath = $root . '/_docara/search-index.json';
    $searchRuntimePath = $root . '/_docara/search.js';
    if (! $searchEnabled) {
        if (file_exists($searchIndexPath)
            || file_exists($searchRuntimePath)
            || $searchIndexReferences !== []
            || $searchRuntimeReferences !== []
        ) {
            $broken[] = [
                'page' => '@build',
                'reference' => '@search-artifacts-unexpected',
                'target' => 'Search artifacts or references exist while search is disabled.',
            ];
        }
    } elseif (! docaraSafeRegularFile($searchIndexPath) || ! docaraSafeRegularFile($searchRuntimePath)) {
        $broken[] = [
            'page' => '@build',
            'reference' => '@search-artifacts-missing',
            'target' => 'Enabled search requires safe search-index.json and search.js files.',
        ];
    } else {
        try {
            $searchIndex = json_decode(
                (string) file_get_contents($searchIndexPath),
                true,
                512,
                JSON_THROW_ON_ERROR,
            );
            if (! is_array($searchIndex)
                || array_is_list($searchIndex)
                || ! docaraExactKeys(
                    $searchIndex,
                    ['schema', 'version', 'algorithm', 'content_sha256', 'documents'],
                )
                || ($searchIndex['schema'] ?? null) !== 'docara.search_index.v1'
                || ($searchIndex['version'] ?? null) !== 1
                || ($searchIndex['algorithm'] ?? null) !== 'docara-prefix-v1'
                || ! is_string($searchIndex['content_sha256'] ?? null)
                || preg_match('/\A[a-f0-9]{64}\z/D', $searchIndex['content_sha256']) !== 1
                || ! is_array($searchIndex['documents'] ?? null)
                || ! array_is_list($searchIndex['documents'])
                || $searchIndex['documents'] === []
            ) {
                throw new RuntimeException('Search index root contract is invalid.');
            }

            $actualSearchDocuments = [];
            $documentIds = [];
            $previousIdentity = null;
            foreach ($searchIndex['documents'] as $index => $document) {
                if (! is_array($document)
                    || array_is_list($document)
                    || ! docaraExactKeys(
                        $document,
                        ['id', 'url', 'locale', 'title', 'description', 'trail', 'headings', 'text'],
                    )
                    || ! is_string($document['id'] ?? null)
                    || preg_match('/\A[a-f0-9]{64}\z/D', $document['id']) !== 1
                    || ! is_string($document['url'] ?? null)
                    || ! docaraSearchUrlIsSafe($document['url'], $deploymentBase)
                    || ! is_string($document['locale'] ?? null)
                    || preg_match('/\A[a-z]{2}(?:-[A-Z]{2})?\z/D', $document['locale']) !== 1
                    || ! is_string($document['title'] ?? null)
                    || $document['title'] === ''
                    || ! is_string($document['description'] ?? null)
                    || ! is_array($document['trail'] ?? null)
                    || ! array_is_list($document['trail'])
                    || ! is_array($document['headings'] ?? null)
                    || ! array_is_list($document['headings'])
                    || ! is_string($document['text'] ?? null)
                ) {
                    throw new RuntimeException("Search document [$index] has an invalid contract.");
                }
                foreach ($document['trail'] as $part) {
                    if (! is_string($part) || $part === '') {
                        throw new RuntimeException("Search document [$index] has an invalid trail.");
                    }
                }
                foreach ($document['headings'] as $headingIndex => $heading) {
                    if (! is_array($heading)
                        || array_is_list($heading)
                        || ! docaraExactKeys($heading, ['level', 'text'])
                        || ! is_int($heading['level'] ?? null)
                        || $heading['level'] < 1
                        || $heading['level'] > 6
                        || ! is_string($heading['text'] ?? null)
                        || $heading['text'] === ''
                    ) {
                        throw new RuntimeException(
                            "Search document [$index] heading [$headingIndex] has an invalid contract.",
                        );
                    }
                }

                $identity = $document['locale'] . "\0" . $document['url'];
                if ($previousIdentity !== null && strcmp($previousIdentity, $identity) >= 0) {
                    throw new RuntimeException('Search documents are not strictly sorted or contain duplicates.');
                }
                $previousIdentity = $identity;
                if (isset($documentIds[$document['id']])) {
                    throw new RuntimeException('Search document id is duplicated.');
                }
                $documentIds[$document['id']] = true;
                if ($document['id'] !== hash('sha256', $identity)) {
                    throw new RuntimeException("Search document [$index] id does not match locale and URL.");
                }
                $actualSearchDocuments[] = $identity;
            }

            if ($actualSearchDocuments !== $expectedSearchDocuments) {
                throw new RuntimeException('Search documents do not exactly match indexed manifest pages.');
            }
            $calculatedContentHash = hash('sha256', docaraCanonicalJson($searchIndex['documents']));
            if (! hash_equals($calculatedContentHash, $searchIndex['content_sha256'])) {
                throw new RuntimeException('Search content_sha256 does not match canonical documents.');
            }
            $runtimeHash = hash_file('sha256', $searchRuntimePath);
            if (! is_string($runtimeHash)) {
                throw new RuntimeException('Search runtime hash could not be calculated.');
            }
            $expectedIndexReference = $deploymentBase . '_docara/search-index.json?docara_v='
                . $searchIndex['content_sha256'];
            $expectedRuntimeReference = $deploymentBase . '_docara/search.js?docara_v=' . $runtimeHash;
            if (count($searchIndexReferences) !== $expectedSearchSurfaceCount
                || array_values(array_unique($searchIndexReferences)) !== [$expectedIndexReference]
            ) {
                throw new RuntimeException('Search index references do not match the generated content revision.');
            }
            if (count($searchRuntimeReferences) !== $expectedSearchSurfaceCount
                || array_values(array_unique($searchRuntimeReferences)) !== [$expectedRuntimeReference]
            ) {
                throw new RuntimeException('Search runtime references do not match the generated byte revision.');
            }
        } catch (Throwable $exception) {
            $broken[] = [
                'page' => '@build',
                'reference' => '@search-index-contract',
                'target' => $exception->getMessage(),
            ];
        }
    }
}

if ($manifestError === null) {
    $catalogPath = $root . '/_docara/component-catalog.json';
    if (! docaraSafeRegularFile($catalogPath)) {
        $broken[] = [
            'page' => '@build',
            'reference' => '@component-catalog-contract',
            'target' => 'The effective component catalogue is missing or unsafe.',
        ];
    } else {
        try {
            $catalog = json_decode(
                (string) file_get_contents($catalogPath),
                true,
                512,
                JSON_THROW_ON_ERROR,
            );
            if (! is_array($catalog)
                || array_is_list($catalog)
                || ! docaraExactKeys(
                    $catalog,
                    [
                        'schema',
                        'version',
                        'framework_pair',
                        'provider_revision',
                        'content_sha256',
                        'entries',
                        'nonclaims',
                    ],
                )
                || ($catalog['schema'] ?? null) !== 'docara.effective_component_catalog.v1'
                || ($catalog['version'] ?? null) !== 1
                || ! is_string($catalog['framework_pair'] ?? null)
                || $catalog['framework_pair'] !== $expectedFrameworkPair
                || ! is_string($catalog['provider_revision'] ?? null)
                || $catalog['provider_revision'] !== $expectedFrameworkProviderRevision
                || ! is_string($catalog['content_sha256'] ?? null)
                || preg_match('/\A[a-f0-9]{64}\z/D', $catalog['content_sha256']) !== 1
                || ! is_array($catalog['entries'] ?? null)
                || ! array_is_list($catalog['entries'])
                || $catalog['entries'] === []
            ) {
                throw new RuntimeException('Effective component catalogue root contract is invalid.');
            }

            $nonclaims = $catalog['nonclaims'] ?? null;
            if (! is_array($nonclaims)
                || array_is_list($nonclaims)
                || ! docaraExactKeys(
                    $nonclaims,
                    [
                        'catalog_is_canonical_framework_registry',
                        'all_framework_components_supported',
                        'production_ready',
                        'public_release_ready',
                    ],
                )
            ) {
                throw new RuntimeException('Effective component catalogue nonclaims are incomplete.');
            }
            foreach ($nonclaims as $value) {
                if ($value !== false) {
                    throw new RuntimeException('Effective component catalogue contains a readiness overclaim.');
                }
            }

            $previousId = null;
            $supportedSmart = [];
            foreach ($catalog['entries'] as $index => $entry) {
                if (! is_array($entry) || array_is_list($entry)) {
                    throw new RuntimeException("Effective component catalogue entry [$index] is invalid.");
                }
                $contractError = docaraCatalogEntryContractError(
                    $entry,
                    (string) $expectedFrameworkPair,
                    (string) $expectedFrameworkProviderRevision,
                );
                if ($contractError !== null) {
                    throw new RuntimeException("Effective component catalogue entry [$index]: $contractError");
                }
                if ($previousId !== null && strcmp($previousId, $entry['id']) >= 0) {
                    throw new RuntimeException('Effective component catalogue entries are not strictly sorted or contain duplicates.');
                }
                $previousId = $entry['id'];

                foreach (['example_ref'] as $pathKey) {
                    if (isset($entry[$pathKey])
                        && (! is_string($entry[$pathKey]) || ! docaraCatalogSafePath($entry[$pathKey]))
                    ) {
                        throw new RuntimeException("Effective component catalogue entry [$index] has an unsafe reference.");
                    }
                }
                foreach ($entry['presentation'] as $localizedPresentation) {
                    if (is_array($localizedPresentation)
                        && isset($localizedPresentation['example_ref'])
                        && (! is_string($localizedPresentation['example_ref'])
                            || ! docaraCatalogSafePath($localizedPresentation['example_ref']))
                    ) {
                        throw new RuntimeException(
                            "Effective component catalogue entry [$index] has an unsafe localized example.",
                        );
                    }
                }
                $provenance = $entry['provenance'] ?? null;
                if (! is_array($provenance) || array_is_list($provenance)) {
                    throw new RuntimeException("Effective component catalogue entry [$index] has invalid provenance.");
                }
                foreach (['definition_ref', 'manifest_ref', 'admission_authority'] as $pathKey) {
                    if (isset($provenance[$pathKey])
                        && (! is_string($provenance[$pathKey]) || ! docaraCatalogSafePath($provenance[$pathKey]))
                    ) {
                        throw new RuntimeException("Effective component catalogue entry [$index] has unsafe provenance.");
                    }
                }

                if ($entry['lifecycle'] === 'supported') {
                    foreach (['renderer', 'tests', 'docs', 'demo'] as $evidenceKey) {
                        if (($entry['verification'][$evidenceKey] ?? null) !== true) {
                            throw new RuntimeException(
                                "Supported component catalogue entry [$index] has incomplete evidence.",
                            );
                        }
                    }
                    if (! is_string($entry['example_ref'] ?? null)
                        || ! docaraCatalogSafePath($entry['example_ref'])
                    ) {
                        throw new RuntimeException(
                            "Supported component catalogue entry [$index] is missing a safe example.",
                        );
                    }
                    if ($entry['family'] === 'framework_smart') {
                        $supportedSmart[] = $entry['id'];
                    }
                } else {
                    if (($entry['verification']['demo'] ?? null) !== false) {
                        throw new RuntimeException(
                            "Unavailable component catalogue entry [$index] incorrectly claims a live demo.",
                        );
                    }
                    $gap = $entry['gap'] ?? null;
                    foreach (['owner', 'reason', 'fallback', 'admission_condition'] as $gapKey) {
                        if (! is_array($gap)
                            || ! is_string($gap[$gapKey] ?? null)
                            || trim($gap[$gapKey]) === ''
                        ) {
                            throw new RuntimeException(
                                "Unavailable component catalogue entry [$index] lacks a complete gap contract.",
                            );
                        }
                    }
                }
            }
            if ($supportedSmart !== $expectedSupportedComponents) {
                throw new RuntimeException('Supported Smart catalogue entries do not match the exact runtime admission set.');
            }
            $calculatedContentHash = hash('sha256', docaraCanonicalJson($catalog['entries']));
            if (! hash_equals($calculatedContentHash, $catalog['content_sha256'])) {
                throw new RuntimeException('Effective component catalogue content_sha256 does not match canonical entries.');
            }
            $catalogPolicies = [];
            foreach ($catalog['entries'] as $entry) {
                if (($entry['family'] ?? null) === 'framework_smart') {
                    $catalogPolicies[$entry['id']] = $entry['consumer_policy'];
                }
            }
            if (hash('sha256', docaraCanonicalJson($catalogPolicies)) !== $expectedConsumerPolicyHash) {
                throw new RuntimeException(
                    'Smart catalogue consumer policies do not match the exact runtime policy receipt.',
                );
            }
            docaraBootstrapTrustedSource();
            $trustedCatalog = EffectiveComponentCatalogBuilder::bundled(
                FrameworkLock::fromArray($expectedFrameworkLock),
            )->build();
            if (docaraCanonicalJson($catalog) !== docaraCanonicalJson($trustedCatalog)) {
                throw new RuntimeException(
                    'Effective component catalogue does not match the trusted source projection.',
                );
            }
            $trustedCatalogVerified = true;
            foreach ($catalog['entries'] as $entry) {
                if (($entry['family'] ?? null) === 'framework_smart'
                    && ($entry['provenance']['upstream_revision'] ?? null) !== $expectedFrameworkSmartRevision
                ) {
                    throw new RuntimeException(
                        'Supported Smart catalogue provenance does not match the exact runtime revision.',
                    );
                }
            }
            $encodedCatalog = docaraCanonicalJson($catalog);
            if (preg_match('~(?:^|[/@])(?:main|master|latest)(?:$|[/])~i', $encodedCatalog) === 1
                || docaraCatalogContainsAbsoluteFilesystemReference($catalog)
            ) {
                throw new RuntimeException('Effective component catalogue contains unsafe provenance or a moving reference.');
            }
        } catch (Throwable $exception) {
            $broken[] = [
                'page' => '@build',
                'reference' => '@component-catalog-contract',
                'target' => $exception->getMessage(),
            ];
        }
    }
}

if ($manifestError === null && $trustedCatalogVerified && is_array($trustedCatalog)) {
    try {
        docaraBootstrapTrustedSource();
        $packageRoot = dirname(__DIR__);
        foreach ([
            'resources/component-catalog/native',
            'resources/component-catalog/typed',
            'resources/component-catalog/smart',
            'resources/component-catalog/requirements',
            'resources/component-catalog/examples',
            'resources/component-catalog/assets',
            'resources/framework/manifests',
            'resources/schemas',
        ] as $trustedDirectory) {
            docaraAssertTrustedPackagedDirectory($packageRoot, $trustedDirectory);
        }
        docaraAssertTrustedPackagedFile($packageRoot, 'resources/framework/runtime-lock.json');

        if (! is_array($expectedFrameworkLock)) {
            throw new RuntimeException('Exact Framework lock is unavailable for catalogue page reconstruction.');
        }
        $freshTrustedCatalog = EffectiveComponentCatalogBuilder::bundled(
            FrameworkLock::fromArray($expectedFrameworkLock),
        )->build();
        if (docaraCanonicalJson($freshTrustedCatalog) !== docaraCanonicalJson($trustedCatalog)) {
            throw new RuntimeException('Trusted component catalogue changed during page reconstruction.');
        }

        $runtimeAssetBase = rtrim($deploymentBase, '/') . '/_docara/framework';
        $runtime = FrameworkComponentRuntime::fromLock($expectedFrameworkLock, $runtimeAssetBase);
        $projector = new PortableComponentCatalogProjector(new PortableMarkdownRenderer);
        $htmlRenderer = new PortableHtmlRenderer;
        $catalogManifestConfiguration = null;
        foreach ($manifestPageRecords as $manifestPageRecord) {
            if (($manifestPageRecord['output'] ?? null) !== 'components/catalog/index.html') {
                continue;
            }
            if ($catalogManifestConfiguration !== null) {
                throw new RuntimeException(
                    'Resolved-page manifest contains more than one component catalogue index.',
                );
            }
            $configuration = $manifestPageRecord['resolved_page_plan']['configuration'] ?? null;
            if (! is_array($configuration) || array_is_list($configuration)) {
                throw new RuntimeException(
                    'Resolved component catalogue index configuration is invalid.',
                );
            }
            $catalogManifestConfiguration = $configuration;
        }
        if (! is_array($catalogManifestConfiguration)) {
            throw new RuntimeException(
                'Resolved-page manifest is missing the component catalogue index.',
            );
        }
        $catalogLocale = $catalogManifestConfiguration['locale']
            ?? $catalogManifestConfiguration['default_locale']
            ?? null;
        if (! is_string($catalogLocale)
            || preg_match('/\A[a-z]{2}(?:-[A-Z]{2})?\z/D', $catalogLocale) !== 1
        ) {
            throw new RuntimeException(
                'Resolved component catalogue locale is invalid.',
            );
        }
        $expectedBaseConfiguration = $catalogManifestConfiguration;
        $expectedBaseConfiguration['base_url'] = $deploymentBase;
        $expectedBaseConfiguration['default_locale'] = $catalogLocale;
        $expectedBaseConfiguration['locale'] = $catalogLocale;
        $expectedBaseConfiguration['settings'] = is_array(
            $expectedBaseConfiguration['settings'] ?? null,
        )
            ? $expectedBaseConfiguration['settings']
            : ['theme' => 'system'];
        $basePlan = new ResolvedPagePlan(
            page: '@docara/component-catalog.md',
            markdown: '',
            configuration: $expectedBaseConfiguration,
            frameworkLock: $expectedFrameworkLock,
            trace: [],
            provenance: [],
        );
        $expectedProjection = $projector->project(
            catalog: $freshTrustedCatalog,
            runtime: $runtime,
            basePlan: $basePlan,
            contentRoot: 'content',
            baseUrl: $deploymentBase,
            homeUrl: $deploymentBase,
            reservedDocumentIds: $htmlRenderer->reservedDocumentIds(),
        );
        $expectedReceipt = $expectedProjection['receipt'] ?? null;
        if (! is_array($expectedReceipt)) {
            throw new RuntimeException('Trusted component catalogue page projection produced no receipt.');
        }

        $receipt = docaraCatalogPagesReceipt(
            $root . '/.docara/component-catalog-pages.json',
        );
        if (! hash_equals(
            (string) $freshTrustedCatalog['content_sha256'],
            (string) $receipt['catalog_content_sha256'],
        )) {
            throw new RuntimeException(
                'Generated component catalogue page receipt does not match the trusted catalogue hash.',
            );
        }
        if (docaraCanonicalJson($receipt) !== docaraCanonicalJson($expectedReceipt)) {
            throw new RuntimeException(
                'Generated component catalogue page receipt does not match the trusted page projection.',
            );
        }

        $expectedPagesByOutput = [];
        foreach ($expectedProjection['pages'] ?? [] as $page) {
            if (! is_array($page)
                || ! is_string($page['output'] ?? null)
                || ! is_string($page['url'] ?? null)
                || ! is_string($page['content_html'] ?? null)
            ) {
                throw new RuntimeException('Trusted component catalogue page projection is incomplete.');
            }
            $expectedPagesByOutput[$page['output']] = $page;
        }
        ksort($expectedPagesByOutput, SORT_STRING);
        $expectedOutputs = array_keys($expectedPagesByOutput);
        $actualCatalogOutputs = docaraSafeFileInventory($root, 'components/catalog');
        if ($actualCatalogOutputs !== $expectedOutputs) {
            throw new RuntimeException(
                'Generated component catalogue HTML outputs do not exactly match the trusted page set.',
            );
        }

        $manifestByOutput = [];
        foreach ($manifestPageRecords as $page) {
            if (is_array($page) && is_string($page['output'] ?? null)) {
                $manifestByOutput[$page['output']] = $page;
            }
        }
        foreach ($expectedPagesByOutput as $output => $expectedPage) {
            $manifestPage = $manifestByOutput[$output] ?? null;
            if (! is_array($manifestPage)
                || ($manifestPage['url'] ?? null) !== $expectedPage['url']
            ) {
                throw new RuntimeException(
                    "Generated component catalogue route [$output] does not match the resolved-page manifest.",
                );
            }
        }

        $expectedIndex = $expectedPagesByOutput['components/catalog/index.html'] ?? null;
        if (! is_array($expectedIndex)) {
            throw new RuntimeException('Trusted component catalogue index projection is missing.');
        }
        $actualIndexHtml = file_get_contents($root . '/components/catalog/index.html');
        if (! is_string($actualIndexHtml)) {
            throw new RuntimeException('Generated component catalogue index could not be read.');
        }
        $actualIndexFragment = docaraCatalogContractFragment(
            $actualIndexHtml,
            '//*[@data-docara-component-catalog-index]',
        );
        $expectedIndex['branding'] = ['title' => 'Docara'];
        $expectedIndex['breadcrumbs'] = [];
        $expectedIndex['previous'] = null;
        $expectedIndex['next'] = null;
        $expectedIndexHtml = $htmlRenderer->render(
            $expectedIndex,
            [],
            'Docara',
            $expectedIndex['components']->assetPlan,
        );
        $expectedIndexFragment = docaraCatalogContractFragment(
            $expectedIndexHtml,
            '//*[@data-docara-component-catalog-index]',
        );
        if (! hash_equals(
            $projector->normalizedFragmentHash($expectedIndexFragment),
            $projector->normalizedFragmentHash($actualIndexFragment),
        )) {
            throw new RuntimeException(
                'Generated component catalogue index fragment does not match the trusted projection.',
            );
        }
        docaraAssertCatalogShellContract(
            $actualIndexHtml,
            $expectedIndex,
            (string) $expectedReceipt['index']['route'],
        );

        $trustedEntriesById = [];
        foreach ($freshTrustedCatalog['entries'] as $entry) {
            if (is_array($entry) && is_string($entry['id'] ?? null)) {
                $trustedEntriesById[$entry['id']] = $entry;
            }
        }
        foreach ($expectedReceipt['pages'] as $receiptPage) {
            $id = (string) $receiptPage['id'];
            $output = (string) $receiptPage['output'];
            $expectedPage = $expectedPagesByOutput[$output] ?? null;
            $trustedEntry = $trustedEntriesById[$id] ?? null;
            if (! is_array($expectedPage) || ! is_array($trustedEntry)) {
                throw new RuntimeException("Trusted component catalogue detail [$id] is incomplete.");
            }
            $actualHtml = file_get_contents($root . '/' . $output);
            if (! is_string($actualHtml)) {
                throw new RuntimeException("Generated component catalogue detail [$id] could not be read.");
            }

            $detailSelector = '//*[@data-docara-component-detail="' . $id . '"]';
            $sourceSelector = '//*[@data-docara-component-source="' . $id . '"]';
            $demoSelector = '//*[@data-docara-component-demo="' . $id . '"]';
            $expectedPage['branding'] = ['title' => 'Docara'];
            $expectedPage['breadcrumbs'] = [];
            $expectedPage['previous'] = null;
            $expectedPage['next'] = null;
            $expectedHtml = $htmlRenderer->render(
                $expectedPage,
                [],
                'Docara',
                $expectedPage['components']->assetPlan,
            );
            $actualDetail = docaraCatalogContractFragment($actualHtml, $detailSelector);
            $expectedDetail = docaraCatalogContractFragment(
                $expectedHtml,
                $detailSelector,
            );
            if (! hash_equals(
                $projector->normalizedFragmentHash($expectedDetail),
                $projector->normalizedFragmentHash($actualDetail),
            )) {
                throw new RuntimeException(
                    "Generated component catalogue detail [$id] does not match the trusted contract fragment.",
                );
            }
            docaraAssertCatalogShellContract(
                $actualHtml,
                $expectedPage,
                (string) $expectedReceipt['index']['route'],
            );

            $expectedSourcePath = $packageRoot . '/' . (string) $receiptPage['example_ref'];
            if (! docaraSafeRegularFile($expectedSourcePath)) {
                throw new RuntimeException("Packaged component catalogue example [$id] is missing or unsafe.");
            }
            $expectedSource = file_get_contents($expectedSourcePath);
            if (! is_string($expectedSource)) {
                throw new RuntimeException("Packaged component catalogue example [$id] could not be read.");
            }
            $expectedSource = str_replace(["\r\n", "\r"], "\n", $expectedSource);
            if (! hash_equals(
                $expectedSource,
                docaraCatalogContractText($actualHtml, $sourceSelector),
            )) {
                throw new RuntimeException(
                    "Generated component catalogue source [$id] does not match the packaged example.",
                );
            }

            $actualDemo = docaraCatalogContractFragment($actualHtml, $demoSelector);
            $expectedDemo = docaraCatalogContractFragment(
                $expectedHtml,
                $demoSelector,
            );
            if (! hash_equals(
                $projector->normalizedFragmentHash($expectedDemo),
                $projector->normalizedFragmentHash($actualDemo),
            )) {
                throw new RuntimeException(
                    "Generated component catalogue demo [$id] does not match the trusted rendering.",
                );
            }
        }

        $expectedAssets = $projector->assets();
        $expectedAssetOutputs = array_keys($expectedAssets);
        sort($expectedAssetOutputs, SORT_STRING);
        $actualAssetOutputs = docaraSafeFileInventory($root, '_docara/component-catalog');
        if ($actualAssetOutputs !== $expectedAssetOutputs) {
            throw new RuntimeException(
                'Generated component catalogue assets do not exactly match the trusted projection.',
            );
        }
        foreach ($expectedAssets as $relative => $bytes) {
            $actualBytes = file_get_contents($root . '/' . $relative);
            if (! is_string($actualBytes) || ! hash_equals(hash('sha256', $bytes), hash('sha256', $actualBytes))) {
                throw new RuntimeException("Generated component catalogue asset [$relative] is incorrect.");
            }
        }
    } catch (Throwable $exception) {
        $broken[] = [
            'page' => '@build',
            'reference' => '@component-catalog-pages-contract',
            'target' => $exception->getMessage(),
        ];
    }
}

$result = [
    'schema' => 'docara.static_build_verification.v1',
    'deployment_base' => $deploymentBase,
    'html_pages' => count($htmlFiles),
    'local_references_checked' => $checked,
    'broken' => $broken,
];
fwrite(STDOUT, json_encode($result, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");

exit($broken === [] ? 0 : 1);
