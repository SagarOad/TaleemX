"""
services/arabic_localize.py

Fast, deterministic Arabic localization for Ask AI UI payloads.
Avoids sending large executive-briefing JSON through the LLM (slow, brittle).
"""

from __future__ import annotations

import copy
import re
from typing import Any

# Severity / status enums shown in tables
_ENUM_MAP = {
    "high": "عالي",
    "medium": "متوسط",
    "low": "منخفض",
    "strong": "قوي",
    "good": "جيد",
    "watch": "يحتاج متابعة",
    "critical": "حرج",
    "neutral": "محايد",
    "present": "حاضر",
    "absent": "غائب",
    "staff": "موظف",
    "student": "طالب",
}

# Common KPI / metric / column labels
_LABEL_MAP = {
    "active students": "الطلاب النشطون",
    "active staff": "الموظفون النشطون",
    "teachers": "المعلمون",
    "student attendance % this month": "نسبة حضور الطلاب هذا الشهر",
    "students absent today": "الطلاب الغائبون اليوم",
    "fee collected this month": "الرسوم المحصلة هذا الشهر",
    "outstanding fee balance": "رصيد الرسوم المستحق",
    "new admissions this month": "القبول الجديد هذا الشهر",
    "new admissions last month": "القبول الجديد الشهر الماضي",
    "admission enquiries this month": "استفسارات القبول هذا الشهر",
    "open complaints": "الشكاوى المفتوحة",
    "behaviour incidents this month": "حوادث السلوك هذا الشهر",
    "estimated net surplus this month": "الفائض الصافي المقدر هذا الشهر",
    "avg exam score %": "متوسط درجة الامتحان %",
    "department_name": "اسم القسم",
    "active_staff_count": "عدد الموظفين النشطين",
    "risk_area": "مجال الخطر",
    "risk_indicator": "مؤشر الخطر",
    "issue_count": "عدد الحالات",
    "severity": "الخطورة",
    "recommended_action": "الإجراء المقترح",
    "concern_area": "مجال الاهتمام",
    "priority_note": "ملاحظة الأولوية",
    "class_name": "الصف",
    "attendance_percent": "نسبة الحضور",
    "month_label": "الشهر",
    "new_admissions": "قبول جديد",
    "metric": "المؤشر",
    "value": "القيمة",
    "metric_name": "المؤشر",
    "metric_value": "القيمة",
}

# Executive briefing static strings
_EXEC_STRINGS = {
    "School performance — executive briefing": "أداء المدرسة — تقرير تنفيذي",
    "Overall health score:": "درجة الصحة العامة:",
    "Strengths:": "نقاط القوة:",
    "Priorities:": "الأولويات:",
    "Watch:": "متابعة:",
    "Strong performance": "أداء قوي",
    "Good — some areas to watch": "جيد — بعض المجالات تحتاج متابعة",
    "Needs improvement": "يحتاج تحسين",
    "Critical attention required": "يتطلب اهتماماً عاجلاً",
    "Attendance target": "هدف الحضور",
    "Fee health": "صحة التحصيل",
    "Growth": "النمو",
    "Finance": "المالية",
    "Collections vs outstanding": "التحصيل مقابل المستحق",
    "Admissions vs last month": "القبول مقابل الشهر الماضي",
    "Income − expenses − payroll ≥ 0": "الإيراد − المصروف − الرواتب ≥ 0",
    "Value": "القيمة",
    "Charts and detailed risk/gap tables are below.": "الرسوم البيانية وجداول المخاطر والفجوات أدناه.",
    "Based on live data from students, attendance, fees, finance, admissions, exams, and operations.": (
        "استناداً إلى بيانات حية من الطلاب والحضور والرسوم والمالية والقبول والامتحانات والعمليات."
    ),
}

_MODULE_LABELS = {
    "School Performance & Risk": "أداء المدرسة والمخاطر",
    "Staff & HR": "الموظفون والموارد البشرية",
    "Students": "الطلاب",
    "Attendance": "الحضور",
    "Fees": "الرسوم",
    "Finance": "المالية",
    "General": "عام",
    "Front Office": "الاستقبال",
    "Exams & Marks": "الامتحانات والدرجات",
    "Behaviour": "السلوك",
}

_SUGGESTION_MAP = {
    "how is our school performing overall": "كيف أداء مدرستنا بشكل عام؟",
    "give me risk analysis of the school": "أعطني تحليل مخاطر المدرسة",
    "what can we improve at our school": "ما الذي يمكننا تحسينه في مدرستنا؟",
    "what is our profit this month": "ما ربحنا هذا الشهر؟",
    "show attendance summary per student this month": "اعرض ملخص حضور كل طالب هذا الشهر",
    "where is our school lacking": "أين تنقص مدرستنا؟",
    "what departments do we have": "ما الأقسام لدينا؟",
    "how many staff in each department": "كم موظفاً في كل قسم؟",
    "how many teachers do we have": "كم معلماً لدينا؟",
    "give me hr headcount summary": "أعطني ملخص أعداد الموارد البشرية",
    "how many students do we have": "كم طالباً لدينا؟",
    "show fee collection trend by month": "اعرض اتجاه تحصيل الرسوم شهرياً",
    "list students at risk academically or financially": "اعرض الطلاب المعرضين للخطر أكاديمياً أو مالياً",
    "compare new student admissions this month vs last month": "قارن قبول الطلاب الجدد هذا الشهر بالشهر الماضي",
}


