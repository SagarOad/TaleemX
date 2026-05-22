"""One-off helper: append risk + HR QA pairs to qa_pairs_business.jsonl."""
import json
from pathlib import Path

P = (
    "CASE WHEN LOWER(COALESCE(at.type, '')) IN ('p','present','1') "
    "OR LOWER(COALESCE(at.long_lang_name, '')) LIKE '%present%' THEN 1 ELSE 0 END"
)

RISK_SQL = (
    "SELECT risk_area, risk_indicator, issue_count, severity, recommended_action FROM ( "
    "SELECT 'Academic & attendance' AS risk_area, "
    "'Students below 75% attendance (month)' AS risk_indicator, COUNT(*) AS issue_count, "
    "CASE WHEN COUNT(*) >= 10 THEN 'High' WHEN COUNT(*) >= 3 THEN 'Medium' ELSE 'Low' END AS severity, "
    "'Review class attendance; contact guardians' AS recommended_action "
    "FROM (SELECT s.id FROM student_attendences sa "
    "JOIN student_session ss ON ss.id = sa.student_session_id "
    "JOIN students s ON s.id = ss.student_id "
    "LEFT JOIN attendence_type at ON at.id = sa.attendence_type_id "
    "WHERE MONTH(sa.date)=MONTH(CURDATE()) AND YEAR(sa.date)=YEAR(CURDATE()) "
    f"GROUP BY s.id HAVING ROUND(100.0*SUM({P})/NULLIF(COUNT(*),0),2)<75) t "
    "UNION ALL SELECT 'Finance','Students with outstanding fees',COUNT(*), "
    "CASE WHEN COUNT(*)>=10 THEN 'High' WHEN COUNT(*)>=3 THEN 'Medium' ELSE 'Low' END, "
    "'Fee reminders and class-wise collection drive' "
    "FROM (SELECT s.id FROM students s JOIN student_session ss ON ss.student_id=s.id "
    "JOIN student_fees_master sfm ON sfm.student_session_id=ss.id "
    "LEFT JOIN (SELECT sfd.student_fees_master_id,SUM(CAST(jt.amt AS DECIMAL(10,2))) paid "
    "FROM student_fees_deposite sfd,JSON_TABLE(sfd.amount_detail,'$.*' "
    "COLUMNS(amt VARCHAR(50) PATH '$.amount')) jt GROUP BY sfd.student_fees_master_id) p "
    "ON p.student_fees_master_id=sfm.id GROUP BY s.id "
    "HAVING COALESCE(SUM(sfm.amount),0)-COALESCE(SUM(p.paid),0)>0) f "
    "UNION ALL SELECT 'Finance','Monthly deficit (income-expense-payroll)', "
    "CASE WHEN (COALESCE((SELECT SUM(amount) FROM income "
    "WHERE MONTH(date)=MONTH(CURDATE()) AND YEAR(date)=YEAR(CURDATE())),0)"
    "-COALESCE((SELECT SUM(amount) FROM expenses "
    "WHERE MONTH(date)=MONTH(CURDATE()) AND YEAR(date)=YEAR(CURDATE())),0)"
    "-COALESCE((SELECT SUM(net_salary) FROM staff_payroll "
    "WHERE MONTH(payment_date)=MONTH(CURDATE()) AND YEAR(payment_date)=YEAR(CURDATE())),0))<0 "
    "THEN 1 ELSE 0 END, "
    "CASE WHEN (COALESCE((SELECT SUM(amount) FROM income "
    "WHERE MONTH(date)=MONTH(CURDATE()) AND YEAR(date)=YEAR(CURDATE())),0)"
    "-COALESCE((SELECT SUM(amount) FROM expenses "
    "WHERE MONTH(date)=MONTH(CURDATE()) AND YEAR(date)=YEAR(CURDATE())),0)"
    "-COALESCE((SELECT SUM(net_salary) FROM staff_payroll "
    "WHERE MONTH(payment_date)=MONTH(CURDATE()) AND YEAR(payment_date)=YEAR(CURDATE())),0))<0 "
    "THEN 'High' ELSE 'Low' END, 'Review expenses and payroll vs income' "
    "UNION ALL SELECT 'Discipline','Negative behaviour (month)',COUNT(DISTINCT si.student_id), "
    "CASE WHEN COUNT(DISTINCT si.student_id)>=5 THEN 'High' "
    "WHEN COUNT(DISTINCT si.student_id)>=1 THEN 'Medium' ELSE 'Low' END, "
    "'Behaviour support for repeat cases' "
    "FROM student_incidents si JOIN student_behaviour sb ON sb.id=si.incident_id "
    "WHERE sb.point<0 AND MONTH(si.created_at)=MONTH(CURDATE()) "
    "AND YEAR(si.created_at)=YEAR(CURDATE()) "
    "UNION ALL SELECT 'Operations','Open complaints',COUNT(*), "
    "CASE WHEN COUNT(*)>=3 THEN 'High' WHEN COUNT(*)>=1 THEN 'Medium' ELSE 'Low' END, "
    "'Close complaint loop with owners' FROM complaint "
    "WHERE action_taken IS NULL OR TRIM(COALESCE(action_taken,''))='' "
    "UNION ALL SELECT 'Admissions','Pending enquiries',COUNT(*), "
    "CASE WHEN COUNT(*)>=10 THEN 'High' WHEN COUNT(*)>=3 THEN 'Medium' ELSE 'Low' END, "
    "'Front-office follow-up on pipeline' FROM enquiry "
    "WHERE LOWER(status) IN ('active','pending','open') "
    ") r WHERE issue_count > 0 ORDER BY FIELD(severity,'High','Medium','Low'), "
    "issue_count DESC LIMIT 12"
)

