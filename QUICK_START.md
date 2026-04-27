# STUDENT PORTAL - QUICK START GUIDE

## Initial Setup (Do This First!)

### Step 1: Initialize Database
Open your browser and go to:
```
http://localhost/Myproject/setup_database.php
```
You should see: "✓ All tables created successfully!"

### Step 2: Add Test Data (Optional but Recommended)
Go to:
```
http://localhost/Myproject/add_sample_data.php
```
You should see: "✓ All sample data added successfully!"

### Step 3: Create a Student Account
Go to:
```
http://localhost/Myproject/register.php
```
- Fill in your details
- Select "Student" as user type
- Click Submit

### Step 4: Login to Student Portal
Go to:
```
http://localhost/Myproject/
```
- Enter your username and password
- Click Login

## What You Can Do

✅ **View Courses** (📚)
- See all your enrolled courses
- Browse available courses to enroll in
- See course details (instructor, credits, semester)

✅ **Assignments** (📝)
- View all assignments for your courses
- Submit answers (text and/or file)
- Track submission status
- See feedback when graded

✅ **Grades** (📊)
- View your overall GPA
- See grades by course with percentages
- View individual assignment grades
- Letter grades (A, B, C, D, F)

✅ **Resources** (📖)
- Download lecture notes and materials
- Watch educational videos
- Access external links
- Filter by course

✅ **Messages** (💬)
- Send messages to instructors and other students
- Read received messages
- View sent messages
- Track unread message count

✅ **Profile** (👤)
- View your profile information
- Edit personal details (phone, address, major, year level)
- Change your password

## Navigation

All features are accessible from the top navigation bar:
- 📊 Dashboard (Home)
- 📚 Courses
- 📝 Assignments
- 📊 Grades
- 📖 Resources
- 💬 Messages
- 👤 Profile
- 🚪 Logout

## File Uploads

When submitting assignments, you can upload:
- PDF files (.pdf)
- Word documents (.doc, .docx)
- Text files (.txt)
- ZIP files (.zip)

Files are saved in the `submissions/` directory.

## Help

### I forgot my password
Currently, contact the administrator. Future versions will have password reset via email.

### I can't see my courses
1. Make sure you're logged in as a student
2. Go to "Courses" and enroll in courses
3. Courses will appear in your dashboard

### Assignment submission failed
1. Check your file size
2. Verify file format is supported
3. Ensure you've filled in the message field
4. Try again

### I can't send messages
1. Make sure you've selected a recipient
2. Fill in both subject and message
3. Check that recipient exists in the system

---

**For full documentation, see: STUDENT_PORTAL_README.md**
