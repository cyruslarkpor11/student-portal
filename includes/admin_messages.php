<?php
require_once "includes/admin_helpers.php";

adminEnsureSupportTables($conn);
$admin = adminRequireUser($conn);

$message = "";
$error = "";
$statusFilter = $_GET["status"] ?? "all";
$viewId = isset($_GET["view"]) ? (int) $_GET["view"] : 0;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST["action"] ?? "";
    $contactId = (int) ($_POST["contact_id"] ?? 0);

    try {
        if ($contactId <= 0) {
            throw new RuntimeException("Invalid contact message selected.");
        }

        if ($action === "mark_read") {
            $stmt = $conn->prepare("UPDATE contacts SET status = 'read' WHERE id = ?");
            $stmt->execute([$contactId]);
            $message = "Contact message marked as read.";
        } elseif ($action === "resolve") {
            $stmt = $conn->prepare("UPDATE contacts SET status = 'resolved' WHERE id = ?");
            $stmt->execute([$contactId]);
            $message = "Contact message marked as resolved.";
        } elseif ($action === "reopen") {
            $stmt = $conn->prepare("UPDATE contacts SET status = 'new' WHERE id = ?");
            $stmt->execute([$contactId]);
            $message = "Contact message reopened.";
        } elseif ($action === "delete") {
            $stmt = $conn->prepare("DELETE FROM contacts WHERE id = ?");
            $stmt->execute([$contactId]);
            $message = "Contact message deleted.";
            if ($viewId === $contactId) {
                $viewId = 0;
            }
        }
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}

$counts = [
    "all" => adminCount($conn, "SELECT COUNT(*) FROM contacts"),
    "new" => adminCount($conn, "SELECT COUNT(*) FROM contacts WHERE status = 'new'"),
    "read" => adminCount($conn, "SELECT COUNT(*) FROM contacts WHERE status = 'read'"),
    "resolved" => adminCount($conn, "SELECT COUNT(*) FROM contacts WHERE status = 'resolved'"),
];

$params = [];
$whereSql = "";
if (in_array($statusFilter, ["new", "read", "resolved"], true)) {
    $whereSql = "WHERE status = ?";
    $params[] = $statusFilter;
}

