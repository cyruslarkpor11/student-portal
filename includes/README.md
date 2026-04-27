# Student Portal - Complete Setup Guide

## Overview
This is a fully functional student portal for the African Methodist Episcopal Zion University Nimba Extension. Students can view courses, assignments, grades, resources, messages, and manage their profiles with picture uploads.

## Features Implemented

✅ **Student Dashboard**
- Overview of enrolled courses, assignments, and messages
- Quick access to all portal features

✅ **View Courses**
- See all enrolled courses
- Course information including credits and descriptions

✅ **View Assignments**
- Track all assignments for enrolled courses
- Due dates and submission status
- Total marks for each assignment

✅ **View Grades**
- See grades for all submitted assignments
- Overall performance percentage
- Feedback from instructors

✅ **Learning Resources**
- Access course materials and documents
- Download resources from instructors

✅ **Messages**
- Send messages to instructors
- Receive and read messages from instructors
- Inbox management

✅ **Profile Management**
- View personal information (Student ID, Department, Status)
- Edit name, phone, and address
- **FULLY FUNCTIONAL PROFILE PICTURE UPLOAD**
  - Drag and drop or browse for images
  - Supports JPG, PNG, GIF formats
  - Maximum file size: 5MB
  - Automatic old picture deletion
  - Real-time display of uploaded pictures

## Database Structure

The portal uses 8 main tables:

1. **users** - User authentication
2. **student_info** - Student profile data
3. **courses** - Course information
4. **enrollments** - Student course enrollments
5. **assignments** - Course assignments
6. **grades** - Student grades
7. **resources** - Course materials
8. **messages** - Communication between students and instructors

## Installation Steps

### 1. Database Setup
- Access phpMyAdmin or your database management tool
- Create a database named `myproject`
- Run: `http://localhost/xampp/htdocs/Myproject/includes/create_tables.php`

### 2. Insert Sample Data
- Run: `http://localhost/xampp/htdocs/Myproject/insert_sample_data.php`
- This creates test accounts and sample data

### 3. Test Credentials

**Student Login:**
- Username: `student1`
- Password: `student123`
- Student ID: `CS001`
- Department: `Computer Science`
- Status: `active`

**Instructor Login:**
- Username: `instructor1`
- Password: `instructor123`

## File Structure

```
Myproject/
├── includes/
│   ├── db.php                 (Database connection)
│   ├── menu.php               (Navigation)
│   ├── create_tables.php      (Database tables)
│   └── formhandler.php        (Form processing)
├── css/
│   └── style.css              (Portal styling)
├── uploads/
│   └── profiles/              (Student profile pictures)
├── student_portal.php         (Main dashboard)
├── view_courses.php           (Courses page)
├── view_assignments.php       (Assignments page)
├── view_grades.php            (Grades page)
├── view_resources.php         (Resources page)
├── view_messages.php          (Messages page)
├── view_profile.php           (Profile management)
├── logout.php                 (Logout handler)
├── insert_sample_data.php     (Sample data seeder)
└── README.md                  (This file)
```

## How to Use

### For Students:

1. **Login** to the portal using your credentials
2. **Dashboard** - See overview of your academic status
3. **Courses** - View all your enrolled courses
4. **Assignments** - Check assignment deadlines
5. **Grades** - Review your performance and feedback
6. **Resources** - Download course materials
7. **Messages** - Communicate with instructors
8. **Profile** - Update your information and upload a picture

### Profile Picture Upload:

1. Go to **Profile** section
2. Click on the profile picture area or "Upload Picture" button
3. Select an image file (JPG, PNG, or GIF, max 5MB)
4. Click "Upload Picture"
5. Your new picture will be displayed immediately
6. Old pictures are automatically deleted

## Key Features

### Security
- Password hashing with PHP's password_hash()
- Session management
- SQL prepared statements to prevent injection
- File type validation for uploads

### Database Integrity
- Foreign key relationships
- Unique constraints on important fields
- Proper indexing for performance

### User Interface
- Responsive design (works on desktop and mobile)
- Gradient backgrounds and modern styling
- Intuitive navigation
- Color-coded sections for easy identification

### Functionality
- Real-time grade calculations
- Message management with read/unread status
- File upload with validation
- Enrollment tracking
- Grade feedback system

## Troubleshooting

### Profile Picture Upload Not Working
- Ensure `uploads/profiles/` directory exists and is writable
- Check file permissions (set to 755)
- Verify file size is under 5MB
- Supported formats: JPG, JPEG, PNG, GIF

### Database Connection Error
- Ensure XAMPP MySQL is running
- Check database name is `myproject`
- Verify credentials in `includes/db.php`
- Default: localhost, root, no password

### Session Errors
- Clear browser cookies and cache
- Ensure PHP sessions are enabled
- Check write permissions on `/tmp` or session directory

## Customization

### Add More Courses
Insert data into the `courses` table via database or create an admin panel.

### Modify Styling
Edit `css/style.css` to change colors, fonts, or layouts.

### Change Profile Picture Size
Modify the dimension in `css/style.css`:
```css
.profile-image {
    width: 200px;  /* Change this */
    height: 200px; /* And this */
}
```

### Add More Student Fields
Add columns to `student_info` table and update the profile form in `view_profile.php`.

## Support

For issues or questions:
1. Check database tables are created properly
2. Verify file permissions
3. Review error logs
4. Ensure all files are in correct directories

## License

This student portal is developed for African Methodist Episcopal Zion University Nimba Extension.

---

**Created:** April 2024
**Last Updated:** April 2024
