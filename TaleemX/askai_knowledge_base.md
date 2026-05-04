# Smart School System — Knowledge Base

This document is the authoritative reference for the Ask AI assistant integrated
into the Smart School System. It describes, in plain language, what the product
is, who uses it, and what every module does. The AI module reads this file to
answer end‑user questions about features, navigation, permissions, and typical
workflows.

---

## 1. Product Overview

Smart School System is a full-featured School / College / Institute Management
ERP built on the CodeIgniter 3 PHP framework. It is a multi-role web
application with a complementary mobile REST API, an optional Online Course
(LMS) addon, a CMS-driven public school website, and an external Ask AI
microservice.

- Backend framework: CodeIgniter 3 (MVC).
- Database: MySQL / MariaDB.
- Front end: Bootstrap + jQuery + DataTables with a branded "ss-*" design
  system (light/dark mode, RTL support, configurable brand color).
- Languages: 70+ UI translations (English, Arabic, Hindi, French, Spanish,
  Chinese, Urdu, Bengali, Tamil, Telugu, Marathi, etc.).
- Multi-tenant friendly SaaS validation library (resource quotas, storage
  checks).
- Payment gateways: Stripe, PayPal, PayU, Razorpay, Paytm, CCAvenue, Midtrans,
  Paymongo, Pesapal, Billplz, Mpesa, Mobireach, 2Checkout, Walkingm, and more.
- SMS gateways: Twilio, Nexmo, Clickatell, MSG91, Text Local, SMS Country,
  Mobireach, Africastalking, Msgnineone, Custom SMS, and a generic SMS gateway.
- Email: PHP Mailer, AWS SES (Aws_mail), custom Mailgateway, Mailer library.
- Push notifications: Firebase Cloud Messaging through `Pushnotification`
  library for the student/parent/staff mobile apps.
- File storage: Local filesystem plus AWS S3 (via `Aws3` and `Media_storage`).
- Project layout at the repository root:
  - `application/` — primary web app (controllers, models, views, libraries).
  - `api/` — separate CodeIgniter app serving the mobile REST API.
  - `addons/online_course/` — installable LMS (Online Course) addon.
  - `backend/` — shared front-end assets (CSS, JS, themes, plugins).
  - `uploads/` — user-uploaded documents and images.
  - `system/` — CodeIgniter core.
  - `temp/`, `backup/` — runtime working directories.

## 2. User Roles and Access Control

The system uses Role-Based Access Control (RBAC) implemented in
`application/libraries/Rbac.php` and `application/libraries/Auth.php`.
Permissions are grouped in three database tables:

- `permission_group` — staff/admin module permissions (shown on the admin
  sidebar).
- `permission_student` — permissions exposed to the Student role.
- `permission_parent` — permissions exposed to the Parent role.

Each permission row has a human name, a `short_code` (used throughout the
codebase, e.g. `student`, `fees_collection`, `online_admission`), flags for
view/add/edit/delete, and an `is_active` switch. The Super Admin can enable or
disable whole modules, which hides them from every sidebar instantly.

Typical roles out of the box:

- Super Admin — unrestricted access, manages modules, roles, sessions, system
  settings, and add-ons.
- Admin — day-to-day school administrator.
- Accountant — fees, income, expense, payroll, finance reports.
- Teacher — classes, subjects, timetable, homework, marks, attendance,
  online exams.
- Librarian — books and library member management.
- Receptionist — front office (admission enquiry, visitors, dispatch, phone
  calls, complaints).
- Student — own profile, fees, timetable, homework, marks, exams, library,
  transport, hostel, online courses, chat.
- Parent — view of their child(ren)'s data, online fee payment, chat, notices.

Key related files:

- `application/controllers/admin/Roles.php` + `Role_model.php` — create / edit
  roles.
- `application/controllers/admin/Users.php` + `User_model.php` — manage staff
  login accounts, assign roles, reset passwords, lock / unlock users.
- `application/controllers/admin/Module.php` + `Module_model.php` — turn
  modules on/off per role.
- `application/controllers/admin/Sidemenu.php` + `Sidebarmenu_model.php` —
  drag-and-drop ordering and visibility of the left navigation.
- `application/controllers/admin/Userlog.php` + `Userlog_model.php` — audit
  log of who logged in, when, and from which IP.

## 3. Authentication and Login

- Unified login page: `application/views/admin/login.php` served by
  `Welcome.php`. Students and parents have their own login pages under
  `application/views/user/`.
