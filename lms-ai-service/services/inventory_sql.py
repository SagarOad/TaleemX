"""
Inventory module SQL — TaleemX tables item, item_stock, item_category, item_issue.
(NOT inventory_stock / inventory_issue legacy names in old QA pairs.)
"""

from __future__ import annotations

import re
from dataclasses import dataclass, field
from typing import TYPE_CHECKING, Optional

if TYPE_CHECKING:
    from services.db_service import DBService

from services.date_filters import (
    month_english_name,
    parse_month_from_question,
    parse_year_from_question,
    sql_date_filter,
)
from services.question_normalize import routing_question


def _esc(s: str) -> str:
    return (s or "").replace("'", "''")


@dataclass
class InventoryReport:
    message: str = ""
    columns: list[str] = field(default_factory=list)
    rows: list = field(default_factory=list)
    sql_note: str = ""


def is_inventory_question(question: str) -> bool:
    rq = routing_question(question).lower()
    if re.search(r"\bbooks?\b", rq) and "inventory" not in rq:
        return False
    if re.search(r"\blibrary\b", rq) and "inventory" not in rq:
        return False
    return bool(
        re.search(r"\binventory\b", rq)
        or re.search(r"\bitem stock\b|\bstock items?\b", rq)
        or re.search(r"\blaboratory items?\b|\blab items?\b", rq)
        or re.search(r"\blow stock\b", rq)
        or (
            re.search(r"\bitems?\b", rq)
            and re.search(
                r"\bissued\b|\bstock\b|\blaboratory\b|\blab\b|\bcategor|"
                r"\b(?:newly|new)\s+(?:added|received)\b|\badded\s+this\b",
                rq,
            )
        )
    )


def _extract_category_filter(question: str) -> Optional[str]:
    rq = routing_question(question)
    if re.search(r"\blaboratory\b|\blab items?\b", rq, re.IGNORECASE):
        return "laboratory"
    for pat in (
        r"(?:for|in)\s+(?:the\s+)?([A-Za-z][A-Za-z\s\-]{2,40}?)\s+items?",
        r"(?:current|show)\s+(?:inventory|stock)\s+(?:for|of)\s+(?:the\s+)?(.+?)(?:\?|$|\.)",
        r"\bcategory\s+(?:of\s+)?([A-Za-z][A-Za-z\s\-]{2,40})",
    ):
        m = re.search(pat, rq, re.IGNORECASE)
        if m:
            cat = " ".join(m.group(1).split()).strip("?.!,;:")
            low = cat.lower()
            if low not in {"all", "the", "current", "new", "newly", "show"} and len(cat) >= 3:
                return cat
    return None


def _category_where(alias: str, category: Optional[str]) -> str:
    if not category:
        return ""
    esc = _esc(category)
    return f" AND LOWER({alias}.item_category) LIKE LOWER('%{esc}%')"


def _period_sql_and_label(column: str, question: str) -> tuple[str, str, str]:
    """
    Build a date filter for inventory date columns.

    Returns (primary_sql, human_label, fallback_sql).
    fallback_sql is month-only (any year) when a month name is given without a year.
    """
    rq = routing_question(question).lower()
    if re.search(r"\btoday\b", rq):
        return f"DATE({column}) = CURDATE()", "today", ""
    if re.search(r"\bthis week\b|\bcurrent week\b", rq):
        return (
            f"YEARWEEK({column}, 1) = YEARWEEK(CURDATE(), 1)",
            "this week",
            "",
        )

    df = sql_date_filter(column, question)
    month_num = parse_month_from_question(question)

    if df.kind == "today":
        return df.sql_on_column, "today", ""
    if df.kind == "this_month":
        return df.sql_on_column, "this month", ""
    if df.kind == "exact" and df.sql_on_column:
        disp = df.parsed.strftime("%B %d, %Y") if df.parsed else "selected date"
        return df.sql_on_column, disp, ""
    if df.kind == "month" and month_num:
        year = parse_year_from_question(question)
        label = (
            f"{month_english_name(month_num)} {year}"
            if year
            else month_english_name(month_num)
        )
        fallback = f"MONTH({column}) = {month_num}" if not year else ""
        return df.sql_on_column, label, fallback

    return (
        f"MONTH({column}) = MONTH(CURDATE()) AND YEAR({column}) = YEAR(CURDATE())",
        "this month",
        "",
    )


def _run_with_period_fallback(
    db: "DBService",
    question: str,
    date_column: str,
    build_sql,
    *,
    max_rows: int,
) -> tuple[list, list[str], str, str, Optional[str]]:
    """
    Execute SQL built from a period filter; if empty and a month-only fallback
    exists, retry without the year constraint.
    """
    period_sql, period_label, fallback_sql = _period_sql_and_label(date_column, question)
    sql = build_sql(period_sql)
    rows, cols, err = db.execute(sql, max_rows=max_rows)
    if err:
        return rows, cols, period_label, sql, err
    if not rows and fallback_sql:
        sql_fb = build_sql(fallback_sql)
        rows_fb, cols_fb, err_fb = db.execute(sql_fb, max_rows=max_rows)
        if not err_fb and rows_fb:
            return rows_fb, cols_fb, f"{period_label} (all years)", sql_fb, None
    return rows, cols, period_label, sql, None


