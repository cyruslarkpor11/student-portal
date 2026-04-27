# 🎓 STUDENT & INSTRUCTOR PORTAL - QUICK START GUIDE

## ✅ System Setup Complete!

Your fully functional student and instructor portal is ready to use. Follow these simple steps to get started.

---

## 🚀 STEP 1: Initialize Database

### Run Database Setup (IMPORTANT - DO THIS FIRST)

1. Open your browser and go to:
   ```
   http://localhost/xampp/htdocs/Myproject/includes/create_tables.php
   ```
   You should see: "All tables created successfully!"

2. Then insert sample data:
   ```
   http://localhost/xampp/htdocs/Myproject/insert_sample_data.php
   ```
   You should see login credentials displayed

---

## 👤 STEP 2: Login to Portal

### Test Credentials

**Admin Account (for registration):**
- URL: `http://localhost/xampp/htdocs/Myproject/index.php`
- Username: `admin`
- Password: `admin123`

**Instructor Account:**
- Username: `instructor1`
- Password: `instructor123`

**Student Account 1:**
- Username: `student1`
- Password: `student123`

**Student Account 2:**
- Username: `student2`
- Password: `student123`

---

## 📚 STEP 3: Explore Portal Features

### For Students:
Once logged in as a student, you'll see the dashboard with these sections:

#### 1. 📊 Dashboard
- Welcome message with your name
- Quick stats (enrolled courses, pending assignments, unread messages)
- Fast access to all portal features

#### 2. 📚 View Courses
- See all your enrolled courses
- View course codes, names, credits
- Course descriptions

#### 3. 📝 View Assignments
- See all assignments from your courses
- Check due dates
- View total marks for each assignment
- Submit assignments (if enabled)

#### 4. 📊 View Grades
- Check all your grades and scores
- See your overall performance percentage
- Read instructor feedback
- **📋 COMPREHENSIVE GRADE SHEET** with:
  - Student Name, ID, Sex, Major, Program
  - Course details with credits and points
  - Instructor names and final grades
  - Total credits, total points, and GPA calculation

#### 5. 📖 Resources
- Download course materials
- Access PDFs, documents, and files
- View upload dates

#### 6. 💬 Messages
- Send messages to instructors
- Read incoming messages
- Keep track of conversations

#### 7. 👥 Chat Room
- Interact with other students
- Real-time messaging
- Course-related discussions

#### 8. 👤 Profile Management
- View your student information:
  - ✅ Student ID
  - ✅ Department
  - ✅ Status (Active/Inactive/Graduated)
  - ✅ Email address

- **✅ UPLOAD PROFILE PICTURE** (Fully Functional!)
  - Click on profile picture area
  - Select image (JPG, PNG, or GIF)
  - Max file size: 5MB
  - Picture updates instantly
  - Old picture automatically deleted

- Edit your information:
  - First Name
  - Last Name
  - Phone Number
  - Address

### For Instructors:
Once logged in as an instructor, you'll see the instructor dashboard with these sections:

#### 1. 📊 Instructor Dashboard
- Welcome message with your name
- Quick stats (courses teaching, total students, pending grades)

#### 2. � Grade Assignments
- View student submissions for your courses
- Grade assignments with points and feedback
- Automatic grade letter calculation (A-F)
- Update grades in real-time

#### 3. 📖 Post Resources
- Upload course materials for students
- Support for PDFs, documents, images
- Resources visible to enrolled students only

#### 4. 💬 Send Messages
- Send messages to enrolled students
- Bulk messaging to entire class
- Private conversations with individual students

#### 5. 👥 Student Chat Room
- Access to student chat room
- Monitor student interactions
- Participate in discussions

#### 6. 👤 Profile Management
- View and update instructor information
- Upload profile picture

### For Admins:
- Register new users (students/instructors)
- Access restricted to admin accounts only

---
- Welcome message with your name
- Quick stats (enrolled courses, pending assignments, unread messages)
- Fast access to all portal features

### 2. 📚 View Courses
- See all your enrolled courses
- View course codes, names, credits
- Course descriptions

### 3. 📝 View Assignments
- See all assignments from your courses
- Check due dates
- View total marks for each assignment

### 4. 📊 View Grades
- Check all your grades and scores
- See your overall performance percentage
- Read instructor feedback

