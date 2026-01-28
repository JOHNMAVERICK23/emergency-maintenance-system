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
    
    $startDate = $_POST['em_start_date'] ?? null;
    $endDate = $_POST['em_end_date'] ?? null;
    $techNote = $_POST['tech_note'] ?? null;

    if ($startDate && strtotime($startDate) < strtotime($request['EMReportDate'])) {
        $error = "Start date cannot be before the report date.";
    } elseif ($endDate && empty($techNote) && empty($request['EMTechnicianNote'])) {
        $error = "Technician note is required when setting completion date.";
    } else {
        global $pdo;
        $stmt = $pdo->prepare("
            UPDATE emergencymaintenances 
            SET EMStartDate = ?, EMEndDate = ?, EMTechnicianNote = ?
            WHERE ID = ?
        ");
        
        if ($stmt->execute([$startDate ?: null, $endDate, $techNote ?: $request['EMTechnicianNote'], $emId])) {
            if (isset($_POST['parts'])) {
                foreach ($_POST['parts'] as $part) {
                    if (!empty($part['part_id']) && !empty($part['amount']) && $part['amount'] > 0) {
                        $partId = $part['part_id'];
                        $amount = $part['amount'];
                        
                        $stmt2 = $pdo->prepare("SELECT EffectiveLife FROM parts WHERE ID = ?");
                        $stmt2->execute([$partId]);
                        $partInfo = $stmt2->fetch();
                        
                        if ($partInfo['EffectiveLife']) {
                            $isWithinLife = checkPartReplacement($request['AssetID'], $partId, $partInfo['EffectiveLife']);
                            if ($isWithinLife) {
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        .page-header {
            background: #f5f5f5;
            border-bottom: 2px solid #e0e0e0;
            padding: 2rem 0;
            margin-bottom: 2rem;
        }

        .page-header h1 {
            color: #1a1a1a;
            font-weight: 700;
            margin-bottom: 0;
        }

        .card {
            border: 2px solid #e0e0e0;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        }

        .card-header {
            background: #1a1a1a;
            color: white;
            font-weight: 700;
            padding: 1.25rem;
            border: none;
        }

        .card-header h5 {
            margin: 0;
            color: white;
            font-weight: 700;
        }

        .info-section {
            padding: 1.5rem;
            border-bottom: 2px solid #f0f0f0;
        }

        .info-section:last-child {
            border-bottom: none;
        }

        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
        }

        .info-item {
            display: flex;
            flex-direction: column;
        }

        .info-label {
            color: #1a1a1a;
            font-weight: 700;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.5rem;
        }

        .info-value {
            color: #555;
            font-size: 14px;
            line-height: 1.6;
        }

        .info-value code {
            background: #f5f5f5;
            padding: 2px 6px;
            border-radius: 3px;
            font-weight: 500;
        }

        .description-text {
            background: #f9f9f9;
            border-left: 4px solid #1a1a1a;
            padding: 1rem;
            border-radius: 4px;
            color: #555;
            line-height: 1.6;
            font-size: 14px;
        }

        .form-section {
            background: white;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            padding: 1.5rem;
            margin-top: 2rem;
        }

        .form-section-title {
            color: #1a1a1a;
            font-weight: 700;
            font-size: 16px;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #e0e0e0;
        }

        .part-entry {
            background: #f9f9f9;
            padding: 1rem;
            border-radius: 6px;
            border: 1px solid #e0e0e0;
            margin-bottom: 1rem;
        }

        .part-select, .part-amount {
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            padding: 10px 12px;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .part-select:focus, .part-amount:focus {
            border-color: #1a1a1a;
            box-shadow: 0 0 0 4px rgba(26, 26, 26, 0.1);
        }

        .btn-remove-part {
            background: #d32f2f;
            color: white;
            border: none;
            padding: 8px 16px;
            font-weight: 600;
            border-radius: 6px;
            font-size: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
        }

        .btn-remove-part:hover {
            background: #b71c1c;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(211, 47, 47, 0.3);
        }

        .btn-add-part {
            background: #1a1a1a;
            color: white;
            border: none;
            padding: 8px 16px;
            font-weight: 600;
            border-radius: 6px;
            font-size: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            margin-bottom: 1rem;
        }

        .btn-add-part:hover {
            background: #333;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(26, 26, 26, 0.2);
        }

        .btn-back {
            background: #888;
            color: white;
            border: none;
            padding: 10px 20px;
            font-weight: 600;
            border-radius: 6px;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .btn-back:hover {
            background: #666;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(136, 136, 136, 0.3);
            text-decoration: none;
            color: white;
        }

        .btn-submit {
            background: #1a1a1a;
            color: white;
            border: none;
            padding: 10px 20px;
            font-weight: 600;
            border-radius: 6px;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            cursor: pointer;
        }

        .btn-submit:hover {
            background: #333;
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(26, 26, 26, 0.2);
            color: white;
        }

        .alert-danger {
            background: #ffebee;
            border: 2px solid #d32f2f;
            color: #c62828;
            border-radius: 6px;
            padding: 12px 15px;
            margin-bottom: 1.5rem;
        }

        .alert-success {
            background: #f1f8f6;
            border: 2px solid #1a1a1a;
            color: #1a1a1a;
            border-radius: 6px;
            padding: 12px 15px;
            margin-bottom: 1.5rem;
        }

        .alert-warning {
            background: #fff3e0;
            border: 2px solid #f57c00;
            color: #e65100;
            border-radius: 6px;
            padding: 12px 15px;
            margin-bottom: 1.5rem;
        }

        .table {
            background: white;
            border-radius: 8px;
            overflow: hidden;
        }

        .table th {
            background: #f5f5f5;
            border-bottom: 2px solid #e0e0e0;
            font-weight: 700;
            color: #1a1a1a;
            padding: 1rem;
            text-transform: uppercase;
            font-size: 12px;
            letter-spacing: 0.5px;
        }

        .table td {
            padding: 1rem;
            vertical-align: middle;
            border-bottom: 1px solid #f0f0f0;
            color: #555;
        }

        .completed-message {
            background: #f1f8f6;
            border-left: 4px solid #1a1a1a;
            padding: 1.5rem;
            border-radius: 6px;
            margin-top: 2rem;
        }

        .completed-message p {
            margin: 0;
            color: #1a1a1a;
            font-weight: 500;
        }

        @media (max-width: 768px) {
            .info-grid {
                grid-template-columns: 1fr;
            }

            .info-section {
                padding: 1rem;
            }

            .card-body {
                padding: 1rem;
            }

            .btn {
                width: 100%;
                margin-bottom: 0.5rem;
            }
        }
    </style>
</head>
<body class="bg-light">
    <div class="container-fluid mt-4">
        <?php if (isset($error)): ?>
            <div class="alert alert-danger" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($warning)): ?>
            <div class="alert alert-warning" role="alert">
                <i class="fas fa-info-circle me-2"></i>
                <?php echo htmlspecialchars($warning); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                Request updated successfully!
            </div>
        <?php endif; ?>

        <div class="card shadow-sm mb-4">
            <div class="card-header">
                <h5><i class="fas fa-file-alt me-2"></i>Emergency Maintenance Request Details</h5>
            </div>
            <div class="card-body p-0">
                <div class="info-section">
                    <div class="info-grid">
                        <div class="info-item">
                            <span class="info-label">Asset Serial Number</span>
                            <span class="info-value">
                                <code><?php echo htmlspecialchars($request['AssetSN']); ?></code>
                            </span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Asset Name</span>
                            <span class="info-value">
                                <?php echo htmlspecialchars($request['AssetName']); ?>
                            </span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Department</span>
                            <span class="info-value">
                                <?php echo htmlspecialchars($request['DepartmentName']); ?>
                            </span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Priority</span>
                            <span class="info-value">
                                <strong><?php echo htmlspecialchars($request['PriorityName']); ?></strong>
                            </span>
                        </div>
                    </div>
                </div>

                <div class="info-section">
                    <div class="info-grid">
                        <div class="info-item">
                            <span class="info-label">Registered On</span>
                            <span class="info-value">
                                <?php echo date('Y-m-d', strtotime($request['EMReportDate'])); ?>
                            </span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Completed On</span>
                            <span class="info-value">
                                <?php echo $request['EMEndDate'] 
                                    ? date('Y-m-d', strtotime($request['EMEndDate'])) 
                                    : '<span class="text-muted">Not completed</span>'; 
                                ?>
                            </span>
                        </div>
                    </div>
                </div>

                <div class="info-section">
                    <div class="info-item">
                        <span class="info-label">Description of Emergency</span>
                        <div class="description-text">
                            <?php echo nl2br(htmlspecialchars($request['DescriptionEmergency'])); ?>
                        </div>
                    </div>
                </div>

                <div class="info-section">
                    <div class="info-item">
                        <span class="info-label">Other Considerations</span>
                        <div class="description-text">
                            <?php echo $request['OtherConsiderations'] 
                                ? nl2br(htmlspecialchars($request['OtherConsiderations']))
                                : '<span class="text-muted">None provided</span>';
                            ?>
                        </div>
                    </div>
                </div>

                <?php if ($request['EMTechnicianNote']): ?>
                    <div class="info-section">
                        <div class="info-item">
                            <span class="info-label">Technician Note</span>
                            <div class="description-text">
                                <?php echo nl2br(htmlspecialchars($request['EMTechnicianNote'])); ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="card shadow-sm mb-4">
            <div class="card-header">
                <h5><i class="fas fa-exchange-alt me-2"></i>Changed Parts</h5>
            </div>
            <div class="card-body">
                <?php if (empty($changedParts)): ?>
                    <p class="text-muted"><i class="fas fa-info-circle me-2"></i>No parts have been changed for this request.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Part Name</th>
                                    <th>Amount</th>
                                    <th>Effective Life (days)</th>
                                    <th style="width: 120px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($changedParts as $part): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($part['PartName']); ?></td>
                                        <td><?php echo number_format($part['Amount'], 2); ?></td>
                                        <td><?php echo $part['EffectiveLife'] ?: '<span class="text-muted">N/A</span>'; ?></td>
                                        <td>
                                            <?php if ($canEdit): ?>
                                                <a href="?id=<?php echo $emId; ?>&remove_part=<?php echo $part['ID']; ?>" 
                                                   class="btn-remove-part"
                                                   onclick="return confirm('Are you sure you want to remove this part?')">
                                                    <i class="fas fa-trash me-1"></i>Remove
                                                </a>
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

        <?php if ($canEdit): ?>
            <div class="form-section">
                <div class="form-section-title">
                    <i class="fas fa-edit me-2"></i>Update Request
                </div>

                <form method="POST" action="">
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="form-label required">Work Start Date</label>
                            <input type="date" class="form-control" name="em_start_date" 
                                   value="<?php echo $request['EMStartDate'] ? date('Y-m-d', strtotime($request['EMStartDate'])) : ''; ?>"
                                   min="<?php echo date('Y-m-d', strtotime($request['EMReportDate'])); ?>" 
                                   required>
                            <small class="text-muted">Must be on or after <?php echo date('Y-m-d', strtotime($request['EMReportDate'])); ?></small>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Completion Date</label>
                            <input type="date" class="form-control" name="em_end_date" 
                                   value="<?php echo $request['EMEndDate'] ? date('Y-m-d', strtotime($request['EMEndDate'])) : ''; ?>"
                                   min="<?php echo $request['EMStartDate'] ? date('Y-m-d', strtotime($request['EMStartDate'])) : date('Y-m-d', strtotime($request['EMReportDate'])); ?>">
                            <small class="text-muted">Optional - set when work is completed</small>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Technician Note</label>
                        <textarea class="form-control" name="tech_note" rows="3"><?php echo htmlspecialchars($request['EMTechnicianNote'] ?? ''); ?></textarea>
                        <small class="text-muted">Required when setting completion date</small>
                    </div>
                    
                    <div class="mb-4">
                        <div class="form-section-title">
                            <i class="fas fa-plus-circle me-2"></i>Add Parts
                        </div>
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
                                    <input type="number" class="form-control part-amount" name="parts[0][amount]" 
                                           step="0.01" min="0.01" placeholder="Amount">
                                </div>
                                <div class="col-md-2">
                                    <button type="button" class="btn-remove-part remove-part">
                                        <i class="fas fa-trash me-1"></i>Remove
                                    </button>
                                </div>
                            </div>
                        </div>
                        <button type="button" id="add-part" class="btn-add-part">
                            <i class="fas fa-plus me-1"></i>Add Another Part
                        </button>
                    </div>
                    
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <a href="manager_dashboard.php" class="btn-back">
                            <i class="fas fa-arrow-left me-2"></i>Back
                        </a>
                        <button type="submit" class="btn-submit">
                            <i class="fas fa-save me-2"></i>Update Request
                        </button>
                    </div>
                </form>
            </div>
        <?php else: ?>
            <div class="completed-message">
                <p>
                    <i class="fas fa-check-circle me-2"></i>
                    This request has been completed on <?php echo date('Y-m-d', strtotime($request['EMEndDate'])); ?> and cannot be modified.
                </p>
                <a href="manager_dashboard.php" class="btn-back" style="display: inline-block; margin-top: 1rem;">
                    <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                </a>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
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
                            <input type="number" class="form-control part-amount" name="parts[${partCounter}][amount]" 
                                   step="0.01" min="0.01" placeholder="Amount">
                        </div>
                        <div class="col-md-2">
                            <button type="button" class="btn-remove-part remove-part">
                                <i class="fas fa-trash me-1"></i>Remove
                            </button>
                        </div>
                    </div>
                `;
                $('#parts-container').append(newPart);
                partCounter++;
            });
            
            $(document).on('click', '.remove-part', function() {
                $(this).closest('.part-entry').remove();
            });
        });
    </script>
</body>
</html>