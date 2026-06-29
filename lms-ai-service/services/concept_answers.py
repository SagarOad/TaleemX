"""
Fast, deterministic explanations for TaleemX module concepts (no SQL / LLM loop).
"""

from __future__ import annotations

import re
from typing import Optional


_EXPLAIN_MARKERS = re.compile(
    r"\b(?:explain|what\s+is|describe|tell\s+me\s+about|meaning|purpose|how\s+does)\b"
)


def is_explain_concept_question(question: str) -> bool:
    return bool(_EXPLAIN_MARKERS.search((question or "").lower()))


def try_concept_answer(question: str) -> Optional[str]:
    q = (question or "").lower().strip()
    if not q:
        return None

    if re.search(r"\bdisable[\s-]?reasons?\b", q) and _EXPLAIN_MARKERS.search(q):
        return (
            "The **Disable Reason** feature in TaleemX lives under "
            "**Student Information → Disable Reason**. It is a master list of "
            "standard reasons—such as transfer to another school, graduation, "
            "mid-year withdrawal, or fee default—that staff must pick when "
            "disabling a student account. You add, edit, or remove these reason "
            "codes on that screen; they are setup data, not live analytics.\n\n"
            "When an admin disables a student from the student list or profile, "
            "TaleemX requires one of these reasons and optionally a short remark. "
            "The choice is saved on the student record and the action is logged in "
            "the user audit log, so you always know who was deactivated and why.\n\n"
            "To review actual disabled students and their reasons, ask a data "
            "question such as **show disabled students** or **list students "
            "disabled due to transfer**—Ask AI can pull those rows from the database."
        )

    if re.search(r"\bquick\s+fee", q) and re.search(
        r"\b(?:explain|what\s+is|describe|meaning)\b", q
    ):
        return (
            "**Quick fees** in TaleemX are **custom installment fees** (fee groups with "
            "nature `custom`) that you can assign and collect outside the regular fee schedule. "
            "Open **Fee Collection → Collect Fees** or **Quick Fees** to add installments for a "
            "student; collected quick fees appear in the student's fee deposit history."
        )

    if re.search(r"\bcarry[\s-]?forward\b", q) and re.search(
        r"\b(?:explain|what\s+is|report|describe)\b", q
    ):
        return (
            "The **Carry Forward Fees** report lists **unpaid balances moved into the current "
            "session** from a previous session. Use **Fee Collection → Carry Forward Fees** to "
            "review students with forwarded dues before collecting them in the new year."
        )

    if re.search(r"\bsearch\s+due\s+fee|\bfeesearch\b|\bdue\s+fees\b", q) and re.search(
        r"\b(?:explain|what\s+is|how)\b", q
    ):
        return (
            "**Search due fees** (**Fee Collection → Search due fees**) lists students who still "
            "owe regular or transport fees. Balance = **due amount − (paid + discount)** from each "
            "fee line's deposit JSON — the same logic Ask AI uses for remaining-fee questions."
        )

    if (
        "income" in q
        and "expense" in q
        and _EXPLAIN_MARKERS.search(q)
        and re.search(r"\b(?:report|statement|versus|vs|comparison|compare)\b", q)
    ):
        return (
            "The **Income vs Expense** report in TaleemX compares **money coming in** "
            "against **money going out** for a chosen period, so leadership can see whether "
            "the school ran a surplus or a deficit. Open **Finance → Income vs Expense** (the "
            "income and expense screens live under the Income and Expenses modules).\n\n"
            "**Income** rows come from the `income` table, each tagged with an **income head** "
            "(`income_head` — e.g. donations, rent, miscellaneous fees). **Expense** rows come "
            "from the `expenses` table, each tagged with an **expense head** (`expense_head` — "
            "e.g. salaries, utilities, maintenance). The report sums each side over the date "
            "filter and shows the **net balance** (total income − total expense); note that "
            "regular tuition collected through the Fees module is tracked separately in the "
            "fee collection reports.\n\n"
            "For live numbers, ask a data question such as **show income vs expense for this "
            "month**, **total income this year**, or **list expenses by head**."
        )

    if re.search(r"\bfee\s+collection\b", q) and _EXPLAIN_MARKERS.search(q):
        if re.search(r"\bclass[\s-]?wise\b", q):
            return (
                "A **class-wise fee collection** report rolls up fee payments **by class** for a "
                "chosen period (for example, this June). Instead of listing every receipt line, "
                "you see how much each grade contributed—paying students and total collected per "
                "class—so leadership can spot strong or weak collection by grade.\n\n"
                "In TaleemX this comes from the same payment ledger as the main **Collection "
                "Report** (**Finance → Collection Report** / **Fees Collection → Reports**): each "
                "deposit in `student_fees_deposite` is tied to a student session, class, fee group, "
                "and fee type. Class-wise views aggregate those payment amounts after filters.\n\n"
                "To pull the numbers, ask a data question such as **show June fee collection "
                "class-wise** or **how much fee was collected in April by class**."
            )
        return (
            "The **Fee Collection Report** in TaleemX summarizes **fee payments received** in a "
            "date range. Open **Finance → Collection Report** (or the fees collection reports under "
            "**Fees Collection**). Each row links a student to a **fee group** and **fee type**, "
            "showing amount paid, discount, fine, payment mode, and who recorded the collection.\n\n"
            "The report reads from the fee deposit ledger (`student_fees_deposite`), including "
            "regular fees and transport fees when that module is enabled. You can filter by date, "
            "fee type, class, section, fee group, and receiving staff—matching how admins reconcile "
            "daily or monthly collections.\n\n"
            "For actual totals, ask **show fee collection for April** or **how much fee was "
            "collected this month**; for a breakdown by grade, ask **show June fee collection "
            "class-wise**."
        )

    return None
