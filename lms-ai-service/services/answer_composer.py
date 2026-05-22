"""Deterministic summaries for KPI / risk / attendance result shapes."""

from __future__ import annotations

from typing import Optional

from services.text_sanitize import sanitize_cell


def _cols_lower(columns: list) -> list[str]:
    return [str(c).lower().strip() for c in columns]


def _cell(row: tuple, idx: int):
    return row[idx] if idx < len(row) else None


def _num(val) -> float | None:
    if val is None or val == "":
        return None
    try:
        return float(val)
    except (TypeError, ValueError):
        return None


def compose_answer(question: str, columns: list, rows: list) -> Optional[str]:
    if not rows or not columns:
        return None

    q = (question or "").lower()
    cl = _cols_lower(columns)

    if len(cl) == 2 and cl[0] in ("metric", "metric_name") and cl[1] in ("value", "metric_value"):
        return _compose_metrics(q, columns, rows)

    if "severity" in cl and ("risk_indicator" in cl or "risk_area" in cl):
        return _compose_risk(columns, rows)

    if "concern_area" in cl and "issue_count" in cl:
        return _compose_gaps(columns, rows)

    if "department_name" in cl and "department" in q:
        return _compose_departments(columns, rows)

    if "attendance_percent" in cl and "student_name" in cl:
        return _compose_attendance_rank(q, columns, rows)

    if "person_name" in cl and "request_type" in cl and "leave" in q:
        st = sum(1 for r in rows if str(_cell(r, cl.index("request_type"))).lower() == "student")
        return (
            f"I found **{len(rows)}** approved leave request(s): "
            f"**{st}** student · **{len(rows) - st}** staff. Details are in the table below."
        )

    return None


def _compose_metrics(q: str, columns: list, rows: list) -> str:
    metrics = {str(_cell(r, 0)): _cell(r, 1) for r in rows}
    lines = ["**School performance snapshot** (from your live database):", ""]
    att = _num(metrics.get("Student attendance % this month"))
    if att is not None:
        lines.append(f"- Attendance this month: **{att}%**")
    for key in ("Active students", "Fee collected this month", "Outstanding fee balance"):
        if key in metrics:
            lines.append(f"- {key}: **{metrics[key]}**")
    lines.append("")
    lines.append("Full KPI breakdown is in the table below.")
    return "\n".join(lines)


def _compose_risk(columns: list, rows: list) -> str:
    cl = _cols_lower(columns)
    i_ind = cl.index("risk_indicator") if "risk_indicator" in cl else 0
    i_cnt = cl.index("issue_count") if "issue_count" in cl else 2
    i_sev = cl.index("severity") if "severity" in cl else None
    lines = [f"**Risk analysis** — **{len(rows)}** active signal(s):", ""]
    for row in rows[:10]:
        ind = sanitize_cell(_cell(row, i_ind))
        cnt = _cell(row, i_cnt)
        sev = str(_cell(row, i_sev) or "") if i_sev is not None else ""
        tag = f"**[{sev}]** " if sev else ""
        lines.append(f"- {tag}{ind}: **{cnt}**")
    lines.append("")
    lines.append("Severity and recommended actions are in the table below.")
    return "\n".join(lines)


def _compose_departments(columns: list, rows: list) -> str:
    cl = _cols_lower(columns)
    i_name = cl.index("department_name")
    i_cnt = cl.index("active_staff_count") if "active_staff_count" in cl else None
    if i_cnt is not None:
        lines = [f"**{len(rows)}** department(s) with active staff headcount:", ""]
        for row in rows[:25]:
            name = sanitize_cell(_cell(row, i_name))
            cnt = _cell(row, i_cnt)
            lines.append(f"- **{name}**: {cnt} staff")
        if len(rows) > 25:
            lines.append(f"- …and {len(rows) - 25} more in the table below.")
        else:
            lines.append("")
            lines.append("Full breakdown is in the table below.")
        return "\n".join(lines)

    names = [sanitize_cell(_cell(r, i_name)) for r in rows if _cell(r, i_name)]
    if not names:
        return "No departments are defined in the HR module yet."
    if len(names) == 1:
        return f"The school has **1** department: **{names[0]}**."
    preview = ", ".join(f"**{n}**" for n in names[:12])
    extra = f" (and {len(names) - 12} more)" if len(names) > 12 else ""
    return (
        f"The school has **{len(names)}** departments: {preview}{extra}. "
        "See the table below for the full list."
    )


def _compose_gaps(columns: list, rows: list) -> str:
    cl = _cols_lower(columns)
    i_a = cl.index("concern_area")
    i_c = cl.index("issue_count")
    top = rows[0]
    lines = [
        f"**Top gap:** {sanitize_cell(_cell(top, i_a))} (**{_cell(top, i_c)}** cases).",
        "",
    ]
    for row in rows[:8]:
        lines.append(f"- {sanitize_cell(_cell(row, i_a))}: {_cell(row, i_c)}")
    lines.append("")
    lines.append("See the table for severity and priority notes.")
    return "\n".join(lines)


def _compose_attendance_rank(q: str, columns: list, rows: list) -> str:
    cl = _cols_lower(columns)
    i_name = cl.index("student_name")
    i_pct = cl.index("attendance_percent") if "attendance_percent" in cl else None
    best = rows[0]
    name = sanitize_cell(_cell(best, i_name))
    pct = _cell(best, i_pct) if i_pct is not None else None
    if "best" in q or "top" in q:
        lead = f"**{name}** leads with **{pct}%** attendance" if pct is not None else f"**{name}** leads attendance"
        return f"{lead} (of {len(rows)} students with records). Full ranking is below."
    return f"Attendance summary for **{len(rows)}** students this month is in the table below."
