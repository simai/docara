<?php
namespace Simai\Docara\Translate;
use Exception;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Exception\CommonMarkException;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\FrontMatter\Data\SymfonyYamlFrontMatterParser;
use League\CommonMark\Extension\FrontMatter\FrontMatterExtension;
use League\CommonMark\Extension\FrontMatter\FrontMatterParser;
use League\CommonMark\Node\Block\AbstractBlock;
use League\CommonMark\Node\Inline\Text;
use League\CommonMark\Node\Node;
use League\CommonMark\Parser\MarkdownParser;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Symfony\Component\Intl\Languages;
use Symfony\Component\Yaml\Yaml;
use Simai\Docara\CustomTags\CustomTagRegistry;
use Simai\Docara\CustomTags\CustomTagsExtension;
use Simai\Docara\CustomTags\TagRegistry;
use Simai\Docara\Interface\CustomTagInterface;
class Translate
{
    public string $subscriptionKey;
    public string $region;
    public string $projectRoot;
    public string $endpoint;
    public array $docaraConfig;
    public array $usedLocales;
    public array $hashData;
    public array $prevTranslation;
    public string $cachePath;
    private array $files = [];
    public string $targetDir;
    public string $docsDir;
    private CustomTagRegistry $registry;
    public array $config;
    private MarkdownParser $parser;
    private $logger;
    /**
     * @throws Exception
     */
    public function __construct(array $params = [], callable $logger = null)
    {
        if (! isset($params) || ! is_array($params)) {
            throw new Exception('Missing parameters');
        }
        $this->logger = $logger;
        $this->projectRoot = getcwd();
        $this->subscriptionKey = $_ENV['AZURE_KEY'] ?? getenv('AZURE_KEY') ?? '';
        $this->region = $_ENV['AZURE_REGION'] ?? getenv('AZURE_REGION') ?? '';
        $this->endpoint = $_ENV['AZURE_ENDPOINT'] ?? getenv('AZURE_ENDPOINT') ?? 'https://api.cognitive.microsofttranslator.com';
        $this->config = require $this->projectRoot . '/translate.config.php';
        $docsDir = app('config')->get('docara.docsDir', 'docs');
        $this->docsDir = trim((string) $docsDir, '/\\') ?: 'docs';
        $this->targetDir = $this->config['main'] . "source/{$this->docsDir}/{$this->config['default_lang']}";
        $this->registerDocaraConfig();
    }
    /**
     * @throws CommonMarkException
     * @throws Exception
     */
    public function init(): void
    {
        if (! isset($this->config['languages'])) {
            throw new Exception("Missing required parameter 'languages'");
        }
        $this->cachePath = $this->config['main'] . $this->config['cache_dir'] . 'translations/';
        $this->loadCache();
        $this->initParser();
        $this->collectFiles();
    }
    /**
     * @throws Exception
     */
    private function registerDocaraConfig(): void
    {
        if (! is_file($this->config['main'] . 'config.php')) {
            throw new Exception("Missing required file 'config.php'");
        }
        $this->docaraConfig = require $this->config['main'] . 'config.php';
        $instances = [];
        $namespace = 'App\\Helpers\\CustomTags\\';
        foreach ($this->docaraConfig['tags'] as $short) {
            $class = $namespace . $short;
            if (class_exists($class)) {
                $obj = new $class;
                if ($obj instanceof CustomTagInterface) {
                    $instances[] = $obj;
                }
            }
        }
        $this->registry = TagRegistry::register($instances);
        $this->usedLocales = $this->docaraConfig['locales'] ?? [];
    }
    private function frontMatterParser($originalMarkdown
    ): array {
        $fronMatterParser = new FrontMatterParser(new SymfonyYamlFrontMatterParser);
        $fronMatterDocument = $fronMatterParser->parse($originalMarkdown);
        $frontMatter = $fronMatterDocument->getFrontMatter();
        $content = $fronMatterDocument->getContent();
        return [$frontMatter, $content];
    }
    private function initParser(): void
    {
        $environment = new Environment([]);
        $environment->addExtension(new CustomTagsExtension($this->registry));
        $environment->addExtension(new CommonMarkCoreExtension);
        $environment->addExtension(new FrontMatterExtension);
        $this->parser = new MarkdownParser($environment);
    }
    /**
     * @throws CommonMarkException
     */
    private function collectFiles(): void
    {
        if (is_dir($this->targetDir)) {
            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($this->targetDir)
            );
            foreach ($files as $file) {
                if ($file->isFile()) {
                    switch ($file->getExtension()) {
                        case 'php':
                        case 'md':
                            $this->files[$file->getExtension()][] = $file;
                            break;
                        default:
                            $this->files['other'][] = $file;
                    }
                }
            }
            $this->translateFiles();
        }
    }
    /**
     * @return int[]
     */
    private function getNodeLines(Node $node): array
    {
        $parent = $node;
        $arReturn = [
            'start' => 0,
            'end' => 0,
        ];
        while ($parent !== null && ! $parent instanceof AbstractBlock) {
            $parent = $parent->parent();
        }
        if ($parent !== null) {
            if (method_exists($parent, 'getStartLine')) {
                $arReturn['start'] = $parent->getStartLine();
            }
            if (method_exists($parent, 'getEndLine')) {
                $arReturn['end'] = $parent->getEndLine();
            }
        }
        return $arReturn;
    }
    private function chunkTextArray(array $items, int $maxChars = 9000): array
    {
        $chunks = [];
        $currentChunk = [];
        $currentLength = 0;
        foreach ($items as $item) {
            $length = mb_strlen($item['text']);
            if ($length >= $maxChars) {
                if (! empty($currentChunk)) {
                    $chunks[] = $currentChunk;
                    $currentChunk = [];
                    $currentLength = 0;
                }
                $chunks[] = [$item];
                continue;
            }
            if ($currentLength + $length > $maxChars) {
                $chunks[] = $currentChunk;
                $currentChunk = [];
                $currentLength = 0;
            }
            $currentChunk[] = $item;
            $currentLength += $length;
        }
        if (! empty($currentChunk)) {
            $chunks[] = $currentChunk;
        }
        return $chunks;
    }
    /**
     * @throws CommonMarkException
     */
    private function generateTranslateContent(string $file, string $lang): string
    {
        $document = $this->parser->parse($file);
        $textNodes = [];
        $walker = $document->walker();
        while ($event = $walker->next()) {
            $node = $event->getNode();
            if (
                $event->isEntering() &&
                ($node instanceof Text)
            ) {
                $text = trim($node->getLiteral());
                if ($text !== '') {
                    $textNodes[] = $node;
                }
            }
        }
        $textsToTranslateArray = [];
        foreach ($textNodes as $node) {
            $text = $node->getLiteral();
            if (! preg_match('/\p{L}/u', $text)) {
                continue;
            }
            $lines = $this->getNodeLines($node);
            $textsToTranslateArray[] = [
                'text' => $text,
                'start' => $lines['start'],
                'end' => $lines['end'],
            ];
        }
        if (count($textsToTranslateArray)) {
            $flattenArray = array_map(fn ($item) => $item['text'], $textsToTranslateArray);
            [$excludedKeys, $flattenArray] = $this->checkCached($flattenArray, $lang);
            $keys = array_keys($textsToTranslateArray);
            $keysAssoc = array_flip($excludedKeys);
            $extracted = array_intersect_key($textsToTranslateArray, $keysAssoc);
            foreach ($extracted as $key => $value) {
                $extracted[$key]['translated'] = $flattenArray[$key];
            }
            $textsToTranslateArray = array_values(array_diff_key($textsToTranslateArray, $keysAssoc));
            $normalizedMarkdown = str_replace("\r\n", "\n", $file);
            $lines = preg_split('/\R/u', $normalizedMarkdown);
            $chunks = $this->chunkTextArray($textsToTranslateArray);
            $finalTranslated = [];
            foreach ($chunks as $chunk) {
                $translatedChunk = $this->translateText($chunk, $lang);
                $finalTranslated = array_merge($finalTranslated, $translatedChunk);
                $chars = 0;
                foreach ($chunk as $c) {
                    $chars += mb_strlen($c['text']);
                }
                $this->throttleByCharsPerMinute($chars);
            }
            $finalBlock = $finalTranslated;
            $i = 0;
            foreach ($keys as $k) {
                if (array_key_exists($k, $extracted)) {
                    $finalTranslated[$k] = $extracted[$k];
                } else {
                    $finalTranslated[$k] = $finalBlock[$i++];
                }
            }
            foreach (array_reverse($finalTranslated) as $block) {
                $startLine = $block['start'];
                $endLine = $block['end'];
                $blockText = implode("\n", array_slice($lines, $startLine - 1, $endLine - $startLine + 1));
                $translatedText = $block['translated'];
                $originalText = $block['text'];
                $replacedBlockText = $this->replace_last_literal($blockText, $originalText, $translatedText);
                $replacedLines = explode("\n", $replacedBlockText);
                array_splice($lines, $startLine - 1, $endLine - $startLine + 1, $replacedLines);
            }
            return implode("\n", $lines);
        }
        return $file;
    }
    private function replace_last_literal(string $haystack, string $search, string $replace): string
    {
        $pos = mb_strrpos($haystack, $search);
        if ($pos === false) {
            return $haystack;
        }
        return mb_substr($haystack, 0, $pos)
            . $replace
            . mb_substr($haystack, $pos + mb_strlen($search));
    }
    private function mb_ucfirst(string $s, string $enc = 'UTF-8'): string
    {
        if ($s === '') {
            return $s;
        }
        $first = mb_substr($s, 0, 1, $enc);
        $rest = mb_substr($s, 1, null, $enc);
        return mb_strtoupper($first, $enc) . $rest;
    }
    /**
     * @throws CommonMarkException
     * @throws Exception
     */
    private function translateFiles(): void
    {
        $usedLangKeys = array_keys($this->usedLocales);
        foreach ($this->files as $type => $files) {
            foreach ($files as $file) {
                $filePathName = $file->getPathname();
                $fileName = $file->getFilename();
                $relativePath = ltrim(str_replace($this->config['main'], '', $filePathName), '/\\');
                $content = file_get_contents($filePathName);
                foreach ($this->config['languages'] as $lang) {
                    if (in_array($lang, $usedLangKeys)) {
                        throw new Exception('Language "' . $lang . '" is already translated.');
                    }
                    $hash = md5($content);
                    $srcPath = $file->getPathname();
                    $search = "{$this->docsDir}/{$this->config['default_lang']}";
                    $replace = "{$this->docsDir}/{$lang}";
                    $destPath = str_replace($search, $replace, $srcPath);
                    if ($lang === $this->config['default_lang']) {
                        $this->log("Skip default {$lang}: {$relativePath}");
                        continue;
                    }
                    if (isset($this->hashData[$lang][$filePathName]) && $hash === $this->hashData[$lang][$filePathName]) {
                        $this->log("Skip cached {$lang}: {$relativePath}");
                        continue;
                    }
                    $this->log("Translating {$lang}: {$relativePath}");
                    $this->hashData[$lang][$filePathName] = $hash;
                    if ($type === 'md') {
                        [$fromMatter, $original] = $this->frontMatterParser($content);
                        $translatedFromMatter = $this->translateFromMatter($fromMatter, $lang);
                        $translatedMarkdown = $this->generateTranslateContent($original, $lang);
                        $yamlBlock = "---\n" . Yaml::dump($translatedFromMatter) . "---\n\n";
                        $translated = $yamlBlock . $translatedMarkdown;
                    } else {
                        if (in_array($fileName, ['.lang.php', '.settings.php'])) {
                            $data = include $filePathName;
                            if ($fileName === '.lang.php') {
                                $translated = $this->translateLangFiles($data, $lang);
                                $translated = "<?php\nreturn " . var_export($translated, true) . ";\n";
                            } elseif ($fileName === '.settings.php') {
                                $data = include $filePathName;
                                $translated = $this->generateSettingsTranslate($data, $lang);
                                $translated = "<?php\nreturn " . var_export($translated, true) . ";\n";
                            } else {
                                $translated = $content;
                            }
                        } else {
                            $translated = $content;
                        }
                    }
                    $dir = dirname($destPath);
                    if (! is_dir($dir)) {
                        mkdir($dir, 0777, true);
                    }
                    file_put_contents($destPath, $translated);
                    $relativeDest = ltrim(str_replace($this->config['main'], '', $destPath), '/\\');
                    $this->log("Saved {$lang}: {$relativeDest}");
                }
            }
        }
        $this->saveCache();
    }
    private function generateSettingsTranslate(array $settings, string $lang): array
    {
        $paths = [];
        $texts = [];
        if (isset($settings['title']) && is_string($settings['title'])
            && preg_match('/\p{L}/u', $settings['title'])) {
            $paths[] = ['title'];
            $texts[] = $settings['title'];
        }
        if (! empty($settings['menu']) && is_array($settings['menu'])) {
            foreach ($settings['menu'] as $menuKey => $menuVal) {
                if (is_string($menuVal) && preg_match('/\p{L}/u', $menuVal)) {
                    $paths[] = ['menu', $menuKey];
                    $texts[] = $menuVal;
                }
            }
        }
        if (! $paths) {
            return $settings;
        }
        [$cachedIdx, $stringsWithCached] = $this->checkCached($texts, $lang);
        $toTranslate = [];
        $mapIdx = [];
        foreach ($stringsWithCached as $i => $text) {
            if (! in_array($i, $cachedIdx, true)) {
                if ($text !== '') {
                    $mapIdx[] = $i;
                    $toTranslate[] = ['Text' => $text];
                }
            }
        }
        $decoded = $toTranslate ? $this->curlRequest($toTranslate, $lang) : [];
        foreach ($stringsWithCached as $i => $text) {
            if (in_array($i, $cachedIdx, true)) {
                $translated = $text;
                $shouldCache = true;
            } else {
                $k = array_search($i, $mapIdx, true);
                $translated = $decoded[$k]['translations'][0]['text'] ?? $text;
                $shouldCache = isset($decoded[$k]['translations'][0]['text']);
            }
            $this->setByPath($settings, $paths[$i], $translated, $lang, $shouldCache);
        }
        return $settings;
    }
    private function setByPath(array &$arr, array $path, mixed $value, string $lang, bool $shouldCache = true): void
    {
        $ref = &$arr;
        foreach ($path as $idx => $key) {
            if ($idx === count($path) - 1) {
                if ($shouldCache) {
                    $this->setCached($lang, $value, $ref[$key]);
                }
                $ref[$key] = $value;
                return;
            }
            if (! isset($ref[$key]) || ! is_array($ref[$key])) {
                $ref[$key] = [];
            }
            $ref = &$ref[$key];
        }
    }
    private function throttleByCharsPerMinute(int $chars): void
    {
        $limit = $this->config['chars_per_minute'] ?? 30000;
        $seconds = ($chars / max(1, $limit)) * 60.0;
        $seconds += mt_rand(0, 200) / 1000.0;
        usleep((int) round($seconds * 1_000_000));
    }
    private function translateLangFiles($langContent, $lang): array
    {
        $items = [];
        $keys = [];
        [$excludedKeys, $langContent] = $this->checkCached($langContent, $lang);
        foreach ($langContent as $k => $v) {
            if (! in_array($k, $excludedKeys) && is_string($v) && preg_match('/\p{L}/u', $v)) {
                $keys[] = $k;
                $items[] = ['Text' => $v];
            }
        }
        return $this->makeContent($items, $langContent, $lang, $keys);
    }
    private function translateFromMatter(array $frontMatter, string $lang): array
    {
        if (
            empty($this->config['frontMatter']) ||
            ! is_array($this->config['frontMatter'])
        ) {
            return $frontMatter;
        }
        [$excludedKeys, $frontMatter] = $this->checkCached($frontMatter, $lang);
        $items = [];
        $keys = [];
        foreach ($frontMatter as $k => $v) {
            if (! in_array($k, $excludedKeys) && in_array($k, $this->config['frontMatter']) && is_string($v) && preg_match('/\p{L}/u', $v)) {
                $keys[] = $k;
                $items[] = ['Text' => $v];
            }
        }
        return $this->makeContent($items, $frontMatter, $lang, $keys);
    }
    private function curlRequest(array $data, string $toLang): array
    {
        $url = $this->endpoint . '/translate?api-version=3.0&to=' . $toLang;
        $headers = [
            'Content-Type: application/json',
            'Ocp-Apim-Subscription-Key: ' . $this->subscriptionKey,
            'Ocp-Apim-Subscription-Region: ' . $this->region,
        ];
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data, JSON_UNESCAPED_UNICODE),
            CURLOPT_HTTPHEADER => $headers,
        ]);
        $response = curl_exec($ch);
        if (curl_errno($ch)) {
            $this->log('Translate request error: ' . curl_error($ch), 'error');
            curl_close($ch);

            return [];
        }
        $status = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);
        if ($status < 200 || $status >= 300) {
            $this->log("Translate request failed with status {$status}", 'error');
            $this->log('Response: ' . mb_substr((string) $response, 0, 2000), 'error');

            return [];
        }

        return json_decode($response, true);
    }
    private function translateText($textsToTranslate, $toLang): array
    {
        $postData = array_map(fn ($item) => ['Text' => $item['text']], $textsToTranslate);
        $translateData = $this->curlRequest($postData, $toLang);
        foreach ($textsToTranslate as $index => &$original) {
            $translated = $translateData[$index]['translations'][0]['text'] ?? null;
            if ($translated !== null) {
                $original['translated'] = $translated;
                $this->setCached($toLang, $translated, $original['text']);
            } else {
                $original['translated'] = $original['text'];
            }
        }
        return $textsToTranslate;
    }
    private function makeContent(array $items, $langContent, $lang, array $keys): mixed
    {
        if (! $items) {
            return $langContent;
        }
        $translateData = $this->curlRequest($items, $lang);
        foreach ($translateData as $i => $entry) {
            $translated = $entry['translations'][0]['text'] ?? null;
            if ($translated !== null) {
                $this->setCached($lang, $translated, $langContent[$keys[$i]]);
                $langContent[$keys[$i]] = $translated;
            }
        }
        return $langContent;
    }
    private function checkCached(array $original, string $lang): array
    {
        $keys = [];
        if (! isset($original)) {
            return $keys;
        }
        foreach ($original as $k => $v) {
            $translated = $this->getCached($lang, $v);
            if ($translated !== null) {
                $keys[] = $k;
                $original[$k] = $translated;
            }
        }
        return [$keys, $original];
    }
    private function setCached(string $toLang, string $translatedText, string $originalText): void
    {
        $key = $this->normalize($originalText);
        $this->prevTranslation[$toLang][$key] = $translatedText;
    }
    private function getCached(string $toLang, string $originalText): ?string
    {
        $key = $this->normalize($originalText);
        return $this->prevTranslation[$toLang][$key] ?? null;
    }
    private function saveCache(): void
    {
        if (! is_dir($this->cachePath)) {
            mkdir($this->cachePath, 0777, true);
        }
        $arTranslated = [];
        foreach ($this->prevTranslation as $lang => $map) {
            $langFullName = $this->mb_ucfirst(Languages::getName($lang));
            $arTranslated[$lang] = $langFullName;
            file_put_contents($this->cachePath . "translate_{$lang}.json", json_encode($map, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        }
        file_put_contents($this->cachePath . '.config.json', json_encode($arTranslated, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        file_put_contents($this->cachePath . 'hash.json', json_encode($this->hashData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        echo 'Translate complete';
    }
    private function loadCache(): void
    {
        if (! is_dir($this->cachePath)) {
            mkdir($this->cachePath, 0777, true);
        }
        foreach ($this->config['languages'] as $lang) {
            $prevTranslation = $this->cachePath . "translate_{$lang}.json";
            if (file_exists($prevTranslation)) {
                $this->prevTranslation[$lang] = json_decode(file_get_contents($prevTranslation), true) ?: [];
            } else {
                $this->prevTranslation[$lang] = [];
            }
        }
        if (file_exists($this->cachePath . 'hash.json')) {
            $this->hashData = json_decode(file_get_contents($this->cachePath . 'hash.json'), true) ?: [];
        }
    }
    private function normalize(string $s): string
    {
        $s = str_replace(["\r\n", "\r"], "\n", $s);
        $s = preg_replace('/\h+/u', ' ', $s);
        return sha1(trim($s));
    }
    private function log(string $message, string $level = 'info'): void
    {
        if ($this->logger) {
            call_user_func($this->logger, $message, $level);
        }
    }
}
