# B0 combined baseline verification

Verified candidate: `2e8f463294b9d18d221e10adb70e9db709c653b1`.

## Integrated history

- local baseline: `9b1290bf547a8c87651704a9554be0acc881aebf`;
- remote Windows path-safety input:
  `a913dce60b7246d24f9afaf6e9f259a5b7c097e0`;
- merge commit: `d5dc300`;
- combined green candidate: `2e8f463294b9d18d221e10adb70e9db709c653b1`.

The two conflicts were resolved by retaining the current locale URL projector
and Smart validation while adding the shared cross-platform FilesystemPath
boundary checks.

## Correction

The asset-plan failure was a stale semantic assertion, not serialization
drift. `component_runtime.asset_plan` was already a structured array. Search
now intentionally uses `sf-button` in the page shell, so button runtime assets
exist on every searchable page. The corrected test independently checks:

- shared shell assets;
- page content `normalized_calls`;
- alert runtime isolation;
- absence of unrelated content calls.

The fresh-checkout template-mirror fixture and its two no-vendor scripts were
also updated to load the new FilesystemPath dependency. Candidate installation
instructions no longer embed an obsolete commit; they require recording the
SHA from the exact local checkout.

## Results

- PHPUnit: PASS, 627 tests and 5,661 assertions.
- Pint: PASS.
- Composer validate strict: PASS; the old bundled Composer emits PHP 8.4
  deprecation notices, but the package contract is valid.
- PHP syntax: PASS, 389 PHP/Blade files checked.
- Documentation build: PASS.
- Static verifier: PASS, 271 HTML pages, 20,512 local references, zero broken.
- Repeated documentation builds: PASS, identical aggregate digest
  `c250677b25de3c15d87f30ce0d6dcded9611d87531ae1871120b83f4ba6badf9`.
- Git branch divergence: closed; local branch contains the remote-only commit.

## Verdict

B0 PASS. This accepts only the combined baseline. It does not accept the
portable-only product boundary, legacy removal, package distribution or
release readiness.