- Forgot password flow: `admin/forgotpassword.php` and `admin/change_password.php`,
  reset tokens sent by email/SMS.
- Password rehash, Bcrypt hashing, and "first login force change password"
  are handled in `Auth.php`.
- Captcha defense for login is provided by `Captcha.php` + `Captchalib.php`.
- Two-factor-style verification for high-privilege actions is available via
  `Disable_reason.php` (requires a reason before disabling users/students).

## 4. Academic Master Data

These modules define the academic scaffolding used by everything else.

- **Classes** (`Classes.php`, `class_model.php`) — define class names (e.g.,
  "Class 1", "Grade 10") and their display order.
- **Sections** (`Sections.php`, `section_model.php`) — reusable sections (A,
  B, C) that are linked to classes via `class_section`.
- **Subjects** (`Subject.php`, `subject_model.php`) — define subjects with
  type (Theory / Practical) and subject code.
- **Subject Group** (`Subjectgroup.php`, `Subjectgroup_model.php`) — grouping
  of subjects per class/section (e.g., "Science Group for Class 10-A").
- **Batch Subject** (`Batchsubject.php`, `Batchsubject_model.php`) — assign
  optional/elective subject groups to batches of students.
- **Teacher–Subject Assignment** (`Teachersubject_model.php`) — maps which
  teacher teaches which subject in which class/section.
- **Sessions** (`Sessions.php`, `Session_model.php`) — academic years/sessions
  such as 2025-2026. Super admin picks the "current session". Data is
  partitioned by session; student progression is handled through
  `stdtransfer` (Student Transfer / promote students).
- **School Houses** (`Schoolhouse.php`, `Schoolhouse_model.php`) — traditional
  house groups (Red, Blue, Green, Yellow).
- **Holiday** (`Holiday.php`, `Holiday_model.php`) — official holidays shown
  in the calendar and excluded from attendance.
- **Calendar / Events** (`Calendar.php`, `Calendar_model.php`) — school-wide,
  class-wide, or role-specific events, rendered with FullCalendar.
- **Timeline** (`Timeline.php`, `Timeline_model.php`) — school news board and
  per-staff / per-student timeline posts with attachments.

## 5. Student Management

- Controller: `application/controllers/admin/Student.php` (very large; single
  file that handles list, add, edit, view, search, import, bulk delete,
  enable/disable, profile photo, documents, custom fields, timeline,
  multi-class students).
- Model: `application/models/Student_model.php` (core student CRUD and
  queries used by most other modules).
- Public-facing student controllers: `application/controllers/students/`
  and `application/controllers/user/`.
- Views live in `application/views/student/` (create/edit/list, profile,
  admission slip, ID card preview).
- Related tables and concepts:
  - `student` — core profile (name, DOB, gender, contact).
  - `student_session` — links student to a class/section for a given
    academic session. Historical data is preserved here.
  - `students_custom_fields` — dynamic fields defined via
    `admin/Customfield.php`.
  - Guardian/parent details, sibling tracking, transport route, hostel room,
    RFID number, category, religion, caste.
- Features:
  - Single-student and bulk CSV import with template download.
  - Student ID card generation (`admin/Studentidcard.php` +
    `Generateidcard.php` + `student_id_card_model.php`).
  - Admit card generation (`Admitcard.php`, `admitcard_model.php`).
  - Certificate generation (`Certificate.php` + `Generatecertificate.php`).
  - Transfer certificate (`Transfercertificate.php`,
    `Transfercertificate_model.php`).
  - Student timeline with images and documents.
  - Multi-class student support (same student in multiple classes for
    vocational/extra-curricular subjects).
  - Disable student with reason (`Disable_reason.php`) — audited in user log.
  - Student profile fields editable by the student can be controlled via
    `Student_edit_field_model.php`.

## 6. Staff / HR Management

- **Staff Directory** (`admin/Staff.php`, `Staff_model.php`) — full employee
  records: personal details, joining date, department, designation,
  qualifications, work experience, leaves, documents, salary details, bank
  info, contract, social IDs, EPF/ESI numbers, resume attachments.
- **Departments** (`admin/Department.php`) and **Designations**
  (`admin/Designation.php`).
- **Staff ID Card** (`admin/Staffidcard.php` + `Generatestaffidcard.php`).
- **Leave Management**:
  - `admin/Leavetypes.php` — define leave categories (Casual, Sick, etc.).
  - `admin/Approve_leave.php` / `admin/Leaverequest.php` — submit, approve,
    reject leave applications (with document upload).
  - `Apply_leave_model.php`, `Leaverequest_model.php`, `Leavetypes_model.php`.
