# Docara performance and responsive images correction

Status: completed and locally verified; release operations remain out of scope.

## Outcome

Address the actionable parts of the external Docara 2.3.0 follow-up without
introducing speculative global budgets or replacing the complete icon font
with the known incomplete Framework runtime subset.

## Baseline

- Ordinary Markdown images had no prose-level maximum inline size.
- Production builds already emitted hash-bound page and Framework receipts,
  but did not expose a compact resource-size view for project owners.
- The preloaded official Material Symbols file is complete but large. The
  smaller historical Framework file is an incomplete subset and is not a safe
  replacement.

## Implemented correction

- Constrain prose images to the content column with intrinsic height and no
  forced upscaling.
- Compile image, figure, media and embed ratios to the real SIMAI Framework
  `aspect-*` utilities. Preserve the existing exact 21:9 contract with a
  package-owned compatibility rule because Framework 5.4.0 does not publish
  that one utility.
- Publish `.docara/performance.json` from final generated HTML after all public
  assets exist.
- Record initial CSS, font and external-script references per page, local
  bytes/hashes, inline CSS/JavaScript, unique local totals and the ten largest
  shared resources.
- Hash-bind the receipt from `resolved-page-plans.json` and recompute it in
  `verify-static`.
- Keep the mechanism report-only. Project owners may apply their own measured
  budgets without turning one product profile into a Docara-wide rule.

## Deferred boundary

Icon transfer reduction requires an authoritative complete icon projection or
subset contract from SIMAI Framework. Docara must not silently use the current
incomplete runtime font, discover glyphs with a lossy heuristic, or make an
external font tool a hidden PHP build dependency.

## Verification

- All 85 PHPUnit test files passed under PHP 8.4.20, including the complete
  `PortableSiteBuilderTest`, `PortableDocumentationSiteTest` and
  `StaticBuildVerifierTest` classes. The file matrix was executed with four
  isolated PHPUnit processes after the sequential run exposed and corrected
  one stale exact-tree count.
- The documentation production build generated 128 source pages. Project
  validation reported only the existing five report-mode editorial reviews
  and no technical errors.
- `verify-static` checked 263 HTML pages and 35,556 local references with no
  broken references. Performance receipt publication, hash binding,
  deterministic recomputation and tamper rejection passed.
- Desktop browser smoke at a 1266 CSS-pixel content viewport confirmed that an
  intrinsic 186 by 186 image remained 186 by 186, while the declared 16:9
  frame rendered at 658 by 370.125 with computed `aspect-ratio: 16 / 9`.
- Mobile browser smoke at a 376 CSS-pixel content viewport confirmed the same
  intrinsic image size, a 320 by 180 framed image and zero document-level
  horizontal overflow.
- Pint, Composer validation and `git diff --check` passed. Composer emitted
  only upstream PHP 8.4 deprecation notices from its PHAR dependencies.

## Boundaries

- No commit, push, tag, release, package publication or public deploy.
- Existing native View Transition planning files are unrelated user work and
  remain untouched.
