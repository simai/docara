<?php

declare(strict_types=1);

namespace Simai\Docara\Console;

use Simai\Docara\Application\OperationFailureMapper;
use Simai\Docara\Application\OperationResult;
use Throwable;

abstract class ApplicationCommand extends Command
{
    protected string $base;

    public function setBase(?string $cwd = null): static
    {
        $this->base = $cwd ?: (getcwd() ?: '.');

        return $this;
    }

    /** @param array<string, mixed> $arguments */
    protected function runOperation(callable $operation, ?string $name = null, array $arguments = []): int
    {
        try {
            $result = $operation();
            if (! $result instanceof OperationResult) {
                throw new \LogicException('SDK_RESULT_CONTRACT_INVALID');
            }
        } catch (Throwable $exception) {
            $result = (new OperationFailureMapper)->map(
                $this->base,
                $name ?? $this->operationName(),
                $arguments === [] ? $this->operationArguments() : $arguments,
                $exception,
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

    private function operationName(): string
    {
        $command = $this->getName() ?? 'operation';
        if ($command === 'scaffold') {
            return is_string($this->input->getOption('apply')) ? 'scaffold.apply' : 'scaffold.plan';
        }
        if ($command === 'qa') {
            return is_string($this->input->getOption('verify')) ? 'qa.verify' : 'qa.plan';
        }

        return $command;
    }

    /** @return array<string, mixed> */
    private function operationArguments(): array
    {
        $arguments = $this->input->getArguments();
        foreach (['page', 'apply', 'verify'] as $option) {
            if ($this->input->hasOption($option)) {
                $value = $this->input->getOption($option);
                if ($value !== null && $value !== false) {
                    $arguments[$option === 'apply' || $option === 'verify' ? 'plan_id' : $option] = $value;
                }
            }
        }

        return $arguments;
    }
}
