<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'resident') {
    header('Location: ../login.php');
    exit;
}
require_once '../config/database.php';

$errors  = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $barangay    = trim($_POST['barangay'] ?? '');
    $location    = trim($_POST['location'] ?? '');
    $category    = trim($_POST['category'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if (empty($barangay))    $errors[] = 'Please select a barangay.';
    if (empty($location))    $errors[] = 'Location is required.';
    if (empty($category))    $errors[] = 'Please select a category.';
    if (empty($description)) $errors[] = 'Description is required.';

    $photo = null;
    if (!empty($_FILES['photo']['name'])) {
        $allowed = ['jpg', 'jpeg', 'png'];
        $ext     = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed)) {
            $errors[] = 'Photo must be JPG or PNG.';
        } elseif ($_FILES['photo']['size'] > 5 * 1024 * 1024) {
            $errors[] = 'Photo must be 5MB or less.';
        } else {
            $filename    = uniqid() . '.' . $ext;
            $photo       = 'uploads/complaints/' . $filename;
            $destination = realpath(__DIR__ . '/../uploads/complaints') . '/' . $filename;
            move_uploaded_file($_FILES['photo']['tmp_name'], $destination);
        }
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("INSERT INTO complaints (user_id, barangay, location, category, description, photo) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$_SESSION['user_id'], $barangay, $location, $category, $description, $photo]);
        $success = 'Complaint submitted successfully! LGU Gerona will review it shortly.';
    }
}

$barangays = [
    'Abagon', 'Amacalan', 'Apsayan', 'Ayson', 'Bawa', 'Buenlag',
    'Bularit', 'Calayaan', 'Carbonel', 'Cardona', 'Caturay', 'Danzo',
    'Dicolor', 'Don Basilio', 'Luna', 'Mabini', 'Magaspac', 'Malayep',
    'Matapitap', 'Matayumcab', 'New Salem', 'Oloybuaya', 'Padapada',
    'Parsolingan', 'Pinasling', 'Plastado', 'Poblacion 1', 'Poblacion 2',
    'Poblacion 3', 'Quezon', 'Rizal', 'Salapungan', 'San Agustin',
    'San Antonio', 'San Bartolome', 'San Jose', 'Santa Lucia', 'Santiago',
    'Sembrano', 'Singat', 'Sulipa', 'Tagumbao', 'Tangcaran', 'Villa Paz'
];

$categories = [
    'Road and Infrastructure',
    'Garbage and Sanitation',
    'Flooding and Drainage',
    'Illegal Construction',
    'Noise Complaint',
    'Illegal Logging',
    'Water Supply',
    'Electricity',
    'Public Safety',
    'Others'
];