- **Staff Attendance** (`admin/Staffattendance.php`, `Staffattendancemodel.php`,
  `StaffAttendaceSetting_model.php`) — daily attendance with configurable
  types (Present, Absent, Late, Half-day, Holiday); biometric import
  supported through `Biometric.php`.
- **Payroll** (`admin/Payroll.php`, `Payroll_model.php`, config
  `application/config/payroll.php`) — salary templates, allowances,
  deductions, monthly payroll generation, payslip PDF.
- **Timeline** for staff (`staff_timeline.php`).

## 7. Attendance

- **Student Attendance** (`admin/Stuattendence.php`, `Stuattendence_model.php`)
  — daily class-level attendance. Configurable attendance types from
  `Attendencetype_model.php`.
- **Subject-wise Attendance** (`admin/Subjectattendence.php`,
  `Studentsubjectattendence_model.php`) — attendance taken per subject per
  period; useful for higher education.
- **Attendance Settings** (`StudentAttendaceSetting_model.php`) — SMS/email
  notification on absent, threshold warnings.
- **Attendance Reports** (`Attendencereports.php` + views in
  `application/views/attendencereports/`) — monthly, date-range, absent-list,
  low-attendance, and percentage reports with export to PDF/CSV.

## 8. Examinations

- **Exam** (`admin/Exam.php`, `Exam_model.php`) — define exam names.
- **Exam Schedule** (`admin/Examschedule.php`, `admin/Exam_schedule.php`,
  `Examschedule_model.php`) — map exams to classes/sections with subject
  date, time, room, and max marks.
- **Exam Group** (`admin/Examgroup.php`, `Examgroup_model.php`,
  `Examgroupstudent_model.php`, `Examsubject_model.php`) — group multiple
  exams (e.g., Term 1: FA1 + FA2 + SA1) for consolidated reporting.
- **Marks Entry** (`admin/Mark.php`, `Mark_model.php`, views under
  `application/views/admin/mark/`) — grid-style marks entry per subject.
- **Exam Results** (`admin/Examresult.php`, `Examresult_model.php`) — publish,
  lock, unlock results.
- **Marks Division / Grades** (`admin/Marksdivision.php`,
  `Marksdivision_model.php`, `admin/Grade.php`, `Grade_model.php`) — grading
  scales (A+, A, B etc.) and division/class awarded.
- **Marksheet** (`admin/Marksheet.php`, `Marksheet_model.php`) — customisable
  marksheet templates and PDF generation.
- **Admit Card** — see Student Management.
- **Report Card / Reports** (`Report.php`) — large general reporting
  controller for exam reports, tabulation sheets, ranking, progress reports.

## 9. Online Exam

- **Online Exam** (`admin/Onlineexam.php`, `Onlineexam_model.php`,
  `Onlineexamquestion_model.php`, `Onlineexamresult_model.php`,
  `Question_model.php`).
- Controller `admin/Question.php` manages a reusable question bank with
  multiple-choice, true/false, and short-answer questions (with images).
- Exams are timed, randomised, with auto-grading for objective questions.
- Students launch exams from their portal under
  `application/controllers/students/` and their mobile app via
  `api/application/controllers/Webservice.php`.
- Results include score, correct/incorrect answers, time taken, leaderboard.

## 10. Syllabus & Lesson Plan

- **Syllabus** (`admin/Syllabus.php`, `Syllabus_model.php`) — upload subject
  syllabus with attachment; visible to students/parents.
- **Lesson Plan** (`admin/Lessonplan.php`, `Lessonplan_model.php`) — chapter
  and topic breakdown, scheduled delivery dates, status (pending/complete),
  teacher assignment.

## 11. Homework

- Controller `application/controllers/Homework.php` (shared by admin/teacher)
  and model `Homework_model.php`.
- Features: create homework per class/section/subject, attach files, set
  submission date, mark evaluation status (complete / incomplete), leave
  teacher comments, bulk email/SMS notify.
- Student submissions accepted from the student portal and mobile API.

## 12. Timetable

- **Class Timetable** (`admin/Timetable.php`, `Timetable_model.php`) —
  weekly period grid per class/section with subject, teacher, room, time.
- **Subject Timetable** (`Subjecttimetable_model.php`) — period-wise subject
  mapping used in subject attendance and teacher dashboards.
- `Class_section_time_model.php` — default day start/end times per class.

## 13. Library

