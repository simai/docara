<?php

declare(strict_types=1);

namespace Simai\Docara\PortableSite;

use Simai\Docara\I18n\LocaleRegistry;
use Simai\Docara\I18n\LocaleRoutingPolicy;
use Simai\Docara\Portable\PortableConfigurationException;

/**
 * Packs a portal build (docara.json `portal`) into the release files a portal
 * assembles: `<product>-<version>-portal.tar.gz` with index.html at the top,
 * its `.sha256` and the `simai.io.portal-artifact` v1 receipt.
 *
 * The archive is reproducible: entries are sorted, every entry has mtime 0,
 * owner root (0:0) and mode 0644 or 0755, and the gzip header is fixed (no
 * name, mtime 0, unknown OS). The compressed bytes depend only on the zlib
 * deflate implementation, so one machine always gives the same digest.
 *
 * Before it writes anything it checks the build: no links, none of the files
 * the portal owns, index.html at the top, and on every page `<html lang>` of
 * the portal locale, a title, a description (redirect pages excepted), a
 * canonical address under site_url (a redirect page names its target under the
 * base path), the link back to the portal home and one Framework pair.
 */
final class PortalArtifactWriter
{
    public const RECEIPT_SCHEMA = 'simai.io.portal-artifact';

    public const RECEIPT_VERSION = 1;

    private const BLOCK = 512;

    private const RECORD = 10240;

