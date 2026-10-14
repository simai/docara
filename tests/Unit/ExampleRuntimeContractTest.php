<?php

declare(strict_types=1);

namespace Docara\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class ExampleRuntimeContractTest extends TestCase
{
    public function test_framework_scripts_are_propagated_into_sandboxed_examples(): void
    {
        $root = dirname(__DIR__, 2);
        $shell = (string) file_get_contents($root . '/resources/portable/declarative-shell.js');
        $renderer = (string) file_get_contents($root . '/src/PortableSite/PortableMarkdownRenderer.php');

        self::assertStringContainsString("scripts:Array.from(document.querySelectorAll('script[data-docara-framework-asset][src]'))", $shell);
        self::assertStringContainsString('simai.framework.icon_font.ready', $shell);
        self::assertStringNotContainsString('inlineScripts.length>0', $shell);
        self::assertStringContainsString('inlineScripts:', $shell);
        self::assertStringContainsString('portableExampleStyle(script.textContent', $shell);
        self::assertStringContainsString('exampleFontAsset', $shell);
        self::assertStringContainsString('simai.framework.icon_font.css', $shell);
        self::assertStringContainsString('inlineStyles=styles', $shell);
        self::assertStringContainsString('simai.framework.icon_fallback_font.css', $shell);
        self::assertStringContainsString('function exampleDesignTokens()', $shell);
        self::assertStringContainsString('function collectExampleTokenNames(rules,names)', $shell);
        self::assertStringContainsString("names=new Set(['--sf-text--size','--sf-text--height'])", $shell);
        self::assertStringContainsString('collectExampleTokenNames(sheet.cssRules,names)', $shell);
        self::assertStringContainsString('designTokens:exampleDesignTokens()', $shell);
        self::assertStringContainsString('rootFontSize:getComputedStyle(document.documentElement).fontSize', $shell);
        self::assertStringContainsString('Array.isArray(data.scripts)?data.scripts:[]', $renderer);
        self::assertStringContainsString('Array.isArray(data.inlineScripts)?data.inlineScripts:[]', $renderer);
        self::assertStringContainsString('Array.isArray(data.inlineStyles)?data.inlineStyles:[]', $renderer);
        self::assertStringContainsString('data-docara-example-framework-inline-style', $renderer);
        self::assertStringContainsString('URL.createObjectURL(new Blob([font.bytes]', $renderer);
        self::assertStringContainsString('data-docara-example-framework-inline-script', $renderer);
        self::assertStringContainsString('script.textContent=content', $renderer);
        self::assertStringContainsString('Promise.all(styleLoads)', $renderer);
        self::assertLessThan(
            strpos($renderer, "var current=Array.from(document.querySelectorAll('link[data-docara-example-framework-style]')"),
            strpos($renderer, "var currentInlineStyles=Array.from(document.querySelectorAll('style[data-docara-example-framework-inline-style]')"),
            'Portable font styles must be installed before external Framework stylesheets can trigger opaque-origin font requests.',
        );
        self::assertStringContainsString('data-docara-example-framework-script', $renderer);
        self::assertStringContainsString('script.async=false', $renderer);
    }

    public function test_inline_examples_cannot_submit_a_form_through_the_documentation_page(): void
    {
        $shell = (string) file_get_contents(dirname(__DIR__, 2) . '/resources/portable/declarative-shell.js');
        $css = (string) file_get_contents(dirname(__DIR__, 2) . '/resources/portable/declarative-shell.css');

        self::assertStringContainsString(
            "Array.from(document.querySelectorAll('[data-docara-example-inline-preview]')).forEach(function(root){\n"
            . "    root.addEventListener('submit',function(event){event.preventDefault()},true);",
            $shell,
        );
        self::assertStringContainsString('.docara-prose p:not(:where(.docara-example-inline *))', $css);
        self::assertStringContainsString('.docara-prose sf-button:not(:where(.docara-example-inline *))', $css);
        self::assertStringContainsString('.docara-example-inline{display:flow-root;min-inline-size:0}', $css);
        self::assertStringContainsString('.docara-prose li:not(:where(.docara-example-inline *))', $css);
        self::assertStringContainsString('.docara-prose h2[id]:not(:where(.docara-example-inline *))', $css);
    }

