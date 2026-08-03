<?php

declare(strict_types=1);

namespace Simai\Docara\Console;

use Simai\Docara\Application\Diagnostic;
use Simai\Docara\Application\OperationResult;
use Simai\Docara\Portable\PortableConfigurationException;
use Throwable;

abstract class ApplicationCommand extends Command
{
    protected string $base;

    public function setBase(?string $cwd = null): static
    {
        $this->base = $cwd ?: (getcwd() ?: '.');

        return $this;
    }

    protected function runOperation(callable $operation): int
    {
        try {
            $result = $operation();
            if (! $result instanceof OperationResult) {
                throw new \LogicException('SDK_RESULT_CONTRACT_INVALID');
            }
        } catch (Throwable $exception) {
            $code = $this->diagnosticCode($exception);
            $result = OperationResult::failure(
                $this->getName() ?? 'operation',
                $this->getName(),
                new Diagnostic(
                    $code,
                    'error',
                    $exception->getMessage(),
                    owner: 'docara.application',
                    suggestion: $this->suggestion($code),
                ),
                2,
            );
        }

        $formatter = new OperationResultFormatter;
        $this->output->write((bool) $this->input->getOption('json') ? $formatter->json($result) : $formatter->human($result));

        return $result->exitCode;
    }

    protected function printBanner(): void
    {
        if ($this->input->hasOption('json') && (bool) $this->input->getOption('json')) {
            return;
        }
        parent::printBanner();
    }

    private function diagnosticCode(Throwable $exception): string
    {
        if ($exception instanceof PortableConfigurationException) {
            return $exception->errorCode;
        }
        $message = $exception->getMessage();
        if (preg_match('/^([A-Z][A-Z0-9_]+):/', $message, $matches) === 1) {
            return $matches[1];
        }

        return $exception instanceof \InvalidArgumentException ? 'SDK_INPUT_INVALID' : 'SDK_OPERATION_FAILED';
    }

    private function suggestion(string $code): string
    {
        return match ($code) {
            'SDK_PROJECT_CONFIG_MISSING' => 'Run the command from an initialized Docara project root.',
            'SDK_DISCOVERY_KIND_UNKNOWN', 'SDK_SCHEMA_KIND_UNKNOWN' => 'Use one of the kinds shown by docara list --help.',
            default => 'Fix the reported input or project contract and retry the same command.',
        };
    }
}
