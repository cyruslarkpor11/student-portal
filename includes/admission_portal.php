<?php
session_start();
require "includes/db.php";
require "includes/mail.php";

$message = "";
$submitted = false;

// Ensure uploads directory exists
$uploadsDir = __DIR__ . "/uploads/admission_docs";
if (!is_dir($uploadsDir)) {
    @mkdir($uploadsDir, 0755, true);
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["submit_application"])) {
    // Collect form data
    $first_name = htmlspecialchars($_POST["first_name"] ?? "");
    $last_name = htmlspecialchars($_POST["last_name"] ?? "");
    $email = htmlspecialchars($_POST["email"] ?? "");
    $phone = htmlspecialchars($_POST["phone"] ?? "");
    $dob = htmlspecialchars($_POST["dob"] ?? "");
    $gender = htmlspecialchars($_POST["gender"] ?? "");
    $program = htmlspecialchars($_POST["program"] ?? "");
    $address = htmlspecialchars($_POST["address"] ?? "");
    $high_school = htmlspecialchars($_POST["high_school"] ?? "");
    $gpa = htmlspecialchars($_POST["gpa"] ?? "");
    $diplomaPath = null;
    $transcriptPath = null;
    $supportingDocsPath = null;

    // Validate required fields
    if (empty($first_name) || empty($last_name) || empty($email) || empty($phone) || empty($dob)) {
        $message = "<div class='alert error'>Please fill all required fields.</div>";
    } else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "<div class='alert error'>Invalid email address.</div>";
    } else if ($_FILES["diploma"]["error"] == UPLOAD_ERR_NO_FILE) {
        $message = "<div class='alert error'>Please upload your Diploma document (PDF).</div>";
    } else if ($_FILES["transcript"]["error"] == UPLOAD_ERR_NO_FILE) {
        $message = "<div class='alert error'>Please upload your Transcript document (PDF).</div>";
    } else {
        // Validate files
        $files = [
            'diploma' => $_FILES["diploma"],
            'transcript' => $_FILES["transcript"],
            'supporting_docs' => $_FILES["supporting_docs"]
        ];
        
        $maxSize = 50 * 1024 * 1024; // 50MB
        $validFiles = true;
        
        foreach ($files as $fileKey => $file) {
            if ($file["error"] != UPLOAD_ERR_OK && $file["error"] != UPLOAD_ERR_NO_FILE) {
                $message = "<div class='alert error'>File upload error for " . ucfirst(str_replace('_', ' ', $fileKey)) . ". Please try again.</div>";
                $validFiles = false;
                break;
            }
            
            if ($file["error"] == UPLOAD_ERR_OK) {
                $fileSize = $file["size"];
                $fileMime = mime_content_type($file["tmp_name"]);
                
                if ($fileSize > $maxSize) {
                    $message = "<div class='alert error'>" . ucfirst(str_replace('_', ' ', $fileKey)) . " file size exceeds 50MB limit. Current size: " . round($fileSize / 1024 / 1024, 2) . "MB</div>";
                    $validFiles = false;
                    break;
                } else if ($fileMime !== "application/pdf") {
                    $message = "<div class='alert error'>" . ucfirst(str_replace('_', ' ', $fileKey)) . " must be a PDF file.</div>";
                    $validFiles = false;
                    break;
                }
            }
        }
        
        if ($validFiles) {
            try {
                // Process file uploads
                foreach ($files as $fileKey => $file) {
                    if ($file["error"] == UPLOAD_ERR_OK) {
                        // Generate unique filename
                        $fileName = $fileKey . "_" . time() . "_" . uniqid() . ".pdf";
                        $filePath = $uploadsDir . "/" . $fileName;
                        
                        // Move uploaded file
                        if (!move_uploaded_file($file["tmp_name"], $filePath)) {
                            throw new Exception("Failed to upload " . ucfirst(str_replace('_', ' ', $fileKey)) . " document.");
                        }
                        
                        $webPath = "uploads/admission_docs/" . $fileName;
                        
                        switch ($fileKey) {
                            case 'diploma':
                                $diplomaPath = $webPath;
                                break;
                            case 'transcript':
                                $transcriptPath = $webPath;
                                break;
                            case 'supporting_docs':
                                $supportingDocsPath = $webPath;
                                break;
                        }
                    }
                }
                
                // Insert admission application
                $stmt = $conn->prepare("
                    INSERT INTO admission_applications (first_name, last_name, email, phone, date_of_birth, gender, program_applied, address, high_school, gpa, diploma_path, transcript_path, supporting_docs_path, application_date, status)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), 'pending')
                ");
                $stmt->execute([$first_name, $last_name, $email, $phone, $dob, $gender, $program, $address, $high_school, $gpa, $diplomaPath, $transcriptPath, $supportingDocsPath]);
                $applicationId = $conn->lastInsertId();
                
                // Send confirmation email to applicant
                sendAdmissionConfirmationEmail($first_name, $email, $applicationId, $program);
                
                // Send notification to admin
                sendAdminNewApplicationNotification($first_name . ' ' . $last_name, $email, $program, $applicationId);
                
                $message = "<div class='alert success'>✓ Application submitted successfully! We will review your application and contact you within 5-7 business days. A confirmation email has been sent to <strong>" . htmlspecialchars($email) . "</strong></div>";
                $submitted = true;
            } catch (Exception $e) {
                $message = "<div class='alert error'>Error submitting application: " . htmlspecialchars($e->getMessage()) . "</div>";
            }
        }
    }
}