$contactsStmt = $conn->prepare("
    SELECT id, name, username, email, subject, message, status, submitted_at
    FROM contacts
    $whereSql
    ORDER BY submitted_at DESC
");
$contactsStmt->execute($params);
$contacts = $contactsStmt->fetchAll(PDO::FETCH_ASSOC);

$selectedContact = null;
if ($viewId > 0) {
    $selectedStmt = $conn->prepare("
        SELECT id, name, username, email, subject, message, status, submitted_at
        FROM contacts
        WHERE id = ?
    ");
    $selectedStmt->execute([$viewId]);
    $selectedContact = $selectedStmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

adminRenderHeader("View Messages", "messages");
?>
<h2 class="page-title">View Messages</h2>
<p class="page-subtitle">Check all contact form submissions, keep track of their status, and review full message details.</p>

<?php if ($message !== ""): ?>
    <div class="notice"><?php echo adminH($message); ?></div>
<?php endif; ?>

<?php if ($error !== ""): ?>
    <div class="error"><?php echo adminH($error); ?></div>
<?php endif; ?>

<div class="panel">
    <div class="section-actions">
        <h3>Message Filters</h3>
        <div class="actions">
            <a class="button <?php echo $statusFilter === "all" ? "" : "button-secondary"; ?>" href="admin_messages.php">All (<?php echo $counts["all"]; ?>)</a>
            <a class="button <?php echo $statusFilter === "new" ? "" : "button-secondary"; ?>" href="admin_messages.php?status=new">New (<?php echo $counts["new"]; ?>)</a>
            <a class="button <?php echo $statusFilter === "read" ? "" : "button-secondary"; ?>" href="admin_messages.php?status=read">Read (<?php echo $counts["read"]; ?>)</a>
            <a class="button <?php echo $statusFilter === "resolved" ? "" : "button-secondary"; ?>" href="admin_messages.php?status=resolved">Resolved (<?php echo $counts["resolved"]; ?>)</a>
        </div>
    </div>
</div>

<div class="grid grid-2">
    <div class="panel">
        <div class="section-actions">
            <h3>Contact Submissions</h3>
            <span class="muted"><?php echo count($contacts); ?> shown</span>
        </div>

        <?php if ($contacts): ?>
            <table>
                <thead>
                    <tr>
                        <th>Sender</th>
                        <th>Subject</th>
                        <th>Status</th>
                        <th>Submitted</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($contacts as $contact): ?>
                        <tr>
                            <td>
                                <strong><?php echo adminH($contact["name"]); ?></strong><br>
                                <span class="muted"><?php echo adminH($contact["email"]); ?></span>
                                <?php if (!empty($contact["username"])): ?>
                                    <br><span class="muted">Portal user: <?php echo adminH($contact["username"]); ?></span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo adminH($contact["subject"] ?: "No subject"); ?></td>
                            <td><span class="badge badge-<?php echo adminH($contact["status"]); ?>"><?php echo adminH($contact["status"]); ?></span></td>
                            <td><?php echo adminH(date("M j, Y g:i A", strtotime($contact["submitted_at"]))); ?></td>
                            <td>
                                <div class="actions">
                                    <a class="button button-secondary" href="admin_messages.php?status=<?php echo adminH($statusFilter); ?>&view=<?php echo (int) $contact["id"]; ?>">Open</a>
                                    <form method="post" class="inline-form">
                                        <input type="hidden" name="action" value="resolve">
                                        <input type="hidden" name="contact_id" value="<?php echo (int) $contact["id"]; ?>">
                                        <button type="submit">Resolve</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="empty-state">No contact submissions match this filter.</div>
        <?php endif; ?>
    </div>

    <div class="panel">
        <div class="section-actions">
            <h3>Message Detail</h3>
            <?php if ($selectedContact): ?>
                <span class="badge badge-<?php echo adminH($selectedContact["status"]); ?>"><?php echo adminH($selectedContact["status"]); ?></span>
            <?php endif; ?>
        </div>

        <?php if ($selectedContact): ?>
            <p><strong>From:</strong> <?php echo adminH($selectedContact["name"]); ?></p>
            <p><strong>Email:</strong> <?php echo adminH($selectedContact["email"]); ?></p>
            <p><strong>Portal Username:</strong> <?php echo adminH($selectedContact["username"] ?: "Guest / not provided"); ?></p>
            <p><strong>Subject:</strong> <?php echo adminH($selectedContact["subject"] ?: "No subject"); ?></p>
            <p><strong>Received:</strong> <?php echo adminH(date("F j, Y g:i A", strtotime($selectedContact["submitted_at"]))); ?></p>

            <div class="panel" style="margin-top: 16px;">
                <strong>Message</strong>
                <p style="white-space: pre-wrap; margin-bottom: 0;"><?php echo adminH($selectedContact["message"]); ?></p>
            </div>

            <div class="actions" style="margin-top: 16px;">
                <form method="post" class="inline-form">
                    <input type="hidden" name="action" value="mark_read">
                    <input type="hidden" name="contact_id" value="<?php echo (int) $selectedContact["id"]; ?>">
                    <button type="submit" class="button button-secondary">Mark Read</button>
                </form>
                <form method="post" class="inline-form">
                    <input type="hidden" name="action" value="resolve">
                    <input type="hidden" name="contact_id" value="<?php echo (int) $selectedContact["id"]; ?>">
                    <button type="submit">Resolve</button>
                </form>
                <form method="post" class="inline-form">
                    <input type="hidden" name="action" value="reopen">
                    <input type="hidden" name="contact_id" value="<?php echo (int) $selectedContact["id"]; ?>">
                    <button type="submit" class="button button-secondary">Reopen</button>
                </form>
                <form method="post" class="inline-form" onsubmit="return confirm('Delete this contact message permanently?');">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="contact_id" value="<?php echo (int) $selectedContact["id"]; ?>">
                    <button type="submit" class="button button-danger">Delete</button>
                </form>
            </div>
        <?php else: ?>
            <div class="empty-state">Select a contact submission to read the full message.</div>
        <?php endif; ?>
    </div>
</div>
<?php adminRenderFooter(); ?>
