# G1C-R2 — runtime ownership and security

Status: PASS

The accepted public path remains one typed IR, one renderer registry, one
`SmartComponentGateway` and one `PageBuilder`. Provider/artifact/lock data owns
namespace admission and template policy. `ui.alert`, `ui.button`, all
`docara.*` shell components and the project-local `fixture.notice` use that
path. A second exact-lock Framework fixture and a project component require no
component-specific engine registration.

Structural scans found no component-ID list or switch in active Goal 1
runtime/search/admission: `SmartRenderer`, `PortableSearchTextExtractor`,
`FrameworkConsumerPolicy` and the available `DocumentParser` path contain no
`ui.alert`, `ui.button`, `docara.*` or `fixture.notice`. The remaining shell
region allowlist in `RegionCompositionResolver` is explicitly Goal 2 Design
Registry debt and is not claimed here.

Focused command selected provider/contract/gateway/template/search/security
suites. Result: 79 tests, 671 assertions, PASS. Contract/provider-only result:
20 tests, 89 assertions, PASS. Full PHPUnit at implementation revision, with
the exact SF5 source checkout, passed 373 tests and 7,239 assertions.

Negative coverage includes reserved namespace, duplicate and namespace
collision, moving ref, traversal/unsafe asset path, symlink directory/template
escape and undeclared or unsafe template selection. No root/path/schema gate
was weakened.