    public function test_framed_examples_take_the_page_typography_with_its_faces_sent_as_bytes(): void
    {
        $root = dirname(__DIR__, 2);
        $shell = (string) file_get_contents($root . '/resources/portable/declarative-shell.js');
        $renderer = (string) file_get_contents($root . '/src/PortableSite/PortableMarkdownRenderer.php');

        self::assertStringNotContainsString('Arial', $renderer);
        self::assertStringNotContainsString("root.style.setProperty('--sf-text--family'", $renderer);
        self::assertStringNotContainsString("root.style.setProperty('--sf-heading--family'", $renderer);
        self::assertStringNotContainsString("root.style.setProperty('--sf-display--family'", $renderer);
        self::assertStringContainsString('The frame takes the page\'s font families with the other tokens.', $renderer);

        // The Inter faces live in the Framework foundation stylesheet link (the
        // runtime projection's core.css), which the frame receives as text with
        // its font files as bytes.
        self::assertStringContainsString('link[data-docara-framework-asset][rel="stylesheet"]', $shell);
        self::assertStringContainsString('codepoints=exampleCodepoints(source)', $shell);
        self::assertStringContainsString('portableExampleStylesheet(item.link,codepoints,item.faces)', $shell);
        self::assertStringContainsString('||!exampleFaceNeeded(rule,codepoints)?\'\':rule', $shell);
        self::assertStringContainsString('function exampleFaceNeeded(rule,codepoints)', $shell);
        self::assertStringContainsString('missing.some(function(token){return rule.indexOf(token)!==-1})?\'\':rule', $shell);
        self::assertStringContainsString("exampleFontAssets[resolved.href]=exampleFontsSettled.then(function(){return fetch(resolved.href,{credentials:'same-origin',cache:'force-cache'})})", $shell);
    }

    public function test_icon_fonts_reach_only_frames_that_show_icons(): void
    {
        $root = dirname(__DIR__, 2);
        $shell = (string) file_get_contents($root . '/resources/portable/declarative-shell.js');
        $renderer = (string) file_get_contents($root . '/src/PortableSite/PortableMarkdownRenderer.php');

        // Icons named in the frame's own sources are known at build time.
        self::assertStringContainsString("preg_match('/sf-icon|material-symbols/i', \$authored)", $renderer);
        self::assertStringContainsString('data-docara-example-icons="', $renderer);
        self::assertStringContainsString("(frame.getAttribute('data-docara-example-icons')||'').split(/\\s+/)", $shell);
        // Smart components render theirs later; the frame reports them once.
        self::assertStringContainsString("root.querySelector('sf-icon,.sf-icon,[class*=\"material-symbols\"]')", $renderer);
        self::assertStringContainsString("parent.postMessage({type:'docara:example-icons',icons:icons},'*');", $renderer);
        self::assertStringContainsString("event.data.type!=='docara:example-icons'", $shell);
        self::assertStringContainsString("['outlined','rounded','shape','full'].indexOf(token)!==-1", $shell);
        // Icon styles, the icon-ready runtime and icon faces travel only with icons.
        self::assertStringContainsString("var inlineScripts=icons?Array.from(document.querySelectorAll('script[data-docara-framework-asset=\"simai.framework.icon_font.ready\"]:not([src])')):[];", $shell);
        self::assertStringContainsString('var inlineStyles=icons?Array.from(', $shell);
        self::assertStringContainsString('if(state.icons.rounded||state.icons.shape){', $shell);
        self::assertStringContainsString("if(icons&&!subsetStyle&&fresh('icon-faces:'+link.href))", $shell);
        self::assertStringContainsString("return /font-family\\s*:\\s*[\"']?Material (?:Symbols|Icons)/i.test(rule)", $shell);
        self::assertStringContainsString("(faces==='text'&&exampleIconFace(rule))", $shell);
    }