### 5. 📖 Resources
- Download course materials
- Access PDFs, documents, and files
- View upload dates

### 6. 💬 Messages
- Send messages to instructors
- Read incoming messages
- Keep track of conversations

### 7. 👤 Profile Management
- View your student information:
  - ✅ Student ID
  - ✅ Department
  - ✅ Status (Active/Inactive/Graduated)
  - ✅ Email address

- **✅ UPLOAD PROFILE PICTURE** (Fully Functional!)
  - Click on profile picture area
  - Select image (JPG, PNG, or GIF)
  - Max file size: 5MB
  - Picture updates instantly
  - Old picture automatically deleted

- Edit your information:
  - First Name
  - Last Name
  - Phone Number
  - Address

---

## 🖼️ PROFILE PICTURE UPLOAD - DETAILED INSTRUCTIONS

### How to Upload Your Profile Picture

1. **Navigate to Profile**
   - Click the "👤 Profile" button on the dashboard
   - Or use the navigation menu

2. **Profile Picture Section**
   - You'll see a profile picture area at the top left
   - If no picture is uploaded, it shows "No Picture"

3. **Upload Image**
   - Click the "Upload Picture" button
   - Select an image file from your computer
   - Supported formats: JPG, JPEG, PNG, GIF
   - Maximum size: 5MB

4. **View Your Picture**
   - Picture uploads immediately
   - Displayed in the profile section
   - Shown on your dashboard

5. **Replace Picture**
   - Simply upload a new picture
   - Old picture automatically deleted
   - New picture replaces the old one

---

## 🌐 Deployment Option (Recommended for beginners)

### Option 1: Railway
Railway is a beginner-friendly platform that makes it easy to deploy PHP apps with a managed database.

1. Open: `https://railway.app`
2. Sign up and create a new project.
3. Connect your GitHub repository or deploy from local files.
4. Add a MySQL plugin and configure the database.
5. Set environment variables in Railway for your DB connection:
   - `DB_HOST`
   - `DB_NAME`
   - `DB_USER`
   - `DB_PASSWORD`
   - `DB_PORT`
6. Deploy the app and use the Railway URL.
7. Open the deployed URL and run the setup scripts if needed:
   ```
   https://<your-railway-app>.railway.app/includes/create_tables.php
   https://<your-railway-app>.railway.app/insert_sample_data.php
   ```

> Tip: For local testing, keep using XAMPP and the `localhost` URLs from STEP 1.


---

## 📊 Sample Data Included

The system comes with pre-populated data:

### Courses
- **CS101**: Introduction to Programming (3 credits) - Instructor: John Doe
- **CS102**: Data Structures (4 credits) - Instructor: John Doe
- **CS201**: Web Development (3 credits) - Instructor: Jane Smith

### Assignments
- Multiple assignments per course
- Different due dates
- Varying marks/points
- Sample submissions and grades

### Grades
- Sample grades already recorded
- Feedback from instructors
- Overall performance percentage calculated

### Messages
- Sample messages from instructors
- Ready to send new messages

### Resources
- Sample course materials uploaded
- Available for download

### Chat
- Student chat room with sample messages

---

### Messages
- Sample messages from instructors
- Ready to send new messages

---

## 🔒 Security Features

✅ Password hashing with PHP `password_hash()`
✅ Session-based authentication
✅ SQL prepared statements (prevents injection)
✅ File type validation for uploads
✅ File size limits enforced
✅ User type verification (student/instructor/admin)

---

## 📁 Directory Structure

```
Myproject/
├── includes/
│   ├── db.php                    ← Database connection
│   ├── menu.php                  ← Navigation menu
│   ├── create_tables.php         ← Create database tables
│   └── formhandler.php           ← Login handler
├── css/
│   └── style.css                 ← Portal styling
├── uploads/
│   ├── profiles/                 ← Profile pictures stored here
│   └── resources/                ← Course resources stored here
├── index.php                     ← Login page
├── student_portal.php            ← Student dashboard
├── instructor_portal.php         ← Instructor dashboard
├── view_courses.php              ← Courses page (student/instructor)
├── view_assignments.php          ← Assignments page (student grading for instructors)
├── view_grades.php               ← Grades page (students)
├── view_resources.php            ← Resources page (instructor upload for students)
├── view_messages.php             ← Messages page (instructor to enrolled students)
├── chat_room.php                 ← Student chat room
├── view_profile.php              ← Profile management
├── logout.php                    ← Logout handler
├── insert_sample_data.php        ← Sample data seeder
├── register.php                  ← Admin-only registration
└── README.md                     ← Full documentation
```

