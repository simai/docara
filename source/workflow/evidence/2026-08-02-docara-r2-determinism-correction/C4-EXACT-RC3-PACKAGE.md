# C4 — exact unpublished rc.3 package

Status: `pass`

Two independent `git clone --no-local` checkouts built the package from exact
source `be0ba2db5254e468c7c014016ade02e8b4f3f16c`, planned version
`2.0.0-rc.3` and uncreated tag parameter `v2.0.0-rc.3`.

| Item | Exact result |
| --- | --- |
| ZIP SHA-256 | `630d971e94a1222624304a3a5c2a7791586c0b7866ede5b8f3506c93bdebadc0` |
| external manifest SHA-256 | `0d0c280fc93824d76bafb703a5be8b70cf3cf34128e94ac4bf6906e3648a35af` |
| checksum file SHA-256 | `e567bc57b4cd6d3b75036787a2d3ece846b40e822981c3ff73ec337b745198b3` |
| embedded SBOM SHA-256 | `b685e8334be20141bf656a5d04f2d2b324f9dcaa11adf3face71a6cc52151945` |
| package files | 650 |

ZIP, manifest and checksum files were byte-identical between the two clones.
The repository verifier passed both artifacts, including normalized archive
metadata, path/case/symlink/mode policy, packaged documentation links and
package surface. No tag, release or publication was created.

Rollback: source and artifacts are immutable inputs; the former rc.2 remains
preserved only as `superseded_after_determinism_audit` history.