- **Books** (`admin/Book.php`, `Book_model.php`) — title, author, ISBN,
  publisher, copies, price, rack, subject.
- **Issue / Return** (`admin/Issueitem.php` inside the Library module;
  `Bookissue_model.php`) — issue book to member, mark returned, fine
  calculation.
- **Library Members** (`admin/Member.php`, `Librarymember_model.php`,
  `Librarymanagement_model.php`) — students and staff can be library members
  with a library card number.
- **Librarians** (`admin/Librarian.php`, `Librarian_model.php`) — dedicated
  librarian staff accounts.

## 14. Inventory (Stores & Supplies)

- **Item Category** (`admin/Itemcategory.php`).
- **Item** (`admin/Item.php`, `Item_model.php`).
- **Item Store** (`admin/Itemstore.php`) — locations where items are stored.
- **Item Supplier** (`admin/Itemsupplier.php`).
- **Item Stock** (`admin/Itemstock.php`, `Itemstock_model.php`) — purchases,
  quantity in hand.
- **Issue Item** (`admin/Issueitem.php`, `Itemissue_model.php`) — issue items
  to staff/students/departments and mark returns.

## 15. Transport

- **Vehicles** (`admin/Vehicle.php`, `Vehicle_model.php`) — bus/van details,
  registration, insurance expiry, driver contact.
- **Routes** (`admin/Route.php`, `Route_model.php`) — named routes with
  origin, end point, total distance.
- **Pickup Points** (`admin/Pickuppoint.php`, `Pickuppoint_model.php`) — stops
  along a route with pickup time and monthly fee.
- **Route Pickup Points** (`Routepickuppoint_model.php`) — the many-to-many
  link between routes and their stops.
- **Vehicle–Route** (`admin/Vehroute.php`, `Vehroute_model.php`) — which
  vehicle runs which route on which shift.
- **Student Transport Fee** (`Studenttransportfee_model.php`,
  `Transportfee_model.php`) — auto-generate monthly transport fees per student.

## 16. Hostel

- **Hostel** (`admin/Hostel.php`, `Hostel_model.php`) — boys'/girls' hostels
  with address and warden contact.
- **Room Type** (`admin/Roomtype.php`, `Roomtype_model.php`) — AC/Non-AC,
  Single/Double/Dormitory, with cost.
- **Hostel Rooms** (`admin/Hostelroom.php`, `Hostelroom_model.php`) — room
  numbers, capacity, current occupants.

## 17. Front Office

- **Admission Enquiry** (`admin/Enquiry.php`, `Enquiry_model.php`) — capture
  leads with source, assigned-to, follow-up dates, notes.
- **Visitors** (`admin/Visitors.php`, `Visitors_model.php`,
  `Visitors_purpose_model.php`) — log visitor name, purpose, who met, time
  in/out, photo.
- **Phone Call / General Call** (`Generalcall.php`, `General_call_model.php`)
  — record incoming/outgoing calls.
- **Dispatch / Receive** (`admin/Dispatch.php`, `admin/Receive.php`,
  `Dispatch_model.php`) — postal register for letters/parcels sent and
  received.
- **Complaint** (`admin/Complaint.php`, `Complaint_Model.php`,
  `admin/Complainttype.php`, `ComplaintType_model.php`) — log complaints with
  type, source, status, action taken.
- **References** (`admin/Reference.php`, `Reference_model.php`) and
  **Sources** (`admin/Source.php`, `Source_model.php`) — lookup dropdowns for
  enquiry and admission origin.

## 18. Fees Collection

One of the most feature-rich subsystems. Main files:

- `application/controllers/Studentfee.php` (collection UI, receipt printing,
  fine and discount application).
- `application/models/Studentfeemaster_model.php` (very large; master ledger
  model covering invoice generation, payment, balance, discount, refund).
- `application/models/Studentfee_model.php`.
- `application/controllers/admin/Feemaster.php` and `admin/Feegroup.php`,
  `admin/Feecategory.php`, `admin/Feetype.php`, `admin/Feediscount.php`,
  `admin/Feereminder.php`, `admin/Feesforward.php`.

Concepts:

- **Fee Type** — atomic fee lines (Tuition, Exam, Library).
- **Fee Group** — bundle of fee types (e.g., "Class 10 Annual").
- **Fee Master** — schedule of due dates and amounts for a fee group.
- **Fee Session Group** (`Feesessiongroup_model.php`) — assigns fee groups
  to a specific class/section in a specific session.
