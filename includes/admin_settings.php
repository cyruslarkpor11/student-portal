<?php
require_once "includes/admin_helpers.php";

adminEnsureSupportTables($conn);
$admin = adminRequireUser($conn);

$message = "";
$error = "";
$definitions = adminSettingDefinitions();

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    try {
        foreach ($definitions as $key => $meta) {
            $value = $_POST[$key] ?? $meta["default"];
            adminSaveSetting($conn, $key, is_string($value) ? trim($value) : (string) $value, (int) $admin["id"]);
        }
        $message = "System settings saved successfully.";
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}

$settings = adminGetSettings($conn);
$auditStmt = $conn->query("
    SELECT s.setting_key, s.updated_at, u.username
    FROM settings s
    LEFT JOIN users u ON s.updated_by = u.id
    ORDER BY s.updated_at DESC
    LIMIT 5
");
$recentUpdates = $auditStmt->fetchAll(PDO::FETCH_ASSOC);

adminRenderHeader("System Settings", "settings");
?>
<h2 class="page-title">System Settings</h2>
<p class="page-subtitle">Configure system settings and options for the portal.</p>

<?php if ($message !== ""): ?>
    <div class="notice"><?php echo adminH($message); ?></div>
<?php endif; ?>

<?php if ($error !== ""): ?>
    <div class="error"><?php echo adminH($error); ?></div>
<?php endif; ?>

<div class="grid grid-2">
    <div class="panel">
        <h3>Portal Configuration</h3>
        <form method="post">
            <?php foreach ($definitions as $key => $meta): ?>
                <div class="form-group">
                    <label for="<?php echo adminH($key); ?>"><?php echo adminH($meta["label"]); ?></label>
                    <?php if ($meta["type"] === "textarea"): ?>
                        <textarea id="<?php echo adminH($key); ?>" name="<?php echo adminH($key); ?>"><?php echo adminH($settings[$key] ?? $meta["default"]); ?></textarea>
                    <?php elseif ($meta["type"] === "select"): ?>
                        <select id="<?php echo adminH($key); ?>" name="<?php echo adminH($key); ?>">
                            <?php foreach ($meta["options"] as $optionValue => $optionLabel): ?>
                                <option value="<?php echo adminH($optionValue); ?>" <?php echo ($settings[$key] ?? $meta["default"]) === $optionValue ? "selected" : ""; ?>>
                                    <?php echo adminH($optionLabel); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    <?php else: ?>
                        <input
                            type="<?php echo adminH($meta["type"]); ?>"
                            id="<?php echo adminH($key); ?>"
                            name="<?php echo adminH($key); ?>"
                            value="<?php echo adminH($settings[$key] ?? $meta["default"]); ?>"
                        >
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>

            <button type="submit">Save Settings</button>
        </form>
    </div>

    <div class="panel">
        <h3>Current Snapshot</h3>
        <table>
            <thead>
                <tr>
                    <th>Setting</th>
                    <th>Value</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($definitions as $key => $meta): ?>
                    <tr>
                        <td><?php echo adminH($meta["label"]); ?></td>
                        <td><?php echo nl2br(adminH($settings[$key] ?? $meta["default"])); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <h3 style="margin-top: 24px;">Recent Setting Updates</h3>
        <?php if ($recentUpdates): ?>
            <table>
                <thead>
                    <tr>
                        <th>Setting Key</th>
                        <th>Updated By</th>
                        <th>Updated At</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentUpdates as $row): ?>
                        <tr>
                            <td><?php echo adminH($row["setting_key"]); ?></td>
                            <td><?php echo adminH($row["username"] ?: "Unknown"); ?></td>
                            <td><?php echo adminH(date("M j, Y g:i A", strtotime($row["updated_at"]))); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="empty-state">No settings have been saved yet.</div>
        <?php endif; ?>
    </div>
</div>
<?php adminRenderFooter(); ?>
