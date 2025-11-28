<?php

namespace Simai\Docara\Console;

use Exception;
use Symfony\Component\Console\Input\InputOption;
use Simai\Docara\Translate\Translate;

class TranslateCommand extends Command
{
    protected function configure()
    {
        $this->setName('translate')
            ->setDescription('Translate documentation into configured languages.')
            ->addOption('test', 't', InputOption::VALUE_NONE, 'Run a single test request to the translate API and exit.');
    }

    protected function fire()
    {
        try {
            $this->banner();
            $this->validateEnv();
            if ($this->input->getOption('test')) {
                return $this->testTranslate();
            }
            $translator = new Translate([], function ($message, $level = 'info') {
                if ($level === 'error') {
                    $this->console->error($message);
                } else {
                    $this->console->comment($message);
                }
            });
            $translator->init();

            $this->console->info('Translate complete')->line();

            return static::SUCCESS;
        } catch (Exception $e) {
            $this->console->error($e->getMessage())->line();

            return static::FAILURE;
        }
    }

    private function testTranslate(): int
    {
        $endpoint = $_ENV['AZURE_ENDPOINT'] ?? 'https://api.cognitive.microsofttranslator.com';
        $url = rtrim($endpoint, '/') . '/translate?api-version=3.0&to=en';
        $payload = [['Text' => 'Привет мир']];
        $headers = [
            'Content-Type: application/json',
            'Ocp-Apim-Subscription-Key: ' . $_ENV['AZURE_KEY'],
            'Ocp-Apim-Subscription-Region: ' . $_ENV['AZURE_REGION'],
        ];

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 10,
        ]);

        $response = curl_exec($ch);
        if (curl_errno($ch)) {
            $this->console->error('Test translate request failed: ' . curl_error($ch));
            curl_close($ch);

            return static::FAILURE;
        }

        $status = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);

        $this->console->comment("Test request status: {$status}");

        if ($status < 200 || $status >= 300) {
            $this->console->error('Response: ' . mb_substr((string) $response, 0, 1000));

            return static::FAILURE;
        }

        $decoded = json_decode((string) $response, true);
        $sample = $decoded[0]['translations'][0]['text'] ?? null;

        if ($sample === null) {
            $this->console->error('Unexpected response: ' . mb_substr((string) $response, 0, 1000));

            return static::FAILURE;
        }

        $this->console->info("Sample translation: {$sample}");

        return static::SUCCESS;
    }

    private function validateEnv(): void
    {
        $missing = [];
        foreach (['DOCS_DIR', 'AZURE_KEY', 'AZURE_REGION'] as $envKey) {
            if (empty($_ENV[$envKey])) {
                $missing[] = $envKey;
            }
        }

        if ($missing) {
            $list = implode(', ', $missing);
            $this->console->error("Missing required env vars: {$list}");
            throw new \RuntimeException("Missing env vars: {$list}");
        }
    }
}