- **Fee Category** — used for transport, hostel, and optional fees.
- **Fee Discount** — percent or flat discounts applied per group or per
  student (`StudentAppliedDiscount_model.php`).
- **Fee Reminder** — scheduled email/SMS reminders before and after due date.
- **Fees Forward** — carry forward previous dues into new session.
- **Balance Fees** (`Balancefees.php`, `Balancefees_model.php`) — outstanding
  dues report.
- **Offline Payment** (`admin/Offlinepayment.php`, `OfflinePayment_model.php`)
  — parents upload proof of bank transfer; admin approves it.
- **Online Payment** — integrated gateways (see Payment Gateways section).
- **Receipts / Transactions** (`admin/Transaction.php`) — list, filter,
  reprint.
- Receipt PDF template: `backend/fee_receipt_pdf_style.css`.

## 19. Income & Expense

- **Income Heads** (`admin/Incomehead.php`, `Incomehead_model.php`).
- **Income Entries** (`admin/Income.php`, `Income_model.php`).
- **Expense Heads** (`admin/Expensehead.php`, `Expensehead_model.php`).
- **Expenses** (`admin/Expense.php`, `Expense_model.php`) — attach bill
  images, vendor details.
- **Finance Reports** (`Financereports.php` + views in
  `application/views/financereports/`) — daily/monthly/yearly summaries,
  fee collection vs expense, income-by-head, transaction ledger, balance
  sheet, transport fees collection, hostel fees.

## 20. Accountant

- Separate role managed through `Accountant_model.php`, allowed access to
  fees, income, expense, payroll, and finance reports only.

## 21. Communication

- **Chat** (`admin/Chat.php`, `Chat_model.php`, `Chatuser_model.php`) —
  internal messaging between staff, students, and parents. Supports
  group/broadcast messages.
- **Mail/SMS Campaign** (`admin/Mailsms.php`, `Messages_model.php`,
  `Mailsmsconf.php`) — bulk email + SMS + push notification to selected
  audience (class/section/role/custom). Template-driven.
- **Notifications** (`admin/Notification.php`, `Notification_model.php`,
  `Notificationsetting_model.php`) — per-event notification switches
  (new fee, exam schedule, homework, attendance, etc.) and notification
  center for the logged-in user.
- **Notice Board / Send Notification** — see also `Notice` entries in the
  sidebar; images stored in `uploads/notice_board_images/`.
- **Share Content** (`Sharecontent_model.php`) — send downloadable content
  (videos, assignments, documents) to selected classes.
- **Content Management** (`admin/Content.php`, `Content_model.php`,
  `admin/Contenttype.php`, `Contenttype_model.php`) — internal content library
  for uploading lecture videos, PDF notes, documents, segmented by type.
- **Video Tutorial** (`admin/Video_tutorial.php`, `Video_tutorial_model.php`)
  — YouTube/Vimeo/Upload tutorials shared with classes.
- **Upload Content** (`Uploadcontent_model.php`) — general document sharing
  helper shared across homework, syllabus, content.
- **Push Notification** (`application/libraries/Pushnotification.php`) —
  wrapper around Firebase HTTP v1 used by API endpoints to push events to
  the student and parent mobile apps.

## 22. Alumni

- Controllers: `admin/Alumni.php`; model `Alumni_model.php`.
- Features: alumni directory, alumni events, alumni gallery, import ex-students
  from existing student records.

## 23. Online Admission

- `application/controllers/onlineadmission/` (public form) and
  `application/controllers/admin/Onlineadmission.php` (review, approve,
  convert into student). Model `Onlinestudent_model.php`.
- Supports configurable application fee with online payment before approval.
- Admission form uploads saved in `uploads/admission_form/`.

## 24. Online Course (LMS) — Addon

Installed from `addons/online_course/` (installer + uninstaller + SQL). When
active, exposes a full LMS:

- **Courses** (`application/controllers/Course.php`, `Course_model.php`) —
  course title, thumbnail, summary, level, language, duration, price,
  category, tags, SEO fields, certificate template.
- **Categories & Tags** (`Coursecategory_model.php`, `Coursetag_model.php`).
- **Sections & Lessons** (`Coursesection_model.php`, `Courselesson_model.php`)
  — lessons with video (upload/YouTube/Vimeo/URL), attached files, duration.
- **Quizzes & Exams** (`Coursequiz_model.php`, `Courseexam_model.php`,
  `Courseexamquestion_model.php`) — per-course timed tests.
- **Assignments** (`Courseassignment_model.php`) — uploadable assignments
  with grading.