// Check application status
$app_status = null;
if (isset($_POST["check_status"]) && isset($_POST["app_email"])) {
    $check_email = htmlspecialchars($_POST["app_email"]);
    $stmt = $conn->prepare("SELECT * FROM admission_applications WHERE email = ? ORDER BY application_date DESC LIMIT 1");
    $stmt->execute([$check_email]);
    $app_status = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admission Portal - AME Zion University</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(rgba(16, 14, 45, 0.8), rgba(69, 26, 123, 0.6)), url("Images/download%201.jpg") center/cover no-repeat fixed;
            color: white;
            min-height: 100vh;
            padding: 20px;
        }
        header {
            background: rgba(0, 0, 0, 0.55);
            padding: 30px;
            text-align: center;
            border-bottom: 3px solid #ffd700;
            margin-bottom: 30px;
            border-radius: 10px;
        }
        header h1 {
            color: #ffd700;
            font-size: 2.2em;
            margin-bottom: 10px;
        }
        header p {
            color: #ddd;
            font-size: 1.1em;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
        }
        .tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        .tab-button {
            padding: 12px 25px;
            background: rgba(255, 215, 0, 0.2);
            color: white;
            border: 2px solid #ffd700;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1em;
            transition: all 0.3s;
            font-weight: 600;
        }
        .tab-button.active {
            background: #ffd700;
            color: #000;
        }
        .tab-button:hover {
            background: rgba(255, 215, 0, 0.3);
        }
        .tab-content {
            display: none;
            background: rgba(0, 0, 0, 0.35);
            padding: 40px;
            border-radius: 10px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 215, 0, 0.2);
        }
        .tab-content.active {
            display: block;
        }
        h2 {
            color: #ffd700;
            margin-bottom: 25px;
            font-size: 1.8em;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
        }
        label {
            display: block;
            margin-bottom: 8px;
            color: #ffd700;
            font-weight: 600;
        }
        input, select, textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid rgba(255, 215, 0, 0.3);
            border-radius: 5px;
            background: rgba(255, 255, 255, 0.1);
            color: white;
            font-size: 1em;
        }
        select {
            background: rgba(0, 0, 0, 0.35);
        }
        input::placeholder, textarea::placeholder {
            color: rgba(255, 255, 255, 0.6);
        }
        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: #ffd700;
            background: rgba(255, 255, 255, 0.15);
            box-shadow: 0 0 5px rgba(255, 215, 0, 0.3);
        }
        select:focus {
            background: rgba(0, 0, 0, 0.5);
        }
        input[type="file"] {
            padding: 8px;
        }
        input[type="file"]::file-selector-button {
            background: #4CAF50;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 600;
            transition: background 0.3s;
        }
        input[type="file"]::file-selector-button:hover {
            background: #45a049;
        }
        small {
            font-size: 0.85em;
        }
        button {
            padding: 12px 30px;
            background: #4CAF50;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 1em;
            cursor: pointer;
            transition: background 0.3s;
            font-weight: 600;
            margin-top: 10px;
        }
        button:hover {
            background: #45a049;
        }
        .alert {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-weight: 600;
        }
        .alert.success {
            background: rgba(76, 175, 80, 0.3);
            border: 1px solid #4CAF50;
            color: #90EE90;
        }
        .alert.error {
            background: rgba(244, 67, 54, 0.3);
            border: 1px solid #F44336;
            color: #FF6B6B;
        }
        .status-box {
            background: rgba(255, 215, 0, 0.1);
            padding: 20px;
            border-radius: 8px;
            border: 2px solid rgba(255, 215, 0, 0.3);
            margin-top: 20px;
        }
        .status-item {
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid rgba(255, 215, 0, 0.2);
        }
        .status-item:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }
        .status-item strong {
            color: #ffd700;
        }
        .status-badge {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-weight: 600;
            margin-top: 5px;
        }
        .status-badge.pending {
            background: rgba(255, 193, 7, 0.3);
            color: #FFD700;
        }
        .status-badge.approved {
            background: rgba(76, 175, 80, 0.3);
            color: #90EE90;
        }
        .status-badge.rejected {
            background: rgba(244, 67, 54, 0.3);
            color: #FF6B6B;
        }
        .info-section {
            background: rgba(255, 215, 0, 0.05);
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #ffd700;
        }
        .back-link {
            display: inline-block;
            padding: 10px 20px;
            background: rgba(0, 0, 0, 0.3);
            color: #ffd700;
            text-decoration: none;
            border-radius: 5px;
            transition: background 0.3s;
            margin-bottom: 20px;
        }
        .back-link:hover {
            background: rgba(0, 0, 0, 0.5);
        }
    </style>
