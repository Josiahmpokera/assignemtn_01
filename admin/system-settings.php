<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../config/database.php';
require_once 'header.php';

// Check if admin is logged in
if (!isset($_SESSION["admin_id"])) {
    header("location: login.php");
    exit;
}

$error = '';
$success = '';

// Check if system_settings table exists
$table_check = mysqli_query($conn, "SHOW TABLES LIKE 'system_settings'");
if (mysqli_num_rows($table_check) == 0) {
    // Table doesn't exist, create it
    $sql = file_get_contents('create_system_settings_table.sql');
    if (mysqli_multi_query($conn, $sql)) {
        do {
            if ($result = mysqli_store_result($conn)) {
                mysqli_free_result($result);
            }
        } while (mysqli_next_result($conn));
        $success = "System settings table created successfully!";
    } else {
        $error = "Error creating system settings table: " . mysqli_error($conn);
    }
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Start transaction
    mysqli_begin_transaction($conn);
    
    try {
        foreach ($_POST['settings'] as $key => $value) {
            $value = trim($value);
            $sql = "UPDATE system_settings SET setting_value = ? WHERE setting_key = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "ss", $value, $key);
            mysqli_stmt_execute($stmt);
        }
        
        mysqli_commit($conn);
        $success = "Settings updated successfully!";
    } catch (Exception $e) {
        mysqli_rollback($conn);
        $error = "Error updating settings: " . $e->getMessage();
    }
}

// Get all settings
$sql = "SELECT * FROM system_settings ORDER BY setting_key";
$result = mysqli_query($conn, $sql);
$settings = [];
while ($row = mysqli_fetch_assoc($result)) {
    $settings[$row['setting_key']] = $row;
}
?>

<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="mb-0">System Settings</h3>
                </div>
                <div class="card-body">
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <?php if (!empty($success)): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php endif; ?>
                    
                    <form method="post" id="settingsForm">
                        <div class="mb-3">
                            <label for="site_name" class="form-label">Site Name <span class="text-danger">*</span></label>
                            <input type="text" name="settings[site_name]" id="site_name" class="form-control" 
                                   value="<?php echo htmlspecialchars($settings['site_name']['setting_value'] ?? ''); ?>" required>
                            <small class="text-muted"><?php echo htmlspecialchars($settings['site_name']['setting_description'] ?? ''); ?></small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="site_email" class="form-label">Site Email <span class="text-danger">*</span></label>
                            <input type="email" name="settings[site_email]" id="site_email" class="form-control" 
                                   value="<?php echo htmlspecialchars($settings['site_email']['setting_value'] ?? ''); ?>" required>
                            <small class="text-muted"><?php echo htmlspecialchars($settings['site_email']['setting_description'] ?? ''); ?></small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="site_phone" class="form-label">Contact Phone</label>
                            <input type="text" name="settings[site_phone]" id="site_phone" class="form-control" 
                                   value="<?php echo htmlspecialchars($settings['site_phone']['setting_value'] ?? ''); ?>">
                            <small class="text-muted"><?php echo htmlspecialchars($settings['site_phone']['setting_description'] ?? ''); ?></small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="site_address" class="form-label">Address</label>
                            <textarea name="settings[site_address]" id="site_address" class="form-control" rows="2"><?php echo htmlspecialchars($settings['site_address']['setting_value'] ?? ''); ?></textarea>
                            <small class="text-muted"><?php echo htmlspecialchars($settings['site_address']['setting_description'] ?? ''); ?></small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="site_description" class="form-label">Site Description</label>
                            <textarea name="settings[site_description]" id="site_description" class="form-control" rows="3"><?php echo htmlspecialchars($settings['site_description']['setting_value'] ?? ''); ?></textarea>
                            <small class="text-muted"><?php echo htmlspecialchars($settings['site_description']['setting_description'] ?? ''); ?></small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="maintenance_mode" class="form-label">Maintenance Mode</label>
                            <select name="settings[maintenance_mode]" id="maintenance_mode" class="form-select">
                                <option value="0" <?php echo ($settings['maintenance_mode']['setting_value'] ?? '0') == '0' ? 'selected' : ''; ?>>Disabled</option>
                                <option value="1" <?php echo ($settings['maintenance_mode']['setting_value'] ?? '0') == '1' ? 'selected' : ''; ?>>Enabled</option>
                            </select>
                            <small class="text-muted"><?php echo htmlspecialchars($settings['maintenance_mode']['setting_description'] ?? ''); ?></small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="registration_enabled" class="form-label">User Registration</label>
                            <select name="settings[registration_enabled]" id="registration_enabled" class="form-select">
                                <option value="1" <?php echo ($settings['registration_enabled']['setting_value'] ?? '1') == '1' ? 'selected' : ''; ?>>Enabled</option>
                                <option value="0" <?php echo ($settings['registration_enabled']['setting_value'] ?? '1') == '0' ? 'selected' : ''; ?>>Disabled</option>
                            </select>
                            <small class="text-muted"><?php echo htmlspecialchars($settings['registration_enabled']['setting_description'] ?? ''); ?></small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="max_file_size" class="form-label">Maximum File Size (bytes)</label>
                            <input type="number" name="settings[max_file_size]" id="max_file_size" class="form-control" 
                                   value="<?php echo htmlspecialchars($settings['max_file_size']['setting_value'] ?? '5242880'); ?>">
                            <small class="text-muted"><?php echo htmlspecialchars($settings['max_file_size']['setting_description'] ?? ''); ?></small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="allowed_file_types" class="form-label">Allowed File Types</label>
                            <input type="text" name="settings[allowed_file_types]" id="allowed_file_types" class="form-control" 
                                   value="<?php echo htmlspecialchars($settings['allowed_file_types']['setting_value'] ?? ''); ?>">
                            <small class="text-muted"><?php echo htmlspecialchars($settings['allowed_file_types']['setting_description'] ?? ''); ?></small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="timezone" class="form-label">Timezone</label>
                            <select name="settings[timezone]" id="timezone" class="form-select">
                                <?php
                                $timezones = DateTimeZone::listIdentifiers();
                                foreach ($timezones as $tz) {
                                    $selected = ($settings['timezone']['setting_value'] ?? 'UTC') == $tz ? 'selected' : '';
                                    echo "<option value=\"$tz\" $selected>$tz</option>";
                                }
                                ?>
                            </select>
                            <small class="text-muted"><?php echo htmlspecialchars($settings['timezone']['setting_description'] ?? ''); ?></small>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">Save Settings</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.card {
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
}

.card-header {
    background-color: #f8f9fa;
    border-bottom: 1px solid rgba(0, 0, 0, 0.125);
}

.form-label {
    font-weight: 500;
}

textarea.form-control {
    min-height: 100px;
}
</style>

<?php require_once '../templates/footer.php'; ?> 