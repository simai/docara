<?php

declare(strict_types=1);

namespace Simai\Docara\PortableSite;

use Simai\Docara\Framework\FrameworkAssetPlan;

final class PortableHtmlRenderer
{
    /**
     * @param  array<string, mixed>  $page
     * @param  list<array<string, string|bool>>  $navigation
     */
    public function render(
        array $page,
        array $navigation,
        string $siteTitle,
        FrameworkAssetPlan $assets,
    ): string {
        $preset = (string) $page['preset'];
        $body = $preset === 'landing'
            ? $this->landing($page)
            : $this->documentation($page, $navigation);
        $locale = $this->escape((string) $page['locale']);
        $title = $this->escape((string) $page['title'] . ' — ' . $siteTitle);
        $description = trim((string) ($page['description'] ?? ''));
        $descriptionTag = $description === ''
            ? ''
            : "\n    <meta name=\"description\" content=\"{$this->escape($description)}\">";

        return '<!doctype html>' . "\n"
            . '<html lang="' . $locale . '" class="theme-light" data-docara-theme="system">' . "\n"
            . '<head>' . "\n"
            . '    <meta charset="utf-8">' . "\n"
            . '    <meta name="viewport" content="width=device-width, initial-scale=1">' . $descriptionTag . "\n"
            . '    <title>' . $title . '</title>' . "\n"
            . '    ' . $this->themeBootstrap((string) $page['theme']) . "\n"
            . $this->indent($assets->headHtml(), 4) . "\n"
            . '    <style>' . $this->shellCss() . '</style>' . "\n"
            . '</head>' . "\n"
            . '<body class="bg-surface">' . "\n"
            . '    <a class="docara-skip-link bg-surface-0 border radius-1 p-1" href="#docara-main">К содержанию</a>' . "\n"
            . $this->header($siteTitle, (string) $page['home_url']) . "\n"
            . $body . "\n"
            . '    ' . $this->themeController() . "\n"
            . '</body>' . "\n"
            . '</html>' . "\n";
    }

    /** @param array<string, mixed> $page @param list<array<string, string|bool>> $navigation */
    private function documentation(array $page, array $navigation): string
    {
        $links = [];
        foreach ($navigation as $item) {
            $classes = 'docara-nav-link p-1 radius-1';
            $current = '';
            if ($item['active'] === true) {
                $classes .= ' bg-surface-container weight-6';
                $current = ' aria-current="page"';
            }
            $links[] = '                <a class="' . $classes . '" href="'
                . $this->escape((string) $item['url']) . '"' . $current . '>'
                . $this->escape((string) $item['title']) . '</a>';
        }

        return '    <div class="docara-docs-layout gap-3 p-3">' . "\n"
            . '        <aside class="docara-sidebar bg-surface-0 border radius-2 p-2">' . "\n"
            . '            <nav class="flex flex-col gap-1" aria-label="Разделы документации">' . "\n"
            . implode("\n", $links) . "\n"
            . '            </nav>' . "\n"
            . '        </aside>' . "\n"
            . '        <main id="docara-main" tabindex="-1" class="docara-content docara-prose bg-surface-0 border radius-2 p-3" data-width="'
            . $this->escape((string) $page['max_width']) . '">' . "\n"
            . $this->indent((string) $page['content_html'], 12) . "\n"
            . '        </main>' . "\n"
            . '    </div>';
    }

    /** @param array<string, mixed> $page */
    private function landing(array $page): string
    {
        return '    <main id="docara-main" tabindex="-1" class="docara-landing flex flex-col gap-4 p-4">' . "\n"
            . '        <article class="docara-content docara-prose bg-surface-0 border radius-3 p-4" data-width="'
            . $this->escape((string) $page['max_width']) . '">' . "\n"
            . $this->indent((string) $page['content_html'], 12) . "\n"
            . '        </article>' . "\n"
            . '    </main>';
    }

    private function header(string $siteTitle, string $homeUrl): string
    {
        return '    <header class="sticky top-0 z-2 flex items-center content-main-between gap-2 p-2 bg-surface-0 border-bottom-1 border-outline-variant">' . "\n"
            . '        <a class="docara-brand color-on-surface decoration-none weight-7" href="' . $this->escape($homeUrl) . '">' . $this->escape($siteTitle) . '</a>' . "\n"
            . '        <button id="docara-theme-toggle" class="docara-theme-toggle bg-surface-0 border radius-1 p-1" type="button" aria-label="Тема: система. Переключить на светлую тему" aria-live="polite">Тема: система</button>' . "\n"
            . '    </header>';
    }

    private function themeBootstrap(string $configuredTheme): string
    {
        $configured = in_array($configuredTheme, ['light', 'dark', 'system'], true) ? $configuredTheme : 'system';
        $json = json_encode($configured, JSON_THROW_ON_ERROR);

        return '<script data-docara-theme-bootstrap>(function(){var configured=' . $json
            . ",saved='';try{saved=localStorage.getItem('docara-theme')||''}catch(e){}var mode=/^(light|dark|system)$/.test(saved)?saved:configured;"
            . "if(mode==='system'){document.cookie='sf-theme=; Path=/; Max-Age=0; SameSite=Lax'}else{document.cookie='sf-theme='+mode+'; Path=/; Max-Age=31536000; SameSite=Lax'};"
            . "var dark=mode==='dark'||(mode==='system'&&window.matchMedia&&window.matchMedia('(prefers-color-scheme: dark)').matches);"
            . "var root=document.documentElement;root.classList.remove('theme-light','theme-dark');root.classList.add(dark?'theme-dark':'theme-light');"
            . 'root.dataset.docaraTheme=mode;window.SF_BOOT_CONFIG=window.SF_BOOT_CONFIG||{};window.SF_BOOT_CONFIG.preloader={enabled:false};})();</script>';
    }