- **Certificates** (`Coursecertificate_model.php`) — auto-issued on course
  completion, printable PDF.
- **Enrolments / Payments** (`Studentcourse_model.php`, `Course_payment_model.php`,
  `Courseofflinepayment_model.php`) — paid or free enrolment, coupon support.
- **Reports** (`Coursereport_model.php`) — per-student progress, quiz scores,
  sales.
- **Cart & Checkout** (`Cart.php`, `Checkout.php`) — course shopping cart
  with multiple gateways.
- Storefront views: `application/views/onlinecourse/` (course listing,
  detail, player). Public controllers in
  `application/controllers/onlinecourse/`.
- Dedicated mail/SMS helper: `application/libraries/Course_mail_sms.php`.
- Dedicated gateways: `Course_stripe_payment.php`, `Course_paypal_payment.php`,
  `api/application/controllers/course_payment/`.

## 25. Front CMS (Public School Website)

- Controller `application/controllers/Site.php` renders the public front-end.
- Admin editor: `application/controllers/admin/Frontcms.php` and
  `application/controllers/admin/front/` + views in
  `application/views/admin/frontcms/`.
- Models: `Cms_page_model.php`, `Cms_page_content_model.php`,
  `Cms_menu_model.php`, `Cms_menuitems_model.php`, `Cms_program_model.php`,
  `Cms_media_model.php`, `Frontcms_setting_model.php`.
- Features: drag-and-drop page sections, hero sliders, gallery, news,
  programs, contact form, menu builder, multilingual pages, SEO metadata,
  theme selection (`admin/Theme.php`, `FrontTheme.php`).

## 26. Certificates

- **Student Certificate** (`admin/Certificate.php`, `Certificate_model.php`)
  — designer for certificate templates with dynamic placeholders.
- **Generate Certificate** (`admin/Generatecertificate.php`,
  `Generatecertificate_model.php`) — bulk generation and printing.
- **Transfer Certificate** (`admin/Transfercertificate.php`) — final TC when
  a student leaves the school; PDF stored in
  `uploads/transfer_certificate/`.
- **ID Card Templates** (`admin/Generateidcard.php`,
  `admin/Staffidcard.php`, `admin/Generatestaffidcard.php`) — visual
  designer for student and staff ID cards.

## 27. Reports

- General `Report.php` — large consolidated reports controller with
  dozens of report types (student, staff, attendance, exam, finance,
  transport, hostel, library, inventory).
- Sub-modules with their own report controllers/views:
  - `Attendencereports.php`
  - `Financereports.php`
  - `Balancefees.php`
- Views live in `application/views/reports/`, `application/views/attendencereports/`,
  `application/views/financereports/`, `application/views/balancefees/`.
- Most reports support CSV / PDF / Excel export and print.

## 28. System Settings & Administration

- **General Settings** (`admin/Schsettings.php`, `Schsettings.php`,
  `Setting_model.php`) — school name, logo, address, currency, date format,
  timezone, language, session start month, branding colours, PDF header/
  footer, favicon.
- **Payment Gateway Settings** (`admin/Paymentsettings.php`,
  `Paymentsetting_model.php`) — enable/disable gateways and store API keys.
- **Email Config** (`Emailconfig.php`, `Emailconfig_model.php`) — SMTP
  settings, from address, test email.
- **SMS Config** (`Smsconfig.php`, `Smsconfig_model.php`) — select SMS gateway
  and credentials.
- **Print Header / Footer** (`admin/Print_headerfooter.php`) — customise
  printed documents (receipts, marksheets, ID cards).
- **Roles & Permissions** (`admin/Roles.php`, `admin/Users.php`).
- **Modules On/Off** (`admin/Module.php`).
- **Sidebar Menu Builder** (`admin/Sidemenu.php`).
- **Custom Fields** (`admin/Customfield.php`, `Customfield_model.php`) — add
  arbitrary fields to student, staff, enquiry, visitor, online admission.
- **System Fields** (`admin/Systemfield.php`) — toggle built-in fields as
  required/visible.
- **Currency** (`admin/Currency.php`, `Currency_model.php`).
- **Languages** (`admin/Language.php`, `Language_model.php`,
  `Langpharses_model.php`) — manage language phrases and active languages.
- **File Types** (`Filetype_model.php`, views in `admin/filetype.php`).
- **Media Storage / AWS S3** (`Media_storage.php`, `Aws3.php`) — switch uploads
  between local disk and S3; includes migration helper.