    public function test_the_full_icon_font_reaches_a_frame_only_when_its_icon_runtime_asks(): void
    {
        $root = dirname(__DIR__, 2);
        $shell = (string) file_get_contents($root . '/resources/portable/declarative-shell.js');
        $renderer = (string) file_get_contents($root . '/src/PortableSite/PortableMarkdownRenderer.php');
        $planner = (string) file_get_contents($root . '/src/Framework/FrameworkAssetPlanner.php');

        // The subset goes with the first icon; the full font waits for a request.
        self::assertStringContainsString("document.querySelectorAll(state.icons.full?'style[data-docara-framework-asset=\"simai.framework.icon_font.css\"],style[data-docara-framework-asset=\"simai.framework.icon_fallback_font.css\"]':'style[data-docara-framework-asset=\"simai.framework.icon_font.css\"]')", $shell);
        self::assertStringContainsString("if(token==='full'&&!state.icons.outlined){state.icons.outlined=true;changed=true}", $shell);
        // The frame document identifies itself to the Framework icon runtime.
        self::assertStringContainsString("document.documentElement.setAttribute('data-docara-example-frame','');", $renderer);
        // That runtime asks at the moment the page would load the full font.
        self::assertStringContainsString('if(!style&&document.documentElement.hasAttribute("data-docara-example-frame")){fallbackPending=requestFullFont()', $planner);
        self::assertStringContainsString('parent.postMessage({type:"docara:example-icons",icons:["full"]},"*")', $planner);
    }

    public function test_frames_receive_each_font_once_from_the_page_cache(): void
    {
        $root = dirname(__DIR__, 2);
        $shell = (string) file_get_contents($root . '/resources/portable/declarative-shell.js');
        $renderer = (string) file_get_contents($root . '/src/PortableSite/PortableMarkdownRenderer.php');

        self::assertStringContainsString('document.fonts.ready.catch(function(){})', $shell);
        self::assertStringContainsString("fetch(resolved.href,{credentials:'same-origin',cache:'force-cache'})", $shell);
        self::assertStringContainsString('if(!key||state.sent[key])return false;', $shell);
        self::assertStringContainsString('bootScripts=bootScripts.filter(function(script){return fresh(keyOf(script))});', $shell);
        self::assertStringContainsString("event.data.type!=='docara:example-ready'", $shell);
        self::assertStringContainsString('state.sent=Object.create(null);', $shell);
        self::assertStringContainsString('if(state.generation!==generation)return;', $shell);
        self::assertStringContainsString('state.queue=state.queue.then(function(){', $shell);
        self::assertStringContainsString("parent.postMessage({type:'docara:example-ready'},'*');", $renderer);
    }

    public function test_frame_documents_carry_the_page_language_and_a_title(): void
    {
        $root = dirname(__DIR__, 2);
        $shell = (string) file_get_contents($root . '/resources/portable/declarative-shell.js');
        $renderer = (string) file_get_contents($root . '/src/PortableSite/PortableMarkdownRenderer.php');

        self::assertStringContainsString("lang:document.documentElement.lang||''", $shell);
        self::assertStringContainsString("title:frame.getAttribute('title')||''", $shell);
        self::assertStringContainsString("var label=message('examples.example');", $shell);
        self::assertStringContainsString("if(element.tagName==='IFRAME'){element.setAttribute('title',label);return}", $shell);
        self::assertStringContainsString('{document.documentElement.lang=data.lang}', $renderer);
        self::assertStringContainsString('{document.title=data.title}', $renderer);
        self::assertStringContainsString("'<title>' . \$this->escapeHtml(\$label) . '</title>'", $renderer);
    }

    public function test_example_height_measures_content_instead_of_its_current_viewport(): void
    {
        $shell = (string) file_get_contents(dirname(__DIR__, 2) . '/resources/portable/declarative-shell.js');
        $renderer = (string) file_get_contents(dirname(__DIR__, 2) . '/src/PortableSite/PortableMarkdownRenderer.php');

        self::assertStringContainsString('scrolling="no"', $renderer);
        self::assertStringContainsString("document.documentElement.style.minHeight='0'", $renderer);
        self::assertStringContainsString("body.style.minHeight='0'", $renderer);
        self::assertStringContainsString("document.documentElement.style.overflow='hidden'", $renderer);
        self::assertStringContainsString("body.style.overflow='hidden'", $renderer);
        self::assertStringContainsString('function applyDesignEnvironment(data)', $renderer);
        self::assertStringContainsString('Object.keys(tokens).sort().slice(0,4096)', $renderer);
        self::assertStringContainsString('contentBottom-contentTop', $renderer);
        self::assertStringNotContainsString('body.scrollHeight,root.scrollHeight', $renderer);
        self::assertStringContainsString("frame.style.blockSize=Math.max(32,Math.ceil(height))+'px'", $shell);
        self::assertStringNotContainsString('Math.min(4096', $shell);
    }

