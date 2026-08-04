# B4 — accepted form wave and exact `ui.list-item` gate

Date: 2026-08-04
Docara input HEAD: `3c657ed540f65880b3e0f1e4fb7972bd546940c9`
Docara frozen partial product candidate: `ccb076a89535954022ca89eb70b84d6c81d80de3`
Outcome: `blocked_unaccepted_ui_list_item`

## Accepted immutable owner input

The independent owner audit accepted the unchanged Framework form wave with
`PASS_WITH_NOTES`:

| Field | Exact value |
| --- | --- |
| owner repository | `simai/bx-simai.main` |
| product candidate | `7e0b87187ceb1f89fad730094bcc4aada3e4f3f2` |
| owner handoff HEAD | `8d7b986d92ee04f2213d48c00fa3669c8d2f0a78` |
| host adapter parent | `b3cdff87563ff78e7eddf044048a4b298fc69036` |
| source | `ui-loader@f615583112c16d05ba75dbc7e0f99eadd4c4d9d9` |
| builder | `ui-builder@367b3423f9707b850c6bef9476ab8d1ed44039e1` |
| packet content SHA-256 | `83551f972ad0b1a6e2037f61583769e32a4a78081e01ed0a0fe888b1187baca1` |
| builder tree SHA-256 | `e595b910e991ef126355ac9e907e0438ccf8686102466713122708354e8246c8` |

The packet contains exactly `ui.input`, `ui.dropdown` and `ui.checkbox`.
Independent canonical JSON recomputation matched the recorded packet content
hash. The unchanged artifact tree hashes also matched:

| ID | Artifact tree SHA-256 |
| --- | --- |
| `ui.input` | `412ad74b135d1fd7b4bb9fd60b928552a9dc5241a1c904ba47d2d44a5bf40fd6` |
| `ui.dropdown` | `2b148478ffd73e5e1f1162249ed2d7d0a10e11e16545bf99dfba41501c4e2131` |
| `ui.checkbox` | `cb3f0a565993743a58c5c4d3c09f6d6b998d7b7b11af0793996dee44a0748a11` |

The owner cross-host proof records byte/semantic equality for HTML, assets and
hydration with warnings `[]` and empty render stderr. The exact builder command
is builder-owned and must be invoked as:

```bash
node "$BUILDER/scripts/run-smart-product-build.cjs" \
  "$BUILDER" "$SOURCE/src" "$FRESH_OUTPUT" "$DIAGNOSTICS"
```

This corrects the ambiguous relative command in the immutable packet's
reproduction prose without mutating the accepted packet bytes.

## Exact blocker

The accepted `ui.dropdown` manifest declares only this options contract:

```json
{
  "options": {
    "accepts": ["ui.list-item"],
    "required": false,
    "multiple": true
  }
}
```

The accepted packet has no `ui.list-item` artifact. The current Docara Smart
registry also has no `ui.list-item` definition. Therefore a populated, useful
dropdown cannot pass registry/admission/slot validation from immutable accepted
inputs.

The prohibited alternatives are not equivalent:

- inventing an `items` prop would fork the accepted ABI;
- injecting raw option markup would bypass admitted Smart ownership;
- registering an empty dropdown as supported would overstate useful behavior;
- implementing `ui.list-item` inside Docara would take ownership away from the
  Framework owner without independent acceptance.

This is the explicit Goal B stop condition. No Framework packet bytes were
imported and no partial runtime integration was committed, so the frozen B0-B3
product candidate remains unchanged and rollback is simply this governance
commit's parent.

## Reproduction

```bash
export OWNER_PACKET=/Users/rim/Git/.worktrees/bx-simai-main-docara-goalb-form-wave/docs/developer/specifications/examples/portable-smart-form-wave

# Verify the packet content-address and its exact three component IDs.
python3 - <<'PY'
import hashlib, json, os
from pathlib import Path
p = Path(os.environ["OWNER_PACKET"]) / "packet.json"
value = json.loads(p.read_text())
expected = value.pop("packet_content_sha256")
actual = hashlib.sha256(json.dumps(
    value, ensure_ascii=False, separators=(",", ":"), sort_keys=True
).encode()).hexdigest()
assert actual == expected
assert [row["id"] for row in value["components"]] == [
    "ui.checkbox", "ui.dropdown", "ui.input"
]
PY

# Verify exact owner artifact trees.
find "$OWNER_PACKET/artifacts/ui.input" -type f -print0 | sort -z \
  | xargs -0 sha256sum | sed "s#  $OWNER_PACKET/artifacts/ui.input/#  #" | sha256sum
find "$OWNER_PACKET/artifacts/ui.dropdown" -type f -print0 | sort -z \
  | xargs -0 sha256sum | sed "s#  $OWNER_PACKET/artifacts/ui.dropdown/#  #" | sha256sum
find "$OWNER_PACKET/artifacts/ui.checkbox" -type f -print0 | sort -z \
  | xargs -0 sha256sum | sed "s#  $OWNER_PACKET/artifacts/ui.checkbox/#  #" | sha256sum

# Confirm the current admitted registry does not own the required dependency.
/Applications/ServBay/package/php/8.4/8.4.20/bin/php -r '
require "vendor/autoload.php";
$keys = Simai\Docara\Smart\SmartRegistry::bundled()->keys();
if (in_array("ui.list-item", $keys, true)) { exit(1); }
echo "ui.list-item absent\n";
'
```

## Resume condition

Resume B4 only from an independently accepted immutable `ui.list-item` owner
artifact/packet containing manifest, view, preset, template, slot, assets and
hydration hashes plus a green unchanged-artifact cross-host proof with
warnings `[]` and empty render stderr. Goal C remains unauthorized.

## Governance-only verification

No `src/`, `resources/`, `stubs/` or public content file changed. The accepted
B0-B3 product/runtime candidate and its build/package/browser artifacts were
not rebuilt or relabelled.

- full PHPUnit on ServBay PHP 8.4.20: 460 tests / 8,544 assertions, PASS;
- focused project-context/documentation/Atlas/provider suite: 37 tests /
  1,991 assertions, PASS;
- Pint `--test`: PASS;
- Composer `validate --strict`: PASS; only tool-owned PHP 8.4 deprecation
  notices were emitted;
- tracked JSON and YAML parse: PASS;
- `php scripts/project-context.php generate` followed by `check`: PASS,
  issues `[]`;
- `git diff --check`: PASS.