GAPS_SQL = (
    "SELECT concern_area, issue_count, severity, priority_note FROM ( "
    "SELECT 'Low attendance (<75%)' AS concern_area, COUNT(*) AS issue_count, "
    "CASE WHEN COUNT(*)>=10 THEN 'High' WHEN COUNT(*)>=3 THEN 'Medium' ELSE 'Low' END AS severity, "
    "'Attendance intervention program' AS priority_note "
    "FROM (SELECT s.id FROM student_attendences sa "
    "JOIN student_session ss ON ss.id=sa.student_session_id "
    "JOIN students s ON s.id=ss.student_id "
    "LEFT JOIN attendence_type at ON at.id=sa.attendence_type_id "
    "WHERE MONTH(sa.date)=MONTH(CURDATE()) AND YEAR(sa.date)=YEAR(CURDATE()) "
    f"GROUP BY s.id HAVING ROUND(100.0*SUM({P})/NULLIF(COUNT(*),0),2)<75) a "
    "UNION ALL SELECT 'Outstanding fees',COUNT(*), "
    "CASE WHEN COUNT(*)>=10 THEN 'High' WHEN COUNT(*)>=3 THEN 'Medium' ELSE 'Low' END, "
    "'Accelerate fee collection' "
    "FROM (SELECT s.id FROM students s JOIN student_session ss ON ss.student_id=s.id "
    "JOIN student_fees_master sfm ON sfm.student_session_id=ss.id "
    "LEFT JOIN (SELECT sfd.student_fees_master_id,SUM(CAST(jt.amt AS DECIMAL(10,2))) paid "
    "FROM student_fees_deposite sfd,JSON_TABLE(sfd.amount_detail,'$.*' "
    "COLUMNS(amt VARCHAR(50) PATH '$.amount')) jt GROUP BY sfd.student_fees_master_id) p "
    "ON p.student_fees_master_id=sfm.id GROUP BY s.id "
    "HAVING COALESCE(SUM(sfm.amount),0)-COALESCE(SUM(p.paid),0)>0) b "
    "UNION ALL SELECT 'Homework with no submissions (month)',COUNT(*), "
    "CASE WHEN COUNT(*)>=20 THEN 'High' WHEN COUNT(*)>=5 THEN 'Medium' ELSE 'Low' END, "
    "'Homework completion drive' "
    "FROM homework h WHERE MONTH(COALESCE(h.homework_date,h.submit_date))=MONTH(CURDATE()) "
    "AND YEAR(COALESCE(h.homework_date,h.submit_date))=YEAR(CURDATE()) "
    "AND NOT EXISTS (SELECT 1 FROM submit_assignment sa WHERE sa.homework_id=h.id) "
    "UNION ALL SELECT 'Open complaints',COUNT(*), "
    "CASE WHEN COUNT(*)>=3 THEN 'High' WHEN COUNT(*)>=1 THEN 'Medium' ELSE 'Low' END, "
    "'Resolve grievances' FROM complaint "
    "WHERE action_taken IS NULL OR TRIM(COALESCE(action_taken,''))='' "
    "UNION ALL SELECT 'Pending staff leave',COUNT(*), "
    "CASE WHEN COUNT(*)>=5 THEN 'High' WHEN COUNT(*)>=1 THEN 'Medium' ELSE 'Low' END, "
    "'Approve or plan cover for pending leave' FROM staff_leave_request "
    "WHERE LOWER(status) IN ('pending','in progress') "
    ") z WHERE issue_count>0 ORDER BY issue_count DESC LIMIT 20"
)

