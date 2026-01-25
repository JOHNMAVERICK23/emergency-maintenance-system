<?php
require_once 'includes/auth.php';
require_once 'includes/functions.php';
requireLogin();

if (!isAdmin()) {
    header('Location: ap_dashboard.php');
    exit();
}

$emId = $_GET['id'] ?? 0;
$request = getEmergencyMaintenanceDetails($emId);
$changedParts = getChangedParts($emId);
$allParts = getAllParts();

if (!$request) {
    header('Location: manager_dashboard.php');
    exit();
}

$canEdit = empty($request['EMEndDate']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$canEdit) {
        header('Location: manage_request.php?id=' . $emId);
        exit();
    }
    
    $startDate = $_POST['em_start_date'];
    $endDate = $_POST['em_end_date'] ?: null;
    $techNote = $_POST['tech_note'] ?: null;
    
    // Validation
    if ($startDate && strtotime($startDate) < strtotime($request['EMReportDate'])) {
        $error = "Start date cannot be before the report date.";
    } elseif ($endDate && empty($techNote)) {
        $error = "Technician note is required when setting completion date.";
    } else {
        global $pdo;
        $stmt = $pdo->prepare("
            UPDATE emergencymaintenances 
            SET EMStartDate = ?, EMEndDate = ?, EMTechnicianNote = ?
            WHERE ID = ?
        ");
        
        if ($stmt->execute([$startDate ?: null, $endDate, $techNote, $emId])) {
            // Handle parts addition
            if (isset($_POST['parts'])) {
                foreach ($_POST['parts'] as $part) {
                    if (!empty($part['part_id']) && !empty($part['amount']) && $part['amount'] > 0) {
                        $partId = $part['part_id'];
                        $amount = $part['amount'];
                        
                        // Check if part replacement is within effective life
                        $stmt2 = $pdo->prepare("SELECT EffectiveLife FROM parts WHERE ID = ?");
                        $stmt2->execute([$partId]);
                        $partInfo = $stmt2->fetch();
                        
                        if ($partInfo['EffectiveLife']) {
                            $isWithinLife = checkPartReplacement($request['AssetID'], $partId, $partInfo['EffectiveLife']);
                            if ($isWithinLife) {
                                // Show warning but still allow
                                $warning = "Part was replaced within its effective life period.";
                            }
                        }
                        
                        $stmt3 = $pdo->prepare("
                            INSERT INTO changedparts (EmergencyMaintenanceID, PartID, Amount)
                            VALUES (?, ?, ?)
                        ");
                        $stmt3->execute([$emId, $partId, $amount]);
                    }
                }
            }
            
            header('Location: manage_request.php?id=' . $emId . '&success=1');
            exit();
        } else {
            $error = "Failed to update request.";
        }
    }
}

// Handle part removal
if (isset($_GET['remove_part'])) {
    $partId = $_GET['remove_part'];
    global $pdo;
    $stmt = $pdo->prepare("DELETE FROM changedparts WHERE ID = ? AND EmergencyMaintenanceID = ?");
    $stmt->execute([$partId, $emId]);
    header('Location: manage_request.php?id=' . $emId);
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage EM Request - Emergency Maintenance System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body class="bg-light">
    <div class="container mt-4">
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if (isset($warning)): ?>
            <div class="alert alert-warning"><?php echo $warning; ?></div>
        <?php endif; ?>
        
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">Request updated successfully!</div>
        <?php endif; ?>
        
        <div class="card shadow mb-4">
            <div class="card-header bg-success text-white">
                <h4 class="mb-0">Emergency Maintenance Request Details</h4>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Asset SN:</strong> <?php echo htmlspecialchars($request['AssetSN']); ?></p>
                        <p><strong>Asset Name:</strong> <?php echo htmlspecialchars($request['AssetName']); ?></p>
                        <p><strong>Department:</strong> <?php echo htmlspecialchars($request['DepartmentName']); ?></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Registered On:</strong> <?php echo date('Y-m-d', strtotime($request['EMReportDate'])); ?></p>
                        <p><strong>Completed On:</strong> <?php echo $request['EMEndDate'] ? date('Y-m-d', strtotime($request['EMEndDate'])) : 'Not completed'; ?></p>
                        <p><strong>Priority:</strong> <?php echo htmlspecialchars($request['PriorityName']); ?></p>
                    </div>
                </div>
                
                <hr>
                
                <h5>Description</h5>
                <p><?php echo nl2br(htmlspecialchars($request['DescriptionEmergency'])); ?></p>
                
                <h5>Other Considerations</h5>
                <p><?php echo nl2br(htmlspecialchars($request['OtherConsiderations'])); ?></p>
                
                <?php if ($request['EMTechnicianNote']): ?>
                    <h5>Technician Note</h5>
                    <p><?php echo nl2br(htmlspecialchars($request['EMTechnicianNote'])); ?></p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Parts Section -->
        <div class="card shadow mb-4">
            <div class="card-header bg-secondary text-white">
                <h5 class="mb-0">Changed Parts</h5>
            </div>
            <div class="card-body">
                <?php if (empty($changedParts)): ?>
                    <p class="text-muted">No parts have been changed for this request.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Part Name</th>
                                    <th>Amount</th>
                                    <th>Effective Life (days)</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($changedParts as $part): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($part['PartName']); ?></td>
                                        <td><?php echo $part['Amount']; ?></td>
                                        <td><?php echo $part['EffectiveLife'] ?: 'N/A'; ?></td>
                                        <td>
                                            <?php if ($canEdit): ?>
                                                <a href="?id=<?php echo $emId; ?>&remove_part=<?php echo $part['ID']; ?>" 
                                                   class="btn btn-danger btn-sm"
                                                   onclick="return confirm('Remove this part?')">Remove</a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Edit Form (only if not completed) -->
        <?php if ($canEdit): ?>
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Update Request</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label class="form-label">Work Start Date *</label>
                                <input type="date" class="form-control" name="em_start_date" 
                                       value="<?php echo $request['EMStartDate'] ? date('Y-m-d', strtotime($request['EMStartDate'])) : ''; ?>"
                                       min="<?php echo date('Y-m-d', strtotime($request['EMReportDate'])); ?>" 
                                       required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Completion Date</label>
                                <input type="date" class="form-control" name="em_end_date" 
                                       value="<?php echo $request['EMEndDate'] ? date('Y-m-d', strtotime($request['EMEndDate'])) : ''; ?>"
                                       min="<?php echo $request['EMStartDate'] ? date('Y-m-d', strtotime($request['EMStartDate'])) : date('Y-m-d', strtotime($request['EMReportDate'])); ?>">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Technician Note</label>
                            <textarea class="form-control" name="tech_note" rows="3"><?php echo htmlspecialchars($request['EMTechnicianNote']); ?></textarea>
                            <small class="text-muted">Required when setting completion date</small>
                        </div>
                        
                        <!-- Parts Addition -->
                        <div class="mb-3">
                            <h6>Add Parts</h6>
                            <div id="parts-container">
                                <div class="part-entry row mb-2">
                                    <div class="col-md-5">
                                        <select class="form-control part-select" name="parts[0][part_id]">
                                            <option value="">Select Part</option>
                                            <?php foreach ($allParts as $part): ?>
                                                <option value="<?php echo $part['ID']; ?>" 
                                                        data-life="<?php echo $part['EffectiveLife']; ?>">
                                                    <?php echo htmlspecialchars($part['Name']); ?>
                                                    (<?php echo $part['EffectiveLife'] ? $part['EffectiveLife'] . ' days' : 'N/A'; ?>)
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <input type="number" class="form-control" name="parts[0][amount]" 
                                               step="0.01" min="0.01" placeholder="Amount">
                                    </div>
                                    <div class="col-md-2">
                                        <button type="button" class="btn btn-danger btn-sm remove-part">Remove</button>
                                    </div>
                                </div>
                            </div>
                            <button type="button" id="add-part" class="btn btn-secondary btn-sm">Add Another Part</button>
                        </div>
                        
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="manager_dashboard.php" class="btn btn-secondary me-md-2">Back</a>
                            <button type="submit" class="btn btn-success">Update Request</button>
                        </div>
                    </form>
                </div>
            </div>
        <?php else: ?>
            <div class="alert alert-info">
                This request has been completed and cannot be modified.
                <a href="manager_dashboard.php" class="btn btn-secondary btn-sm ms-3">Back to Dashboard</a>
            </div>
        <?php endif; ?>
    </div>

    <script>
        $(document).ready(function() {
            let partCounter = 1;
            
            $('#add-part').click(function() {
                const newPart = `
                    <div class="part-entry row mb-2">
                        <div class="col-md-5">
                            <select class="form-control part-select" name="parts[${partCounter}][part_id]">
                                <option value="">Select Part</option>
                                <?php foreach ($allParts as $part): ?>
                                    <option value="<?php echo $part['ID']; ?>" 
                                            data-life="<?php echo $part['EffectiveLife']; ?>">
                                        <?php echo htmlspecialchars($part['Name']); ?>
                                        (<?php echo $part['EffectiveLife'] ? $part['EffectiveLife'] . ' days' : 'N/A'; ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <input type="number" class="form-control" name="parts[${partCounter}][amount]" 
                                   step="0.01" min="0.01" placeholder="Amount">
                        </div>
                        <div class="col-md-2">
                            <button type="button" class="btn btn-danger btn-sm remove-part">Remove</button>
                        </div>
                    </div>
                `;
                $('#parts-container').append(newPart);
                partCounter++;
            });
            
            $(document).on('click', '.remove-part', function() {
                $(this).closest('.part-entry').remove();
            });
            
            // Check part replacement warning
            $(document).on('change', '.part-select', function() {
                const selectedOption = $(this).find('option:selected');
                const effectiveLife = selectedOption.data('life');
                const partId = selectedOption.val();
                
                if (partId && effectiveLife) {
                    // Here you would typically make an AJAX call to check if part was recently replaced
                    // For now, we'll just show a generic warning
                    console.log('Check if part ID ' + partId + ' was replaced within ' + effectiveLife + ' days');
                }
            });
        });
    </script>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>