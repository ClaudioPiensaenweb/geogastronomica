"""
Genera geogastronomica.zip para distribucion.
Usa forward-slashes en las rutas internas para compatibilidad Linux/Plesk.
"""

import zipfile, os, pathlib

ROOT = pathlib.Path(__file__).resolve().parent
OUT  = ROOT / "geogastronomica.zip"
PREFIX = "geogastronomica/"

EXCLUDE_DIRS  = {".git", ".github", ".planning", "__pycache__", "node_modules"}
EXCLUDE_FILES = {
    "build-zip.py", "CLAUDE.md", ".gitignore", ".gitattributes",
    "geogastronomica-1.5.0.zip", "geogastronomica.zip",
}
EXCLUDE_EXTS = {".pyc"}

def should_include(rel: pathlib.PurePosixPath) -> bool:
    parts = rel.parts
    if any(p in EXCLUDE_DIRS for p in parts):
        return False
    if parts[-1] in EXCLUDE_FILES:
        return False
    if rel.suffix in EXCLUDE_EXTS:
        return False
    return True

with zipfile.ZipFile(OUT, "w", zipfile.ZIP_DEFLATED) as zf:
    for path in sorted(ROOT.rglob("*")):
        if path.is_dir():
            continue
        rel = path.relative_to(ROOT)
        posix = pathlib.PurePosixPath(rel)
        if not should_include(posix):
            continue
        arcname = PREFIX + str(posix)
        zf.write(path, arcname)
        print(f"  + {arcname}")

print(f"\nOK {OUT.name} ({OUT.stat().st_size // 1024} KB)")
