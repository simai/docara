<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

/**
 * The sidebar keeps its scroll across pages of the site, and the current item
 * is brought into view: the inline script after the sidebar restores before
 * the first paint, the shell saves the position and handles the mobile drawer.
 */
final class NavigationScrollMemoryTest extends TestCase
{
    private const VIEWPORT = 'aside[data-docara-region="sidebar"]>.docara-sidebar-panel>.docara-sidebar-scroll>.sf-scrollbar__viewport';

    #[Test]
    public function the_page_restores_the_sidebar_right_after_it_before_the_shell_runs(): void
    {
        $template = $this->file('resources/publisher/templates/page.php');
        $script = $this->inlineScript();

        $aside = strpos($template, '        </aside>' . "\n<?php /* Before the first paint");
        self::assertIsInt($aside, 'The restore script follows the sidebar, so it runs as soon as the sidebar exists.');
        self::assertLessThan(strpos($template, 'docara-reading-column'), $aside);
        foreach ([
            "document.querySelector('" . self::VIEWPORT . "')",
            'new URL(document.referrer).origin===location.origin',
            "sessionStorage.getItem('docara.navigation.scroll')",
            '}catch(e){}',
            "v.querySelector('[aria-current=\"page\"]')",
        ] as $needle) {
            self::assertStringContainsString($needle, $script);
        }
        // Only the sidebar box moves, at once: no scrollIntoView, which would
        // scroll the page too, and no smooth scrolling.
        self::assertStringNotContainsString('scrollIntoView', $script);
        self::assertStringNotContainsString('smooth', $script);
    }

    #[Test]
    public function the_shell_saves_the_position_and_restores_the_drawer_when_it_opens(): void
    {
        $shell = $this->file('resources/portable/declarative-shell.js');
        foreach ([
            "var key='docara.navigation.scroll';",
            "document.querySelector('" . self::VIEWPORT . "')",
            'try{sessionStorage.setItem(key,JSON.stringify(state))}catch(error){}',
            "try{stored=JSON.parse(sessionStorage.getItem(key)||'null')}catch(error){}",
            "window.addEventListener('pagehide',save);",
            // Capture phase: the drawer is still open when its link is clicked.
            "if(link&&((sidebar&&sidebar.contains(link))||(drawer&&drawer.contains(link))))save();\n    },true);",
            "function closeSheet(){\n      navigationScroll.keepDrawer();",
            'navigationScroll.openDrawer(dialog);',
            "navigationScroll.reveal(dialog.querySelector('.docara-mobile-sheet-content'),target);",
            'target.focus({preventScroll:true});',
        ] as $needle) {
            self::assertStringContainsString($needle, $shell);
        }
        self::assertStringNotContainsString('scrollIntoView', $shell);

        $navigation = $this->file('resources/smart/assets/navigation.js');
        self::assertStringContainsString("var rail=document.querySelector('" . self::VIEWPORT . "')", $navigation);
        self::assertStringNotContainsString("var rail=document.querySelector('.docara-sidebar')", $navigation);
    }

    #[Test]
    public function the_inline_script_restores_within_the_site_and_centres_a_hidden_current_item(): void
    {
        $node = getenv('DOCARA_NODE_BINARY') ?: (new ExecutableFinder)->find('node');
        if (! is_string($node)) {
            self::markTestSkipped('Node.js runs the sidebar script.');
        }

        $harness = <<<'JS'
const script = process.argv[1];
function run(referrer, stored, currentTop) {
  const viewport = {
    scrollTop: 0,
    style: { scrollBehavior: '' },
    getBoundingClientRect() { return { top: 100, bottom: 700, height: 600 }; },
    querySelector() {
      const top = currentTop - viewport.scrollTop;
      return { getBoundingClientRect: () => ({ top, bottom: top + 40, height: 40 }) };
    },
  };
  global.document = { referrer, querySelector: () => viewport };
  global.location = { origin: 'https://docs.example' };
  global.sessionStorage = { getItem: () => (stored === undefined ? null : stored) };
  new Function(script)();
  return viewport.scrollTop;
}
console.log(JSON.stringify({
  sameSite: run('https://docs.example/a/', '{"sidebar":420,"drawer":0}', 600),
  sameSiteCurrentHidden: run('https://docs.example/a/', '{"sidebar":420,"drawer":0}', 1500),
  direct: run('', '{"sidebar":420,"drawer":0}', 600),
  directCurrentHidden: run('', '{"sidebar":420,"drawer":0}', 1500),
  otherSite: run('https://elsewhere.example/', '{"sidebar":420}', 600),
  brokenStorage: run('https://docs.example/a/', '{', 600),
}));
JS;
        $process = new Process([$node, '-e', $harness, $this->inlineScript()]);
        $process->mustRun();

        self::assertSame([
            // Kept where the reader left it; the current item is in view.
            'sameSite' => 420,
            // Kept, then the current item (1500 - 420 = 1080 from the top) centred.
            'sameSiteCurrentHidden' => 1500 - 100 - 280,
            // Opened directly: the current item is already visible.
            'direct' => 0,
            'directCurrentHidden' => 1500 - 100 - 280,
            'otherSite' => 0,
            'brokenStorage' => 0,
        ], json_decode($process->getOutput(), true, flags: JSON_THROW_ON_ERROR));
    }

    private function inlineScript(): string
    {
        self::assertSame(1, preg_match(
            '~<script data-docara-sidebar-scroll>(.*?)</script>~s',
            $this->file('resources/publisher/templates/page.php'),
            $match,
        ));

        return $match[1];
    }

    private function file(string $path): string
    {
        $contents = file_get_contents(dirname(__DIR__, 2) . '/' . $path);
        self::assertIsString($contents);

        return $contents;
    }
}
