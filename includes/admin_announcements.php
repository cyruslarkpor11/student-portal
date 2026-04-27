<?php
require_once "includes/admin_helpers.php";

adminEnsureSupportTables($conn);
$admin = adminRequireUser($conn);

$message = "";
$error = "";
$editId = isset($_GET["edit"]) ? (int) $_GET["edit"] : 0;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST["action"] ?? "";

    try {
        if ($action === "create_announcement" || $action === "update_announcement") {
            $announcementId = (int) ($_POST["announcement_id"] ?? 0);
            $title = trim($_POST["title"] ?? "");
            $body = trim($_POST["body"] ?? "");
            $audience = $_POST["audience"] ?? "all";
            $status = $_POST["status"] ?? "draft";
            $publishedAt = trim($_POST["published_at"] ?? "");

            if ($title === "" || $body === "") {
                throw new RuntimeException("Announcement title and body are required.");
            }

            $publishedValue = $publishedAt !== "" ? $publishedAt : null;
            if ($status === "published" && $publishedValue === null) {
                $publishedValue = date("Y-m-d H:i:s");
            }

            if ($action === "create_announcement") {
                $stmt = $conn->prepare("
                    INSERT INTO announcements (title, body, audience, status, published_at, created_by)
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$title, $body, $audience, $status, $publishedValue, $admin["id"]]);
                $message = "Announcement created successfully.";
            } else {
                if ($announcementId <= 0) {
                    throw new RuntimeException("Invalid announcement selected.");
                }

                $stmt = $conn->prepare("
                    UPDATE announcements
                    SET title = ?, body = ?, audience = ?, status = ?, published_at = ?
                    WHERE announcement_id = ?
                ");
                $stmt->execute([$title, $body, $audience, $status, $publishedValue, $announcementId]);
                $message = "Announcement updated successfully.";
            }
        } elseif ($action === "delete_announcement") {
            $announcementId = (int) ($_POST["announcement_id"] ?? 0);
            if ($announcementId <= 0) {
                throw new RuntimeException("Invalid announcement selected.");
            }

            $stmt = $conn->prepare("DELETE FROM announcements WHERE announcement_id = ?");
            $stmt->execute([$announcementId]);
            $message = "Announcement deleted successfully.";
            if ($editId === $announcementId) {
                $editId = 0;
            }
        }
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}

