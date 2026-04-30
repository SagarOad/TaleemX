-- db/init/02_lms_schema_seed.sql
-- Creates a realistic LMS schema and seeds sample data for local API testing.

CREATE TABLE IF NOT EXISTS users (
  id INT PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  email VARCHAR(255) UNIQUE NOT NULL,
  role ENUM('student','teacher','admin') NOT NULL,
  created_at DATETIME NOT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1
);

CREATE TABLE IF NOT EXISTS courses (
  id INT PRIMARY KEY,
  title VARCHAR(255) NOT NULL,
  description TEXT,
  teacher_id INT NOT NULL,
  category VARCHAR(100),
  status ENUM('draft','published','archived') NOT NULL DEFAULT 'draft',
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  FOREIGN KEY (teacher_id) REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS enrollments (
  id INT PRIMARY KEY,
  student_id INT NOT NULL,
  course_id INT NOT NULL,
  enrolled_at DATETIME NOT NULL,
  status ENUM('active','completed','dropped') NOT NULL DEFAULT 'active',
  progress_percent DECIMAL(5,2) NOT NULL DEFAULT 0,
  FOREIGN KEY (student_id) REFERENCES users(id),
  FOREIGN KEY (course_id) REFERENCES courses(id)
);

CREATE TABLE IF NOT EXISTS lessons (
  id INT PRIMARY KEY,
  course_id INT NOT NULL,
  title VARCHAR(255) NOT NULL,
  content TEXT,
  video_url VARCHAR(500),
  subtitles TEXT,
  order_index INT NOT NULL DEFAULT 1,
  duration_seconds INT NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL,
  FOREIGN KEY (course_id) REFERENCES courses(id)
);

CREATE TABLE IF NOT EXISTS assignments (
  id INT PRIMARY KEY,
  course_id INT NOT NULL,
  lesson_id INT NOT NULL,
  title VARCHAR(255) NOT NULL,
  description TEXT,
  due_date DATETIME,
  max_score DECIMAL(8,2) NOT NULL DEFAULT 100,
  FOREIGN KEY (course_id) REFERENCES courses(id),
  FOREIGN KEY (lesson_id) REFERENCES lessons(id)
);

CREATE TABLE IF NOT EXISTS submissions (
  id INT PRIMARY KEY,
  assignment_id INT NOT NULL,
  student_id INT NOT NULL,
  submitted_at DATETIME,
  score DECIMAL(8,2),
  feedback TEXT,
  status ENUM('pending','graded','late') NOT NULL DEFAULT 'pending',
  FOREIGN KEY (assignment_id) REFERENCES assignments(id),
  FOREIGN KEY (student_id) REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS quiz_results (
  id INT PRIMARY KEY,
  student_id INT NOT NULL,
  course_id INT NOT NULL,
  quiz_name VARCHAR(255) NOT NULL,
  score DECIMAL(8,2) NOT NULL,
  max_score DECIMAL(8,2) NOT NULL DEFAULT 100,
  taken_at DATETIME NOT NULL,
  FOREIGN KEY (student_id) REFERENCES users(id),
  FOREIGN KEY (course_id) REFERENCES courses(id)
);

CREATE TABLE IF NOT EXISTS announcements (
  id INT PRIMARY KEY,
  course_id INT NOT NULL,
  author_id INT NOT NULL,
  title VARCHAR(255) NOT NULL,
  body TEXT,
  created_at DATETIME NOT NULL,
  FOREIGN KEY (course_id) REFERENCES courses(id),
  FOREIGN KEY (author_id) REFERENCES users(id)
);

-- Reset seed data to keep local test environment deterministic.
SET FOREIGN_KEY_CHECKS = 0;
DELETE FROM announcements;
DELETE FROM quiz_results;
DELETE FROM submissions;
DELETE FROM assignments;
DELETE FROM lessons;
DELETE FROM enrollments;
DELETE FROM courses;
DELETE FROM users;
SET FOREIGN_KEY_CHECKS = 1;

INSERT INTO users (id, name, email, role, created_at, is_active) VALUES
(1, 'Admin One', 'admin1@lms.local', 'admin', '2026-01-01 09:00:00', 1),
(2, 'Teacher Sara', 'sara@lms.local', 'teacher', '2026-01-01 09:10:00', 1),
(3, 'Teacher Imran', 'imran@lms.local', 'teacher', '2026-01-01 09:15:00', 1),
(4, 'Aisha Khan', 'aisha@lms.local', 'student', '2026-01-05 11:00:00', 1),
(5, 'Bilal Ahmed', 'bilal@lms.local', 'student', '2026-01-05 11:05:00', 1),
(6, 'Hina Raza', 'hina@lms.local', 'student', '2026-01-05 11:10:00', 1),
(7, 'Omar Ali', 'omar@lms.local', 'student', '2026-01-05 11:15:00', 1),
(8, 'Zara Noor', 'zara@lms.local', 'student', '2026-01-05 11:20:00', 1),
(9, 'Usman Tariq', 'usman@lms.local', 'student', '2026-01-05 11:25:00', 1),
(10, 'Fatima Saleem', 'fatima@lms.local', 'student', '2026-01-05 11:30:00', 1),
(11, 'Hamza Iqbal', 'hamza@lms.local', 'student', '2026-01-05 11:35:00', 1),
(12, 'Mariam Asif', 'mariam@lms.local', 'student', '2026-01-05 11:40:00', 1),
(13, 'Yousaf Khan', 'yousaf@lms.local', 'student', '2026-01-05 11:45:00', 0);

INSERT INTO courses (id, title, description, teacher_id, category, status, created_at, updated_at) VALUES
(101, 'Python Basics', 'Introduction to Python programming.', 2, 'Programming', 'published', '2026-02-01 10:00:00', '2026-03-01 10:00:00'),
(102, 'Data Analytics Fundamentals', 'Core data analysis techniques.', 2, 'Data Science', 'published', '2026-02-03 10:00:00', '2026-03-05 10:00:00'),
(103, 'Database Design', 'Relational modeling and SQL design.', 3, 'Database', 'published', '2026-02-05 10:00:00', '2026-03-10 10:00:00'),
(104, 'Cloud Essentials', 'Foundations of cloud computing.', 3, 'Cloud', 'draft', '2026-02-10 10:00:00', '2026-03-12 10:00:00');

INSERT INTO enrollments (id, student_id, course_id, enrolled_at, status, progress_percent) VALUES
(1001, 4, 101, '2026-03-01 09:00:00', 'active', 76.50),
(1002, 5, 101, '2026-03-01 09:03:00', 'active', 63.00),
(1003, 6, 101, '2026-03-01 09:06:00', 'completed', 100.00),
(1004, 7, 101, '2026-03-01 09:09:00', 'dropped', 22.00),
(1005, 8, 102, '2026-03-01 09:12:00', 'active', 49.00),
(1006, 9, 102, '2026-03-01 09:15:00', 'active', 58.00),
(1007, 10, 102, '2026-03-01 09:18:00', 'dropped', 15.00),
(1008, 11, 103, '2026-03-01 09:21:00', 'active', 39.00),
(1009, 12, 103, '2026-03-01 09:24:00', 'completed', 100.00),
(1010, 13, 103, '2026-03-01 09:27:00', 'dropped', 5.00),
(1011, 4, 102, '2026-03-01 09:30:00', 'active', 67.00),
(1012, 5, 103, '2026-03-01 09:33:00', 'active', 41.00);

INSERT INTO lessons (id, course_id, title, content, video_url, subtitles, order_index, duration_seconds, created_at) VALUES
(2001, 101, 'Variables and Types', 'Learn variables and primitive types.', 'https://youtu.be/example1', 'Variables store values in memory...', 1, 780, '2026-03-02 10:00:00'),
(2002, 101, 'Loops in Python', 'For and while loops.', 'https://youtu.be/example2', 'Loops help automate repetitive tasks...', 2, 840, '2026-03-02 10:30:00'),
(2003, 102, 'Data Cleaning', 'Prepare datasets for analysis.', 'https://youtu.be/example3', 'Data cleaning includes missing value handling...', 1, 900, '2026-03-03 10:00:00'),
(2004, 103, 'Normalization', 'Design normalized relational schemas.', 'https://youtu.be/example4', 'Normalization reduces data redundancy...', 1, 870, '2026-03-04 10:00:00');

INSERT INTO assignments (id, course_id, lesson_id, title, description, due_date, max_score) VALUES
(3001, 101, 2001, 'Python Quiz 1', 'Basic syntax and variables quiz.', '2026-03-20 23:59:00', 100),
(3002, 102, 2003, 'Analytics Worksheet', 'Practice data cleaning tasks.', '2026-03-22 23:59:00', 100),
(3003, 103, 2004, 'ER Diagram Exercise', 'Design a normalized schema.', '2026-03-25 23:59:00', 100);

INSERT INTO submissions (id, assignment_id, student_id, submitted_at, score, feedback, status) VALUES
(4001, 3001, 4, '2026-03-18 16:00:00', 88, 'Good fundamentals.', 'graded'),
(4002, 3001, 5, '2026-03-19 10:00:00', 71, 'Need better naming conventions.', 'graded'),
(4003, 3001, 6, '2026-03-20 08:00:00', 94, 'Excellent work.', 'graded'),
(4004, 3002, 8, '2026-03-21 14:00:00', 82, 'Solid cleaning pipeline.', 'graded'),
(4005, 3002, 9, NULL, NULL, NULL, 'pending'),
(4006, 3003, 11, '2026-03-24 19:00:00', 77, 'Schema is mostly correct.', 'graded');

INSERT INTO quiz_results (id, student_id, course_id, quiz_name, score, max_score, taken_at) VALUES
(5001, 4, 101, 'Week 1 Quiz', 86, 100, '2026-03-12 11:00:00'),
(5002, 5, 101, 'Week 1 Quiz', 72, 100, '2026-03-12 11:05:00'),
(5003, 6, 101, 'Week 1 Quiz', 95, 100, '2026-03-12 11:10:00'),
(5004, 7, 101, 'Week 1 Quiz', 41, 100, '2026-03-12 11:15:00'),
(5005, 8, 102, 'Week 1 Quiz', 79, 100, '2026-03-13 11:00:00'),
(5006, 9, 102, 'Week 1 Quiz', 68, 100, '2026-03-13 11:05:00'),
(5007, 10, 102, 'Week 1 Quiz', 33, 100, '2026-03-13 11:10:00'),
(5008, 11, 103, 'Week 1 Quiz', 75, 100, '2026-03-14 11:00:00'),
(5009, 12, 103, 'Week 1 Quiz', 92, 100, '2026-03-14 11:05:00'),
(5010, 13, 103, 'Week 1 Quiz', 29, 100, '2026-03-14 11:10:00');

INSERT INTO announcements (id, course_id, author_id, title, body, created_at) VALUES
(6001, 101, 2, 'Welcome to Python Basics', 'Please complete lesson 1 before Friday.', '2026-03-01 12:00:00'),
(6002, 102, 2, 'Assignment Deadline', 'Analytics worksheet due this weekend.', '2026-03-18 09:00:00'),
(6003, 103, 3, 'Quiz Reminder', 'Week 1 quiz is now available.', '2026-03-10 09:00:00');