    public function test_example_controls_keep_responsive_viewer_and_soft_wrap_scopes_separate(): void
    {
        $root = dirname(__DIR__, 2);
        $shell = (string) file_get_contents($root . '/resources/portable/declarative-shell.js');
        $styles = (string) file_get_contents($root . '/resources/portable/declarative-shell.css');

        self::assertStringContainsString("example.dataset.sourceActive=key==='example'?'false':'true'", $shell);
        self::assertStringContainsString("var selected=example.dataset.sourceActive!=='true'", $shell);
        self::assertStringContainsString('viewerButton.hidden=!active&&!selected', $shell);
        self::assertStringNotContainsString('viewerButton.hidden=false', $shell);
        self::assertStringContainsString("wrapButton.hidden=example.dataset.sourceActive!=='true'", $shell);
        self::assertStringContainsString("viewerDialog=document.createElement('dialog')", $shell);
        self::assertStringContainsString('viewerDialog.showModal()', $shell);
        self::assertStringContainsString("viewerDialog.addEventListener('close',restoreViewer)", $shell);
        self::assertStringContainsString("example.dataset.docaraExampleViewerActive='true'", $shell);
        self::assertStringContainsString('var exampleViewportWidths={desktop:1280,tablet:768,mobile:390}', $shell);
        self::assertStringContainsString('sessionStorage.setItem(exampleViewportStorageKey,viewport)', $shell);
        self::assertStringContainsString("previewFrame.style.inlineSize=exampleViewportWidths[viewport]+'px'", $shell);
        self::assertStringContainsString("document.dispatchEvent(new CustomEvent('docara:example-viewport-change'", $shell);
        self::assertStringContainsString("previewFrame.style.removeProperty('inline-size')", $shell);
        self::assertStringContainsString("message('examples.viewport_'+button.dataset.docaraExampleViewport)", $shell);
        self::assertStringContainsString('viewportControls.hidden=!active||!selected', $shell);
        self::assertStringContainsString("localStorage.setItem(sourceWrapStorageKey,sourceWrapActive?'true':'false')", $shell);
        self::assertStringContainsString("sourceWrapStorageKey='docara.source.wrap'", $shell);
        self::assertStringNotContainsString("message(active?'examples.unwrap':'examples.wrap')", $shell);
        self::assertStringContainsString("document.addEventListener('docara:source-wrap-change'", $shell);
        self::assertStringContainsString('if(text&&text.textContent!==label){text.textContent=label}', $shell);
        self::assertStringContainsString("button.dataset.unwrapIcon||'format_text_overflow'", $shell);
        self::assertStringContainsString('syncSourceWrapIcon(button,active)', $shell);
        self::assertStringContainsString('syncSourceWrapIcon(wrapButton,active)', $shell);
        self::assertStringContainsString("block.dataset.docaraCodeWrapActive=active?'true':'false'", $shell);
        self::assertStringContainsString("window.matchMedia('(max-width: 640px)').matches", $shell);
        self::assertStringContainsString("var lines=Array.from(code.querySelectorAll('.hljs-ln-code'))", $shell);
        self::assertStringContainsString('[data-docara-example-wrap-active="true"]', $styles);
        self::assertStringContainsString('[data-docara-example-wrap-active="true"] [data-docara-example-panel] .docara-code-scroll code{min-inline-size:0;white-space:pre-wrap;overflow-wrap:anywhere}', $styles);
        self::assertStringContainsString('white-space:pre-wrap', $styles);
        self::assertStringContainsString('.docara-example-viewer-dialog', $styles);
        self::assertStringContainsString('.docara-example-preview[data-docara-example-viewer-active="true"]', $styles);
        // Full-screen examples fill the viewing area, so fixed overlays such as a modal drawer are visible.
        self::assertStringContainsString('iframe[data-docara-example-frame]{flex:0 0 auto;min-block-size:100%;', $styles);
        self::assertStringContainsString('[data-docara-example-viewer]{display:none!important}', $styles);
        self::assertStringNotContainsString('.docara-example-preview__viewport[aria-pressed="true"]', $styles);
        self::assertStringContainsString("button.setAttribute('aria-pressed',active?'true':'false')", $shell);
        self::assertStringContainsString("button.classList.toggle('active',active)", $shell);
        self::assertStringContainsString('.docara-example-preview .docara-example-preview__action:focus:not(:focus-visible)', $styles);
        self::assertStringNotContainsString('.docara-example-preview .docara-example-preview__action:focus,.docara-example-preview .docara-example-preview__action:focus-visible', $styles);
    }
}
