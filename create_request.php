<?php
require_once 'includes/auth.php';
require_once 'includes/functions.php';
requireLogin();

$assetId = $_GET['asset_id'] ?? 0;
$priorities = getAllPriorities();

// Check if asset exists and belongs to user
$assets = getAssetsByEmployee($_SESSION['user_id']);
$asset = null;
foreach ($assets as $a) {
    if ($a['ID'] == $assetId) {
        $asset = $a;
        break;
    }
}

if (!$asset) {
    header('Location: ap_dashboard.php');
    exit();
}

// Check for open requests
if ($asset['OpenRequests'] > 0) {
    header('Location: ap_dashboard.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $priorityId = $_POST['priority_id'];
    $description = $_POST['description'];
    $considerations = $_POST['considerations'];
    
    global $pdo;
    $stmt = $pdo->prepare("
        INSERT INTO emergencymaintenances 
        (AssetID, PriorityID, DescriptionEmergency, OtherConsiderations, EMReportDate) 
        VALUES (?, ?, ?, ?, CURDATE())
    ");
    
    if ($stmt->execute([$assetId, $priorityId, $description, $considerations])) {
        header('Location: ap_dashboard.php?success=1');
        exit();
    } else {
        $error = "Failed to create request. Please try again.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New EM Request - Emergency Maintenance System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-header bg-success text-white">
                        <h4 class="mb-0">Register New Emergency Maintenance Request</h4>
                    </div>
                    <div class="card-body">
                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>
                        
                        <form method="POST" action="">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Asset SN</label>
                                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($asset['AssetSN']); ?>" readonly>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Asset Name</label>
                                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($asset['AssetName']); ?>" readonly>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Department</label>
                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($asset['DepartmentName']); ?>" readonly>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Priority *</label>
                                <select class="form-control" name="priority_id" required>
                                    <option value="">Select Priority</option>
                                    <?php foreach ($priorities as $priority): ?>
                                        <option value="<?php echo $priority['ID']; ?>">
                                            <?php echo htmlspecialchars($priority['Name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Description of Emergency *</label>
                                <textarea class="form-control" name="description" rows="3" required 
                                          placeholder="Describe the emergency situation"></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Other Considerations</label>
                                <textarea class="form-control" name="considerations" rows="2" 
                                          placeholder="Any other considerations or notes"></textarea>
                            </div>
                            
                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <a href="ap_dashboard.php" class="btn btn-secondary me-md-2">Cancel</a>
                                <button type="submit" class="btn btn-success">Submit Request</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>