</head>
<body>
    <header>
        <h1>🎓 Admission Portal</h1>
        <p>African Methodist Episcopal Zion University Nimba Extension</p>
    </header>

    <div class="container">
        <a href="index.php" class="back-link">← Back to Login Page</a>

        <div class="tabs">
            <button class="tab-button active" onclick="switchTab(event, 'apply')">📝 New Application</button>
            <button class="tab-button" onclick="switchTab(event, 'requirements')">📋 Requirements</button>
            <button class="tab-button" onclick="switchTab(event, 'status')">🔍 Check Status</button>
            <button class="tab-button" onclick="switchTab(event, 'faq')">❓ FAQs</button>
        </div>

        <!-- New Application Tab -->
        <div id="apply" class="tab-content active">
            <h2>Apply Now</h2>
            <?php echo $message; ?>
            
            <?php if (!$submitted): ?>
            <form method="POST" enctype="multipart/form-data">
                <div class="form-row">
                    <div class="form-group">
                        <label>First Name *</label>
                        <input type="text" name="first_name" required>
                    </div>
                    <div class="form-group">
                        <label>Last Name *</label>
                        <input type="text" name="last_name" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Email Address *</label>
                        <input type="email" name="email" required>
                    </div>
                    <div class="form-group">
                        <label>Phone Number *</label>
                        <input type="tel" name="phone" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Date of Birth *</label>
                        <input type="date" name="dob" required>
                    </div>
                    <div class="form-group">
                        <label>Gender</label>
                        <select name="gender">
                            <option value="">Select Gender</option>
                            <option value="male">Male</option>
                            <option value="female">Female</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Program Applied For *</label>
                        <select name="program" required>
                            <option value="">Select Program</option>
                            <option value="undergraduate">Undergraduate (4 years)</option>
                            <option value="master">Master's Program (2 years)</option>
                            <option value="certificate">Certificate Program (1 year)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>High School / Previous College</label>
                        <input type="text" name="high_school" placeholder="Name of institution">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Address</label>
                        <input type="text" name="address" placeholder="Your address">
                    </div>
                    <div class="form-group">
                        <label>Upload Diploma (PDF only, max 50MB) *</label>
                        <input type="file" name="diploma" accept=".pdf" required>
                        <small style="color: #ffd700; margin-top: 5px; display: block;">High school or previous degree diploma</small>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Upload Transcript (PDF only, max 50MB) *</label>
                        <input type="file" name="transcript" accept=".pdf" required>
                        <small style="color: #ffd700; margin-top: 5px; display: block;">Academic transcript with grades</small>
                    </div>
                    <div class="form-group">
                        <label>Upload Supporting Documents (PDF only, max 50MB)</label>
                        <input type="file" name="supporting_docs" accept=".pdf">
                        <small style="color: #ffd700; margin-top: 5px; display: block;">Optional: certificates, recommendations, etc.</small>
                    </div>
                </div>

                <button type="submit" name="submit_application">Submit Application</button>
            </form>
            <?php else: ?>
            <div class="alert success">
                <h3>✓ Application Submitted Successfully!</h3>
                <p>Thank you for applying to AME Zion University. We have received your application and will review it shortly.</p>
                <p>You will receive an email confirmation at the provided email address.</p>
                <a href="admission_portal.php" class="button">Submit Another Application</a>
            </div>
            <?php endif; ?>
        </div>

        <!-- Requirements Tab -->
        <div id="requirements" class="tab-content">
            <h2>Admission Requirements</h2>
            
            <div class="info-section">
                <h3>General Requirements for All Programs</h3>
                <ul style="margin-left: 20px; line-height: 1.8;">
                    <li>Valid High School Diploma or GED</li>
                    <li>Completed application form</li>
                    <li>Official transcripts from previous schools</li>
                    <li>Valid identification document</li>
                    <li>Application fee: $50 (non-refundable)</li>
                    <li>English language proficiency (for international students)</li>
                </ul>
            </div>

            <div class="info-section">
                <h3>Undergraduate Program</h3>
                <ul style="margin-left: 20px; line-height: 1.8;">
                    <li>Minimum High School GPA: 2.5</li>
                    <li>SAT/ACT scores (optional)</li>
                    <li>3 letters of recommendation</li>
                    <li>Personal statement (500 words)</li>
                    <li>Duration: 4 years, 120 credit hours</li>
                </ul>
            </div>

            <div class="info-section">
                <h3>Master's Program</h3>
                <ul style="margin-left: 20px; line-height: 1.8;">
                    <li>Bachelor's degree from accredited institution</li>
                    <li>Minimum undergraduate GPA: 3.0</li>
                    <li>GRE/GMAT scores (required)</li>
                    <li>2 years of relevant work experience</li>
                    <li>2 letters of recommendation from professors</li>
                    <li>Research proposal (500 words)</li>
                    <li>Duration: 2 years, 36 credit hours</li>
                </ul>
            </div>

            <div class="info-section">
                <h3>Certificate Program</h3>
                <ul style="margin-left: 20px; line-height: 1.8;">
                    <li>High School Diploma or equivalent</li>
                    <li>Basic computer literacy</li>
                    <li>1 letter of recommendation</li>
                    <li>Duration: 1 year, 12-15 credit hours</li>
                </ul>
            </div>
        </div>

        <!-- Check Status Tab -->
        <div id="status" class="tab-content">
            <h2>Check Application Status</h2>
            
            <form method="POST">
                <div class="form-group">
                    <label>Enter your email address to check status:</label>
                    <input type="email" name="app_email" placeholder="your.email@example.com" required>
                </div>
                <button type="submit" name="check_status">Check Status</button>
            </form>

            <?php if ($app_status): ?>
            <div class="status-box">
                <div class="status-item">
                    <strong>Applicant Name:</strong> <?php echo htmlspecialchars($app_status['first_name'] . ' ' . $app_status['last_name']); ?>
                </div>
                <div class="status-item">
                    <strong>Program Applied:</strong> <?php echo htmlspecialchars(ucfirst($app_status['program_applied'])); ?>
                </div>
                <div class="status-item">
                    <strong>Application Date:</strong> <?php echo date("F j, Y", strtotime($app_status['application_date'])); ?>
                </div>
                <div class="status-item">
                    <strong>Current Status:</strong>
                    <div class="status-badge <?php echo $app_status['status']; ?>">
                        <?php echo strtoupper($app_status['status']); ?>
                    </div>
                </div>
                <?php if ($app_status['status'] === 'approved'): ?>
                <div class="status-item">
                    <strong>Result:</strong> Congratulations! Your application has been approved. Please check your email for further instructions.
                </div>
                <?php elseif ($app_status['status'] === 'rejected'): ?>
                <div class="status-item">
                    <strong>Result:</strong> We regret to inform you that your application was not approved at this time. You may reapply in the next admission cycle.
                </div>
                <?php else: ?>
                <div class="status-item">
                    <strong>Status:</strong> Your application is under review. You will be notified within 5-7 business days.
                </div>
                <?php endif; ?>
            </div>
            <?php elseif ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["check_status"])): ?>
            <div class="alert error">No application found with that email address.</div>
            <?php endif; ?>
        </div>

        <!-- FAQs Tab -->
        <div id="faq" class="tab-content">
            <h2>Frequently Asked Questions</h2>

            <div class="info-section">
                <strong>Q: How long does the admissions process take?</strong>
                <p>A: Typically, we review applications within 5-7 business days and contact applicants via email with the decision.</p>
            </div>

            <div class="info-section">
                <strong>Q: What is the application fee?</strong>
                <p>A: The non-refundable application fee is $50. This fee helps us process and review your application.</p>
            </div>

            <div class="info-section">
                <strong>Q: Can I apply for multiple programs?</strong>
                <p>A: Yes, you can submit separate applications for different programs. Each application requires a separate fee.</p>
            </div>

            <div class="info-section">
                <strong>Q: What documents do I need to submit?</strong>
                <p>A: You need a valid ID, high school transcripts/diploma, and any relevant certificates. Additional documents depend on your program.</p>
            </div>

            <div class="info-section">
                <strong>Q: When is the next admission deadline?</strong>
                <p>A: We accept applications on a rolling basis throughout the year. Early applications are encouraged for better course selection.</p>
            </div>

            <div class="info-section">
                <strong>Q: Are scholarships available?</strong>
                <p>A: Yes, merit-based and need-based scholarships are available. Contact the financial aid office for more information.</p>
            </div>

            <div class="info-section">
                <strong>Q: Can international students apply?</strong>
                <p>A: Yes, international students are welcome. You must demonstrate English proficiency and have valid visa documentation.</p>
            </div>

            <div class="info-section">
                <strong>Q: What if I have questions not answered here?</strong>
                <p>A: Contact our admissions office at <strong>admissions@amezion-nimba.edu</strong> or call <strong>+231-770-XXX-XXX</strong></p>
            </div>
        </div>
    </div>

    <script>
        function switchTab(evt, tabName) {
            const tabcontent = document.getElementsByClassName("tab-content");
            for (let i = 0; i < tabcontent.length; i++) {
                tabcontent[i].classList.remove("active");
            }

            const tabbuttons = document.getElementsByClassName("tab-button");
            for (let i = 0; i < tabbuttons.length; i++) {
                tabbuttons[i].classList.remove("active");
            }

            document.getElementById(tabName).classList.add("active");
            evt.currentTarget.classList.add("active");
        }
    </script>
</body>
</html>
