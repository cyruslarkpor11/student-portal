# Student Portal - Complete Setup Guide

## Overview
The AME Zion University Student Portal is a fully functional web application that allows students to:
- 📚 View and enroll in courses
- 📝 View and submit assignments
- 📊 Check grades and GPA
- 📖 Access course resources and materials
- 💬 Send and receive messages
- 👤 Manage their profile

## Setup Instructions

### Step 1: Database Setup
Run the database initialization script in your browser:
```
http://localhost/Myproject/setup_database.php
```

This will create all necessary tables:
- courses
- student_courses (enrollments)
- assignments
- student_assignments (submissions)
- grades
- resources
- messages
- student_profile

### Step 2: Add Sample Data (Optional)
To populate the database with test data, visit:
```
http://localhost/Myproject/add_sample_data.php
```

This adds:
- 5 sample courses
- Student enrollments
- Sample assignments
- Sample grades
- Course resources

### Step 3: Login to Student Portal
1. Go to `http://localhost/Myproject/`
2. Use your student credentials to log in
3. You'll be redirected to the Student Portal Dashboard

## Features

### 📚 View Courses
- **Location**: Click "Courses" in navigation or on Dashboard
- **Features**:
  - View enrolled courses with details
  - Browse available courses
  - Enroll in new courses
  - See instructor, credits, semester, and course description

### 📝 Assignments
- **Location**: Click "Assignments" in navigation
- **Features**:
  - View all assignments for enrolled courses
  - Submit assignment answers (text and/or file upload)
  - Track submission status (not started, in progress, submitted, graded)
  - View feedback and points earned when graded
  - Supported file formats: PDF, DOC, DOCX, TXT, ZIP

### 📊 Grades
- **Location**: Click "Grades" in navigation
- **Features**:
  - View overall GPA
  - See grades by course with percentage
  - View all individual assignment grades
  - Letter grade conversion (A, B, C, D, F)
  - Progress visualization with color-coded bars

### 📖 Resources
- **Location**: Click "Resources" in navigation
- **Features**:
  - Browse course materials
  - Filter resources by course
  - Access documents, videos, and links
  - Download or open external resources
  - View upload dates and descriptions

### 💬 Messages
- **Location**: Click "Messages" in navigation
- **Features**:
  - **Inbox**: View received messages with unread count
  - **Sent**: View messages you've sent
  - **Compose**: Write and send messages to other students/instructors
  - Mark messages as read
  - View full message content and details

### 👤 Profile
- **Location**: Click "Profile" in navigation
- **Features**:
  - **View Profile**: See all your information
  - **Edit Profile**: Update personal details
    - Username and email
    - Phone number
    - Date of birth
    - Major and year level
    - Address
  - **Change Password**: Update your account password

## File Structure

```
Myproject/
├── includes/
│   ├── db.php                 # Database connection
│   ├── menu.php              # Navigation menu
│   └── formhandler.php       # Login handler
├── student_portal.php         # Dashboard (home)
├── view_courses.php           # Courses page
├── view_assignments.php       # Assignments page
├── view_grades.php            # Grades page
├── view_resources.php         # Resources page
├── view_messages.php          # Messages page
├── view_profile.php           # Profile page
├── setup_database.php         # Database initialization
└── add_sample_data.php        # Sample data population
```

## Database Schema

### Users Table (existing)
- id, username, email, password, user_type, created_at

### Student Profile
```sql
user_id (PK, FK)
phone
address
date_of_birth
major
year_level
gpa
```

### Courses
```sql
course_id (PK)
course_name
course_code (UNIQUE)
instructor
description
credits
semester
```

### Student Courses (Enrollments)
```sql
enrollment_id (PK)
user_id (FK)
course_id (FK)
enrolled_date
```

### Assignments
```sql
assignment_id (PK)
course_id (FK)
title
description
due_date
total_points
```

### Student Assignments (Submissions)
```sql
submission_id (PK)
user_id (FK)
assignment_id (FK)
submission_file
submission_text
points_earned
feedback
submitted_date
status (enum)
```

### Grades
```sql
grade_id (PK)
user_id (FK)
course_id (FK)
assignment_id (FK)
points_earned
total_points
grade_letter
```

### Resources
```sql
resource_id (PK)
course_id (FK)
title
description
file_path
resource_type (enum)
uploaded_date
```

### Messages
```sql
message_id (PK)
sender_id (FK)
recipient_id (FK)
subject
body
sent_date
is_read
read_date
```

## Key Features & Functionality

### Session Management
- Secure login with hashed passwords
- User type validation (student only)
- Session timeout protection

### Data Validation
- SQL injection prevention via prepared statements
- HTML entity encoding for security
- File upload restrictions

### User Experience
- Responsive design with gradient background
- Intuitive navigation tabs
- Color-coded status badges
- Real-time message notifications (unread count)
- Smooth hover effects and transitions

### Performance
- Prepared statements for all queries
- Efficient database joins
- Pagination support (50 messages limit)
- Optimized SQL queries

## Admin Functions (Future Enhancement)
The portal is designed to work with admin features (can be added):
- Add courses
- Create assignments and set deadlines
- Grade assignments
- Upload resources
- Send system messages

## Security Features
✓ Password hashing with PHP password_hash()
✓ SQL injection prevention
✓ Cross-site scripting (XSS) protection
✓ Session validation
✓ User role verification
✓ HTTPS ready

## Troubleshooting

### Database Connection Failed
- Check if MySQL/XAMPP is running
- Verify credentials in includes/db.php
- Ensure database "myproject" exists

### Login Not Working
- Verify username and password are correct
- Check users table has your account
- Clear browser cache and cookies

### Files Not Uploading
- Check submissions/ directory exists and is writable
- Verify file size limits
- Ensure file format is allowed

### Messages Not Showing
- Verify messages table is created
- Check sender/recipient user IDs exist
- Clear cache if changes don't appear

## Future Enhancements
- Email notifications
- Real-time notifications with WebSockets
- Grade calculation automation
- Attendance tracking
- Discussion forums
- Study groups
- Admin dashboard for instructor grading
- GPA calculation per semester

## Support
For issues or questions, contact the administrator at the university.

---
**AME Zion University Student Portal v1.0**
Last Updated: April 2026
