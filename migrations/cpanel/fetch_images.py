"""
Download every product image that currently lives on Supabase Storage into
uploads/products/, and emit SQL that repoints the database at the local copies.

Those images disappear the moment the Supabase project is dropped, so they have
to come across before the free plan is torn down.

Usage:  python fetch_images.py <export.json>
Writes: uploads/products/*  and  03_image_paths.sql
"""
import io
import json
import os
import sys
import urllib.request

HERE = os.path.dirname(os.path.abspath(__file__))
ROOT = os.path.abspath(os.path.join(HERE, "..", ".."))
SRC = sys.argv[1] if len(sys.argv) > 1 else os.path.expanduser(
    r"~\Downloads\phelyz-supabase-export.json")
DEST_DIR = os.path.join(ROOT, "uploads", "products")
OUT_SQL = os.path.join(HERE, "03_image_paths.sql")


def is_supabase(url):
    return isinstance(url, str) and url.startswith("http") and "supabase" in url


def fetch(url, dest):
    req = urllib.request.Request(url, headers={"User-Agent": "phelyz-migration/1.0"})
    with urllib.request.urlopen(req, timeout=60) as r:
        data = r.read()
    with open(dest, "wb") as f:
        f.write(data)
    return len(data)


def main():
    data = json.load(io.open(SRC, encoding="utf-8"))
    os.makedirs(DEST_DIR, exist_ok=True)

    updates = []
    ok = failed = skipped = 0

    # products.image
    for p in data.get("products") or []:
        url = p.get("image")
        if not is_supabase(url):
            skipped += 1
            continue
        fname = url.split("/")[-1].split("?")[0]
        dest = os.path.join(DEST_DIR, fname)
        try:
            size = fetch(url, dest)
            local = "uploads/products/" + fname
            updates.append("UPDATE products SET image='%s' WHERE id=%d;" % (local, p["id"]))
            print("  OK   product %-3s %-42s %6.1f KB" % (p["id"], fname[:42], size / 1024.0))
            ok += 1
        except Exception as e:
            print("  FAIL product %-3s %s -> %s" % (p["id"], fname[:42], e))
            failed += 1

    # product_images.image_path
    for im in data.get("product_images") or []:
        url = im.get("image_path")
        if not is_supabase(url):
            skipped += 1
            continue
        fname = url.split("/")[-1].split("?")[0]
        dest = os.path.join(DEST_DIR, fname)
        try:
            size = fetch(url, dest)
            local = "uploads/products/" + fname
            updates.append("UPDATE product_images SET image_path='%s' WHERE id=%d;" % (local, im["id"]))
            print("  OK   gallery %-3s %-42s %6.1f KB" % (im["id"], fname[:42], size / 1024.0))
            ok += 1
        except Exception as e:
            print("  FAIL gallery %-3s %s -> %s" % (im["id"], fname[:42], e))
            failed += 1

    header = [
        "-- ============================================================",
        "-- Repoint image columns from Supabase Storage to local uploads/",
        "-- Run AFTER 02_data.sql. Files live in uploads/products/.",
        "-- ============================================================",
    ]
    io.open(OUT_SQL, "w", encoding="utf-8").write(
        "\n".join(header + updates + ["SELECT 'Image paths repointed to local uploads.' AS message;"])
    )

    print("\ndownloaded=%d  failed=%d  not-on-supabase=%d" % (ok, failed, skipped))
    print("wrote %s (%d statements)" % (OUT_SQL, len(updates)))


if __name__ == "__main__":
    main()
