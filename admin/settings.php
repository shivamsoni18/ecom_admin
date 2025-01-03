<?php
session_start();
require_once(__DIR__ . '/../models/Setting.php');

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

$setting = new Setting();
$settings = $setting->getAllSettings();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        .settings-card {
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            margin-bottom: 1.5rem;
        }
        .settings-header {
            background-color: #f8f9fa;
            border-bottom: 1px solid #dee2e6;
            padding: 1rem 1.5rem;
        }
        .settings-body {
            padding: 1.5rem;
        }
        .form-label {
            font-weight: 500;
            color: #495057;
        }
        .form-text {
            color: #6c757d;
        }
        .nav-pills .nav-link {
            color: #495057;
            padding: 0.75rem 1rem;
        }
        .nav-pills .nav-link.active {
            background-color: #0d6efd;
            color: white;
        }
        .tab-content {
            padding: 20px;
            background: #fff;
            border: 1px solid #dee2e6;
            border-radius: 0.375rem;
        }
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <div class="container mt-4">
        <div class="row">
            <div class="col-md-12">
                <div class="card settings-card">
                    <div class="settings-header">
                        <h4 class="mb-0">
                            <i class="fas fa-cog me-2"></i>System Settings
                        </h4>
                    </div>
                    <div class="settings-body">
                        <!-- Settings Navigation -->
                        <ul class="nav nav-pills mb-4" id="settingsTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" data-bs-toggle="pill" data-bs-target="#general">
                                    <i class="fas fa-sliders-h me-2"></i>General
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" data-bs-toggle="pill" data-bs-target="#email">
                                    <i class="fas fa-envelope me-2"></i>Email
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" data-bs-toggle="pill" data-bs-target="#payment">
                                    <i class="fas fa-credit-card me-2"></i>Payment
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" data-bs-toggle="pill" data-bs-target="#social">
                                    <i class="fas fa-share-alt me-2"></i>Social Media
                                </button>
                            </li>
                        </ul>

                        <!-- Settings Content -->
                        <div class="tab-content" id="settingsTabContent">
                            <!-- General Settings -->
                            <div class="tab-pane fade show active" id="general">
                                <form id="generalSettingsForm">
                                    <div class="row mb-4">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Site Name</label>
                                                <input type="text" 
                                                       class="form-control" 
                                                       name="site_name" 
                                                       value="<?php echo htmlspecialchars($settings['site_name'] ?? ''); ?>">
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Site Description</label>
                                                <textarea class="form-control" 
                                                          name="site_description" 
                                                          rows="3"><?php echo htmlspecialchars($settings['site_description'] ?? ''); ?></textarea>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Contact Email</label>
                                                <input type="email" 
                                                       class="form-control" 
                                                       name="contact_email" 
                                                       value="<?php echo htmlspecialchars($settings['contact_email'] ?? ''); ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Currency</label>
                                                <select class="form-select" name="currency">
                                                    <option value="INR" <?php echo ($settings['currency'] ?? 'INR') === 'INR' ? 'selected' : ''; ?>>INR (₹)</option>
                                                    <option value="USD" <?php echo ($settings['currency'] ?? '') === 'USD' ? 'selected' : ''; ?>>USD ($)</option>
                                                    <option value="EUR" <?php echo ($settings['currency'] ?? '') === 'EUR' ? 'selected' : ''; ?>>EUR (€)</option>
                                                    <option value="GBP" <?php echo ($settings['currency'] ?? '') === 'GBP' ? 'selected' : ''; ?>>GBP (£)</option>
                                                </select>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Timezone</label>
                                                <select class="form-select" name="timezone">
                                                    <option value="Asia/Kolkata" <?php echo ($settings['timezone'] ?? 'Asia/Kolkata') === 'Asia/Kolkata' ? 'selected' : ''; ?>>Asia/Kolkata (GMT+5:30)</option>
                                                    <?php
                                                    $timezones = DateTimeZone::listIdentifiers();
                                                    foreach ($timezones as $timezone) {
                                                        if ($timezone !== 'Asia/Kolkata') { // Skip Asia/Kolkata as it's already the first option
                                                            $selected = ($settings['timezone'] ?? '') === $timezone ? 'selected' : '';
                                                            echo "<option value=\"$timezone\" $selected>$timezone</option>";
                                                        }
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Items Per Page</label>
                                                <input type="number" 
                                                       class="form-control" 
                                                       name="items_per_page" 
                                                       value="<?php echo htmlspecialchars($settings['items_per_page'] ?? '10'); ?>">
                                            </div>
                                        </div>
                                    </div>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i>Save General Settings
                                    </button>
                                </form>
                            </div>

                            <!-- Email Settings -->
                            <div class="tab-pane fade" id="email">
                                <form id="emailSettingsForm">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">SMTP Host</label>
                                                <input type="text" 
                                                       class="form-control" 
                                                       name="smtp_host" 
                                                       value="<?php echo htmlspecialchars($settings['smtp_host'] ?? ''); ?>">
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">SMTP Port</label>
                                                <input type="number" 
                                                       class="form-control" 
                                                       name="smtp_port" 
                                                       value="<?php echo htmlspecialchars($settings['smtp_port'] ?? '587'); ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">SMTP Username</label>
                                                <input type="text" 
                                                       class="form-control" 
                                                       name="smtp_username" 
                                                       value="<?php echo htmlspecialchars($settings['smtp_username'] ?? ''); ?>">
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">SMTP Password</label>
                                                <input type="password" 
                                                       class="form-control" 
                                                       name="smtp_password" 
                                                       value="<?php echo htmlspecialchars($settings['smtp_password'] ?? ''); ?>">
                                            </div>
                                        </div>
                                    </div>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i>Save Email Settings
                                    </button>
                                </form>
                            </div>

                            <!-- Payment Settings -->
                            <div class="tab-pane fade" id="payment">
                                <form id="paymentSettingsForm">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Stripe Public Key</label>
                                                <input type="text" 
                                                       class="form-control" 
                                                       name="stripe_public_key" 
                                                       value="<?php echo htmlspecialchars($settings['stripe_public_key'] ?? ''); ?>">
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Stripe Secret Key</label>
                                                <input type="password" 
                                                       class="form-control" 
                                                       name="stripe_secret_key" 
                                                       value="<?php echo htmlspecialchars($settings['stripe_secret_key'] ?? ''); ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">PayPal Client ID</label>
                                                <input type="text" 
                                                       class="form-control" 
                                                       name="paypal_client_id" 
                                                       value="<?php echo htmlspecialchars($settings['paypal_client_id'] ?? ''); ?>">
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">PayPal Secret</label>
                                                <input type="password" 
                                                       class="form-control" 
                                                       name="paypal_secret" 
                                                       value="<?php echo htmlspecialchars($settings['paypal_secret'] ?? ''); ?>">
                                            </div>
                                        </div>
                                    </div>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i>Save Payment Settings
                                    </button>
                                </form>
                            </div>

                            <!-- Social Media Settings -->
                            <div class="tab-pane fade" id="social">
                                <form id="socialSettingsForm">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Facebook URL</label>
                                                <input type="url" 
                                                       class="form-control" 
                                                       name="facebook_url" 
                                                       value="<?php echo htmlspecialchars($settings['facebook_url'] ?? ''); ?>">
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Twitter URL</label>
                                                <input type="url" 
                                                       class="form-control" 
                                                       name="twitter_url" 
                                                       value="<?php echo htmlspecialchars($settings['twitter_url'] ?? ''); ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Instagram URL</label>
                                                <input type="url" 
                                                       class="form-control" 
                                                       name="instagram_url" 
                                                       value="<?php echo htmlspecialchars($settings['instagram_url'] ?? ''); ?>">
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">LinkedIn URL</label>
                                                <input type="url" 
                                                       class="form-control" 
                                                       name="linkedin_url" 
                                                       value="<?php echo htmlspecialchars($settings['linkedin_url'] ?? ''); ?>">
                                            </div>
                                        </div>
                                    </div>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i>Save Social Media Settings
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Handle form submissions
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', async (e) => {
                e.preventDefault();
                const formData = new FormData(e.target);
                const formId = e.target.id;

                try {
                    const response = await fetch('process_settings.php', {
                        method: 'POST',
                        body: JSON.stringify({
                            section: formId.replace('SettingsForm', ''),
                            data: Object.fromEntries(formData)
                        }),
                        headers: {
                            'Content-Type': 'application/json'
                        }
                    });

                    const result = await response.json();
                    
                    if (result.status === 'success') {
                        alert('Settings saved successfully!');
                    } else {
                        alert('Error: ' + result.message);
                    }
                } catch (error) {
                    alert('Error saving settings: ' + error.message);
                }
            });
        });
    </script>
</body>
</html> 