<?php
/**
 * Email helper for the university portal
 * Handles all email sending with HTML templates
 */

$GLOBALS['email_config'] = [
    'from_address' => 'admissions@amezionedu.com',
    'from_name' => 'AME Zion University Admissions',
    'admin_email' => 'admin@amezionedu.com',
    'smtp_enabled' => false, // Set to true if using SMTP server
];

/**
 * Send admission confirmation email to applicant
 */
function sendAdmissionConfirmationEmail($firstName, $email, $applicationId, $program): bool
{
    $config = $GLOBALS['email_config'];
    
    $subject = "Application Received - Reference #" . str_pad($applicationId, 6, "0", STR_PAD_LEFT);
    
    $htmlBody = "
    <!DOCTYPE html>
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: linear-gradient(rgba(16, 14, 45, 0.9), rgba(69, 26, 123, 0.8)); color: #ffd700; padding: 20px; text-align: center; border-radius: 8px; }
            .content { padding: 20px; background: #f9f9f9; border-radius: 8px; margin-top: 10px; }
            .footer { margin-top: 20px; padding-top: 20px; border-top: 1px solid #ddd; font-size: 12px; color: #666; }
            .reference { background: #ffd700; color: #000; padding: 10px; text-align: center; font-weight: bold; border-radius: 5px; margin: 15px 0; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>🎓 Application Received</h1>
            </div>
            
            <div class='content'>
                <p>Dear <strong>" . htmlspecialchars($firstName) . "</strong>,</p>
                
                <p>Thank you for submitting your application to African Methodist Episcopal Zion University Nimba Extension.</p>
                
                <p><strong>Program Applied For:</strong> " . htmlspecialchars(ucfirst(str_replace('_', ' ', $program))) . "</p>
                
                <div class='reference'>
                    Reference #" . str_pad($applicationId, 6, "0", STR_PAD_LEFT) . "
                </div>
                
                <p>Your application has been successfully received. Our admissions team will review it carefully and notify you of the decision within 5-7 business days.</p>
                
                <p>You can check your application status anytime by visiting our admission portal and searching with this email address.</p>
                
                <p><strong>What happens next:</strong></p>
                <ul>
                    <li>Our team reviews your application</li>
                    <li>You receive a status update via email</li>
                    <li>Upon approval, instructions for enrollment are sent</li>
                </ul>
                
                <p>If you have any questions, please don't hesitate to contact us at <strong>" . htmlspecialchars($config['admin_email']) . "</strong></p>
                
                <p>Best regards,<br><strong>Admissions Office</strong><br>AME Zion University</p>
            </div>
            
            <div class='footer'>
                <p>This is an automated email. Please do not reply directly to this message.</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    return sendEmail($email, $subject, $htmlBody);
}

/**
 * Send approval/rejection decision email to applicant
 */
function sendAdmissionDecisionEmail($firstName, $email, $applicationId, $status, $program): bool
{
    $config = $GLOBALS['email_config'];
    $isApproved = strtolower($status) === 'approved';
    
    if ($isApproved) {
        $subject = "Application Approved! 🎉 - Reference #" . str_pad($applicationId, 6, "0", STR_PAD_LEFT);
        $statusColor = '#34a853';
        $statusText = 'APPROVED';
        $messageBody = "
            <p>Congratulations! We are pleased to inform you that your application for the <strong>" . htmlspecialchars(ucfirst(str_replace('_', ' ', $program))) . "</strong> program has been <strong>APPROVED</strong>.</p>
            
            <p>Our enrollment team will contact you shortly with next steps and enrollment instructions.</p>
            
            <p>Thank you for choosing AME Zion University!</p>
        ";
    } else {
        $subject = "Application Update - Reference #" . str_pad($applicationId, 6, "0", STR_PAD_LEFT);
        $statusColor = '#ea4335';
        $statusText = 'NOT APPROVED';
        $messageBody = "
            <p>Thank you for your application to our <strong>" . htmlspecialchars(ucfirst(str_replace('_', ' ', $program))) . "</strong> program.</p>
            
            <p>After careful review by our admissions committee, we regret to inform you that your application was not approved at this time.</p>
            
            <p>You are welcome to reapply in the next admission cycle. We encourage you to reach out to our admissions office if you would like feedback on your application.</p>
            
            <p>We appreciate your interest in AME Zion University.</p>
        ";
    }
    
    $htmlBody = "
    <!DOCTYPE html>
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: linear-gradient(rgba(16, 14, 45, 0.9), rgba(69, 26, 123, 0.8)); color: #ffd700; padding: 20px; text-align: center; border-radius: 8px; }
            .content { padding: 20px; background: #f9f9f9; border-radius: 8px; margin-top: 10px; }
            .footer { margin-top: 20px; padding-top: 20px; border-top: 1px solid #ddd; font-size: 12px; color: #666; }
            .status-badge { background: " . $statusColor . "; color: white; padding: 12px; text-align: center; font-weight: bold; border-radius: 5px; margin: 15px 0; font-size: 18px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>📧 Application Decision</h1>
            </div>
            
            <div class='content'>
                <p>Dear <strong>" . htmlspecialchars($firstName) . "</strong>,</p>
                
                <div class='status-badge'>" . $statusText . "</div>
                
                " . $messageBody . "
                
                <p>Reference #: <strong>" . str_pad($applicationId, 6, "0", STR_PAD_LEFT) . "</strong></p>
                
                <p>Contact: <strong>" . htmlspecialchars($config['admin_email']) . "</strong></p>
                
                <p>Best regards,<br><strong>Admissions Office</strong><br>AME Zion University</p>
            </div>
            
            <div class='footer'>
                <p>This is an automated email. Please do not reply directly to this message.</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    return sendEmail($email, $subject, $htmlBody);
}

/**
 * Send admin notification of new application
 */
function sendAdminNewApplicationNotification($applicantName, $email, $program, $applicationId): bool
{
    $config = $GLOBALS['email_config'];
    $subject = "New Admission Application Submitted - #" . str_pad($applicationId, 6, "0", STR_PAD_LEFT);
    
    $htmlBody = "
    <!DOCTYPE html>
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #0e3052; color: #f0c24b; padding: 15px; text-align: center; border-radius: 8px; }
            .content { padding: 15px; background: #f5f5f5; border-radius: 8px; margin-top: 10px; }
            .info-row { padding: 10px; border-bottom: 1px solid #ddd; }
            .info-label { font-weight: bold; color: #0e3052; }
            .action-button { background: #b31f1f; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin-top: 15px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h2>New Application Received</h2>
            </div>
            
            <div class='content'>
                <p>A new admission application has been submitted and is awaiting review.</p>
                
                <div class='info-row'>
                    <span class='info-label'>Applicant Name:</span> " . htmlspecialchars($applicantName) . "
                </div>
                
                <div class='info-row'>
                    <span class='info-label'>Email:</span> " . htmlspecialchars($email) . "
                </div>
                
                <div class='info-row'>
                    <span class='info-label'>Program:</span> " . htmlspecialchars(ucfirst(str_replace('_', ' ', $program))) . "
                </div>
                
                <div class='info-row'>
                    <span class='info-label'>Reference #:</span> " . str_pad($applicationId, 6, "0", STR_PAD_LEFT) . "
                </div>
                
                <p><a href='http://localhost/Myproject/admin_admissions.php' class='action-button'>Review in Admin Panel</a></p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    return sendEmail($config['admin_email'], $subject, $htmlBody);
}

/**
 * Generic email sending function
 * Supports both mail() and SMTP
 */
function sendEmail($to, $subject, $htmlBody): bool
{
    $config = $GLOBALS['email_config'];
    
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "From: " . $config['from_name'] . " <" . $config['from_address'] . ">\r\n";
    $headers .= "Reply-To: " . $config['from_address'] . "\r\n";
    
    // For localhost testing, emails won't actually send but function returns true
    // In production with mail() or SMTP configured, this will send
    if ($config['smtp_enabled']) {
        // SMTP implementation would go here
        // For now, we use PHP's built-in mail() function
        return @mail($to, $subject, $htmlBody, $headers);
    } else {
        // In localhost environment, just return true (emails won't actually send)
        // You can enable real email by setting smtp_enabled to true and configuring
        return true;
    }
}

/**
 * Update email configuration
 */
function updateEmailConfig($key, $value): void
{
    $GLOBALS['email_config'][$key] = $value;
}

/**
 * Get current email configuration
 */
function getEmailConfig(): array
{
    return $GLOBALS['email_config'];
}
?>