def _item_added_date_expr(alias: str = "i") -> str:
    return f"COALESCE({alias}.date, DATE({alias}.created_at))"


def _stock_added_date_expr(alias: str = "ist") -> str:
    return f"COALESCE({alias}.date, DATE({alias}.created_at))"


def _is_issued_summary(question: str) -> bool:
    rq = routing_question(question).lower()
    return bool(
        re.search(r"\b(?:summarize|summary|breakdown|grouped)\b", rq)
        and re.search(r"\bissued\b", rq)
        and re.search(r"\bcategor", rq)
    )


def _is_issued_list(question: str) -> bool:
    rq = routing_question(question).lower()
    return bool(re.search(r"\bissued\b", rq) and not _is_issued_summary(question))


def _is_newly_added(question: str) -> bool:
    rq = routing_question(question).lower()
    return bool(
        re.search(r"\b(?:newly|new|added|received)\b", rq)
        and re.search(r"\bitems?\b", rq)
        and not _is_issued_list(question)
    )


def _is_low_stock(question: str) -> bool:
    return bool(re.search(r"\blow stock\b", routing_question(question).lower()))


def _is_current_stock(question: str) -> bool:
    rq = routing_question(question).lower()
    return bool(
        re.search(r"\b(?:current|available)\b", rq)
        and re.search(r"\b(?:inventory|stock)\b", rq)
    ) or bool(
        re.search(r"\binventory\b", rq)
        and not _is_issued_list(question)
        and not _is_newly_added(question)
        and not _is_issued_summary(question)
        and not _is_low_stock(question)
    )


