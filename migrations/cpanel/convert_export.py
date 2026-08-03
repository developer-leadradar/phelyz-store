"""
Convert the Supabase JSON export into MySQL INSERT statements for cPanel.

Usage:  python convert_export.py <export.json>

Only the catalogue + accounts are carried across (categories, products,
product_images, shipping_rates, users). Test orders, abandoned carts,
page views and expired tokens are intentionally left behind.
"""
import json
import io
import os
import sys

SRC = sys.argv[1] if len(sys.argv) > 1 else os.path.expanduser(
    r"~\Downloads\phelyz-supabase-export.json")
OUT = os.path.join(os.path.dirname(os.path.abspath(__file__)), "02_data.sql")

TABLE_ORDER = ["categories", "products", "product_images", "shipping_rates", "users"]


def esc(v):
    """Quote a Python value as a MySQL literal."""
    if v is None:
        return "NULL"
    if isinstance(v, bool):
        return "1" if v else "0"
    if isinstance(v, (int, float)):
        return str(v)
    s = str(v)
    s = s.replace("\\", "\\\\")
    s = s.replace("'", "\\'")
    s = s.replace("\r", "\\r")
    s = s.replace("\n", "\\n")
    return "'" + s + "'"


def main():
    data = json.load(io.open(SRC, encoding="utf-8"))

    lines = [
        "-- ============================================================",
        "-- Phelyz Store - catalogue + accounts, exported from Supabase",
        "-- Import AFTER database.sql and 01_schema_additions.sql",
        "-- ============================================================",
        "SET FOREIGN_KEY_CHECKS=0;",
        "SET NAMES utf8mb4;",
        "",
    ]

    summary = []
    for tbl in TABLE_ORDER:
        rows = data.get(tbl) or []
        summary.append((tbl, len(rows)))
        if not rows:
            continue
        lines.append("-- %s (%d rows)" % (tbl, len(rows)))
        lines.append("DELETE FROM %s;" % tbl)
        cols = list(rows[0].keys())
        collist = ",".join("`%s`" % c for c in cols)
        for r in rows:
            vals = ",".join(esc(r.get(c)) for c in cols)
            lines.append("INSERT INTO %s (%s) VALUES (%s);" % (tbl, collist, vals))
        lines.append("")

    lines.append("SET FOREIGN_KEY_CHECKS=1;")
    lines.append("SELECT 'Catalogue + accounts imported.' AS message;")

    io.open(OUT, "w", encoding="utf-8").write("\n".join(lines))

    print("wrote %s (%.1f KB)" % (OUT, os.path.getsize(OUT) / 1024.0))
    for tbl, n in summary:
        print("   %-16s %d rows" % (tbl, n))


if __name__ == "__main__":
    main()
