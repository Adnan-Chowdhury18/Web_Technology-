<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/validation.php';
require_once __DIR__ . '/includes/upload-helper.php';

if (is_logged_in()) {
    header('Location: ' . BASE_URL . 'index.php');
    exit;
}

$errors = [];
$old = [
    'full_name'  => '',
    'email'      => '',
    'university' => '',
    'area_id'    => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require_or_die();

    $old['full_name']  = trim($_POST['full_name'] ?? '');
    $old['email']      = trim($_POST['email'] ?? '');
    $old['university'] = trim($_POST['university'] ?? '');
    $old['area_id']    = $_POST['area_id'] ?? '';
    $password          = $_POST['password'] ?? '';
    $confirmPassword   = $_POST['confirm_password'] ?? '';

    if ($err = validate_full_name($old['full_name'])) {
        $errors[] = $err;
    }

    if ($err = validate_email_format($old['email'])) {
        $errors[] = $err;
    } else {
        $stmt = getDB()->prepare("SELECT user_id FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$old['email']]);
        if ($stmt->fetch()) {
            $errors[] = 'An account with this email already exists. Please log in instead.';
        }
    }

    if ($err = validate_password_policy($password)) {
        $errors[] = $err;
    }
    if ($password !== $confirmPassword) {
        $errors[] = 'Passwords do not match.';
    }

    if ($err = validate_university($old['university'])) {
        $errors[] = $err;
    }

    $areaId = null;
    if ($old['area_id'] !== '') {
        $stmt = getDB()->prepare("SELECT area_id FROM areas WHERE area_id = ? AND is_active = 1 LIMIT 1");
        $stmt->execute([$old['area_id']]);
        if (!$stmt->fetch()) {
            $errors[] = 'Please select a valid area.';
        } else {
            $areaId = (int)$old['area_id'];
        }
    }

    $uploadResult = ['success' => true, 'path' => null, 'error' => null];
    if (empty($errors)) {
        $uploadResult = handle_single_image_upload('profile_image', UPLOAD_DIR_PROFILES);
        if (!$uploadResult['success']) {
            $errors[] = $uploadResult['error'];
        }
    }

    if (empty($errors)) {
        $passwordHash     = password_hash($password, PASSWORD_DEFAULT);
        $profilePhotoPath = $uploadResult['path'] ? 'uploads/profiles/' . $uploadResult['path'] : null;

        $stmt = getDB()->prepare(
            "INSERT INTO users (full_name, email, password_hash, profile_photo, university, area_id, role, status)
             VALUES (?, ?, ?, ?, ?, ?, 'student', 'active')"
        );
        $stmt->execute([
            $old['full_name'],
            $old['email'],
            $passwordHash,
            $profilePhotoPath,
            $old['university'] !== '' ? $old['university'] : null,
            $areaId,
        ]);

        setFlash('success', 'Account created successfully! Please log in to continue.');
        header('Location: ' . BASE_URL . 'login.php');
        exit;
    }
}

$areas = getDB()->query("SELECT area_id, area_name FROM areas WHERE is_active = 1 ORDER BY area_name ASC")->fetchAll();

$pageTitle = 'Register';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<div class="container my-5">
  <div class="row justify-content-center">
    <div class="col-md-7 col-lg-6">
      <div class="cs-form-card">
        <h2 class="cs-section-title text-center mb-1">Create Your Account</h2>
        <p class="text-center text-muted mb-4">Join ClassSwap to buy and sell with students near you.</p>

        <?php if (!empty($errors)): ?>
          <div class="alert alert-danger">
            <ul class="mb-0 ps-3">
              <?php foreach ($errors as $error): ?>
                <li><?= e($error) ?></li>
              <?php endforeach; ?>
            </ul>
          </div>
        <?php endif; ?>

        <form method="post" action="<?= BASE_URL ?>register.php" enctype="multipart/form-data" novalidate>
          <?= csrf_field() ?>

          <div class="mb-3">
            <label for="full_name" class="form-label">Full Name *</label>
            <input type="text" class="form-control" id="full_name" name="full_name" value="<?= e($old['full_name']) ?>" required maxlength="100">
          </div>

          <div class="mb-3">
            <label for="email" class="form-label">Email Address *</label>
            <input type="email" class="form-control" id="email" name="email" value="<?= e($old['email']) ?>" required maxlength="150">
          </div>

          <div class="row">
            <div class="col-md-6 mb-3">
              <label for="password" class="form-label">Password *</label>
              <div class="input-group">
                <input type="password" class="form-control" id="password" name="password" required minlength="8">
                <button class="btn btn-outline-secondary" type="button" data-toggle-password="password">
                  <i class="bi bi-eye"></i>
                </button>
              </div>
              <div class="form-text">At least 8 characters, with letters and numbers.</div>
            </div>
            <div class="col-md-6 mb-3">
              <label for="confirm_password" class="form-label">Confirm Password *</label>
              <div class="input-group">
                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required minlength="8">
                <button class="btn btn-outline-secondary" type="button" data-toggle-password="confirm_password">
                  <i class="bi bi-eye"></i>
                </button>
              </div>
            </div>
          </div>

          <div class="mb-3">
            <label for="university" class="form-label">University (optional)</label>
            <input type="text" class="form-control" id="university" name="university" value="<?= e($old['university']) ?>" maxlength="150">
          </div>

          <div class="mb-3">
            <label for="area_id" class="form-label">Preferred Area (optional)</label>
            <select class="form-select" id="area_id" name="area_id">
              <option value="">Select your area</option>
              <?php foreach ($areas as $area): ?>
                <option value="<?= (int)$area['area_id'] ?>" <?= ((string)$old['area_id'] === (string)$area['area_id']) ? 'selected' : '' ?>>
                  <?= e($area['area_name']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="mb-4">
            <label for="profile_image" class="form-label">Profile Photo (optional)</label>
            <input type="file" class="form-control" id="profile_image" name="profile_image" accept=".jpg,.jpeg,.png,.webp">
            <div class="form-text">JPG, PNG or WEBP. Maximum 3MB.</div>
          </div>

          <button type="submit" class="btn cs-btn-primary w-100">Create Account</button>
        </form>

        <p class="text-center mt-4 mb-0">
          Already have an account? <a href="<?= BASE_URL ?>login.php">Log in</a>
        </p>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>