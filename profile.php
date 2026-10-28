<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/validation.php';
require_once __DIR__ . '/includes/upload-helper.php';

require_login();

$userId = current_user_id();
$db = getDB();

$profileErrors = [];
$passwordErrors = [];

$stmt = $db->prepare("SELECT * FROM users WHERE user_id = ? LIMIT 1");
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user) {
    http_response_code(404);
    die('User not found.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require_or_die();
    $action = $_POST['action'] ?? '';

    if ($action === 'update_profile') {
        $fullName   = trim($_POST['full_name'] ?? '');
        $email      = trim($_POST['email'] ?? '');
        $university = trim($_POST['university'] ?? '');
        $areaId     = $_POST['area_id'] ?? '';
        $currentPwForEmail = $_POST['current_password_for_email'] ?? '';

        if ($err = validate_full_name($fullName)) $profileErrors[] = $err;
        if ($err = validate_email_format($email)) $profileErrors[] = $err;
        if ($err = validate_university($university)) $profileErrors[] = $err;

        $emailChanged = strcasecmp($email, $user['email']) !== 0;

        if ($emailChanged) {
            if ($currentPwForEmail === '' || !password_verify($currentPwForEmail, $user['password_hash'])) {
                $profileErrors[] = 'To change your email, please enter your current password correctly.';
            } else {
                $stmt = $db->prepare("SELECT user_id FROM users WHERE email = ? AND user_id != ? LIMIT 1");
                $stmt->execute([$email, $userId]);
                if ($stmt->fetch()) {
                    $profileErrors[] = 'That email is already in use by another account.';
                }
            }
        }

        $areaIdClean = null;
        if ($areaId !== '') {
            $stmt = $db->prepare("SELECT area_id FROM areas WHERE area_id = ? AND is_active = 1 LIMIT 1");
            $stmt->execute([$areaId]);
            if (!$stmt->fetch()) {
                $profileErrors[] = 'Please select a valid area.';
            } else {
                $areaIdClean = (int)$areaId;
            }
        }

        $uploadResult = ['success' => true, 'path' => null, 'error' => null];
        if (empty($profileErrors)) {
            $uploadResult = handle_single_image_upload('profile_image', UPLOAD_DIR_PROFILES);
            if (!$uploadResult['success']) {
                $profileErrors[] = $uploadResult['error'];
            }
        }

        if (empty($profileErrors)) {
            $newPhotoPath = $user['profile_photo'];
            if ($uploadResult['path']) {
                $newPhotoPath = 'uploads/profiles/' . $uploadResult['path'];
            }

            $stmt = $db->prepare(
                "UPDATE users SET full_name = ?, email = ?, university = ?, area_id = ?, profile_photo = ? WHERE user_id = ?"
            );
            $stmt->execute([
                $fullName,
                $email,
                $university !== '' ? $university : null,
                $areaIdClean,
                $newPhotoPath,
                $userId,
            ]);

            setFlash('success', 'Profile updated successfully.');
            header('Location: ' . BASE_URL . 'profile.php');
            exit;
        }

        $user['full_name']  = $fullName;
        $user['email']      = $email;
        $user['university'] = $university;
        $user['area_id']    = $areaIdClean ?? $areaId;
    }

    if ($action === 'change_password') {
        $currentPassword     = $_POST['current_password'] ?? '';
        $newPassword         = $_POST['new_password'] ?? '';
        $confirmNewPassword  = $_POST['confirm_new_password'] ?? '';

        if (!password_verify($currentPassword, $user['password_hash'])) {
            $passwordErrors[] = 'Your current password is incorrect.';
        }
        if ($err = validate_password_policy($newPassword)) $passwordErrors[] = $err;
        if ($newPassword !== $confirmNewPassword) $passwordErrors[] = 'New passwords do not match.';

        if (empty($passwordErrors)) {
            $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $db->prepare("UPDATE users SET password_hash = ? WHERE user_id = ?");
            $stmt->execute([$newHash, $userId]);

            setFlash('success', 'Password changed successfully.');
            header('Location: ' . BASE_URL . 'profile.php');
            exit;
        }
    }
}

$areas = $db->query("SELECT area_id, area_name FROM areas WHERE is_active = 1 ORDER BY area_name ASC")->fetchAll();

$pageTitle = 'My Profile';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<div class="container my-5">
  <div class="row justify-content-center">
    <div class="col-lg-8">

      <h2 class="cs-section-title mb-4"><i class="bi bi-person-circle"></i> My Profile</h2>

      <div class="cs-form-card mb-4">
        <div class="d-flex align-items-center gap-3 mb-4">
          <img src="<?= e(getProfileImage($user['profile_photo'])) ?>" alt="Profile photo" class="rounded-circle" style="width:80px;height:80px;object-fit:cover;border:2px solid #e5e7eb;">
          <div>
            <h5 class="mb-0"><?= e($user['full_name']) ?></h5>
            <span class="text-muted small"><?= e($user['email']) ?></span>
          </div>
        </div>

        <h5 class="mb-3">Profile Information</h5>

        <?php if (!empty($profileErrors)): ?>
          <div class="alert alert-danger">
            <ul class="mb-0 ps-3">
              <?php foreach ($profileErrors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?>
            </ul>
          </div>
        <?php endif; ?>

        <form method="post" action="<?= BASE_URL ?>profile.php" enctype="multipart/form-data" novalidate>
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="update_profile">

          <div class="mb-3">
            <label for="full_name" class="form-label">Full Name</label>
            <input type="text" class="form-control" id="full_name" name="full_name" value="<?= e($user['full_name']) ?>" required maxlength="100">
          </div>

          <div class="mb-3">
            <label for="email" class="form-label">Email Address</label>
            <input type="email" class="form-control" id="email" name="email" value="<?= e($user['email']) ?>" required maxlength="150">
            <div class="form-text">Changing your email requires your current password below.</div>
          </div>

          <div class="mb-3">
            <label for="current_password_for_email" class="form-label">Current Password <span class="text-muted">(only required if changing email)</span></label>
            <input type="password" class="form-control" id="current_password_for_email" name="current_password_for_email">
          </div>

          <div class="mb-3">
            <label for="university" class="form-label">University</label>
            <input type="text" class="form-control" id="university" name="university" value="<?= e($user['university'] ?? '') ?>" maxlength="150">
          </div>

          <div class="mb-3">
            <label for="area_id" class="form-label">Preferred Area</label>
            <select class="form-select" id="area_id" name="area_id">
              <option value="">Select your area</option>
              <?php foreach ($areas as $area): ?>
                <option value="<?= (int)$area['area_id'] ?>" <?= ((string)($user['area_id'] ?? '') === (string)$area['area_id']) ? 'selected' : '' ?>>
                  <?= e($area['area_name']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="mb-4">
            <label for="profile_image" class="form-label">Update Profile Photo</label>
            <input type="file" class="form-control" id="profile_image" name="profile_image" accept=".jpg,.jpeg,.png,.webp">
            <div class="form-text">JPG, PNG or WEBP. Maximum 3MB.</div>
          </div>

          <button type="submit" class="btn cs-btn-primary">Save Changes</button>
        </form>
      </div>

      <div class="cs-form-card">
        <h5 class="mb-3">Change Password</h5>

        <?php if (!empty($passwordErrors)): ?>
          <div class="alert alert-danger">
            <ul class="mb-0 ps-3">
              <?php foreach ($passwordErrors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?>
            </ul>
          </div>
        <?php endif; ?>

        <form method="post" action="<?= BASE_URL ?>profile.php" novalidate>
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="change_password">

          <div class="mb-3">
            <label for="current_password" class="form-label">Current Password</label>
            <input type="password" class="form-control" id="current_password" name="current_password" required>
          </div>

          <div class="row">
            <div class="col-md-6 mb-3">
              <label for="new_password" class="form-label">New Password</label>
              <input type="password" class="form-control" id="new_password" name="new_password" required minlength="8">
              <div class="form-text">At least 8 characters, with letters and numbers.</div>
            </div>
            <div class="col-md-6 mb-3">
              <label for="confirm_new_password" class="form-label">Confirm New Password</label>
              <input type="password" class="form-control" id="confirm_new_password" name="confirm_new_password" required minlength="8">
            </div>
          </div>

          <button type="submit" class="btn cs-btn-outline">Change Password</button>
        </form>
      </div>

    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>