- **Backup** (`admin/backup.php`) — database and uploads backup/restore.
- **Updater** (`admin/Updater.php`) — apply patch zip, DB migrations
  (`application/controllers/Migrate.php`, CI migrations in
  `application/migrations/` referenced by `application/config/migration.php`).
- **Add-ons** (`admin/Addons.php`, `Addons_model.php`) — install / uninstall
  optional modules (currently the Online Course LMS).
- **User Log** (`admin/Userlog.php`, `Userlog_model.php`) — login/logout
  audit and action log (writes from most models via `MY_Model::log`).
- **Audit** (`admin/Audit.php`, `Audit_model.php`) — record-level audit trail
  for sensitive changes.
- **Captcha** (`admin/Captcha.php`) — refresh captcha for forms.

## 29. Ask AI (In-App Assistant)

- Controller: `application/controllers/admin/Askai.php`.
- Config: `application/config/askai.php` (`askai_api_url`; overridable via
  the `ASKAI_API_URL` environment variable; defaults to
  `https://ai.pixciletechnologies.com/ask`).
- View: `application/views/admin/askai/index.php` — chat UI with sidebar
  conversation history, using the shared `ss-*` design tokens so it follows
  the school's brand colour and dark / light / RTL mode automatically.
- Flow: the browser POSTs the question to `/admin/askai/ask` on the same
  origin. The controller forwards it via cURL (JSON `{question}`) to the
  configured AI microservice and streams back `{answer}`. This avoids
  mixed-content issues when the site is served over HTTPS.
- The AI microservice is expected to answer using this knowledge base
  document as its grounding source.

## 30. Mobile API (REST)

Independent CodeIgniter app under `api/` with its own `application/`,
routing, and deploy path.

- `api/application/controllers/Webservice.php` — the main REST controller
  (≈6,800 lines). Covers login, student profile, timetable, homework,
  attendance, fees (including creating payment intents), exams (offline and
  online), syllabus, calendar, chat, leave application, transport, hostel,
  video tutorials, visitors, online admission status, course enrolments,
  notifications, and staff features.
- `api/application/controllers/Payment.php` and `Payment_request.php` —
  dedicated payment flows (initiate, callback, verify) for the mobile app.
- `api/application/controllers/gateway/` and `gateway_ins/` — gateway-specific
  callbacks (Razorpay, Paytm, Stripe, PayPal, PayU, Midtrans, Paymongo,
  Billplz, etc.).
- `api/application/controllers/course_payment/` — online course checkout
  from the mobile app.
- Auth is API key + per-user token based (`auth_model`, `Auth.php`).
- JSON responses are emitted via helper `json_output()`; timezone honours
  the school's system settings.

## 31. Webhooks & Integrations

- `application/controllers/Webhooks.php` and `api/application/controllers/
  Webhooks.php` — endpoints hit by external services (payment IPNs, SMS
  delivery reports).
- `application/controllers/Cron.php` — scheduled jobs such as fee reminders,
  birthday wishes, daily attendance email, backup, push reminders.
- `application/controllers/Biometric.php` — consumes biometric device
  punch-in/out feeds to populate attendance.

## 32. Supporting Libraries (in `application/libraries/`)

- `Auth.php` — session, login, permission checks, module gating.
- `Rbac.php` — permission helper used in views and controllers.
- `Customlib.php` — shared helpers used almost everywhere (date helpers,
  string format, academic session resolution, permission shortcuts).
- `Module_lib.php` / `Studentmodule_lib.php` — module activation checks.
- `Mailer.php`, `Mailgateway.php`, `Mailsmsconf.php`, `Aws_mail.php`,
  `PHPMailer/` — email delivery stack.
- `Smsgateway.php`, `Twilio.php`, `Nexmo_lib.php`, `Clickatell.php`,
  `Textlocalsms.php`, `Smscountry.php`, `Mobireach_lib.php`,
  `Africastalking_lib.php`, `Msgnineone.php`, `Smseg_lib.php`, `Customsms.php`,
  `Bulk_sms_lib.php` — SMS delivery.
- `Paypal_lib.php`, `Stripe_payment.php`, `Billplz_lib.php`, `Mpesa_lib.php`,
  `Paytm_lib/`, `Midtrans_lib.php`, `Paymongo.php`, `Pesapal_lib.php`,
  `Twocheckout_payment.php`, `Walkingm_lib.php`, `Paypal_payment.php`,
  `Ccavenue_crypto.php` — payment gateway clients.