DEPT_SQL = (
    "SELECT d.department_name, COUNT(s.id) AS active_staff_count "
    "FROM department d LEFT JOIN staff s ON s.department = d.id AND s.is_active = 1 "
    "GROUP BY d.id, d.department_name "
    "ORDER BY active_staff_count DESC, d.department_name LIMIT 50"
)

DEPT_LIST_SQL = (
    "SELECT d.department_name FROM department d ORDER BY d.department_name LIMIT 50"
)

DESIG_SQL = (
    "SELECT sd.designation, COUNT(s.id) AS active_staff_count "
    "FROM staff_designation sd "
    "LEFT JOIN staff s ON s.designation = sd.id AND s.is_active = 1 "
    "GROUP BY sd.id, sd.designation "
    "ORDER BY active_staff_count DESC, sd.designation LIMIT 50"
)

HR_SUMMARY_SQL = (
    "SELECT 'Active staff' AS metric, CAST(COUNT(*) AS CHAR) AS value "
    "FROM staff WHERE is_active = 1 "
    "UNION ALL SELECT 'Teachers', CAST(COUNT(DISTINCT sr.staff_id) AS CHAR) "
    "FROM staff_roles sr JOIN roles r ON r.id = sr.role_id "
    "WHERE LOWER(r.slug) = 'teacher' OR LOWER(r.name) LIKE '%teacher%' "
    "UNION ALL SELECT 'Departments', CAST(COUNT(*) AS CHAR) FROM department "
    "UNION ALL SELECT 'Pending staff leave', CAST(COUNT(*) AS CHAR) "
    "FROM staff_leave_request WHERE LOWER(status) IN ('pending','in progress') "
    "UNION ALL SELECT 'New staff this month', CAST(COUNT(*) AS CHAR) FROM staff "
    "WHERE MONTH(joining_date)=MONTH(CURDATE()) AND YEAR(joining_date)=YEAR(CURDATE())"
)

NEW_PAIRS = [
    ("biz_risk_factors", "what are the risk factors for our school", RISK_SQL, ["business", "risk", "factors"]),
    ("biz_top_risks_month", "what are our top risks this month", RISK_SQL, ["business", "risk", "top"]),
    ("biz_operational_risks", "show operational risks for the school", RISK_SQL, ["business", "risk", "operations"]),
    ("biz_school_risks_improve", "what risks should we address at school", RISK_SQL, ["business", "risk", "improve"]),
    ("biz_what_can_we_improve", "what can we improve at our school", GAPS_SQL, ["business", "risk", "improve", "gaps"]),
    ("biz_areas_to_improve", "what areas should we focus on to improve", GAPS_SQL, ["business", "risk", "improve", "priorities"]),
    ("biz_improvement_priorities", "what should we improve this month", GAPS_SQL, ["business", "risk", "improve"]),
    (
        "biz_where_lacking_improve",
        "where is our school lacking and what can we improve",
        GAPS_SQL,
        ["business", "risk", "gaps", "improve"],
    ),
    ("biz_departments_list", "what departments do we have", DEPT_LIST_SQL, ["hr", "department", "staff"]),
    ("biz_departments_headcount", "how many staff in each department", DEPT_SQL, ["hr", "department", "staff", "count"]),
    ("biz_list_all_departments", "list all departments", DEPT_LIST_SQL, ["hr", "department"]),
    ("biz_staff_by_department", "show staff count by department", DEPT_SQL, ["hr", "department", "staff"]),
    ("biz_designations_list", "what designations do we have", DESIG_SQL, ["hr", "designation", "staff"]),
    ("biz_hr_headcount_summary", "give me hr headcount summary", HR_SUMMARY_SQL, ["hr", "headcount", "summary"]),
]

if __name__ == "__main__":
    path = Path(__file__).resolve().parent.parent / "data" / "qa_pairs_business.jsonl"
    existing_ids = set()
    if path.exists():
        for line in path.read_text(encoding="utf-8").splitlines():
            if line.strip():
                existing_ids.add(json.loads(line)["id"])
    appended = 0
    with path.open("a", encoding="utf-8") as f:
        for pair_id, question, sql, tags in NEW_PAIRS:
            if pair_id in existing_ids:
                continue
            f.write(
                json.dumps(
                    {"id": pair_id, "question": question, "sql": sql, "tags": tags},
                    ensure_ascii=False,
                )
                + "\n"
            )
            appended += 1
    print(f"appended {appended} pairs to {path}")