$active_page = 'complaint';
$page_title  = 'File a Complaint';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>File Complaint — Ragsak Ranyag Gerona</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/gov-header.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #f0f2f5; min-height: 100vh; }

        .main { flex: 1; }

        .content { padding: 0; max-width: none; margin: 0; }

        /* Form wrapper */
        .form-wrapper {
            background: #fff;
            border-top: 1px solid #d0d5dd;
            border-bottom: 1px solid #d0d5dd;
            width: 100%;
        }

        /* Form header */
        .form-header {
            background: #0E4D92;
            padding: 16px 24px;

            display: flex;
            align-items: center;
            gap: 12px;
        }

        .form-header i { color: #fff; font-size: 18px; }
        .form-header h2 { font-size: 15px; font-weight: 700; color: #fff; letter-spacing: 0.3px; }
        .form-header p  { font-size: 12px; color: rgba(255,255,255,0.75); margin-top: 1px; }

        /* Form body */
        .form-body { padding: 24px; }

        /* Section label */
        .section-label {
            font-size: 11px;
            font-weight: 700;
            color: #0E4D92;
            text-transform: uppercase;
            letter-spacing: 1px;
            padding: 10px 0 8px;
            border-bottom: 2px solid #0E4D92;
            margin-bottom: 16px;
        }

        /* Two column grid */
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 0; border: 1px solid #d0d5dd; margin-bottom: 20px; }
        .form-grid.single { grid-template-columns: 1fr; }

        /* Field item */
        .field-item {
            padding: 12px 16px;
            border-right: 1px solid #d0d5dd;
            border-bottom: 1px solid #d0d5dd;
        }

        .field-item:nth-child(even) { border-right: none; }
        .field-item:last-child { border-bottom: none; }
        .field-item:nth-last-child(2):nth-child(odd) { border-bottom: none; }

        .field-item label {
            display: block;
            font-size: 11px;
            font-weight: 700;
            color: #555;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 6px;
        }

        .field-item label span { color: #c0392b; margin-left: 2px; }

        .field-item select,
        .field-item input[type=text] {
            width: 100%;
            padding: 8px 10px;
            border: 1px solid #c8ccd0;
            border-radius: 3px;
            font-size: 13px;
            font-family: 'Inter', sans-serif;
            color: #1a1a1a;
            background: #fff;
            outline: none;
        }

        .field-item select:focus,
        .field-item input[type=text]:focus {
            border-color: #0E4D92;
            box-shadow: 0 0 0 2px rgba(14,77,146,0.12);
        }

        /* Full width field */
        .field-full {
            padding: 12px 16px;
            border: 1px solid #d0d5dd;
            border-top: none;
            margin-bottom: 20px;
        }

        .field-full label {
            display: block;
            font-size: 11px;
            font-weight: 700;
            color: #555;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 6px;
        }

        .field-full label span { color: #c0392b; margin-left: 2px; }

        .field-full textarea {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #c8ccd0;
            border-radius: 3px;
            font-size: 13px;
            font-family: 'Inter', sans-serif;
            color: #1a1a1a;
            outline: none;
            resize: vertical;
            min-height: 110px;
            line-height: 1.6;
        }

        .field-full textarea:focus {
            border-color: #0E4D92;
            box-shadow: 0 0 0 2px rgba(14,77,146,0.12);
        }

        /* Photo upload */
        .upload-area {
            border: 2px dashed #c8ccd0;
            border-radius: 3px;
            padding: 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s;
            background: #fafafa;
        }

        .upload-area:hover { border-color: #0E4D92; background: #EFF5FC; }
        .upload-area i { font-size: 24px; color: #0E4D92; margin-bottom: 6px; display: block; }
        .upload-area p { font-size: 13px; color: #666; }
        .upload-area small { font-size: 11px; color: #aaa; display: block; margin-top: 3px; }
        #photo { display: none; }
        .upload-filename { font-size: 12px; color: #0E4D92; font-weight: 600; margin-top: 6px; }

        /* Alerts */
        .alert-danger {
            padding: 12px 16px;
            margin-bottom: 20px;
            font-size: 13px;
            background: #fdf3f3;
            color: #922b21;
            border: 1px solid #f5c6c6;
            border-left: 4px solid #c0392b;
            border-radius: 3px;
        }

        .alert-success {
            padding: 12px 16px;
            margin-bottom: 20px;
            font-size: 13px;
            background: #f0faf4;
            color: #1e6b3c;
            border: 1px solid #b7dfc9;
            border-left: 4px solid #27ae60;
            border-radius: 3px;
        }

        .err-list { list-style: none; padding: 0; }
        .err-list li { padding: 2px 0; }
        .err-list li::before { content: "▸ "; color: #c0392b; }

        /* Form footer */
        .form-footer {
            background: #f8f9fa;
            padding: 14px 24px;
            border-top: 1px solid #d0d5dd;
            display: flex;
            align-items: center;
            justify-content: space-between;

        }

        .form-footer p { font-size: 12px; color: #888; }
        .form-footer p span { color: #c0392b; }

        .btn-submit {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 28px;
            background: #0E4D92;
            color: #fff;
            font-size: 13px;
            font-weight: 700;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            font-family: 'Inter', sans-serif;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        .btn-submit:hover { background: #0B3D75; }
    </style>
</head>
<body>

<?php require 'includes/header.php'; ?>

<div class="main">
    <div class="content">

        <?php if (!empty($errors)): ?>
            <div class="alert-danger">
                <ul class="err-list">
                    <?php foreach ($errors as $e): ?>
                        <li><?= htmlspecialchars($e) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert-success">
                <i class="fa-solid fa-circle-check"></i>
                <?= $success ?>
            </div>
        <?php endif; ?>

        <div class="form-wrapper">

            <!-- Form Header -->
            <div class="form-header">
                <i class="fa-solid fa-file-lines"></i>
                <div>
                    <h2>COMPLAINT SUBMISSION FORM</h2>
                    <p>Municipality of Gerona, Tarlac — LGU Complaint Management System</p>
                </div>
            </div>

            <form action="complaint.php" method="POST" enctype="multipart/form-data">
                <div class="form-body">

                    <!-- Section 1: Location Info -->
                    <div class="section-label">I. Location Information</div>

                    <div class="form-grid">
                        <div class="field-item">
                            <label>Barangay <span>*</span></label>
                            <select name="barangay" required>
                                <option value="">— Select Barangay —</option>
                                <?php foreach ($barangays as $brgy): ?>
                                    <option value="<?= $brgy ?>" <?= ($_POST['barangay'] ?? '') === $brgy ? 'selected' : '' ?>>
                                        <?= $brgy ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="field-item">
                            <label>Specific Location / Landmark <span>*</span></label>
                            <input type="text" name="location" value="<?= htmlspecialchars($_POST['location'] ?? '') ?>" placeholder="e.g. Near the barangay hall" required>
                        </div>
                    </div>

                    <!-- Section 2: Complaint Details -->
                    <div class="section-label">II. Complaint Details</div>

                    <div class="form-grid">
                        <div class="field-item" style="border-right:1px solid #d0d5dd;grid-column:1/3">
                            <label>Category of Complaint <span>*</span></label>
                            <select name="category" required>
                                <option value="">— Select Category —</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat ?>" <?= ($_POST['category'] ?? '') === $cat ? 'selected' : '' ?>>
                                        <?= $cat ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="field-full">
                        <label>Description of Complaint <span>*</span></label>
                        <textarea name="description" placeholder="Provide a detailed description of the problem, including when it started and how it affects the community..." required><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
                    </div>

                    <!-- Section 3: Supporting Documents -->
                    <div class="section-label">III. Supporting Document / Photo</div>

                    <label class="upload-area" for="photo">
                        <i class="fa-solid fa-cloud-arrow-up"></i>
                        <p>Click to upload a photo of the problem</p>
                        <small>Accepted formats: JPG, PNG — Maximum size: 5MB</small>
                        <div class="upload-filename" id="upload-filename"></div>
                    </label>
                    <input type="file" id="photo" name="photo" accept=".jpg,.jpeg,.png">

                </div>

                <!-- Form Footer -->
                <div class="form-footer">
                    <p>Fields marked with <span>*</span> are required.</p>
                    <button type="submit" class="btn-submit">
                        <i class="fa-solid fa-paper-plane"></i> Submit Complaint
                    </button>
                </div>

            </form>
        </div>
    </div>
</div>

<script>
    document.getElementById('photo').addEventListener('change', function () {
        document.getElementById('upload-filename').textContent = this.files[0] ? '📎 ' + this.files[0].name : '';
    });
</script>
</body>
</html>