    private function themeController(): string
    {
        return '<script data-docara-theme-controller>(function(){var button=document.getElementById(\'docara-theme-toggle\');if(!button)return;'
            . 'var labels={system:\'система\',light:\'светлая\',dark:\'тёмная\'},actions={system:\'системную\',light:\'светлую\',dark:\'тёмную\'};function apply(mode){var dark=mode===\'dark\'||(mode===\'system\'&&window.matchMedia&&window.matchMedia(\'(prefers-color-scheme: dark)\').matches);'
            . 'document.cookie=mode===\'system\'?\'sf-theme=; Path=/; Max-Age=0; SameSite=Lax\':\'sf-theme=\'+mode+\'; Path=/; Max-Age=31536000; SameSite=Lax\';document.documentElement.classList.remove(\'theme-light\',\'theme-dark\');document.documentElement.classList.add(dark?\'theme-dark\':\'theme-light\');document.documentElement.dataset.docaraTheme=mode;var next=mode===\'system\'?\'light\':mode===\'light\'?\'dark\':\'system\';button.textContent=\'Тема: \'+labels[mode];button.setAttribute(\'aria-label\',\'Тема: \'+labels[mode]+\'. Переключить на \'+actions[next]+\' тему\');}'
            . 'button.addEventListener(\'click\',function(){var current=document.documentElement.dataset.docaraTheme||\'system\';var next=current===\'system\'?\'light\':current===\'light\'?\'dark\':\'system\';try{localStorage.setItem(\'docara-theme\',next)}catch(e){}apply(next);});apply(document.documentElement.dataset.docaraTheme||\'system\');})();</script>';
    }

    private function shellCss(): string
    {
        return <<<'CSS'
html{color-scheme:light dark}.theme-light{color-scheme:light}.theme-dark{color-scheme:dark}body{min-height:100vh;background:var(--sf-surface-1);color:var(--sf-on-surface)}.docara-skip-link{position:fixed;inset-block-start:var(--sf-space-1);inset-inline-start:var(--sf-space-1);z-index:100;transform:translateY(-200%);color:var(--sf-on-surface);text-decoration:none}.docara-skip-link:focus{transform:translateY(0)}.docara-nav-link{color:var(--sf-on-surface);text-decoration:none}.docara-theme-toggle{color:var(--sf-on-surface);font:inherit}.docara-skip-link:focus-visible,.docara-brand:focus-visible,.docara-nav-link:focus-visible,.docara-theme-toggle:focus-visible,sf-button>button:focus-visible{outline:3px solid var(--sf-primary,Highlight);outline-offset:3px}.docara-docs-layout{display:grid;grid-template-columns:minmax(13rem,17rem) minmax(0,1fr);max-width:96rem;margin-inline:auto}.docara-sidebar{align-self:start;position:sticky;inset-block-start:5rem}.docara-content{min-width:0;color:var(--sf-on-surface);scroll-margin-block-start:6rem}.docara-content[data-width="compact"]{max-width:45rem}.docara-content[data-width="normal"]{max-width:60rem}.docara-content[data-width="wide"]{max-width:80rem}.docara-content[data-width="full"]{max-width:none}.docara-prose{line-height:1.65}.docara-prose>*+*{margin-block-start:var(--sf-space-2)}.docara-prose h1{font-size:clamp(2rem,5vw,4rem);line-height:1.08;font-weight:750;letter-spacing:-.035em}.docara-prose h2{font-size:clamp(1.45rem,3vw,2.25rem);line-height:1.2;font-weight:700;margin-block-start:var(--sf-space-4)}.docara-prose h3{font-size:1.25rem;line-height:1.3;font-weight:650;margin-block-start:var(--sf-space-3)}.docara-prose p,.docara-prose li{max-width:72ch}.docara-landing{align-items:center;justify-content:center;min-height:calc(100vh - 5rem)}.docara-landing .docara-content{width:min(100%,80rem)}sf-alert,sf-button{display:block}.docara-prose sf-alert,.docara-prose sf-button{margin-block:var(--sf-space-2)}@media(max-width:800px){.docara-docs-layout{grid-template-columns:minmax(0,1fr);padding:var(--sf-space-1)}.docara-sidebar{position:static}.docara-content{padding:var(--sf-space-2);scroll-margin-block-start:var(--sf-space-1)}.docara-landing{padding:var(--sf-space-1);min-height:auto}}
CSS;
    }

    private function indent(string $value, int $spaces): string
    {
        $padding = str_repeat(' ', $spaces);

        return $padding . str_replace("\n", "\n" . $padding, rtrim($value));
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
