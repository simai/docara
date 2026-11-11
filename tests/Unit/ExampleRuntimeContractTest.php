<?php

declare(strict_types=1);

namespace Docara\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Simai\Docara\PortableSite\PortableMarkdownRenderer;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

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
        self::assertStringContainsString('function collectExampleTokenNames(rules,names,declared,widthScoped,inWidth)', $shell);
        self::assertStringContainsString("names=new Set(['--sf-text--size','--sf-text--height'])", $shell);
        self::assertStringContainsString('collectExampleTokenNames(sheet.cssRules,names,declared,widthScoped,', $shell);
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
        self::assertStringContainsString("['outlined','rounded','shape','full','rounded-full','shape-full'].indexOf(token)!==-1", $shell);
        // Icon styles, the icon-ready runtime and icon faces travel only with icons.
        self::assertStringContainsString("var inlineScripts=icons?Array.from(document.querySelectorAll('script[data-docara-framework-asset=\"simai.framework.icon_font.ready\"]:not([src])')):[];", $shell);
        self::assertStringContainsString('var inlineStyles=icons?Array.from(', $shell);
        // A variant icon alone no longer sends a variable variant font: the
        // frame's icon runtime asks for that only for axes no catalogue face draws.
        self::assertStringContainsString("if(state.icons['rounded-full']||state.icons['shape-full']){", $shell);
        self::assertStringNotContainsString('if(state.icons.rounded||state.icons.shape){', $shell);
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
        self::assertStringContainsString('framed=document.documentElement.hasAttribute("data-docara-example-frame")', $planner);
        self::assertStringContainsString('if(!style&&framed){fallbackPending=requestFrameStyle("simai.framework.icon_fallback_font.css","full")', $planner);
        self::assertStringContainsString('parent.postMessage({type:"docara:example-icons",icons:[token]},"*")', $planner);
    }

    public function test_a_frame_receives_only_the_catalogue_faces_its_icon_runtime_asks_for(): void
    {
        $root = dirname(__DIR__, 2);
        $shell = (string) file_get_contents($root . '/resources/portable/declarative-shell.js');
        $planner = (string) file_get_contents($root . '/src/Framework/FrameworkAssetPlanner.php');

        // The runtime asks for one face per family, weight and fill it shows.
        self::assertStringContainsString('requestFrameStyle("simai.framework.icon_catalog.css#"+face.id,"face:"+face.id)', $planner);
        // The shell admits only well-formed face tokens.
        self::assertStringContainsString('/^face:((?:outlined|rounded|sharp)-(?:300|400|500)-[01])$/.exec(token)', $shell);
        // The family rules travel with the first icon; each face separately.
        self::assertStringContainsString("inlineStyles.push({key:'simai.framework.icon_catalog.css',content:catalog.replace(catalogFace,'')});", $shell);
        self::assertStringContainsString("inlineStyles.push({key:'simai.framework.icon_catalog.css#'+id,content:rules.join('')});", $shell);
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

    public function test_a_frame_document_starts_in_the_page_language_before_its_scripts_run(): void
    {
        $shell = (string) file_get_contents(dirname(__DIR__, 2) . '/resources/portable/declarative-shell.js');
        self::assertSame(1, preg_match('/\n  function docaraFrameSource\(source\)\{\n.*?\n  \}\n/s', $shell, $match));
        self::assertStringContainsString("responsiveFrame.setAttribute('srcdoc',docaraFrameSource(source));", $shell);
        $node = (new ExecutableFinder)->find('node');
        self::assertIsString($node, 'The shell check runs the function in Node.js.');

        $cases = [
            'russian page' => ['ru', '', '<!doctype html><html><head></head><body></body></html>'],
            'own theme frame' => ['ru', '', '<!doctype html><html data-docara-example-own-theme><head></head><body></body></html>'],
            'right to left page' => ['ar', 'rtl', '<!doctype html><html><head></head><body></body></html>'],
            'frame with a language' => ['ru', '', '<!doctype html><html lang="en"><head></head><body></body></html>'],
            'page without a language' => ['', '', '<!doctype html><html><head></head><body></body></html>'],
            'invalid language' => ['"><script>', '', '<!doctype html><html><head></head><body></body></html>'],
        ];
        $script = 'const out={};const cases=' . json_encode($cases, JSON_THROW_ON_ERROR) . ';'
            . 'for(const [name,[lang,dir,source]] of Object.entries(cases)){globalThis.document={documentElement:{lang,dir}};'
            . $match[0] . 'out[name]=docaraFrameSource(source)}'
            . 'process.stdout.write(JSON.stringify(out));';
        $process = new Process([$node, '-e', $script]);
        $process->mustRun();
        $result = json_decode($process->getOutput(), true, flags: JSON_THROW_ON_ERROR);

        self::assertSame('<!doctype html><html lang="ru"><head></head><body></body></html>', $result['russian page']);
        self::assertSame('<!doctype html><html lang="ru" data-docara-example-own-theme><head></head><body></body></html>', $result['own theme frame']);
        self::assertSame('<!doctype html><html lang="ar" dir="rtl"><head></head><body></body></html>', $result['right to left page']);
        foreach (['frame with a language', 'page without a language', 'invalid language'] as $unchanged) {
            self::assertSame($cases[$unchanged][2], $result[$unchanged], $unchanged);
        }
    }

    public function test_a_frame_takes_the_page_tokens_but_not_the_width_ladder(): void
    {
        $shell = (string) file_get_contents(dirname(__DIR__, 2) . '/resources/portable/declarative-shell.js');
        self::assertSame(1, preg_match('/\n  function collectExampleTokenNames\(rules,names,declared,widthScoped,inWidth\)\{\n.*?\n  function exampleDesignTokens\(\)\{\n.*?\n    return tokens;\n  \}\n/s', $shell, $match));
        $node = (new ExecutableFinder)->find('node');
        self::assertIsString($node, 'The shell check runs the function in Node.js.');

        // A stylesheet like the Framework's: colours for every width, a size
        // ladder switched by a width query, a token built from the ladder,
        // a reader setting on the root element and a measured breakout width.
        $script = <<<'JS'
const style=(declarations)=>{const names=Object.keys(declarations);const s={length:names.length,getPropertyValue:(n)=>declarations[n]||''};names.forEach((n,i)=>{s[i]=n});return s};
const computedValues={'--sf-primary':'#0a6cff','--sf-text--height-1':'1.5rem','--sf-ui-1--control-height':'calc(1.5rem + 2 * 0.5rem)','--sf-radius--ui':'0.5rem','--sf-container-breakout--page':'1440px','--sf-text--size':'1rem','--sf-text--height':'1.5'};
const computed=style(computedValues);
const root={style:style({'--sf-radius--ui':'var(--sf-radius-1)','--sf-container-breakout--page':'1440px'})};
globalThis.document={documentElement:root,styleSheets:[{media:{mediaText:''},cssRules:[
  {style:style({'--sf-primary':'#0a6cff','--sf-text--height-1':'1.25rem','--sf-ui-1--control-height':'calc(var(--sf-text--height-1) + 2 * 0.5rem)','--sf-text--size':'1rem','--sf-text--height':'1.5'})},
  {media:{mediaText:'(min-width: 60rem)'},cssRules:[{style:style({'--sf-text--height-1':'1.5rem'})}]}
]}]};
globalThis.getComputedStyle=()=>computed;
JS;
        $script .= $match[0] . 'process.stdout.write(JSON.stringify(exampleDesignTokens()));';
        $process = new Process([$node, '-e', $script]);
        $process->mustRun();
        $tokens = json_decode($process->getOutput(), true, flags: JSON_THROW_ON_ERROR);

        self::assertSame([
            '--sf-primary' => '#0a6cff',
            '--sf-radius--ui' => 'var(--sf-radius-1)',
            '--sf-text--height' => '1.5',
            '--sf-text--size' => '1rem',
        ], $tokens);
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
        self::assertStringContainsString("frame.style.blockSize=size+'px'", $shell);
        // An open menu's room hangs over what follows instead of pushing the
        // page down: the frame keeps its flow height in the layout, and a clip
        // path shows only the flow box and the menus.
        self::assertStringContainsString('var extra=Number.isFinite(flow)&&boxes.length?Math.max(0,size-Math.max(32,Math.ceil(flow))):0;', $shell);
        self::assertStringContainsString("frame.style.marginBlockEnd=(-extra)+'px';", $shell);
        self::assertStringContainsString("frame.style.clipPath=\"path('\"+path+\"')\"", $shell);
        self::assertStringContainsString('.docara-example-preview[data-docara-example-overlay]{overflow:visible}', (string) file_get_contents(dirname(__DIR__, 2) . '/resources/portable/declarative-shell.css'));
        self::assertStringContainsString("parent.postMessage({type:'docara:example-height',height:height,flow:flow,overlays:overlays},'*')", $renderer);
        self::assertStringContainsString('var reach=layerRect.bottom+12;', $renderer);
        self::assertStringContainsString("try{open=child.matches(':popover-open,dialog[open]')}catch(error){}if(open)return;", $renderer);
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
        // The desktop width never exceeds the dialog, so a narrow screen does not push the frame past its edge.
        self::assertStringContainsString("previewFrame.style.inlineSize=viewport==='desktop'?'min('+exampleViewportWidths[viewport]+'px, 100%)':exampleViewportWidths[viewport]+'px'", $shell);
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
        self::assertStringNotContainsString("window.matchMedia('not all and (min-width: 44rem)').matches", $shell);
        self::assertStringContainsString("setViewport(button.dataset.docaraExampleViewport,true);\n        // Safari", $shell);
        self::assertStringContainsString('if(document.activeElement!==button){button.focus()}', $shell);
        // A fence names its language in its language-* class only; the header keeps it.
        self::assertStringContainsString("/(?:^|\\s)language-([\\w+-]+)/.exec(code.className||'')", $shell);
        self::assertStringContainsString("||(title&&title.textContent.trim())||'Code'", $shell);
        self::assertStringContainsString("return stored==='true';\n    }catch(error){}\n    // A long line scrolls inside its block on every screen", $shell);
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

    public function test_a_frame_asks_for_every_framework_script_at_once_and_keeps_their_order(): void
    {
        $renderer = (string) file_get_contents(dirname(__DIR__, 2) . '/src/PortableSite/PortableMarkdownRenderer.php');
        self::assertSame(1, preg_match('/\nfunction loadFrameworkScripts\(sources\)\{\n.*?\n\}\n/s', $renderer, $match));
        self::assertStringContainsString('loadFrameworkScripts(Array.isArray(data.scripts)?data.scripts:[]);', $renderer);
        // Each script used to wait for the one before it to load.
        self::assertStringNotContainsString('scriptQueue', $renderer);
        $node = (new ExecutableFinder)->find('node');
        self::assertIsString($node, 'The frame runtime check runs the function in Node.js.');

        // A document that records what is appended to its head and when: the
        // run is synchronous, so every script must be in the head before any
        // of them could have loaded.
        $script = <<<'JS'
const head=[];let loads=0;
function element(src,existing){return{src,async:true,attributes:{},listeners:{},existing,setAttribute(name,value){this.attributes[name]=value},addEventListener(type,listener){this.listeners[type]=listener}}}
globalThis.document={
  head:{appendChild(node){head.push(node)}},
  createElement(){return element('',false)},
  querySelectorAll(){return head.filter(node=>'data-docara-example-framework-script' in node.attributes)},
};
const frameworkScriptSources={};
function measureSettled(){loads++}
JS;
        $script .= $match[0] . <<<'JS'
const page=['https://docs.example/_docara/smart-base.js','https://docs.example/_docara/icons.js','https://docs.example/_docara/core.js'];
loadFrameworkScripts(page);
const first=head.map(node=>({src:node.src,async:node.async,marked:'data-docara-example-framework-script' in node.attributes,listens:typeof node.listeners.load==='function'}));
/* A later environment sends the same list and one more script: only the new one is added, after the others. */
loadFrameworkScripts([...page,'https://docs.example/_docara/late.js',42]);
process.stdout.write(JSON.stringify({first,all:head.map(node=>node.src),loadsBeforeAnyEvent:loads}));
JS;
        $process = new Process([$node, '-e', $script]);
        $process->mustRun();
        $result = json_decode($process->getOutput(), true, flags: JSON_THROW_ON_ERROR);

        self::assertSame([
            ['src' => 'https://docs.example/_docara/smart-base.js', 'async' => false, 'marked' => true, 'listens' => true],
            ['src' => 'https://docs.example/_docara/icons.js', 'async' => false, 'marked' => true, 'listens' => true],
            ['src' => 'https://docs.example/_docara/core.js', 'async' => false, 'marked' => true, 'listens' => true],
        ], $result['first'], 'Every script is inserted at once, in the page order, with async=false so it runs in that order.');
        self::assertSame([
            'https://docs.example/_docara/smart-base.js',
            'https://docs.example/_docara/icons.js',
            'https://docs.example/_docara/core.js',
            'https://docs.example/_docara/late.js',
        ], $result['all']);
        self::assertSame(0, $result['loadsBeforeAnyEvent']);
    }

    public function test_a_framed_example_log_posts_events_own_lines_and_console_rows_to_the_page(): void
    {
        $html = (new PortableMarkdownRenderer)->render(
            ":::example {log=\"click sf-button-press\" log_console=true}\n```html\n<button type=\"button\">Go</button>\n```\n```javascript\nconsole.log('ready');\n```\n:::\n",
        );
        self::assertSame(1, preg_match('~data-docara-srcdoc="([^"]*)"~', $html, $frame));
        $document = html_entity_decode($frame[1], ENT_QUOTES | ENT_HTML5);
        self::assertSame(1, preg_match('~<script data-docara-example-log-runtime>(.*?)</script>~s', $document, $runtime));
        $node = (new ExecutableFinder)->find('node');
        self::assertIsString($node, 'The frame log check runs the runtime in Node.js.');

        $script = <<<'JS'
const listeners={},posted=[],printed=[];
globalThis.window=globalThis;
window.addEventListener=(type,listener,capture)=>{listeners[type]={listener,capture}};
globalThis.parent={postMessage(data,origin){posted.push({data,origin})}};
globalThis.console={log(...a){printed.push(['log',a])},info(...a){printed.push(['info',a])},warn(...a){printed.push(['warn',a])},error(...a){printed.push(['error',a])}};
JS;
        $script .= $runtime[1] . <<<'JS'

listeners['sf-button-press'].listener(new CustomEvent('sf-button-press',{detail:{href:'/ru/guide/',target:'x'.repeat(60),count:2,nested:{a:1},extra:true}}));
listeners.click.listener({type:'click',detail:1,target:{nodeType:1,tagName:'BUTTON',textContent:'  Go  '}});
docara.example.log('submit',{name:'plan',value:'pro'});
console.warn('careful',{x:1});
console.error(new Error('broken'));
process.stdout.write(JSON.stringify({captures:Object.values(listeners).map(entry=>entry.capture),posted,printed:printed.map(entry=>entry[0])}));
JS;
        $process = new Process([$node, '-e', $script]);
        $process->mustRun();
        $result = json_decode($process->getOutput(), true, flags: JSON_THROW_ON_ERROR);

        self::assertNotContains(false, $result['captures'], 'Events are heard in the capture phase, before the example can stop them.');
        self::assertSame(['warn', 'error'], $result['printed'], 'The console still prints what the example writes.');
        $rows = array_map(static fn (array $message): array => $message['data'], $result['posted']);
        self::assertSame([
            ['type' => 'docara:example-log', 'tab' => 'events', 'level' => '', 'name' => 'sf-button-press', 'detail' => '{href: "/ru/guide/", target: "' . str_repeat('x', 39) . '…", count: 2, nested: {…}, …}'],
            ['type' => 'docara:example-log', 'tab' => 'events', 'level' => '', 'name' => 'click', 'detail' => '<button> Go'],
            ['type' => 'docara:example-log', 'tab' => 'events', 'level' => '', 'name' => 'submit', 'detail' => '{name: "plan", value: "pro"}'],
            ['type' => 'docara:example-log', 'tab' => 'console', 'level' => 'warn', 'name' => 'warn', 'detail' => 'careful {x: 1}'],
            ['type' => 'docara:example-log', 'tab' => 'console', 'level' => 'error', 'name' => 'error', 'detail' => 'Error: broken'],
        ], $rows);
    }

    public function test_the_page_writes_log_rows_only_from_its_own_example_frames(): void
    {
        $shell = (string) file_get_contents(dirname(__DIR__, 2) . '/resources/portable/declarative-shell.js');

        self::assertStringContainsString("if(!data||data.type!=='docara:example-log')return;\n      var frame=docaraExampleFrames.find(function(candidate){return candidate.contentWindow===event.source})||null;", $shell);
        self::assertStringContainsString("if(!entry||(data.tab!=='events'&&!(data.tab==='console'&&entry.console)))return;", $shell);
        self::assertStringContainsString("if(['','log','info','warn','error'].indexOf(data.level)===-1||typeof data.name!=='string'", $shell);
        // Row text is set as text, never parsed as markup.
        self::assertStringContainsString('cell.textContent=part[1];', $shell);
        self::assertStringNotContainsString('row.innerHTML', $shell);
        // The page console is wrapped only when an inline example asks for it.
        self::assertStringContainsString("var consoleLogs=exampleLogs.filter(function(entry){return entry.inline&&entry.console});\n    if(!consoleLogs.length)return;", $shell);
        self::assertStringContainsString('api.example.log=function(name,detail,target){', $shell);
    }

    public function test_a_deferred_example_frame_gets_an_absolute_base_so_each_relative_image_is_fetched_once(): void
    {
        $shell = (string) file_get_contents(dirname(__DIR__, 2) . '/resources/portable/declarative-shell.js');
        self::assertSame(1, preg_match('/\n    function absoluteBase\(source\)\{\n.*?\n    \}\n/s', $shell, $match));
        self::assertStringContainsString("frame.setAttribute('srcdoc',absoluteBase(docaraFrameSource(source)));", $shell);
        $node = (new ExecutableFinder)->find('node');
        self::assertIsString($node, 'The shell check runs the function in Node.js.');

        $cases = [
            'path base' => ['https://docs.example', '<html><head><title>A</title><base href="/docs/_docara/examples/a/"></head><body><img src="assets/x.jpg"></body></html>'],
            'absolute base' => ['https://docs.example', '<html><head><base href="https://cdn.example/_docara/examples/a/"></head><body></body></html>'],
            'protocol-relative base' => ['https://docs.example', '<html><head><base href="//cdn.example/a/"></head><body></body></html>'],
            'no base in the head' => ['https://docs.example', '<html><head></head><body><base href="/x/"></body></html>'],
            'opaque page origin' => ['null', '<html><head><base href="/docs/_docara/examples/a/"></head><body></body></html>'],
        ];
        $script = $match[0] . 'const cases=' . json_encode($cases, JSON_THROW_ON_ERROR) . ';const out={};'
            . 'for(const [name,[origin,source]] of Object.entries(cases)){globalThis.window={location:{origin}};out[name]=absoluteBase(source)}'
            . 'process.stdout.write(JSON.stringify(out));';
        $process = new Process([$node, '-e', $script]);
        $process->mustRun();
        $result = json_decode($process->getOutput(), true, flags: JSON_THROW_ON_ERROR);

        self::assertSame(
            '<html><head><title>A</title><base href="https://docs.example/docs/_docara/examples/a/"></head><body><img src="assets/x.jpg"></body></html>',
            $result['path base'],
        );
        foreach (['absolute base', 'protocol-relative base', 'no base in the head', 'opaque page origin'] as $unchanged) {
            self::assertSame($cases[$unchanged][1], $result[$unchanged], $unchanged);
        }
    }
}