- `Form_builder.php` — dynamic form rendering used by custom fields and
  admissions.
- `Datatables.php` — server-side DataTables helper for admin lists.
- `Ajax_pagination.php` — AJAX paginator.
- `ImageResize.php`, `QR_Code.php`, `Slug.php` — utility helpers.
- `SaasValidation.php`, `ResourceQuota.php` — enforce resource quotas when
  operated as SaaS.
- `Version_control.php`, `Db_manager.php` — used by the updater.
- `Zend/`, `Adler32.php`, `Aes.php`, `Enc_lib.php`, `Encoding_lib.php` —
  cryptographic helpers.
- `M_pdf.php` — mPDF wrapper for PDFs.

## 33. Helpers, Config, and Hooks

- `application/helpers/` (see `application/config/autoload.php`) — custom
  helpers for permissions, sidebar rendering, logging, image handling,
  data formatting, and template helpers consumed across views.
- `application/config/` key files:
  - `config.php`, `database.php`, `autoload.php`, `routes.php`,
    `constants.php`, `ss-constants.php` — framework configuration.
  - `app-config.php`, `saas-config.php`, `maintenance_config.php`,
    `license.php` — product-level flags.
  - `custom_filed-config.php`, `front_office.php`, `form-builder.php`,
    `onlinecourse-config.php`, `onlinecoursedata.php`, `thumbnail.php`,
    `payroll.php`, `mailsms.php` — module-specific constants.
  - `askai.php` — Ask AI endpoint (see §29).
- `application/hooks/` wires pre-controller hooks (session, language,
  license).

## 34. Data Storage (`uploads/` Layout)

Each module writes to a dedicated folder, making backup and retention simple:

- `student_images/`, `student_documents/`, `student_id_card/`,
  `student_timeline/`, `student_leavedocuments/`.
- `staff_images/`, `staff_documents/`, `staff_id_card/`, `staff_timeline/`.
- `teacher_images/`, `accountant_images/`, `librarian_images/`,
  `guest_images/`.
- `admission_form/`, `admit_card/`, `marksheet/`, `certificate/`,
  `transfer_certificate/`.
- `book/`, `homework/`, `syllabus_attachment/`, `school_content/`,
  `onlinexam_images/`.
- `course/`, `course_content/`, `video_tutorial/`.
- `communicate/`, `notice_board_images/`, `gallery/`, `front_office/`.
- `inventory_items/`, `school_expense/`, `school_income/`,
  `offline_payments/`.
- `transport_vehicle/`, `vehicle_photo/`.
- `print_headerfooter/`, `addon_images/`.
- `no_image.png` — default avatar/fallback image.

## 35. Typical End-User Workflows

- **Admit a new student**: Front Office records enquiry → Online Admission
  form (or manual Student → Add) → assign class/section/session → fees
  automatically generated from Fee Session Group → ID card printed → login
  credentials emailed.
- **Collect fees**: Admin opens Collect Fees → search student → add payment
  (cash or online gateway) → receipt printed; online payments captured via
  gateway webhooks (`Webhooks.php`).
- **Take attendance**: Teacher opens Student Attendance → select class/
  section/date → mark present/absent → save; SMS/email goes to parents of
  absentees.
- **Conduct an exam**: Create Exam → add Exam Schedule → enter marks →
  publish result; students see result from portal or mobile app.
- **Online course sale**: Admin creates course → publishes → student adds
  to cart → pays via Stripe/PayPal → gains access to lessons → earns
  certificate on completion.
- **Parent daily use**: Login → view child's attendance, homework, exam
  results, pay pending fees, chat with teacher, receive push notifications.

## 36. Ask AI Grounding Rules

When answering questions, the AI assistant should:

1. Prefer answers grounded in this document.
2. Use the terms the UI uses (exact module names above: "Fees Collection",
   "Online Admission", "Front Office", etc.).
3. Mention the sidebar path when guiding navigation
   (e.g., "Front Office → Visitor Book").
4. If the user asks about a feature that requires a permission, mention the
   role that typically has it (Super Admin / Admin / Accountant / Teacher
   / Librarian / Receptionist / Student / Parent).
5. If a feature depends on the Online Course add-on, say so explicitly.
6. For payment / SMS / email questions, list the supported gateways from
   §2 and §32, and point users to the matching settings page.
7. If the question is about the mobile app, reference the REST API in §30.
8. When unsure, ask a clarifying question rather than inventing features
   or file paths not mentioned here.

---

End of knowledge base.
