# Docara development

The [developer and AI agent path](/en/development/developer-sdk/#step-by-step)
goes step by step: find a capability, make a plan, preview the result, check
the change without writing (`dry-run`), apply it by the plan hash and check
it. All steps use the same internal Docara services.

This section is for those who develop the engine itself. The author of an
ordinary site does not need to change the theme: to build, they need only PHP,
Composer and Node.js 20+ from the [quick start](/en/start/quick-start/).

## Guides

- [Getting started with development](/en/development/getting-started/)
- [Architecture](/en/development/architecture/)
- [Interface composition](/en/development/composition/) — including your own project layouts and blocks
- [Smart components](/en/development/smart-components/)
- [SDK for developers and AI agents](/en/development/developer-sdk/)
- [Testing](/en/development/testing/)
- [Examples for developers](/en/development/examples/)

## How to extend capabilities

Start with the simplest extension level: plain Markdown, then a typed Docara
component, then a Smart component from the pinned SIMAI Framework version. If
there is no executable contract yet, add a requirement record with an explicit
safe fallback. A change must not bypass the schema, the component description
(manifest), the exact revision, negative tests and the browser check.

Start each extension with the simplest building block that solves the task.
Do not start with a Smart component just because it exists in the Framework.

### 1. Check plain Markdown

Choose plain Markdown if a heading, text, a link, a list, a quote, a code
block, an image or a small table solves the task.

You can change the set of plain Markdown capabilities only together with
parsing and rendering tests, documentation and a fixture. Do not allow raw
HTML as a workaround for a missing capability.

### 2. Add a typed Docara component

A typed component suits a repeated semantic block that PHP always renders the
same way, for example `card`, `steps`, `cta`, `features` or `columns`.

1. Add one description to `resources/component-catalog/typed/`.
2. Pin the new renderer identifier with a version number.
3. Describe the narrow author syntax and the constraints.
4. Add a fixture and renderer tests: for a valid call and for rejecting an
   invalid one.
5. Add one Markdown page for the component and check it with the same
   PageBuilder.

The renderer may use only checked SIMAI Framework utilities and components:
it does not create a separate design system.

### 3. Admit a Smart component

Admitting a Smart component is needed when you need an exact SIMAI Framework
component and its browser behaviour.

1. Get a manifest from the component owner with the parameters, states and an
   immutable revision.
2. Pin the provider and source repository revisions, the assets and their
   SHA-256 in the Framework lock.
3. Add only narrowing Docara metadata.
4. Check the parameters, constraints, dependency completeness, themes,
   keyboard use, responsiveness and accessibility.
5. Add a separate Markdown page and a fixture. Do not move public text into
   the manifest, the settings or `lang.json`.

Docara metadata cannot widen the manifest or raise the component's readiness
by itself. The detailed admission conditions are in
[“A new SIMAI Framework component”](/en/development/smart-components/#a-new-simai-framework-component).

### 4. Record the need

If the renderer, manifest, assets or acceptance are not ready yet, create a
non-executable record in `resources/component-catalog/requirements/`.

It must name:

- the owner;
- the reason it is unavailable;
- a safe fallback;
- a checkable admission condition;
- an honest status: `admission_pending`, `framework_gap` or `deferred`.

Such a record makes the missing capability visible but does not allow calling
it.

### How to declare a capability ready

A requirement record cannot be switched to `supported` in place.

1. Delete the requirement record.
2. Add the owner's executable record: to the plain Markdown set, to the typed
   component descriptions or to the Smart component metadata with admission by
   the pinned version.
3. Add a renderer, tests, documentation and a fixture.
4. Check the page in Markdown, the machine-readable catalogue, the build check
   and the browsers.
5. Claim the status of this capability only.

The readiness of one record does not mean that Docara is ready for release or
that the other components are ready.