---

## 🎯 Features Summary

| Feature | Status | Description |
|---------|--------|-------------|
| Student Portal | ✅ Complete | Full student dashboard and features |
| Instructor Portal | ✅ Complete | Assignment grading, messaging, resources, chat monitoring |
| Admin Registration | ✅ Complete | Restricted user registration |
| View Courses | ✅ Complete | Student enrollment viewing |
| Grade Assignments | ✅ Complete | Instructor grading with feedback |
| View Grades | ✅ Complete | Student grade viewing with comprehensive grade sheet |
| Post Resources | ✅ Complete | Instructor resource uploads for students |
| Send Messages | ✅ Complete | Instructor-to-enrolled-student messaging |
| Chat Room | ✅ Complete | Student-to-student chat with instructor access |
| Profile Management | ✅ Complete | Info editing, picture upload |
| Comprehensive Grade Sheet | ✅ Complete | Detailed academic record with all student/course info |
| Responsive Design | ✅ Complete | Works on desktop and mobile |

---

## 🔧 Troubleshooting

### Profile Picture Not Uploading?
1. Check browser console for errors
2. Ensure `uploads/profiles/` folder exists
3. Verify folder has write permissions (755)
4. Check file is JPG, PNG, or GIF
5. Ensure file size is less than 5MB

### Can't Login?
1. Use exactly: `student1` and `student123` (student), `instructor1` and `instructor123` (instructor), `admin` and `admin123` (admin)
2. Check database connection
3. Ensure database is created and populated
4. Clear browser cache/cookies

### Grades Not Showing?
1. Run insert_sample_data.php again
2. Check student is enrolled in courses
3. Verify grades table has data

### Instructor Features Not Working?
1. Login as `instructor1` / `instructor123`
2. Ensure courses are assigned to instructor
3. Check student enrollments exist
4. Verify file upload permissions for resources

### Chat Room Not Loading?
1. Check database connection
2. Ensure chat_messages table exists
3. Verify user is logged in

---

## 📱 Mobile Compatibility

The portal is fully responsive:
- ✅ Desktop (1920px and up)
- ✅ Laptop (1024px and up)
- ✅ Tablet (768px and up)
- ✅ Mobile (320px and up)

---

## 💡 Tips & Tricks

1. **Dashboard** - Check this first for a quick overview
2. **Grades** - Automatically calculates your overall percentage
3. **Messages** - Mark as read when you view them
4. **Profile** - Update your picture regularly for better recognition
5. **Resources** - Download materials for offline access

---

## 📞 Need Help?

If you encounter any issues:

1. **Check the main README.md** for detailed documentation
2. **Verify database** - Run `create_tables.php` again
3. **Check file permissions** - Should be 755 or 777
4. **Review server logs** - Check Apache/PHP error logs
5. **Ensure PHP version** - Requires PHP 7.0 or higher

---

## ✨ Next Steps

1. ✅ Run database setup
2. ✅ Insert sample data
3. ✅ Login as student: `student1` / `student123`
4. ✅ Explore student sections (courses, assignments, grades, resources, messages, chat)
5. ✅ Login as instructor: `instructor1` / `instructor123`
6. ✅ Test instructor features (grade assignments, post resources, send messages, monitor chat)
7. ✅ Login as student: `student1` / `student123`
8. ✅ View comprehensive grade sheet with all academic details
9. ✅ Login as admin: `admin` / `admin123`
10. ✅ Test admin registration
11. ✅ Upload profile pictures for all user types

---

## 🎉 You're All Set!

Your student and instructor portal is ready to use. Login now and start exploring!

**Portal URL:** `http://localhost/xampp/htdocs/Myproject/index.php`

---

**Version:** 2.0 - Instructor Portal Edition
**Last Updated:** April 2024
**Status:** ✅ Fully Functional & Executable
