"""
Download Center SQL — content_types, upload_contents, share_contents.
"""

from __future__ import annotations

import re
from dataclasses import dataclass, field
from typing import TYPE_CHECKING, Optional

if TYPE_CHECKING:
    from services.db_service import DBService

from services.question_normalize import routing_question


@dataclass
class DownloadCenterReport:
    message: str = ""
    columns: list[str] = field(default_factory=list)
    rows: list = field(default_factory=list)
    sql_note: str = ""


def is_content_types_question(question: str) -> bool:
    rq = routing_question(question).lower()
    if re.search(r"\bcontent types?\b", rq):
        return True
    if "download center" in rq and re.search(r"\btype\b|\btypes\b", rq):
        return True
    if re.search(r"\b(?:pdf|video|doc)\b", rq) and re.search(
        r"\bcontent types?\b|\btypes available\b", rq
    ):
        return True
    return bool(
        re.search(r"\b(?:all|available|configured)\b", rq)
        and re.search(r"\bcontent types?\b", rq)
    )


def is_content_shared_summary_question(question: str) -> bool:
    rq = routing_question(question).lower()
    return bool(
        re.search(r"\b(?:summarize|summary|overview)\b", rq)
        and re.search(r"\bcontent shared\b|\bshared content\b", rq)
    ) or bool(
        re.search(r"\bcontent shared\b|\bshared content\b", rq)
        and re.search(r"\blast month\b|\bpast month\b|\bthis month\b", rq)
    )


def resolve_download_center_report(
    db: "DBService",
    question: str,
) -> Optional[DownloadCenterReport]:
    if is_content_types_question(question):
        if not db.table_exists("content_types"):
            return DownloadCenterReport(
                message="Download Center content types are not available.",
                sql_note="-- content_types",
            )
        sql = (
            "SELECT id, name AS content_type, description "
            "FROM content_types ORDER BY name ASC"
        )
        rows, cols, err = db.execute(sql, max_rows=50)
        if err:
            return DownloadCenterReport(
                message=f"Could not load content types: {err}",
                sql_note=sql,
            )
        if not rows:
            return DownloadCenterReport(
                message="No **content types** are configured in Download Center.",
                sql_note=sql,
            )
        return DownloadCenterReport(
            message=f"**{len(rows)}** content type(s) configured in **Download Center**:",
            columns=cols,
            rows=rows,
            sql_note=sql,
        )

    if is_content_shared_summary_question(question):
        if not db.table_exists("share_contents"):
            return DownloadCenterReport(
                message="Download Center share module is not available.",
                sql_note="-- share_contents",
            )

        rq = routing_question(question).lower()
        if re.search(r"\bthis month\b|\bcurrent month\b", rq):
            period_sql = (
                "MONTH(sc.share_date) = MONTH(CURDATE()) "
                "AND YEAR(sc.share_date) = YEAR(CURDATE())"
            )
            period_label = "this month"
        else:
            period_sql = (
                "sc.share_date >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH) "
                "AND sc.share_date <= CURDATE()"
            )
            period_label = "the last month"

        total_sql = (
            f"SELECT COUNT(*) AS total_shared_batches FROM share_contents sc "
            f"WHERE {period_sql}"
        )
        total_rows, _, err = db.execute(total_sql, max_rows=1)
        if err:
            return DownloadCenterReport(
                message=f"Could not summarize shared content: {err}",
                sql_note=total_sql,
            )
        total = int(total_rows[0][0]) if total_rows else 0

        type_sql = (
            "SELECT ct.name AS content_type, COUNT(DISTINCT suc.upload_content_id) "
            "AS shared_items, COUNT(*) AS share_links "
            "FROM share_upload_contents suc "
            "JOIN share_contents sc ON sc.id = suc.share_content_id "
            "JOIN upload_contents uc ON uc.id = suc.upload_content_id "
            "JOIN content_types ct ON ct.id = uc.content_type_id "
            f"WHERE {period_sql.replace('sc.', 'sc.')} "
            "GROUP BY ct.id, ct.name "
            "ORDER BY shared_items DESC, ct.name "
            "LIMIT 20"
        )
        type_rows, type_cols, type_err = db.execute(type_sql, max_rows=20)

        top_sql = (
            "SELECT uc.real_name AS content_title, ct.name AS content_type, "
            "COUNT(*) AS times_shared, MAX(sc.share_date) AS last_shared "
            "FROM share_upload_contents suc "
            "JOIN share_contents sc ON sc.id = suc.share_content_id "
            "JOIN upload_contents uc ON uc.id = suc.upload_content_id "
            "LEFT JOIN content_types ct ON ct.id = uc.content_type_id "
            f"WHERE {period_sql.replace('sc.', 'sc.')} "
            "GROUP BY uc.id, content_title, content_type "
            "ORDER BY times_shared DESC, last_shared DESC "
            "LIMIT 10"
        )
        top_rows, top_cols, top_err = db.execute(top_sql, max_rows=10)

        summary_rows = [
            ("Total share batches", str(total)),
        ]
        if not type_err and type_rows:
            for r in type_rows:
                summary_rows.append(
                    (f"Type: {r[0]}", f"{r[1]} item(s), {r[2]} link(s)")
                )
        if not top_err and top_rows:
            best = top_rows[0]
            summary_rows.append(
                (
                    "Most shared item",
                    f"{best[0]} ({best[1]}) — {best[2]} time(s)",
                )
            )

        msg = (
            f"**Download Center — content shared in {period_label}**\n\n"
            f"- **{total}** share batch(es) created\n"
        )
        if type_rows and not type_err:
            msg += f"- **{len(type_rows)}** content type(s) represented in shares\n"
        if top_rows and not top_err:
            msg += f"- Most shared: **{top_rows[0][0]}** ({top_rows[0][2]} shares)\n"

        detail_cols = ["metric", "value"]
        detail_sql = f"{total_sql}; {type_sql}; {top_sql}"
        return DownloadCenterReport(
            message=msg.strip(),
            columns=detail_cols,
            rows=summary_rows,
            sql_note=detail_sql,
        )

    return None
