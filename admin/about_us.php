<?php
session_start();
require_once '../config/database.php';
include 'includes/header.php';

// Check if admin is logged in
if(!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// Handle add, edit, delete actions
$error = '';
$success = '';

// Add new member
if(isset($_POST['add_member'])) {
    $name = trim($_POST['name']);
    $description = trim($_POST['description'] ?? '');
    $photo_url = '';
    if(isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['photo']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        if(in_array($ext, $allowed)) {
            $new_filename = uniqid() . '.' . $ext;
            $upload_path = '../uploads/about_us/' . $new_filename;
            if(!is_dir('../uploads/about_us')) {
                mkdir('../uploads/about_us', 0777, true);
            }
            if(move_uploaded_file($_FILES['photo']['tmp_name'], $upload_path)) {
                $photo_url = 'uploads/about_us/' . $new_filename;
            } else {
                $error = "Failed to upload photo.";
            }
        } else {
            $error = "Invalid file type. Allowed types: " . implode(', ', $allowed);
        }
    }
    if(!$error) {
        $stmt = $conn->prepare("INSERT INTO about_us (name, photo_url, description) VALUES (?, ?, ?)");
        $stmt->execute([$name, $photo_url !== '' ? $photo_url : null, $description !== '' ? $description : null]);
        $success = "Team member added.";
    }
}

// Edit member
if(isset($_POST['edit_member'])) {
    $id = intval($_POST['id']);
    $name = trim($_POST['name']);
    $description = trim($_POST['description'] ?? '');
    $photo_url = $_POST['current_photo'] ?? '';
    if(isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['photo']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        if(in_array($ext, $allowed)) {
            $new_filename = uniqid() . '.' . $ext;
            $upload_path = '../uploads/about_us/' . $new_filename;
            if(!is_dir('../uploads/about_us')) {
                mkdir('../uploads/about_us', 0777, true);
            }
            if(move_uploaded_file($_FILES['photo']['tmp_name'], $upload_path)) {
                $photo_url = 'uploads/about_us/' . $new_filename;
            } else {
                $error = "Failed to upload photo.";
            }
        } else {
            $error = "Invalid file type. Allowed types: " . implode(', ', $allowed);
        }
    }
    if(!$error) {
        $stmt = $conn->prepare("UPDATE about_us SET name=?, photo_url=?, description=? WHERE id=?");
        $stmt->execute([$name, $photo_url !== '' ? $photo_url : null, $description !== '' ? $description : null, $id]);
        $success = "Team member updated.";
    }
}

// Delete member
if(isset($_POST['delete_member'])) {
    $id = intval($_POST['id']);
    $stmt = $conn->prepare("DELETE FROM about_us WHERE id=?");
    $stmt->execute([$id]);
    $success = "Team member deleted.";
}

// Fetch all team members
$stmt = $conn->query("SELECT * FROM about_us ORDER BY id ASC");
$members = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage About Us - Motoshapi Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <h2 class="mb-4">Manage About Us</h2>
        <?php if($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        <?php if($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        <div class="card mb-4">
            <div class="card-header">Add New Team Member</div>
            <div class="card-body">
                <form method="POST" enctype="multipart/form-data">
                    <div class="row g-3 align-items-center">
                        <div class="col-md-3">
                            <input type="file" class="form-control" name="photo" accept="image/*">
                        </div>
                        <div class="col-md-3">
                            <input type="text" class="form-control" name="name" placeholder="Name" required>
                        </div>
                        <div class="col-md-4">
                            <textarea class="form-control" name="description" placeholder="Description (optional)" rows="2"></textarea>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" name="add_member" class="btn btn-primary">Add</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        <div class="card">
            <div class="card-header">Team Members</div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Photo</th>
                                <th>Name</th>
                                <th>Description</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($members as $member): ?>
                            <tr>
                                <form method="POST" enctype="multipart/form-data">
                                    <td style="width: 120px;">
                                        <?php if($member['photo_url']): ?>
                                            <img src="../<?php echo htmlspecialchars($member['photo_url']); ?>" alt="<?php echo htmlspecialchars($member['name']); ?>" class="img-thumbnail" style="width: 100px; height: 100px; object-fit: cover;">
                                        <?php else: ?>
                                            <div class="bg-secondary text-white d-flex align-items-center justify-content-center" style="width: 100px; height: 100px;">No Photo</div>
                                        <?php endif; ?>
                                        <input type="file" class="form-control mt-2" name="photo" accept="image/*">
                                    </td>
                                    <td>
                                        <input type="text" class="form-control" name="name" value="<?php echo htmlspecialchars($member['name']); ?>" required>
                                    </td>
                                    <td>
                                        <textarea class="form-control" name="description" rows="3"><?php echo htmlspecialchars($member['description'] ?? ''); ?></textarea>
                                    </td>
                                    <td>
                                        <input type="hidden" name="id" value="<?php echo $member['id']; ?>">
                                        <input type="hidden" name="current_photo" value="<?php echo htmlspecialchars($member['photo_url']); ?>">
                                        <button type="submit" name="edit_member" class="btn btn-success btn-sm mb-1">Save</button>
                                        <button type="submit" name="delete_member" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this member?');">Delete</button>
                                    </td>
                                </form>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 