    /**
     * @param  array{version:string,release:string,repository:string,commit:string}  $release
     * @return array<string, mixed> the receipt
     */
    public function write(string $siteRoot, string $build, array $release, string $output): array
    {
        $profile = $this->profile($siteRoot);
        $this->assertRelease($release);
        $build = rtrim($build, '/\\');
        if (is_link($build) || ! is_dir($build)) {
            throw new PortableConfigurationException('PORTAL_BUILD_MISSING', "The build folder [$build] does not exist. Run docara build first.");
        }
        [$directories, $files] = $this->entries($build);
        foreach (PortalProfile::PORTAL_FILES as $owned) {
            if (in_array($owned, $files, true)) {
                throw new PortableConfigurationException('PORTAL_FILE_COLLISION', "The build holds [$owned], which the portal owns.");
            }
        }
        if (! in_array('index.html', $files, true)) {
            throw new PortableConfigurationException('PORTAL_INDEX_MISSING', 'The build has no index.html at its top.');
        }
        $pair = null;
        foreach ($files as $file) {
            if (str_ends_with($file, '.html')) {
                $pair = $this->checkPage($profile, $file, (string) file_get_contents($build . '/' . $file), $pair);
            }
        }
        if ($pair === null) {
            throw new PortableConfigurationException('PORTAL_FRAMEWORK_PAIR_MISSING', 'No page names the Framework pair it loads.');
        }

        $archive = $profile->product . '-' . $release['version'] . '-portal.tar.gz';
        if (! is_dir($output) && ! mkdir($output, 0775, true) && ! is_dir($output)) {
            throw new PortableConfigurationException('PORTAL_OUTPUT_INVALID', "Cannot create [$output].");
        }
        $output = rtrim($output, '/\\');
        $real = realpath($output);
        $realBuild = realpath($build);
        if ($real === false || $realBuild === false || $real === $realBuild || str_starts_with($real . '/', $realBuild . '/')) {
            throw new PortableConfigurationException('PORTAL_OUTPUT_INVALID', 'The output folder must be outside the build folder.');
        }
        $archivePath = $output . '/' . $archive;
        $this->writeArchive($build, $directories, $files, $archivePath);
        $sha256 = (string) hash_file('sha256', $archivePath);
        file_put_contents($archivePath . '.sha256', $sha256 . '  ' . $archive . "\n");

        $receipt = [
            'schema' => self::RECEIPT_SCHEMA,
            'version' => self::RECEIPT_VERSION,
            'product' => $profile->product,
            'release' => $release['release'],
            'repository' => $release['repository'],
            'commit' => $release['commit'],
            'locale' => $profile->locale,
            'base_path' => $profile->basePath,
            'archive' => $archive,
            'sha256' => $sha256,
            'files' => count($files),
            'framework_pair' => $pair,
            'docara_version' => $this->docaraVersion(),
        ];
        file_put_contents(
            $output . '/' . $profile->product . '-' . $release['version'] . '-portal-receipt.json',
            json_encode($receipt, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . "\n",
        );

        return $receipt;
    }

    /** The version of the Docara that packs the build (its VERSION file). */
    private function docaraVersion(): string
    {
        $version = trim((string) @file_get_contents(dirname(__DIR__, 2) . '/VERSION'));
        if (preg_match('/\A[0-9]+\.[0-9]+\.[0-9]+(?:-[0-9A-Za-z.-]+)?\z/D', $version) !== 1) {
            throw new PortableConfigurationException('PORTAL_DOCARA_VERSION_MISSING', 'The Docara VERSION file is missing or malformed.');
        }

        return $version;
    }

    private function profile(string $siteRoot): PortalProfile
    {
        $path = rtrim($siteRoot, '/\\') . '/docara.json';
        if (is_link($path) || ! is_file($path)) {
            throw new PortableConfigurationException('PORTAL_SITE_MISSING', "There is no docara.json in [$siteRoot].");
        }
        $site = json_decode((string) file_get_contents($path), true);
        if (! is_array($site)) {
            throw new PortableConfigurationException('PORTAL_SITE_MISSING', 'docara.json is not a JSON object.');
        }
        if (($site['portal'] ?? null) === null) {
            throw new PortableConfigurationException('PORTAL_PROFILE_MISSING', 'docara.json has no portal section; only a portal build can be packed.');
        }
        $locales = LocaleRegistry::fromSite($site);

        return PortalProfile::fromSite(
            $site,
            $locales,
            LocaleRoutingPolicy::fromSite($site, $locales),
            SiteDiscovery::fromSite($site),
        ) ?? throw new PortableConfigurationException('PORTAL_PROFILE_MISSING', 'docara.json has no portal section.');
    }

    /** @param array<string, string> $release */
    private function assertRelease(array $release): void
    {
        $patterns = [
            'version' => '/\A[0-9A-Za-z][0-9A-Za-z.+_-]{0,63}\z/D',
            'release' => '/\A[0-9A-Za-z][0-9A-Za-z.+_-]{0,63}\z/D',
            'repository' => '#\A[A-Za-z0-9][A-Za-z0-9-]{0,38}/[A-Za-z0-9._-]{1,100}\z#D',
            'commit' => '/\A[0-9a-f]{40}\z/D',
        ];
        foreach ($patterns as $key => $pattern) {
            if (! is_string($release[$key] ?? null) || preg_match($pattern, $release[$key]) !== 1) {
                throw new PortableConfigurationException('PORTAL_RELEASE_INVALID', "The release $key is missing or malformed.");
            }
        }
    }

    /**
     * Every directory and regular file under the build, sorted bytewise.
     *
     * @return array{0:list<string>,1:list<string>}
     */
    private function entries(string $build): array
    {
        $directories = [];
        $files = [];
        $pending = [''];
        while ($pending !== []) {
            $relative = array_pop($pending);
            $absolute = $relative === '' ? $build : $build . '/' . $relative;
            $names = scandir($absolute);
            if ($names === false) {
                throw new PortableConfigurationException('PORTAL_BUILD_UNREADABLE', "Cannot read [$absolute].");
            }
            foreach ($names as $name) {
                if ($name === '.' || $name === '..') {
                    continue;
                }
                $child = $relative === '' ? $name : $relative . '/' . $name;
                $path = $build . '/' . $child;
                if (is_link($path)) {
                    throw new PortableConfigurationException('PORTAL_BUILD_LINK', "The build holds a link [$child]; the archive takes regular files only.");
                }
                if (is_dir($path)) {
                    $directories[] = $child;
                    $pending[] = $child;
                } elseif (is_file($path)) {
                    $files[] = $child;
                } else {
                    throw new PortableConfigurationException('PORTAL_BUILD_LINK', "[$child] is not a regular file.");
                }
            }
        }
        sort($directories, SORT_STRING);
        sort($files, SORT_STRING);

        return [$directories, $files];
    }

    private function checkPage(PortalProfile $profile, string $file, string $html, ?string $pair): ?string
    {
        $where = "[$file]";
        $fail = static function (string $what) use ($where): never {
            throw new PortableConfigurationException('PORTAL_PAGE_INVALID', "$where $what");
        };
        if (preg_match('/<html\b[^>]*\blang="([^"]+)"/', $html, $lang) !== 1 || $lang[1] !== $profile->locale) {
            $fail("needs <html lang=\"{$profile->locale}\">.");
        }
        if (preg_match('#<title>\s*([^<]*\S)\s*</title>#', $html) !== 1) {
            $fail('needs a non-empty <title>.');
        }
        if (preg_match('/<link rel="canonical" href="([^"]+)"/', $html, $canonical) !== 1) {
            $fail('needs <link rel="canonical">.');
        }
        $redirect = preg_match('/<meta http-equiv="refresh"/i', $html) === 1;
        if ($redirect) {
            if (! str_starts_with($canonical[1], $profile->siteUrl) && ! str_starts_with($canonical[1], $profile->basePath)) {
                $fail("is a redirect to [{$canonical[1]}], outside {$profile->basePath}.");
            }

            return $pair;
        }
        if (! str_starts_with($canonical[1], $profile->siteUrl)) {
            $fail("has the canonical address [{$canonical[1]}], not under {$profile->siteUrl}.");
        }
        if (preg_match('/<meta name="description" content="[^"]*\S[^"]*">/', $html) !== 1) {
            $fail('needs a description: add `description` to the front matter.');
        }
        $home = htmlspecialchars($profile->homeUrl, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');
        if (! str_contains($html, 'href="' . $home . '" data-docara-portal-home')) {
            $fail("does not link back to {$profile->homeUrl}; build it with the portal section.");
        }
        if (preg_match_all('/data-docara-framework-boot="(ui-[0-9a-f]{12})"/', $html, $pairs) < 1) {
            $fail('does not name the Framework pair it loads.');
        }
        foreach (array_unique($pairs[1]) as $pagePair) {
            if ($pair !== null && $pagePair !== $pair) {
                $fail("loads Framework pair $pagePair; other pages load $pair.");
            }
            $pair = $pagePair;
        }

        return $pair;
    }

    /**
     * @param  list<string>  $directories
     * @param  list<string>  $files
     */
    private function writeArchive(string $build, array $directories, array $files, string $path): void
    {
        $entries = [];
        foreach ($directories as $directory) {
            $entries[$directory . '/'] = null;
        }
        foreach ($files as $file) {
            $entries[$file] = $build . '/' . $file;
        }
        // A directory sorts as its name plus "/", so it comes before its
        // contents.
        ksort($entries, SORT_STRING);

        $handle = fopen($path . '.part', 'wb');
        if ($handle === false) {
            throw new PortableConfigurationException('PORTAL_OUTPUT_INVALID', "Cannot write [$path].");
        }
        $deflate = deflate_init(ZLIB_ENCODING_RAW, ['level' => 9, 'memory' => 9, 'window' => 15, 'strategy' => ZLIB_DEFAULT_STRATEGY]);
        if ($deflate === false) {
            throw new PortableConfigurationException('PORTAL_OUTPUT_INVALID', 'zlib deflate is unavailable.');
        }
        $crc = hash_init('crc32b');
        $size = 0;
        $emit = static function (string $bytes) use ($handle, $deflate, $crc, &$size): void {
            hash_update($crc, $bytes);
            $size += strlen($bytes);
            fwrite($handle, (string) deflate_add($deflate, $bytes, ZLIB_NO_FLUSH));
        };
        // Fixed gzip header: deflate, no flags, mtime 0, maximum compression,
        // unknown operating system.
        fwrite($handle, "\x1f\x8b\x08\x00\x00\x00\x00\x00\x02\xff");
        foreach ($entries as $name => $source) {
            if ($source === null) {
                $emit($this->header($name, 0, '5', 0755));

                continue;
            }
            $contents = (string) file_get_contents($source);
            $emit($this->header($name, strlen($contents), '0', 0644));
            $emit($contents);
            $padding = (self::BLOCK - strlen($contents) % self::BLOCK) % self::BLOCK;
            if ($padding > 0) {
                $emit(str_repeat("\0", $padding));
            }
        }
        $emit(str_repeat("\0", self::BLOCK * 2));
        $tail = (self::RECORD - $size % self::RECORD) % self::RECORD;
        if ($tail > 0) {
            $emit(str_repeat("\0", $tail));
        }
        fwrite($handle, (string) deflate_add($deflate, '', ZLIB_FINISH));
        fwrite($handle, pack('V', (int) hexdec(hash_final($crc))) . pack('V', $size & 0xFFFFFFFF));
        fclose($handle);
        rename($path . '.part', $path);
    }

    /** A POSIX ustar header. */
    private function header(string $name, int $size, string $type, int $mode): string
    {
        [$prefix, $short] = $this->split($name);
        $header = str_pad($short, 100, "\0")
            . $this->octal($mode, 8)
            . $this->octal(0, 8)
            . $this->octal(0, 8)
            . $this->octal($size, 12)
            . $this->octal(0, 12)
            . str_repeat(' ', 8)
            . $type
            . str_repeat("\0", 100)
            . "ustar\x0000"
            . str_pad('root', 32, "\0")
            . str_pad('root', 32, "\0")
            . $this->octal(0, 8)
            . $this->octal(0, 8)
            . str_pad($prefix, 155, "\0")
            . str_repeat("\0", 12);
        $checksum = array_sum(array_map('ord', str_split($header)));

        return substr_replace($header, sprintf('%06o', $checksum) . "\0 ", 148, 8);
    }

    /** @return array{0:string,1:string} prefix and name */
    private function split(string $name): array
    {
        if (strlen($name) <= 100) {
            return ['', $name];
        }
        $directory = str_ends_with($name, '/');
        $trimmed = $directory ? substr($name, 0, -1) : $name;
        // The longest prefix that fits leaves the shortest name.
        for ($slash = strlen($trimmed) - 1; $slash > 0; $slash--) {
            if ($trimmed[$slash] !== '/') {
                continue;
            }
            $prefix = substr($trimmed, 0, $slash);
            $short = substr($trimmed, $slash + 1) . ($directory ? '/' : '');
            if (strlen($short) > 100) {
                break;
            }
            if (strlen($prefix) <= 155) {
                return [$prefix, $short];
            }
        }
        throw new PortableConfigurationException('PORTAL_PATH_TOO_LONG', "[$name] is too long for a ustar archive.");
    }

    private function octal(int $value, int $length): string
    {
        return str_pad(decoct($value), $length - 1, '0', STR_PAD_LEFT) . "\0";
    }
}