def resolve_inventory_report(db: "DBService", question: str) -> Optional[InventoryReport]:
    if not is_inventory_question(question):
        return None

    if not db.table_exists("item") and not db.table_exists("item_stock"):
        if db.table_exists("inventory_stock"):
            return InventoryReport(
                message=(
                    "Inventory tables use legacy names in this database — "
                    "please ask again or contact support."
                ),
                sql_note="-- item tables missing",
            )
        return InventoryReport(
            message="Inventory module is not available.",
            sql_note="-- inventory missing",
        )

    category = _extract_category_filter(question)
    cat_where = _category_where("ic", category)

    if _is_low_stock(question):
        sql = (
            "SELECT i.name AS item_name, ic.item_category AS category, "
            "SUM(ist.quantity) AS available_quantity, i.unit, "
            "ist.item_store_id "
            "FROM item_stock ist "
            "JOIN item i ON i.id = ist.item_id "
            "JOIN item_category ic ON ic.id = i.item_category_id "
            f"WHERE 1=1{cat_where} "
            "GROUP BY i.id, i.name, ic.item_category, i.unit, ist.item_store_id "
            "HAVING SUM(ist.quantity) < 5 "
            "ORDER BY available_quantity ASC "
            "LIMIT 100"
        )
        rows, cols, err = db.execute(sql, max_rows=100)
        if err:
            return InventoryReport(message=f"Could not load low stock: {err}", sql_note=sql)
        if not rows:
            return InventoryReport(
                message="No **low stock** inventory items found.",
                sql_note=sql,
            )
        return InventoryReport(
            message=f"**{len(rows)}** low-stock inventory item(s):",
            columns=cols,
            rows=rows,
            sql_note=sql,
        )

    if _is_issued_summary(question):
        if not db.table_exists("item_issue"):
            return InventoryReport(message="Item issue module is not available.", sql_note="-- item_issue")

        def _summary_sql(period_sql: str) -> str:
            return (
                "SELECT ic.item_category AS category, "
                "COUNT(*) AS issue_transactions, "
                "SUM(ii.quantity) AS total_quantity_issued "
                "FROM item_issue ii "
                "JOIN item i ON i.id = ii.item_id "
                "JOIN item_category ic ON ic.id = i.item_category_id "
                f"WHERE {period_sql}{cat_where} "
                "GROUP BY ic.id, ic.item_category "
                "ORDER BY total_quantity_issued DESC, category "
                "LIMIT 50"
            )

        rows, cols, period_label, sql, err = _run_with_period_fallback(
            db, question, "ii.issue_date", _summary_sql, max_rows=50,
        )
        if err:
            return InventoryReport(message=f"Could not summarize issues: {err}", sql_note=sql)
        if not rows:
            return InventoryReport(
                message=f"No **items issued** in **{period_label}** to summarize by category.",
                sql_note=sql,
            )
        return InventoryReport(
            message=f"**Items issued in {period_label}** summarized by category:",
            columns=cols,
            rows=rows,
            sql_note=sql,
        )

    if _is_issued_list(question):
        if not db.table_exists("item_issue"):
            return InventoryReport(message="Item issue module is not available.", sql_note="-- item_issue")

        def _issued_sql(period_sql: str) -> str:
            return (
                "SELECT i.name AS item_name, ic.item_category AS category, "
                "ii.quantity, ii.issue_date, ii.return_date, "
                "CASE WHEN ii.is_returned IN ('1', 1) THEN 'Not returned' ELSE 'Returned' END "
                "AS return_status, "
                "COALESCE(CONCAT_WS(' ', st.name, st.surname), CONCAT('Staff #', ii.issue_to)) "
                "AS issued_to_staff, "
                "st.employee_id, ii.note "
                "FROM item_issue ii "
                "JOIN item i ON i.id = ii.item_id "
                "JOIN item_category ic ON ic.id = i.item_category_id "
                "LEFT JOIN staff st ON st.id = ii.issue_to "
                f"WHERE {period_sql}{cat_where} "
                "ORDER BY ii.issue_date DESC "
                "LIMIT 200"
            )

        rows, cols, period_label, sql, err = _run_with_period_fallback(
            db, question, "ii.issue_date", _issued_sql, max_rows=200,
        )
        if err:
            return InventoryReport(message=f"Could not load issued items: {err}", sql_note=sql)
        if not rows:
            return InventoryReport(
                message=f"No **inventory items issued** in **{period_label}**.",
                sql_note=sql,
            )
        return InventoryReport(
            message=f"**{len(rows)}** inventory item issue(s) in **{period_label}**:",
            columns=cols,
            rows=rows,
            sql_note=sql,
        )

    if _is_newly_added(question):
        item_date = _item_added_date_expr("i")
        stock_date = _stock_added_date_expr("ist")
        period_item, period_label, fallback_item = _period_sql_and_label(item_date, question)
        period_stock, _, fallback_stock = _period_sql_and_label(stock_date, question)

        def _newly_added_sql(period_i: str, period_s: str) -> str:
            return (
                "SELECT * FROM ("
                "SELECT i.name AS item_name, ic.item_category AS category, i.unit, "
                "COALESCE(i.quantity, 0) AS quantity, "
                f"{item_date} AS added_date, "
                "'Catalog item' AS entry_type, "
                "NULL AS purchase_price, '' AS store_name, '' AS supplier "
                "FROM item i "
                "JOIN item_category ic ON ic.id = i.item_category_id "
                f"WHERE {period_i}{cat_where} "
                "UNION ALL "
                "SELECT i.name AS item_name, ic.item_category AS category, i.unit, "
                "ist.quantity, "
                f"{stock_date} AS added_date, "
                "'Stock receipt' AS entry_type, "
                "ist.purchase_price, "
                "COALESCE(istore.item_store, '') AS store_name, "
                "COALESCE(isup.item_supplier, '') AS supplier "
                "FROM item_stock ist "
                "JOIN item i ON i.id = ist.item_id "
                "JOIN item_category ic ON ic.id = i.item_category_id "
                "LEFT JOIN item_store istore ON istore.id = ist.store_id "
                "LEFT JOIN item_supplier isup ON isup.id = ist.supplier_id "
                f"WHERE {period_s}{cat_where}"
                ") AS newly_added "
                "ORDER BY added_date DESC, item_name "
                "LIMIT 200"
            )

        sql = _newly_added_sql(period_item, period_stock)
        rows, cols, err = db.execute(sql, max_rows=200)
        label = period_label
        if not rows and fallback_item:
            sql = _newly_added_sql(fallback_item, fallback_stock)
            rows, cols, err = db.execute(sql, max_rows=200)
            if rows:
                label = f"{period_label} (all years)"
        if err:
            return InventoryReport(message=f"Could not load new items: {err}", sql_note=sql)
        if not rows:
            return InventoryReport(
                message=f"No **newly added inventory items** in **{label}**.",
                sql_note=sql,
            )
        return InventoryReport(
            message=f"**{len(rows)}** newly added inventory item(s) in **{label}**:",
            columns=cols,
            rows=rows,
            sql_note=sql,
        )

    if _is_current_stock(question) or is_inventory_question(question):
        sql = (
            "SELECT i.name AS item_name, ic.item_category AS category, "
            "SUM(ist.quantity) AS available_quantity, i.unit, "
            "COALESCE(istore.item_store, '') AS store_name, "
            "(SELECT COALESCE(SUM(ii.quantity), 0) FROM item_issue ii "
            " WHERE ii.item_id = i.id) AS total_issued "
            "FROM item_stock ist "
            "JOIN item i ON i.id = ist.item_id "
            "JOIN item_category ic ON ic.id = i.item_category_id "
            "LEFT JOIN item_store istore ON istore.id = ist.store_id "
            f"WHERE 1=1{cat_where} "
            "GROUP BY i.id, i.name, ic.item_category, i.unit, istore.item_store "
            "ORDER BY ic.item_category, i.name "
            "LIMIT 200"
        )
        rows, cols, err = db.execute(sql, max_rows=200)
        if err:
            return InventoryReport(message=f"Could not load inventory: {err}", sql_note=sql)
        label = "Current inventory"
        if category:
            label += f" — **{category}** items"
        if not rows:
            return InventoryReport(
                message=f"No **{label.lower()}** records found.",
                sql_note=sql,
            )
        return InventoryReport(
            message=f"**{len(rows)}** {label} record(s):",
            columns=cols,
            rows=rows,
            sql_note=sql,
        )

    return None
