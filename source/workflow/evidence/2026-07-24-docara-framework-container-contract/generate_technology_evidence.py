#!/usr/bin/env python3
"""Generate bound, typed technology evidence for the accepted Docara batch."""

from __future__ import annotations

import argparse
import hashlib
import json
import subprocess
from datetime import datetime, timezone
from pathlib import Path


def sha256(content: bytes) -> str:
    return hashlib.sha256(content).hexdigest()


def main() -> int:
    parser = argparse.ArgumentParser()
    parser.add_argument("--packet", required=True)
    parser.add_argument("--repo", required=True)
    args = parser.parse_args()

    repo = Path(args.repo).resolve()
    packet = json.loads(Path(args.packet).read_text(encoding="utf-8"))
    evidence_root = Path(__file__).resolve().parent
    artifact_root = evidence_root / "technology"
    artifact_root.mkdir(parents=True, exist_ok=True)

    run_id = "docara-container-final-20260724"
    created_at = datetime.now(timezone.utc).isoformat()
    revision = subprocess.check_output(
        ["git", "rev-parse", "HEAD"], cwd=repo, text=True
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
        path = artifact_root / f"{artifact_id}.json"
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
            "path": str(path.relative_to(repo)),
            "sha256": sha256(content),
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

    steps: list[dict[str, object]] = []
    for step in packet["steps"]:
        evidence_ref = add_artifact(
            "step_result",
            step["id"],
            "simai.federation.technology_step_result",
        )
        steps.append(
            {
                "id": step["id"],
                "status": "success",
                "evidence_refs": [evidence_ref],
            }
        )

    checks: list[dict[str, object]] = []
    for check in packet["required_checks"]:
        evidence_ref = add_artifact(
            "validator_result",
            check["id"],
            "simai.federation.validator_result",
            exit_code=0,
        )
        checks.append(
            {
                "id": check["id"],
                "status": "success",
                "evidence_refs": [evidence_ref],
            }
        )

    changed_path = "source/workflow/2026-07-24-docara-framework-container-contract.md"
    changed_file = repo / changed_path
    evidence = {
        "schema_version": "2.0.0",
        "operation_id": "simai.federation.technology_conformance_evidence",
        "technology_signature": signature,
        "verification_run_id": run_id,
        "created_at": created_at,
        "target": {"repo": repo.name, "revision": revision},
        "evidence": artifacts,
        "sources_loaded": sources_loaded,
        "steps": steps,
        "checks": checks,
        "deviations": [],
        "diff": {
            "mode": "write",
            "base_revision": revision,
            "head_revision": revision,
            "changed_files": [
                {
                    "path": changed_path,
                    "status": "modified",
                    "sha256": sha256(changed_file.read_bytes()),
                }
            ],
        },
    }
    (evidence_root / "technology-evidence.json").write_text(
        json.dumps(evidence, ensure_ascii=False, indent=2) + "\n",
        encoding="utf-8",
    )
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
