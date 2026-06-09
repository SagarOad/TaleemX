import os
import sys

sys.path.insert(0, os.path.join(os.path.dirname(__file__), ".."))

from services.front_office_sql import try_front_office_sql


def test_enquiry_date_includes_class_join():
    sql = try_front_office_sql("Show me 05/01/2026 admission enquiries", date_format="m/d/Y")
    assert sql
    assert "LEFT JOIN classes c ON c.id = e.class_id" in sql
    assert "DATE(e.date) = '2026-05-01'" in sql
    assert "c.`class` AS class_name" in sql


def test_visitor_date_sql_no_bad_join():
    sql = try_front_office_sql(
        "in front office Show 04/28/2026 visitors", date_format="m/d/Y"
    )
    assert sql
    assert "student_id" not in sql
    assert "LEFT JOIN staff sf ON sf.id = v.staff_id" in sql
    assert "DATE(v.date) = '2026-04-28'" in sql


def test_visitor_staff_name_filter():
    sql = try_front_office_sql(
        "Show 04/28/2026 visitors came to meet Staff (Muhammad Abdullah - 9000)",
        date_format="m/d/Y",
    )
    assert sql
    assert "9000" in sql
    assert "Muhammad Abdullah" in sql or "muhammad abdullah" in sql.lower()
