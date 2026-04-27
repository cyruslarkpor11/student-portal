<?php
require_once "includes/admin_helpers.php";
require_once "includes/mail.php";

adminEnsureSupportTables($conn);
$admin = adminRequireUser($conn);

$message = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['id'])) {
    $action = $_POST['action'];
    $id = (int) $_POST['id'];

    if ($id <= 0) {
        $message = ['type' => 'error', 'text' => 'Invalid application id.'];
    } else {
        if ($action === 'approve' || $action === 'reject') {
            $newStatus = $action === 'approve' ? 'approved' : 'rejected';
            
            // Fetch applicant details before updating
            $appStmt = $conn->prepare("SELECT * FROM admission_applications WHERE id = ?");
            $appStmt->execute([$id]);
            $applicant = $appStmt->fetch(PDO::FETCH_ASSOC);
            
            // Update status
            $stmt = $conn->prepare("UPDATE admission_applications SET status = ? WHERE id = ?");
            $stmt->execute([$newStatus, $id]);
            
            // Send decision email to applicant
            if ($applicant) {
                sendAdmissionDecisionEmail(
                    $applicant['first_name'],
                    $applicant['email'],
                    $id,
                    $newStatus,
                    $applicant['program_applied']
                );
            }
            
            $message = ['type' => 'success', 'text' => "Application #$id marked as $newStatus. Decision email sent to applicant."];
        } elseif ($action === 'delete') {
            $stmt = $conn->prepare("DELETE FROM admission_applications WHERE id = ?");
            $stmt->execute([$id]);
            $message = ['type' => 'success', 'text' => "Application #$id deleted."];
        }
    }
}

// Fetch recent applications (handle missing table gracefully)
try {
    $stmt = $conn->query("SELECT * FROM admission_applications ORDER BY application_date DESC LIMIT 200");
    $applications = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Try to ensure support tables (this will create the admissions table if missing)
    try {
        adminEnsureSupportTables($conn);
        $stmt = $conn->query("SELECT * FROM admission_applications ORDER BY application_date DESC LIMIT 200");
        $applications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e2) {
        // Still failing — show a friendly message and prevent fatal error
        $applications = [];
        $errorMessage = 'Database table `admission_applications` is missing or inaccessible. Please run the setup script: includes/create_tables.php';
        $message = ['type' => 'error', 'text' => $errorMessage];
    }
}

adminRenderHeader('Admissions', 'admissions');
?>
    <h2 class="page-title">Admissions</h2>
    <p class="page-subtitle">Review and manage admission applications.</p>

    <?php if ($message): ?>
        <div class="notice" role="status"><?php echo adminH($message['text']); ?></div>
    <?php endif; ?>

    <div class="panel">
        <h3>Applications (most recent first)</h3>
        <?php if (empty($applications)): ?>
            <div class="empty-state">No applications found.</div>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Program</th>
                        <th>Phone</th>
                        <th>Submitted</th>
                        <th>Status</th>
                        <th style="width:220px">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($applications as $app): ?>
                    <tr>
                        <td><?php echo adminH($app['id']); ?></td>
                        <td><?php echo adminH($app['first_name'] . ' ' . $app['last_name']); ?><br><span class="muted"><?php echo adminH($app['high_school']); ?></span></td>
                        <td><?php echo adminH($app['email']); ?></td>
                        <td><?php echo adminH($app['program_applied']); ?></td>
                        <td><?php echo adminH($app['phone']); ?></td>
                        <td><?php echo adminH(date('M j, Y', strtotime($app['application_date']))); ?></td>
                        <td><span class="badge badge-<?php echo adminH($app['status'] ?: 'pending'); ?>"><?php echo adminH($app['status'] ?: 'pending'); ?></span></td>
                        <td>
                            <div class="actions">
                                <form class="inline-form" method="post" onsubmit="return confirm('Approve this application?');">
                                    <input type="hidden" name="id" value="<?php echo adminH($app['id']); ?>">
                                    <input type="hidden" name="action" value="approve">
                                    <button type="submit" class="button">Approve</button>
                                </form>

                                <form class="inline-form" method="post" onsubmit="return confirm('Reject this application?');">
                                    <input type="hidden" name="id" value="<?php echo adminH($app['id']); ?>">
                                    <input type="hidden" name="action" value="reject">
                                    <button type="submit" class="button-secondary">Reject</button>
                                </form>

                                <form class="inline-form" method="post" onsubmit="return confirm('Permanently delete this application?');">
                                    <input type="hidden" name="id" value="<?php echo adminH($app['id']); ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <button type="submit" class="button-danger">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="8">
                            <div style="padding:12px 0;">
                                <strong>Address:</strong> <?php echo adminH($app['address']); ?>
                                &nbsp;&nbsp;|&nbsp;&nbsp; <strong>GPA:</strong> <?php echo adminH($app['gpa']); ?>
                                &nbsp;&nbsp;|&nbsp;&nbsp; <strong>DOB:</strong> <?php echo adminH($app['date_of_birth']); ?>
                                <br><br>
                                <strong>Documents:</strong>
                                <?php if (!empty($app['diploma_path'])): ?>
                                    <a href="<?php echo adminH($app['diploma_path']); ?>" target="_blank" class="button-link">📄 Diploma</a>
                                <?php endif; ?>
                                <?php if (!empty($app['transcript_path'])): ?>
                                    <a href="<?php echo adminH($app['transcript_path']); ?>" target="_blank" class="button-link">📄 Transcript</a>
                                <?php endif; ?>
                                <?php if (!empty($app['supporting_docs_path'])): ?>
                                    <a href="<?php echo adminH($app['supporting_docs_path']); ?>" target="_blank" class="button-link">📄 Supporting Docs</a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

<?php adminRenderFooter();
?>
