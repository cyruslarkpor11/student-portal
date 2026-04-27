<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Terms of Service - AME Zion University Nimba Extension</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: Arial, sans-serif;
            min-height: 100vh;
            color: #333;
            line-height: 1.6;
            background: linear-gradient(rgba(255,255,255,0.20), rgba(255,255,255,0.05)),
                        url("Images/Image 1.jpg") center/cover fixed;
            background-attachment: fixed;
            overflow-x: hidden;
            position: relative;
        }
        body::before {
            content: "";
            position: fixed;
            inset: 0;
            background: radial-gradient(circle at 15% 20%, rgba(255,255,255,0.45), transparent 10%),
                        radial-gradient(circle at 80% 10%, rgba(255,255,255,0.30), transparent 8%);
            mix-blend-mode: screen;
            pointer-events: none;
            opacity: 0.8;
        }
        body::after {
            content: "";
            position: fixed;
            inset: 0;
            background: repeating-linear-gradient(
              45deg,
              rgba(255,255,255,0.05),
              rgba(255,255,255,0.05) 1px,
              transparent 1px,
              transparent 20px
            );
            opacity: 0.35;
            pointer-events: none;
            animation: shine 18s linear infinite;
        }
        @keyframes shine {
            from { transform: translateX(-100%); }
            to { transform: translateX(100%); }
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
            padding: 40px 20px;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
            margin-top: 30px;
            margin-bottom: 30px;
        }
        .header {
            text-align: center;
            margin-bottom: 40px;
            border-bottom: 3px solid #667eea;
            padding-bottom: 20px;
        }
        .header h1 {
            color: #667eea;
            font-size: 2.5em;
            margin-bottom: 10px;
        }
        .header p {
            color: #666;
            font-size: 1.1em;
        }
        h2 {
            color: #667eea;
            margin-top: 30px;
            margin-bottom: 15px;
            border-left: 4px solid #667eea;
            padding-left: 15px;
        }
        h3 {
            color: #764ba2;
            margin-top: 20px;
            margin-bottom: 10px;
        }
        p {
            margin-bottom: 15px;
            text-align: justify;
        }
        ul, ol {
            margin-left: 20px;
            margin-bottom: 15px;
        }
        li {
            margin-bottom: 8px;
        }
        .highlight {
            background: #fff3cd;
            padding: 15px;
            border-left: 4px solid #ffc107;
            margin: 20px 0;
            border-radius: 5px;
        }
        .footer-link {
            text-align: center;
            margin-top: 40px;
            padding-top: 20px;
            border-top: 2px solid #ddd;
        }
        .footer-link a {
            display: inline-block;
            background: #667eea;
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
            transition: background 0.3s;
        }
        .footer-link a:hover {
            background: #764ba2;
        }
    </style>
</head>
<body>
    <?php include "includes/menu.php"; ?>
    <div class="container">
        <div class="header">
            <h1>Terms of Service</h1>
            <p>AME Zion University Nimba Extension - Student & Administration Portal</p>
        </div>

        <h2>1. Introduction and Acceptance of Terms</h2>
        <p>Welcome to the AME Zion University Nimba Extension Student & Administration Portal ("Portal"). These Terms of Service ("Terms") govern your access to and use of this Portal. By accessing or using this Portal, you agree to be bound by these Terms. If you do not agree to these Terms, you are not authorized to use this Portal.</p>

        <h2>2. User Accounts and Responsibilities</h2>
        <h3>2.1 Account Creation</h3>
        <p>To access the Portal, you must create an account with a valid username and password. You are responsible for maintaining the confidentiality of your login credentials and for all activities that occur under your account.</p>
        
        <h3>2.2 User Responsibilities</h3>
        <ul>
            <li>You agree to provide accurate, current, and complete information during registration</li>
            <li>You agree not to share your credentials with any third party</li>
            <li>You agree to notify administrators immediately of any unauthorized access to your account</li>
            <li>You are responsible for all actions taken through your account</li>
        </ul>

        <h2>3. Acceptable Use Policy</h2>
        <p>You agree not to use the Portal for any unlawful or prohibited purposes, including but not limited to:</p>
        <ul>
            <li>Violating any applicable laws or regulations</li>
            <li>Harassing, defaming, or discriminating against other users</li>
            <li>Attempting to gain unauthorized access to the Portal or other users' accounts</li>
            <li>Uploading or transmitting viruses or malicious code</li>
            <li>Engaging in spam, phishing, or fraudulent activities</li>
            <li>Selling, transferring, or assigning access to another person</li>
        </ul>

        <h2>4. Intellectual Property Rights</h2>
        <p>All content, materials, and design elements of the Portal, including but not limited to text, graphics, logos, images, and software, are the property of AME Zion University or its licensors and are protected by international copyright laws.</p>
        <p>You are granted a limited, non-exclusive, non-transferable license to use the Portal solely for your authorized educational or administrative purposes.</p>

        <h2>5. Limitation of Liability</h2>
        <div class="highlight">
            <p><strong>DISCLAIMER:</strong> The Portal is provided on an "AS IS" and "AS AVAILABLE" basis. AME Zion University makes no warranties, expressed or implied, regarding the Portal's operation or the information, content, or materials included on the Portal.</p>
            <p>AME Zion University shall not be liable for any indirect, incidental, special, or consequential damages arising from your use of or inability to use the Portal.</p>
        </div>

        <h2>6. Data and Privacy</h2>
        <p>Your use of the Portal is also governed by our Privacy Policy. Please review our Privacy Policy to understand our practices regarding the collection and use of your personal information.</p>

        <h2>7. User Conduct</h2>
        <p>You agree to conduct yourself professionally and respectfully when using the Portal. Any violations of this Terms of Service may result in:</p>
        <ul>
            <li>Suspension or termination of your account</li>
            <li>Denial of future access to the Portal</li>
            <li>Legal action where applicable</li>
        </ul>

        <h2>8. Modifications to Terms</h2>
        <p>AME Zion University reserves the right to modify these Terms at any time. Continued use of the Portal following any modifications constitutes your acceptance of the new Terms.</p>

        <h2>9. Termination of Access</h2>
        <p>AME Zion University reserves the right to terminate or suspend your access to the Portal at any time, with or without cause, and with or without notice.</p>

        <h2>10. Contact Information</h2>
        <p>For questions regarding these Terms of Service, please contact us at:</p>
        <ul>
            <li><strong>Email:</strong> admin@amezion-nimba.edu</li>
            <li><strong>Phone:</strong> +231-xxx-xxx-xxxx</li>
            <li><strong>Address:</strong> AME Zion University, Nimba Extension, Liberia</li>
        </ul>

        <h2>11. Governing Law</h2>
        <p>These Terms of Service are governed by and construed in accordance with the laws of Liberia, without regard to its conflict of law provisions.</p>

        <p style="margin-top: 40px; text-align: center; color: #666; font-size: 0.9em;">
            <strong>Last Updated:</strong> April 25, 2026
        </p>

        <div class="footer-link">
            <a href="#" onclick="window.history.back(); return false;">Back</a>
            <a href="index.php">Back to Home</a>
        </div>
    </div>
</body>
</html>
