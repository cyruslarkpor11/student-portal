<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Privacy Policy - AME Zion University Nimba Extension</title>
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
            background: #e3f2fd;
            padding: 15px;
            border-left: 4px solid #2196F3;
            margin: 20px 0;
            border-radius: 5px;
        }
        .warning {
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
            margin: 0 5px;
        }
        .footer-link a:hover {
            background: #764ba2;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        table th, table td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: left;
        }
        table th {
            background: #667eea;
            color: white;
        }
    </style>
</head>
<body>
    <?php include "includes/menu.php"; ?>
    <div class="container">
        <div class="header">
            <h1>Privacy Policy</h1>
            <p>AME Zion University Nimba Extension - Student & Administration Portal</p>
        </div>

        <h2>1. Introduction</h2>
        <p>AME Zion University Nimba Extension ("University", "we", "us", or "our") operates the Student & Administration Portal ("Portal"). This Privacy Policy explains how we collect, use, disclose, and safeguard your information when you use the Portal.</p>
        <p>Please read this Privacy Policy carefully. If you do not agree with our policies and practices, please do not use our Portal.</p>

        <h2>2. Information We Collect</h2>
        <h3>2.1 Information You Provide Directly</h3>
        <ul>
            <li><strong>Registration Information:</strong> Username, password, full name, email address, phone number, student/staff ID</li>
            <li><strong>Academic Information:</strong> Course enrollment, grades, academic history, transcripts</li>
            <li><strong>Administrative Information:</strong> Department, role, employment status, administrative actions</li>
            <li><strong>Communication Data:</strong> Messages, inquiries, support requests, feedback</li>
        </ul>

        <h3>2.2 Information Collected Automatically</h3>
        <ul>
            <li>IP address and browser type</li>
            <li>Pages visited and time spent on each page</li>
            <li>Login and logout times</li>
            <li>Device information and operating system</li>
            <li>Referring website or application</li>
            <li>Cookies and similar tracking technologies</li>
        </ul>

        <h2>3. How We Use Your Information</h2>
        <p>We use the information we collect for the following purposes:</p>
        <ul>
            <li>To provide, maintain, and improve the Portal's functionality</li>
            <li>To authenticate users and verify identity</li>
            <li>To communicate with you about your account and academic/administrative matters</li>
            <li>To process academic records and administrative functions</li>
            <li>To generate reports and analytics</li>
            <li>To comply with legal and regulatory requirements</li>
            <li>To detect, prevent, and address fraud and security issues</li>
            <li>To personalize your experience on the Portal</li>
        </ul>

        <h2>4. Legal Basis for Processing</h2>
        <div class="highlight">
            <p>We process your personal data based on the following legal grounds:</p>
            <ul>
                <li><strong>Contract Performance:</strong> Processing necessary to provide educational services</li>
                <li><strong>Legal Obligation:</strong> Compliance with educational regulations and laws</li>
                <li><strong>Legitimate Interest:</strong> Security, fraud prevention, and Portal improvement</li>
                <li><strong>Consent:</strong> Where you have explicitly consented to specific processing</li>
            </ul>
        </div>

        <h2>5. Information Sharing and Disclosure</h2>
        <h3>5.1 When We Share Information</h3>
        <p>We may share your information with:</p>
        <ul>
            <li><strong>University Staff:</strong> Academic advisors, administrators, and relevant departments</li>
            <li><strong>Service Providers:</strong> Third-party vendors who assist in operating the Portal (under confidentiality agreements)</li>
            <li><strong>Legal Authorities:</strong> When required by law or court order</li>
            <li><strong>Parents/Guardians:</strong> For underage students, where permitted by law</li>
        </ul>

        <h3>5.2 Data We Do NOT Share</h3>
        <p>We do not sell, rent, or trade your personal information to third parties for marketing purposes without your explicit consent.</p>

        <h2>6. Data Security</h2>
        <div class="warning">
            <p><strong>Security Measures:</strong> We implement industry-standard security measures including:</p>
            <ul>
                <li>SSL/TLS encryption for data in transit</li>
                <li>Password hashing and salting</li>
                <li>Secure authentication mechanisms</li>
                <li>Regular security audits and updates</li>
                <li>Access controls and role-based permissions</li>
            </ul>
        </div>
        <p>However, no security system is completely foolproof. While we strive to protect your information, we cannot guarantee absolute security.</p>

        <h2>7. Data Retention</h2>
        <p>We retain your personal information for as long as necessary to:</p>
        <ul>
            <li>Provide services and maintain your account</li>
            <li>Comply with legal and regulatory requirements</li>
            <li>Resolve disputes and enforce agreements</li>
            <li>Archive for academic and administrative records (typically 7-10 years per institutional policy)</li>
        </ul>

        <h2>8. Your Rights and Choices</h2>
        <h3>8.1 Access and Correction</h3>
        <p>You have the right to:</p>
        <ul>
            <li>Access your personal information stored in the Portal</li>
            <li>Request correction of inaccurate data</li>
            <li>Request deletion of personal data (subject to legal retention requirements)</li>
            <li>Withdraw consent for specific data processing activities</li>
        </ul>

        <h3>8.2 How to Exercise Your Rights</h3>
        <p>To exercise these rights, please contact us at <strong>privacy@amezion-nimba.edu</strong> with your request and supporting documentation.</p>

        <h2>9. Third-Party Links</h2>
        <p>The Portal may contain links to third-party websites and applications. This Privacy Policy applies only to the Portal. We are not responsible for the privacy practices of external websites. We encourage you to review the privacy policies of any linked sites before providing your information.</p>

        <h2>10. Children's Privacy</h2>
        <p>The Portal is not intended for children under 13 years of age. We do not knowingly collect personal information from children under 13. If we become aware that we have collected information from a child under 13, we will promptly delete such information and notify the parent/guardian.</p>

        <h2>11. International Data Transfers</h2>
        <p>If you access the Portal from outside Liberia, please note that your information may be transferred to, stored in, and processed in Liberia or other countries where the University operates. By using the Portal, you consent to such transfers.</p>

        <h2>12. Cookies and Tracking</h2>
        <h3>12.1 Cookie Usage</h3>
        <p>The Portal uses cookies to:</p>
        <ul>
            <li>Maintain your login session</li>
            <li>Remember your preferences</li>
            <li>Analyze usage patterns</li>
            <li>Enhance security</li>
        </ul>

        <h3>12.2 Managing Cookies</h3>
        <p>You can control cookie settings through your browser. However, disabling cookies may affect the Portal's functionality and your user experience.</p>

        <h2>13. Policy Updates</h2>
        <p>We may update this Privacy Policy to reflect changes in our practices, technology, legal requirements, or other factors. We will notify you of material changes by updating the "Last Updated" date and, when required, by requesting your consent.</p>

        <h2>14. Contact Us</h2>
        <p>If you have questions, concerns, or requests regarding this Privacy Policy, please contact us:</p>
        <table>
            <tr>
                <th>Contact Method</th>
                <th>Details</th>
            </tr>
            <tr>
                <td>Email</td>
                <td>privacy@amezion-nimba.edu</td>
            </tr>
            <tr>
                <td>Phone</td>
                <td>+231-xxx-xxx-xxxx</td>
            </tr>
            <tr>
                <td>Address</td>
                <td>AME Zion University, Nimba Extension, Liberia</td>
            </tr>
            <tr>
                <td>Office Hours</td>
                <td>Monday - Friday, 9:00 AM - 5:00 PM (Local Time)</td>
            </tr>
        </table>

        <h2>15. Data Protection Officer</h2>
        <p>For data protection inquiries, you may also contact our Data Protection Officer at <strong>dpo@amezion-nimba.edu</strong>.</p>

        <p style="margin-top: 40px; text-align: center; color: #666; font-size: 0.9em;">
            <strong>Last Updated:</strong> April 25, 2026<br>
            <strong>Effective Date:</strong> April 25, 2026
        </p>

        <div class="footer-link">
            <a href="#" onclick="window.history.back(); return false;">Back</a>
            <a href="index.php">Back to Home</a>
        </div>
    </div>
</body>
</html>
