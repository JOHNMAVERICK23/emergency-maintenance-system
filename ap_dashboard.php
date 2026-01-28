<?php
require_once 'includes/auth.php';
require_once 'includes/functions.php';
requireLogin();

$assets = getAssetsByEmployee($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Assets - Emergency Maintenance System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .navbar-custom {
            background: #1a1a1a;
        }

        .page-header {
            background: #f5f5f5;
            border-bottom: 2px solid #e0e0e0;
            padding: 2rem 0;
            margin-bottom: 2rem;
        }

        .page-header h1 {
            color: #1a1a1a;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .page-header p {
            color: #888;
            margin: 0;
        }

        .color-legend-box {
            background: white;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }

        .color-legend-box h6 {
            color: #1a1a1a;
            font-weight: 700;
            margin-bottom: 1rem;
            text-transform: uppercase;
            font-size: 12px;
            letter-spacing: 0.5px;
        }

        .legend-row {
            display: flex;
            align-items: center;
            margin-bottom: 0.75rem;
            padding: 0.5rem 0;
        }

        .legend-row:last-child {
            margin-bottom: 0;
        }

        .legend-indicator {
            width: 24px;
            height: 24px;
            border-left: 5px solid;
            border-radius: 2px;
            margin-right: 15px;
            background: white;
            flex-shrink: 0;
        }

        .legend-indicator.open {
            border-left-color: #d32f2f;
            background: #fff5f5;
        }

        .legend-indicator.closed {
            border-left-color: #888;
            background: white;
            border: 1px solid #e0e0e0;
        }

        .legend-text {
            color: #333;
            font-weight: 500;
            font-size: 14px;
        }

        .asset-card {
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            padding: 1.5rem;
            background: white;
            transition: all 0.3s ease;
            margin-bottom: 1.5rem;
        }

        .asset-card:hover {
            border-color: #888;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .asset-open {
            border-left: 5px solid #d32f2f;
            background: #fff5f5;
        }

        .asset-open:hover {
            border-color: #d32f2f;
            box-shadow: 0 4px 12px rgba(211, 47, 47, 0.15);
        }

        .asset-closed {
            border-left: 5px solid #888;
        }

        .asset-card h5 {
            color: #1a1a1a;
            font-weight: 700;
            margin-bottom: 1rem;
        }

        .asset-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .asset-info-item {
            display: flex;
            flex-direction: column;
        }

        .asset-info-label {
            color: #1a1a1a;
            font-weight: 600;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.25rem;
        }

        .asset-info-value {
            color: #555;
            font-size: 14px;
        }

        .asset-status {
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid #e0e0e0;
        }

        .badge-open {
            background: #d32f2f;
            color: white;
            font-weight: 600;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            display: inline-block;
            font-size: 12px;
        }

        .btn-create-request {
            background: #1a1a1a;
            color: white;
            border: none;
            padding: 10px 20px;
            font-weight: 600;
            border-radius: 6px;
            transition: all 0.3s ease;
            width: 100%;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-size: 13px;
        }

        .btn-create-request:hover {
            background: #333;
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(26, 26, 26, 0.2);
            color: white;
            text-decoration: none;
        }

        .btn-create-request:disabled {
            background: #e0e0e0;
            color: #888;
            cursor: not-allowed;
            transform: none;
        }

        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
            background: white;
            border-radius: 8px;
            border: 2px dashed #e0e0e0;
        }

        .empty-state i {
            font-size: 3rem;
            color: #ddd;
            margin-bottom: 1rem;
        }

        .empty-state p {
            color: #888;
            font-size: 16px;
        }

        @media (max-width: 768px) {
            .asset-info {
                grid-template-columns: 1fr;
            }

            .page-header {
                padding: 1.5rem 0;
                margin-bottom: 1.5rem;
            }

            .asset-card {
                padding: 1rem;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark navbar-custom">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">
                <i class="fas fa-tools me-2"></i>Kazan Neft EMS
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <span class="nav-link">
                            <i class="fas fa-user me-2"></i>
                            <?php echo htmlspecialchars($_SESSION['first_name'] . ' ' . $_SESSION['last_name']); ?>
                        </span>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">
                            <i class="fas fa-sign-out-alt me-2"></i>Logout
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="page-header">
        <div class="container-fluid">
            <h1><i class="fas fa-inbox me-2"></i>My Assets</h1>
            <p>Review your assets and emergency maintenance requests</p>
        </div>
    </div>

    <div class="container-fluid">
        <div class="color-legend-box">
            <h6><i class="fas fa-info-circle me-2"></i>Status Indicators</h6>
            <div class="row">
                <div class="col-md-6">
                    <div class="legend-row">
                        <div class="legend-indicator open"></div>
                        <span class="legend-text">Asset with Open Emergency Maintenance Request</span>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="legend-row">
                        <div class="legend-indicator closed"></div>
                        <span class="legend-text">Asset with No Open Requests</span>
                    </div>
                </div>
            </div>
        </div>

        <?php if (empty($assets)): ?>
            <div class="empty-state">
                <i class="fas fa-folder-open"></i>
                <p>No assets assigned to you.</p>
                <small class="text-muted">Contact your manager to have assets assigned.</small>
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($assets as $asset): ?>
                    <div class="col-lg-6 col-md-12">
                        <div class="asset-card <?php echo $asset['OpenRequests'] > 0 ? 'asset-open' : 'asset-closed'; ?>">
                            <h5>
                                <i class="fas fa-cube me-2"></i>
                                <?php echo htmlspecialchars($asset['AssetName']); ?>
                            </h5>

                            <div class="asset-info">
                                <div class="asset-info-item">
                                    <span class="asset-info-label">Serial Number</span>
                                    <span class="asset-info-value">
                                        <code><?php echo htmlspecialchars($asset['AssetSN']); ?></code>
                                    </span>
                                </div>
                                <div class="asset-info-item">
                                    <span class="asset-info-label">Department</span>
                                    <span class="asset-info-value">
                                        <?php echo htmlspecialchars($asset['DepartmentName']); ?>
                                    </span>
                                </div>
                                <div class="asset-info-item">
                                    <span class="asset-info-label">Last Closed EM</span>
                                    <span class="asset-info-value">
                                        <?php echo $asset['LastClosedEM'] 
                                            ? date('Y-m-d', strtotime($asset['LastClosedEM'])) 
                                            : '<span class="text-muted">None</span>'; 
                                        ?>
                                    </span>
                                </div>
                                <div class="asset-info-item">
                                    <span class="asset-info-label">Number of EMs</span>
                                    <span class="asset-info-value">
                                        <strong><?php echo $asset['NumberOfEMs']; ?></strong>
                                    </span>
                                </div>
                            </div>

                            <div class="asset-status">
                                <?php if ($asset['OpenRequests'] > 0): ?>
                                    <span class="badge-open">
                                        <i class="fas fa-exclamation-circle me-2"></i>Open Request
                                    </span>
                                    <p class="text-muted mt-2" style="font-size: 12px; margin: 0;">
                                        You cannot create a new request while one is open.
                                    </p>
                                <?php endif; ?>
                            </div>

                            <div class="mt-3">
                                <?php if ($asset['OpenRequests'] == 0): ?>
                                    <a href="create_request.php?asset_id=<?php echo $asset['ID']; ?>" 
                                       class="btn-create-request">
                                        <i class="fas fa-plus-circle me-2"></i>Send EM Request
                                    </a>
                                <?php else: ?>
                                    <button class="btn-create-request" disabled title="An open request exists for this asset">
                                        <i class="fas fa-lock me-2"></i>Request Already Open
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>