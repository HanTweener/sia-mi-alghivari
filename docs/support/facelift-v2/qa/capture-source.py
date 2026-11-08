"""Capture reviewed prototype source identity without dependency/build trees."""

from datetime import datetime, timezone
import hashlib
import json
from pathlib import Path

qa_dir = Path(__file__).resolve().parent
prototype = qa_dir.parent / "prototype"
source_files = sorted((prototype / "src").rglob("*"))
source_files += sorted(path for path in prototype.iterdir() if path.is_file())
records = [
    {
        "path": path.relative_to(prototype).as_posix(),
        "sha256": hashlib.sha256(path.read_bytes()).hexdigest(),
    }
    for path in source_files
    if path.is_file()
]
record = {
    "captured_at_utc": datetime.now(timezone.utc).isoformat(),
    "revision": "No Git repository; SHA-256 identifies inspected source files",
    "files": records,
    "built_files": [
        {
            "path": path.relative_to(prototype / "dist").as_posix(),
            "sha256": hashlib.sha256(path.read_bytes()).hexdigest(),
        }
        for path in sorted((prototype / "dist").rglob("*"))
        if path.is_file()
    ],
}
(qa_dir / "evidence" / "source-sha256.json").write_text(
    json.dumps(record, indent=2) + "\n", encoding="utf-8"
)
print(f"Captured {len(records)} prototype source/config/document hashes.")
