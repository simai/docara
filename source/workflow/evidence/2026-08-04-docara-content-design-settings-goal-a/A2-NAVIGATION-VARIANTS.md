# A2 — navigation variants

Date: 2026-08-04
Status: `PASS`
Implementation commits: `01deb3b620a1a73f3be0443bab3adc79bc51c371`,
`d2ea745b6f3b0c6df487abafc87e15b81fdaf020`
Exact Goal A product candidate: `8c04160ab50549b060fb933cf80f86193cd92113`

## Outcome

One descriptor `docara.navigation` admits `header`, `tree` and `compact`.
The configured call is resolved by one `BindingRegistry`, then the accepted
SmartComponentGateway, renderer, LayoutComposer and PageBuilder. The compiler
and preview do not dispatch on the component ID. The preview locator is derived
from the exact production DOM node and includes region and selected View where
needed.

`PreviewKernelTest::navigation_presentations_keep_preview_bound_to_the_production_page`
proves that each variant's region fragment is byte-identical to the fragment
extracted from its production page and that the dependency closure contains the
same package Smart artifact.

## Browser matrix

The repository evidence under `browser/{header,tree,compact}/` contains the
finalized plan, sealed reference manifest, report and eight candidate
screenshots per variant. Each variant covers desktop 1440x900 and mobile
390x844, light/dark and LTR/RTL.

| Variant | Plan ID | Reference ID | Artifact SHA-256 | Report SHA-256 | Result |
| --- | --- | --- | --- | --- | --- |
| header | `3ee9c42765e1b7d0e7d65e42f051314ff3a6d627bf1ded3ece252d0518e33726` | `3e29e1071fcee0070bf54f254e76bfc63fe3a576389c236d75f3fe8410934a63` | `06c9f6b213b4579a5c75f9337410b64ee9ba7891223a673dd33416c56bd145de` | `0a835c4a804ffb4852563c8ae434f2734335cdad97aaf8d2748a36a0f2471973` | 8/8 PASS |
| tree | `2554ffc093d506c1a9999c754baf2e6ab805cde0422d65b16719b1e86b34328b` | `60f991cc1e971df6ca3b3bf0a99b5b6104c95219702b3468a6fbf89a3b843624` | `daf55f35926c7636b2626503bca24b2ce828e60e653051b35864a7f1972e3085` | `be513301d361aa31c33874e7c24f2d04d6bbc6c40eef948ddcb044aeff2558df` | 8/8 PASS |
| compact | `ce35579f5f6d241a5ca6c223ef20afe7808cf711016ebae36d0722604ef1f946` | `18977cbc6428c003d1b50a6c75fc8d59dad8eb6fec59c743f070bb1083a679b1` | `f9b8cc756e440996b0720a265306078e6bfff3c2af5ae670d79ef1cc74d06534` | `e22df151df932fcb6155fd7d6a900a0bfe6c62dbf112120472b89ddcee95488a` | 8/8 PASS |

All 24 scenarios report local assets 200, a11y=0, console errors=0, console
warnings=0, overflow=0, keyboard/focus/Esc PASS, reduced motion PASS and visual
diff pixels=0. `docara qa --verify=<plan> --json` independently accepted all
three finalized plans with eight scenarios each.

The browser runner is development evidence only. Normal build and consumer
runtime remain PHP-only and Node-free.

## Rollback

Revert the two implementation commits. The default package Section artifacts
still select their original presentations, so no project content migration is
required.