def _tr_label(text: str) -> str:
    if not text:
        return text
    key = str(text).strip().lower()
    if key in _ENUM_MAP:
        return _ENUM_MAP[key]
    if key in _LABEL_MAP:
        return _LABEL_MAP[key]
    # Partial map for long labels
    for en, ar in _LABEL_MAP.items():
        if en in key or key in en:
            return ar
    return _LABEL_MAP.get(key, str(text).replace("_", " "))


def _tr_cell(val: Any) -> Any:
    if val is None or isinstance(val, (int, float, bool)):
        return val
    s = str(val).strip()
    low = s.lower()
    if low in _ENUM_MAP:
        return _ENUM_MAP[low]
    return s


def _localize_table_block(block: dict) -> dict:
    if not block or block.get("kind") != "table":
        return block
    out = copy.deepcopy(block)
    cols = out.get("columns")
    if isinstance(cols, list):
        out["columns"] = [_tr_label(c) for c in cols]
    rows = out.get("rows")
    if isinstance(rows, list):
        out["rows"] = [
            [_tr_cell(cell) for cell in row] for row in rows
        ]
    return out


def _localize_chart_block(block: dict) -> dict:
    if not block:
        return block
    out = copy.deepcopy(block)
    if isinstance(out.get("label_column"), str):
        out["label_column"] = _tr_label(out["label_column"])
    if isinstance(out.get("value_column"), str):
        out["value_column"] = _tr_label(out["value_column"])
    labels = out.get("labels")
    if isinstance(labels, list):
        out["labels"] = [_tr_label(l) for l in labels]
    datasets = out.get("datasets")
    if isinstance(datasets, list):
        for ds in datasets:
            if isinstance(ds, dict) and isinstance(ds.get("label"), str):
                ds["label"] = _tr_label(ds["label"])
    return out


def localize_structured_data(sd: dict) -> dict:
    """Deterministic Arabic for dashboards — no LLM, no JSON truncation."""
    if not sd or not isinstance(sd, dict):
        return sd

    kind = sd.get("kind")
    if kind == "executive_briefing":
        return _localize_executive_briefing(sd)

    out = copy.deepcopy(sd)
    if kind == "table":
        return _localize_table_block(out)
    if kind in ("bar_chart", "line_chart", "pie_chart"):
        return _localize_chart_block(out)
    if kind == "kpi":
        if isinstance(out.get("label"), str):
            out["label"] = _tr_label(out["label"])
        return out
    if kind == "cards":
        cols = out.get("columns")
        if isinstance(cols, list):
            out["columns"] = [_tr_label(c) for c in cols]
        rows = out.get("rows")
        if isinstance(rows, list):
            out["rows"] = [[_tr_cell(c) for c in row] for row in rows]
        return out

    # Generic table/chart keys nested
    if "columns" in out:
        out = _localize_table_block({**out, "kind": "table"})
    return out


def _localize_executive_briefing(sd: dict) -> dict:
    out = copy.deepcopy(sd)
    health = out.get("health")
    if isinstance(health, dict):
        for field in ("rating", "summary"):
            val = health.get(field)
            if isinstance(val, str):
                for en, ar in _EXEC_STRINGS.items():
                    val = val.replace(en, ar)
                for en, ar in _LABEL_MAP.items():
                    val = re.sub(re.escape(en), ar, val, flags=re.IGNORECASE)
                health[field] = val
        for field in ("strengths", "priorities", "watch_notes"):
            items = health.get(field)
            if isinstance(items, list):
                health[field] = [
                    _localize_narrative_line(str(x)) for x in items
                ]

    kpis = out.get("kpis")
    if isinstance(kpis, list):
        for tile in kpis:
            if isinstance(tile, dict) and isinstance(tile.get("label"), str):
                tile["label"] = _tr_label(tile["label"])

    benchmarks = out.get("benchmarks")
    if isinstance(benchmarks, list):
        for b in benchmarks:
            if isinstance(b, dict):
                if isinstance(b.get("label"), str):
                    b["label"] = _EXEC_STRINGS.get(b["label"], _tr_label(b["label"]))
                if isinstance(b.get("value"), str):
                    b["value"] = _EXEC_STRINGS.get(b["value"], b["value"])

    for key in (
        "risk_table", "gaps_table", "risk_chart", "gaps_chart",
        "admissions_chart", "class_attendance_chart", "kpi_chart",
    ):
        block = out.get(key)
        if not block:
            continue
        if block.get("kind") == "table":
            out[key] = _localize_table_block(block)
        elif block.get("kind") in ("bar_chart", "line_chart", "pie_chart"):
            out[key] = _localize_chart_block(block)

    return out


def _localize_narrative_line(line: str) -> str:
    s = line
    for en, ar in _EXEC_STRINGS.items():
        s = s.replace(en, ar)
    for en, ar in _LABEL_MAP.items():
        if en in s.lower():
            s = re.sub(re.escape(en), ar, s, flags=re.IGNORECASE)
    return s


def localize_module_label(label: str) -> str:
    return _MODULE_LABELS.get((label or "").strip(), label)


def localize_suggestions(items: list[str]) -> list[str]:
    out = []
    for item in items:
        key = (item or "").strip().lower()
        out.append(_SUGGESTION_MAP.get(key, item))
    return out


def should_skip_llm_structured_translate(sd: dict) -> bool:
    """Large or executive payloads must not go through JSON LLM translation."""
    if not sd:
        return True
    if sd.get("kind") == "executive_briefing":
        return True
    import json
    try:
        return len(json.dumps(sd, ensure_ascii=False, default=str)) > 6000
    except (TypeError, ValueError):
        return True