$announcementsStmt = $conn->query("
    SELECT a.announcement_id, a.title, a.body, a.audience, a.status, a.published_at, a.created_at, a.updated_at, u.username AS created_by_name
    FROM announcements a
    LEFT JOIN users u ON a.created_by = u.id
    ORDER BY a.updated_at DESC
");
$announcements = $announcementsStmt->fetchAll(PDO::FETCH_ASSOC);

$selectedAnnouncement = [
    "announcement_id" => 0,
    "title" => "",
    "body" => "",
    "audience" => "all",
    "status" => "draft",
    "published_at" => "",
];

if ($editId > 0) {
    $selectedStmt = $conn->prepare("
        SELECT announcement_id, title, body, audience, status, published_at
        FROM announcements
        WHERE announcement_id = ?
    ");
    $selectedStmt->execute([$editId]);
    $selectedAnnouncement = $selectedStmt->fetch(PDO::FETCH_ASSOC) ?: $selectedAnnouncement;
}

adminRenderHeader("Announcements", "announcements");
?>
<h2 class="page-title">Announcements</h2>
<p class="page-subtitle">Create and manage announcements for students, instructors, admins, or the whole portal.</p>

<?php if ($message !== ""): ?>
    <div class="notice"><?php echo adminH($message); ?></div>
<?php endif; ?>

<?php if ($error !== ""): ?>
    <div class="error"><?php echo adminH($error); ?></div>
<?php endif; ?>

<div class="grid grid-2">
    <div class="panel">
        <div class="section-actions">
            <h3><?php echo $selectedAnnouncement["announcement_id"] ? "Edit Announcement" : "Create Announcement"; ?></h3>
            <?php if ($selectedAnnouncement["announcement_id"]): ?>
                <a class="button button-secondary" href="admin_announcements.php">Switch to Create</a>
            <?php endif; ?>
        </div>

        <form method="post">
            <input type="hidden" name="action" value="<?php echo $selectedAnnouncement["announcement_id"] ? "update_announcement" : "create_announcement"; ?>">
            <?php if ($selectedAnnouncement["announcement_id"]): ?>
                <input type="hidden" name="announcement_id" value="<?php echo (int) $selectedAnnouncement["announcement_id"]; ?>">
            <?php endif; ?>

            <div class="form-group">
                <label for="title">Title</label>
                <input type="text" id="title" name="title" value="<?php echo adminH($selectedAnnouncement["title"]); ?>" required>
            </div>

            <div class="grid grid-2">
                <div class="form-group">
                    <label for="audience">Audience</label>
                    <select id="audience" name="audience">
                        <?php foreach (["all", "students", "instructors", "admins"] as $audience): ?>
                            <option value="<?php echo adminH($audience); ?>" <?php echo $selectedAnnouncement["audience"] === $audience ? "selected" : ""; ?>>
                                <?php echo adminH(ucfirst($audience)); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="status">Status</label>
                    <select id="status" name="status">
                        <?php foreach (["draft", "published", "archived"] as $status): ?>
                            <option value="<?php echo adminH($status); ?>" <?php echo $selectedAnnouncement["status"] === $status ? "selected" : ""; ?>>
                                <?php echo adminH(ucfirst($status)); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label for="published_at">Published At (optional)</label>
                <input type="text" id="published_at" name="published_at" value="<?php echo adminH($selectedAnnouncement["published_at"]); ?>" placeholder="YYYY-MM-DD HH:MM:SS">
            </div>

            <div class="form-group">
                <label for="body">Announcement Body</label>
                <textarea id="body" name="body" required><?php echo adminH($selectedAnnouncement["body"]); ?></textarea>
            </div>

            <button type="submit"><?php echo $selectedAnnouncement["announcement_id"] ? "Save Announcement" : "Create Announcement"; ?></button>
        </form>
    </div>

    <div class="panel">
        <div class="section-actions">
            <h3>Announcement List</h3>
            <span class="muted"><?php echo count($announcements); ?> announcements</span>
        </div>

        <?php if ($announcements): ?>
            <?php foreach ($announcements as $announcement): ?>
                <div class="panel" style="margin-bottom: 14px;">
                    <div class="section-actions">
                        <div>
                            <strong><?php echo adminH($announcement["title"]); ?></strong><br>
                            <span class="muted">Audience: <?php echo adminH($announcement["audience"]); ?></span>
                        </div>
                        <span class="badge badge-<?php echo adminH($announcement["status"]); ?>"><?php echo adminH($announcement["status"]); ?></span>
                    </div>

                    <p style="white-space: pre-wrap;"><?php echo adminH($announcement["body"]); ?></p>
                    <p class="muted">
                        Created by: <?php echo adminH($announcement["created_by_name"] ?: "Unknown"); ?><br>
                        Updated: <?php echo adminH(date("M j, Y g:i A", strtotime($announcement["updated_at"]))); ?>
                        <?php if (!empty($announcement["published_at"])): ?>
                            <br>Published: <?php echo adminH(date("M j, Y g:i A", strtotime($announcement["published_at"]))); ?>
                        <?php endif; ?>
                    </p>

                    <div class="actions">
                        <a class="button button-secondary" href="admin_announcements.php?edit=<?php echo (int) $announcement["announcement_id"]; ?>">Edit</a>
                        <form method="post" class="inline-form" onsubmit="return confirm('Delete this announcement?');">
                            <input type="hidden" name="action" value="delete_announcement">
                            <input type="hidden" name="announcement_id" value="<?php echo (int) $announcement["announcement_id"]; ?>">
                            <button type="submit" class="button button-danger">Delete</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="empty-state">No announcements have been created yet.</div>
        <?php endif; ?>
    </div>
</div>
<?php adminRenderFooter(); ?>
