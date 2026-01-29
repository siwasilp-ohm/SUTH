import json
import zipfile
import xml.etree.ElementTree as ET
from pathlib import Path

NS = {"main": "http://schemas.openxmlformats.org/spreadsheetml/2006/main"}


def load_shared_strings(z):
    if "xl/sharedStrings.xml" not in z.namelist():
        return []
    root = ET.fromstring(z.read("xl/sharedStrings.xml"))
    shared = []
    for si in root.findall("main:si", NS):
        text = "".join(t.text or "" for t in si.findall(".//main:t", NS))
        shared.append(text)
    return shared


def cell_value(cell, shared):
    v = cell.find("main:v", NS)
    if v is None:
        return ""
    if cell.get("t") == "s":
        return shared[int(v.text)] if v.text else ""
    return v.text or ""


def parse_sheet(path: Path):
    with zipfile.ZipFile(path) as z:
        shared = load_shared_strings(z)
        root = ET.fromstring(z.read("xl/worksheets/sheet1.xml"))
        rows = []
        for row in root.findall("main:sheetData/main:row", NS):
            cells = {}
            for c in row.findall("main:c", NS):
                ref = c.get("r")
                col = "".join(ch for ch in ref if ch.isalpha())
                cells[col] = cell_value(c, shared).strip()
            rows.append(cells)
        return rows


def extract_pillars(structure_path: Path):
    rows = parse_sheet(structure_path)
    pillars = []
    for row in rows:
        if row.get("A", "").startswith("Pillar") and row.get("B"):
            indicator_count = row.get("C", "0") or 0
            weight = row.get("D", "0") or 0
            try:
                indicator_count = int(indicator_count)
            except ValueError:
                indicator_count = 0
            try:
                weight = int(weight)
            except ValueError:
                weight = 0
            code = row.get("A", "").strip()
            name = row.get("B", "").strip()
            if code == "Pillar" and name == "ชื่อหมวด":
                continue
            pillars.append(
                {
                    "code": code,
                    "name": name,
                    "indicator_count": indicator_count,
                    "weight": weight,
                }
            )
    return pillars


def parse_indicator_title(text):
    if ":" in text:
        code, title = text.split(":", 1)
        return code.strip(), title.strip()
    return text.strip(), ""


def extract_indicators(pillar_path: Path, pillar_code: str):
    rows = parse_sheet(pillar_path)
    indicators = []
    current_pillar_title = ""
    for row in rows:
        if row.get("A") and row.get("A").startswith("Pillar"):
            current_pillar_title = row.get("A")
        if row.get("B", "").startswith(pillar_code + "."):
            code, title = parse_indicator_title(row.get("B", ""))
            indicators.append(
                {
                    "pillar": pillar_code,
                    "pillar_title": current_pillar_title,
                    "code": code,
                    "title": title,
                    "description": row.get("C", ""),
                    "criteria": row.get("D", ""),
                    "default_self_level": row.get("G", ""),
                    "default_self_score": row.get("H", ""),
                    "default_auditor_level": row.get("I", ""),
                    "default_auditor_score": row.get("J", ""),
                }
            )
    return indicators


def main():
    base = Path(".")
    pillars = extract_pillars(base / "B_ส่วนที่ 2 โครงสร้างและระบบคะแนน.xlsx")
    pillar_files = {
        "H1": base / "C_Pillar 1 Health Promotion (H1) - การส่งเสริมสุขภาพ.xlsx",
        "I2": base / "D_Pillar 2 Industrial Safety & Environment (I2).xlsx",
        "C3": base / "E_Pillar 3 Community Engagement (C3).xlsx",
        "M4": base / "F_Pillar 4 Management & Sustainability (M4) .xlsx",
    }
    indicators = []
    for code, path in pillar_files.items():
        indicators.extend(extract_indicators(path, code))

    output = {
        "pillars": pillars,
        "indicators": indicators,
    }
    Path("data").mkdir(exist_ok=True)
    (Path("data") / "hicm-indicators.json").write_text(
        json.dumps(output, ensure_ascii=False, indent=2), encoding="utf-8"
    )


if __name__ == "__main__":
    main()
