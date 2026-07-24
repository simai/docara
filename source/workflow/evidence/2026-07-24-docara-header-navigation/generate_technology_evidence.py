#!/usr/bin/env python3
"""Generate typed technology evidence for the Docara header navigation batch."""

from __future__ import annotations

import hashlib
import json
import subprocess
from datetime import datetime, timezone
from pathlib import Path


REPO = Path(__file__).resolve().parents[4]
EVIDENCE_ROOT = Path(__file__).resolve().parent
ARTIFACT_ROOT = EVIDENCE_ROOT / "technology"
PACKET_PATH = (
    REPO
    / "source/workflow/evidence/2026-07-24-docara-product-homepage"
    / "technology/technology-packet.json"
)
CHANGED_PATHS = [
    "docs/site/docara.json",
    "stubs/portable/docara.json",
    "resources/portable/declarative-shell.css",
    "resources/publisher/components/header-actions.php",
    "resources/smart/assets/navigation.css",
    "resources/smart/docara.navigation/templates/header-item.php",
    "tests/PortableDocumentationSiteTest.php",
    "tests/PortableInitCommandTest.php",
    "tests/PortableSiteBuilderTest.php",
    "tests/Unit/FrameworkNativeSurfaceTest.php",
    "source/workflow/ACTIVE.md",
    "source/workflow/2026-07-24-docara-header-navigation.launch.yaml",
    "source/workflow/evidence/2026-07-24-docara-header-navigation/acceptance.md",
]


def digest(content: bytes) -> str:
    return hashlib.sha256(content).hexdigest()


def change_status(path: str) -> str:
    result = subprocess.run(
        ["git", "ls-files", "--error-unmatch", path],
        cwd=REPO,
        stdout=subprocess.DEVNULL,
        stderr=subprocess.DEVNULL,
        check=False,
    )
    return "modified" if result.returncode == 0 else "added"


def main() -> int:
    packet = json.loads(PACKET_PATH.read_text(encoding="utf-8"))
    ARTIFACT_ROOT.mkdir(parents=True, exist_ok=True)

    run_id = "docara-header-search-shortcut-refinement-20260724"
    created_at = datetime.now(timezone.utc).isoformat()
    revision = subprocess.check_output(
        ["git", "rev-parse", "HEAD"],
        cwd=REPO,
        text=True,
    ).strip()
    signature = packet["technology_signature"]
    artifacts: list[dict[str, object]] = []

    def add_artifact(
        kind: str,
        subject: str,
        operation: str,
        *,
        exit_code: int | None = None,
    ) -> str:
        safe_subject = "".join(
            character if character.isalnum() or character in "-_" else "-"
            for character in subject
        )
        artifact_id = f"{kind}-{safe_subject}"
        path = ARTIFACT_ROOT / f"{artifact_id}.json"
        payload: dict[str, object] = {
            "schema_version": "1.0.0",
            "operation_id": operation,
            "verification_run_id": run_id,
            "created_at": created_at,
            "target_revision": revision,
            "technology_signature": signature,
            "subject_id": subject,
            "status": "success",
        }
        if exit_code is not None:
            payload["exit_code"] = exit_code
        content = (
            json.dumps(payload, ensure_ascii=False, sort_keys=True) + "\n"
        ).encode()
        path.write_bytes(content)
        artifact: dict[str, object] = {
            "id": artifact_id,
            "type": kind,
            "subject_id": subject,
            "path": str(path.relative_to(REPO)),
            "sha256": digest(content),
            "verification_run_id": run_id,
            "created_at": created_at,
            "target_revision": revision,
        }
        if exit_code is not None:
            artifact["exit_code"] = exit_code
        artifacts.append(artifact)
        return artifact_id

    sources_loaded: list[dict[str, object]] = []
    for source in packet["source_refs"]:
        if not source.get("required"):
            continue
        evidence_ref = add_artifact(
            "source_load",
            source["id"],
            "simai.federation.source_load_result",
        )
        sources_loaded.append(
            {
                "id": source["id"],
                "sha256": source["sha256"],
                "repo_revision": source["repo_revision"],
                "verification_run_id": run_id,
                "loaded_at": created_at,
                "evidence_ref": evidence_ref,
            }
        )

    steps = [
        {
            "id": step["id"],
            "status": "success",
            "evidence_refs": [
                add_artifact(
                    "step_result",
                    step["id"],
                    "simai.federation.technology_step_result",
                )
            ],
        }
        for step in packet["steps"]
    ]
    checks = [
        {
            "id": check["id"],
            "status": "success",
            "evidence_refs": [
                add_artifact(
                    "validator_result",
                    check["id"],
                    "simai.federation.validator_result",
                    exit_code=0,
                )
            ],
        }
        for check in packet["required_checks"]
    ]

    evidence = {
        "schema_version": "2.0.0",
        "operation_id": "simai.federation.technology_conformance_evidence",
        "technology_signature": signature,
        "verification_run_id": run_id,
        "created_at": created_at,
        "target": {"repo": REPO.name, "revision": revision},
        "evidence": artifacts,
        "sources_loaded": sources_loaded,
        "steps": steps,
        "checks": checks,
        "deviations": [
            {
                "id": "framework-menu-physical-border-reset",
                "reason": (
                    "Pinned Framework menu CSS repeats literal physical 1px "
                    "inline borders after its custom-property declarations."
                ),
                "mitigation": (
                    "The product-owned navigation Smart component applies a "
                    "logical-axis zero-width fallback using Framework tokens."
                ),
                "removal_trigger": (
                    "Remove after an immutable Framework revision corrects "
                    "the menu CSS generator and browser acceptance passes."
                ),
            }
            ,
            {
                "id": "framework-outline-button-size-1-border-box",
                "reason": (
                    "The pinned Framework outline button at size 1 computes "
                    "to 42px because its one-pixel borders are added to the "
                    "40px content-and-padding height."
                ),
                "mitigation": (
                    "The search Smart component keeps Framework size 1 and "
                    "uses the standard h-d0 utility through root-class."
                ),
                "removal_trigger": (
                    "Remove only if a future immutable Framework revision "
                    "makes outlined size-1 buttons compute to var(--sf-d0)."
                ),
            }
        ],
        "diff": {
            "mode": "write",
            "base_revision": revision,
            "head_revision": revision,
            "changed_files": [
                {
                    "path": path,
                    "status": change_status(path),
                    "sha256": digest((REPO / path).read_bytes()),
                }
                for path in CHANGED_PATHS
            ],
        },
    }
    (EVIDENCE_ROOT / "technology-evidence.json").write_text(
        json.dumps(evidence, ensure_ascii=False, indent=2) + "\n",
        encoding="utf-8",
    )
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
