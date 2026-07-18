<?php

declare(strict_types=1);

namespace Simai\Docara\PortableSite;

use Simai\Docara\Framework\FrameworkAssetPlan;

final class PortableHtmlRenderer
{
    /**
     * @param  array<string, mixed>  $page
     * @param  list<array<string, mixed>>  $navigation
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
        $branding = $page['branding'];
        $brandTitle = (string) ($branding['title'] ?? $siteTitle);
        $locale = $this->escape((string) $page['locale']);
        $title = $this->escape((string) $page['title'] . ' — ' . $brandTitle);
        $description = trim((string) ($page['description'] ?? ''));
        $descriptionTag = $description === ''
            ? ''
            : "\n    <meta name=\"description\" content=\"{$this->escape($description)}\">";
        $faviconTag = is_string($branding['favicon'] ?? null)
            ? "\n    <link rel=\"icon\" href=\"{$this->escape($branding['favicon'])}\" type=\"{$this->escape((string) $branding['favicon_type'])}\">"
            : '';

        return '<!doctype html>' . "\n"
            . '<html lang="' . $locale . '" class="theme-light">' . "\n"
            . '<head>' . "\n"
            . '    <meta charset="utf-8">' . "\n"
            . '    <meta name="viewport" content="width=device-width, initial-scale=1">' . $descriptionTag . $faviconTag . "\n"
            . '    <title>' . $title . '</title>' . "\n"
            . '    ' . $this->themeBootstrap((string) $page['theme']) . "\n"
            . $this->indent($assets->headHtml(), 4) . "\n"
            . '    <style>' . $this->shellCss() . '</style>' . "\n"
            . '</head>' . "\n"
            . '<body class="bg-surface">' . "\n"
            . '    <a class="docara-skip-link bg-surface-0 border radius-1 p-1" href="#docara-main">К содержанию</a>' . "\n"
            . $this->header($page, $navigation) . "\n"
            . $body . "\n"
            . '    ' . $this->shellController() . "\n"
            . '</body>' . "\n"
            . '</html>' . "\n";
    }

    /** @param array<string, mixed> $page @param list<array<string, mixed>> $navigation */
    private function documentation(array $page, array $navigation): string
    {
        return '    <div class="docara-docs-layout gap-3 p-3">' . "\n"
            . '        <aside class="docara-sidebar bg-surface-0 border radius-2 p-2">' . "\n"
            . $this->indent($this->navigation($navigation, 'Разделы документации'), 12) . "\n"
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

    /** @param array<string, mixed> $page @param list<array<string, mixed>> $navigation */
    private function header(array $page, array $navigation): string
    {
        $branding = $page['branding'];
        $brand = $this->brand($branding, (string) $page['home_url']);
        $mobile = '';
        if ($page['preset'] === 'docs') {
            $mobile = "\n" . '        <details id="docara-mobile-navigation" class="docara-mobile-navigation">' . "\n"
                . '            <summary class="docara-mobile-navigation-summary border radius-1 p-1">Разделы</summary>' . "\n"
                . '            <div class="docara-mobile-navigation-panel bg-surface-0 border radius-2 p-2">' . "\n"
                . $this->indent($this->navigation($navigation, 'Мобильная навигация по документации'), 16) . "\n"
                . '            </div>' . "\n"
                . '        </details>';
        }

        return '    <header class="docara-header sticky top-0 z-2 bg-surface-0 border-bottom-1 border-outline-variant">' . "\n"
            . '        <div class="docara-header-row flex items-center content-main-between gap-2 p-2">' . "\n"
            . $this->indent($brand, 12) . "\n"
            . '            <button class="sf-theme-button sf-icon-button sf-icon-button--icon sf-icon-button--on-surface sf-icon-button--link sf-icon-button--size-1/2 radius-default" data-docara-theme-button type="button" aria-label="Переключить цветовую тему">' . "\n"
            . '                <sf-icon icon="contrast" aria-hidden="true"></sf-icon>' . "\n"
            . '            </button>' . "\n"
            . '        </div>' . $mobile . "\n"
            . '    </header>';
    }

    /** @param array<string, string|null> $branding */
    private function brand(array $branding, string $homeUrl): string
    {
        $title = $this->escape((string) $branding['title']);
        $label = is_string($branding['label'] ?? null)
            ? '<span class="docara-brand-label color-on-surface-variant">' . $this->escape($branding['label']) . '</span>'
            : '';
        $mark = '';
        if (is_string($branding['logo'] ?? null)) {
            $mark = '<span class="docara-brand-mark">'
                . '<img class="docara-brand-logo docara-brand-logo--light" src="' . $this->escape($branding['logo']) . '" alt="">';
            if (is_string($branding['logo_dark'] ?? null)) {
                $mark .= '<img class="docara-brand-logo docara-brand-logo--dark" src="'
                    . $this->escape($branding['logo_dark']) . '" alt="">';
            }
            $mark .= '</span>';
        }

        return '<a class="docara-brand flex items-center gap-1 color-on-surface decoration-none" href="'
            . $this->escape($homeUrl) . '">' . $mark
            . '<span class="docara-brand-copy flex flex-col"><span class="weight-7">' . $title . '</span>'
            . $label . '</span></a>';
    }

    /** @param list<array<string, mixed>> $navigation */
    private function navigation(array $navigation, string $label): string
    {
        return '<nav class="docara-navigation" aria-label="' . $this->escape($label) . '">' . "\n"
            . $this->indent($this->menu($navigation), 4) . "\n"
            . '</nav>';
    }

    /** @param list<array<string, mixed>> $nodes */
    private function menu(array $nodes, int $depth = 1): string
    {
        $items = [];
        foreach ($nodes as $node) {
            $children = $node['children'];
            $isBranch = $children !== [];
            $open = $isBranch && ($node['open'] ?? false) === true;
            $itemClasses = 'sf-menu-item flex flex-col' . ($open ? ' open' : '');
            $itemAttributes = $open ? ' expanded aria-expanded="true"' : ($isBranch ? ' aria-expanded="false"' : '');
            $activeRole = match (true) {
                ($node['active'] ?? false) === true => 'page',
                ($node['current_section'] ?? false) === true => 'section',
                ($node['active_ancestor'] ?? false) === true => 'ancestor',
                default => null,
            };
            if ($activeRole !== null) {
                $itemAttributes .= ' data-docara-active-role="' . $activeRole . '"';
            }
            $frameworkDepth = max(1, min(4, $depth));
            $elementClasses = 'sf-menu-element sf-menu-element--level-' . $frameworkDepth
                . ' flex items-cross-center transition' . ($open ? ' open' : '');
            $current = $activeRole === 'page' ? ' aria-current="page"' : '';
            $activeClass = match ($activeRole) {
                'page' => ' weight-7',
                'section' => ' weight-6',
                'ancestor' => ' weight-5',
                default => '',
            };

            if (is_string($node['url'])) {
                $primary = '<a class="sf-menu-element-wrap docara-navigation-link flex flex-1 items-cross-center radius-1'
                    . $activeClass . '" data-docara-menu-link href="' . $this->escape($node['url']) . '"' . $current . '>'
                    . '<span class="sf-menu-element-text">' . $this->escape((string) $node['title']) . '</span></a>';
            } else {
                $primary = '<span class="sf-menu-element-wrap docara-navigation-label flex flex-1 items-cross-center">'
                    . '<span class="sf-menu-element-text">' . $this->escape((string) $node['title']) . '</span></span>';
            }

            $disclosure = '';
            if ($isBranch) {
                $expanded = $open ? 'true' : 'false';
                $action = $open ? 'Свернуть' : 'Развернуть';
                $icon = $open ? 'expand_less' : 'expand_more';
                $containsCurrent = ($node['active_ancestor'] ?? false) === true;
                $currentAttribute = $containsCurrent ? ' data-docara-contains-current="true"' : '';
                $currentLabel = $containsCurrent ? ', содержит текущую страницу' : '';
                $disclosure = '<button type="button" data-docara-disclosure' . $currentAttribute
                    . ' aria-expanded="' . $expanded
                    . '" aria-label="' . $this->escape($action . ': ' . $node['title'] . $currentLabel)
                    . '" class="sf-icon-button sf-icon-button--icon sf-icon-button--on-surface sf-icon-button--link radius-default sf-icon-button--size-1/3">'
                    . '<sf-icon icon="' . $icon . '" aria-hidden="true"></sf-icon></button>';
            }

            $nested = $isBranch ? "\n" . $this->indent($this->menu($children, $depth + 1), 4) : '';
            $items[] = '<li class="' . $itemClasses . '" data-docara-navigation-depth="' . $depth . '"' . $itemAttributes . '>' . "\n"
                . '    <div role="presentation" tabindex="-1" class="' . $elementClasses . '">' . $primary . $disclosure . '</div>'
                . $nested . "\n"
                . '</li>';
        }

        return '<ul class="sf-menu flex flex-col">' . ($items === [] ? '' : "\n" . $this->indent(implode("\n", $items), 4) . "\n") . '</ul>';
    }

    private function themeBootstrap(string $configuredTheme): string
    {
        $configured = in_array($configuredTheme, ['light', 'dark', 'system'], true) ? $configuredTheme : 'system';
        $json = json_encode($configured, JSON_THROW_ON_ERROR);

        return '<script data-docara-theme-bootstrap>(function(){var configured=' . $json
            . ",cookie=(document.cookie.split('; ').find(function(value){return value.indexOf('sf-theme=')===0})||'').split('=')[1]||'';"
            . "var mode=/^(light|dark)$/.test(cookie)?cookie:configured;if(!cookie&&/^(light|dark)$/.test(configured)){document.cookie='sf-theme='+configured+'; Path=/; Max-Age=31536000; SameSite=Lax'}"
            . "var dark=mode==='dark'||(mode==='system'&&window.matchMedia&&window.matchMedia('(prefers-color-scheme: dark)').matches);"
            . "var root=document.documentElement;root.classList.remove('theme-light','theme-dark');root.classList.add(dark?'theme-dark':'theme-light');"
            . 'window.SF_BOOT_CONFIG=window.SF_BOOT_CONFIG||{};window.SF_BOOT_CONFIG.preloader={enabled:false};})();</script>';
    }

    private function shellController(): string
    {
        return <<<'HTML'
<script data-docara-shell-controller>(function(){
  function protectNativeLink(link){
    if(link.dataset.docaraLinkBound)return;
    link.dataset.docaraLinkBound='1';
    link.addEventListener('click',function(event){event.stopPropagation()});
    link.addEventListener('keydown',function(event){if(event.key==='Enter'){event.stopPropagation()}});
  }
  function syncDisclosure(item){
    var button=item.querySelector(':scope > .sf-menu-element > [data-docara-disclosure]');
    if(!button)return;
    var open=item.classList.contains('open')||item.hasAttribute('expanded')||item.getAttribute('aria-expanded')==='true';
    if(button.getAttribute('aria-expanded')!==String(open)){button.setAttribute('aria-expanded',String(open))}
    var title=(item.querySelector(':scope > .sf-menu-element .sf-menu-element-text')||{}).textContent||'';
    var containsCurrent=button.dataset.docaraContainsCurrent==='true';
    button.setAttribute('aria-label',(open?'Свернуть: ':'Развернуть: ')+title.trim()+(containsCurrent?', содержит текущую страницу':''));
  }
  function bindShell(){
    document.querySelectorAll('[data-docara-menu-link]').forEach(protectNativeLink);
    document.querySelectorAll('.sf-menu-item').forEach(syncDisclosure);
  }
  function revealActiveNavigation(){
    var rail=document.querySelector('.docara-sidebar');
    var active=rail&&rail.querySelector('[aria-current="page"]');
    if(!rail||!active||rail.dataset.docaraActiveRevealed)return;
    var railRect=rail.getBoundingClientRect();
    var activeRect=active.getBoundingClientRect();
    if(railRect.width<=0||railRect.height<=0||activeRect.width<=0||activeRect.height<=0)return;
    rail.dataset.docaraActiveRevealed='1';
    var inset=8;
    if(activeRect.bottom>railRect.bottom-inset){rail.scrollTop+=activeRect.bottom-(railRect.bottom-inset)}
    else if(activeRect.top<railRect.top+inset){rail.scrollTop+=activeRect.top-(railRect.top+inset)}
    window.removeEventListener('resize',scheduleActiveReveal);
  }
  var activeRevealFrame=0;
  function scheduleActiveReveal(){
    if(activeRevealFrame)return;
    activeRevealFrame=requestAnimationFrame(function(){activeRevealFrame=0;revealActiveNavigation()});
  }
  bindShell();
  function revealWhenReady(){
    var fonts=document.fonts&&document.fonts.ready?document.fonts.ready:Promise.resolve();
    var icon=window.customElements&&window.customElements.whenDefined
      ?Promise.race([window.customElements.whenDefined('sf-icon'),new Promise(function(resolve){setTimeout(resolve,800)})])
      :Promise.resolve();
    Promise.all([fonts,icon]).then(function(){
      requestAnimationFrame(function(){requestAnimationFrame(revealActiveNavigation)});
    });
  }
  window.addEventListener('resize',scheduleActiveReveal,{passive:true});
  if(document.readyState==='complete'){revealWhenReady()}
  else{window.addEventListener('load',revealWhenReady,{once:true})}
  new MutationObserver(bindShell).observe(document.body,{subtree:true,childList:true,attributes:true,attributeFilter:['class','expanded','aria-expanded']});
  var mobile=document.getElementById('docara-mobile-navigation');
  if(mobile){
    var summary=mobile.querySelector('summary');
    mobile.addEventListener('keydown',function(event){if(event.key==='Escape'&&mobile.open){event.preventDefault();mobile.open=false;summary.focus()}});
  }
  var themeButton=document.querySelector('[data-docara-theme-button]');
  if(themeButton){
    function syncThemeLabel(){var dark=document.documentElement.classList.contains('theme-dark');themeButton.setAttribute('aria-label','Переключить на '+(dark?'светлую':'тёмную')+' тему')}
    syncThemeLabel();
    new MutationObserver(syncThemeLabel).observe(document.documentElement,{attributes:true,attributeFilter:['class']});
  }
})();</script>
HTML;
    }

    private function shellCss(): string
    {
        return <<<'CSS'
html{color-scheme:light dark}.theme-light{color-scheme:light}.theme-dark{color-scheme:dark}body{min-height:100vh;background:var(--sf-surface-1);color:var(--sf-on-surface)}.docara-skip-link{position:fixed;inset-block-start:var(--sf-space-1);inset-inline-start:var(--sf-space-1);z-index:100;transform:translateY(-200%);color:var(--sf-on-surface);text-decoration:none}.docara-skip-link:focus{transform:translateY(0)}.docara-header{min-height:4.5rem}.docara-header-row{max-width:96rem;margin-inline:auto}.docara-brand{min-width:0;text-decoration:none}.docara-brand-mark{display:grid;place-items:center;inline-size:2.25rem;block-size:2.25rem;flex:0 0 auto}.docara-brand-logo{display:block;max-inline-size:100%;max-block-size:100%;object-fit:contain}.docara-brand-logo--dark{display:none}.theme-dark .docara-brand-logo--light:has(+.docara-brand-logo--dark){display:none}.theme-dark .docara-brand-logo--dark{display:block}.docara-brand-copy{min-width:0;line-height:1.15}.docara-brand-label{font-size:.75rem;font-weight:500}.docara-mobile-navigation{display:none}.docara-navigation-link{min-width:0;color:var(--sf-on-surface);text-decoration:none}.docara-navigation-label{min-width:0;color:var(--sf-on-surface)}.docara-navigation-link .sf-menu-element-text,.docara-navigation-label .sf-menu-element-text{overflow-wrap:anywhere}.docara-navigation [data-docara-active-role="ancestor"]>.sf-menu-element{--sf-menu-element--background-color:var(--sf-surface-container);--sf-menu-element--border-color:var(--sf-outline-variant)}.docara-navigation [data-docara-active-role="section"]>.sf-menu-element{--sf-menu-element--background-color:var(--sf-secondary-container);--sf-menu-element--border-color:var(--sf-outline);border-inline-start-width:2px}.docara-navigation [data-docara-active-role="page"]>.sf-menu-element{--sf-menu-element--background-color:var(--sf-primary-container);--sf-menu-element--border-color:var(--sf-primary);border-inline-start-width:4px}.docara-navigation [data-docara-active-role="section"]>.sf-menu-element>.docara-navigation-link,.docara-navigation [data-docara-active-role="section"]>.sf-menu-element>.docara-navigation-label{color:var(--sf-on-secondary-container)}.docara-navigation [data-docara-active-role="page"]>.sf-menu-element>.docara-navigation-link,.docara-navigation [data-docara-active-role="page"]>.sf-menu-element>.docara-navigation-label{color:var(--sf-on-primary-container)}.docara-skip-link:focus-visible,.docara-brand:focus-visible,.docara-navigation-link:focus-visible,.docara-mobile-navigation-summary:focus-visible,.sf-theme-button:focus-visible,[data-docara-disclosure]:focus-visible,sf-button>button:focus-visible{outline:3px solid var(--sf-primary,Highlight);outline-offset:3px}.docara-docs-layout{display:grid;grid-template-columns:minmax(14rem,18rem) minmax(0,1fr);max-width:96rem;margin-inline:auto}.docara-sidebar{align-self:start;position:sticky;inset-block-start:5.5rem;max-block-size:calc(100vh - 7rem);overflow:auto}.docara-content{min-width:0;color:var(--sf-on-surface);scroll-margin-block-start:6rem}.docara-content[data-width="compact"]{max-width:45rem}.docara-content[data-width="normal"]{max-width:60rem}.docara-content[data-width="wide"]{max-width:80rem}.docara-content[data-width="full"]{max-width:none}.docara-prose{line-height:1.65}.docara-prose>*+*{margin-block-start:var(--sf-space-2)}.docara-prose h1{font-size:clamp(2rem,5vw,4rem);line-height:1.08;font-weight:750;letter-spacing:-.035em}.docara-prose h2{font-size:clamp(1.45rem,3vw,2.25rem);line-height:1.2;font-weight:700;margin-block-start:var(--sf-space-4)}.docara-prose h3{font-size:1.25rem;line-height:1.3;font-weight:650;margin-block-start:var(--sf-space-3)}.docara-prose p,.docara-prose li{max-width:72ch}.docara-landing{align-items:center;justify-content:center;min-height:calc(100vh - 5rem)}.docara-landing .docara-content{width:min(100%,80rem)}sf-alert,sf-button{display:block}.docara-prose sf-alert,.docara-prose sf-button{margin-block:var(--sf-space-2)}@media(max-width:800px){.docara-header{min-height:auto}.docara-mobile-navigation{display:block;margin:0 var(--sf-space-1) var(--sf-space-1)}.docara-mobile-navigation-summary{cursor:pointer;color:var(--sf-on-surface);font-weight:650}.docara-mobile-navigation-summary::marker{color:var(--sf-primary)}.docara-mobile-navigation-panel{margin-block-start:var(--sf-space-1);max-block-size:min(70vh,36rem);overflow:auto}.docara-docs-layout{grid-template-columns:minmax(0,1fr);padding:var(--sf-space-1)}.docara-sidebar{display:none}.docara-content{padding:var(--sf-space-2);scroll-margin-block-start:7rem}.docara-landing{padding:var(--sf-space-1);min-height:auto}}@media(prefers-reduced-motion:reduce){*,*::before,*::after{scroll-behavior:auto!important;transition-duration:.01ms!important;animation-duration:.01ms!important;animation-iteration-count:1!important}}
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
