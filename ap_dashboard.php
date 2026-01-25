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
    <link rel="stylesheet" href="css/style.css">
    <style>
        body {
            background-color: #f5f5f5;
        }
        .navbar-custom {
            background-color: #2E7D32 !important;
        }
        .asset-card {
            border: 1px solid #ddd;
            border-radius: 8px;
            margin-bottom: 15px;
            transition: all 0.3s ease;
        }
        .asset-card:hover {
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .asset-open {
            border-left: 5px solid #dc3545;
            background-color: #fff5f5;
        }
        .asset-closed {
            border-left: 5px solid #2E7D32;
        }
        .btn-danger {
            background-color: #dc3545;
            border-color: #dc3545;
        }
        .stat-card {
            background: white;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark navbar-custom">
        <div class="container">
            <a class="navbar-brand" href="#">Kazan Neft EMS</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <span class="nav-link">Welcome, <?php echo $_SESSION['first_name'] . ' ' . $_SESSION['last_name']; ?></span>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="stat-card">
                    <h3>My Assets</h3>
                    <p class="text-muted">Review your assets and emergency maintenance requests</p>
                </div>
            </div>
        </div>

        <div class="row">
            <?php if (empty($assets)): ?>
                <div class="col-md-12">
                    <div class="alert alert-info">No assets assigned to you.</div>
                </div>
            <?php else: ?>
                <?php foreach ($assets as $asset): ?>
                    <div class="col-md-6">
                        <div class="asset-card <?php echo $asset['OpenRequests'] > 0 ? 'asset-open' : 'asset-closed'; ?> p-3">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h5><?php echo htmlspecialchars($asset['AssetName']); ?></h5>
                                    <p class="mb-1"><strong>SN:</strong> <?php echo htmlspecialchars($asset['AssetSN']); ?></p>
                                    <p class="mb-1"><strong>Department:</strong> <?php echo htmlspecialchars($asset['DepartmentName']); ?></p>
                                    <p class="mb-1"><strong>Last Closed EM:</strong> 
                                        <?php echo $asset['LastClosedEM'] ? date('Y-m-d', strtotime($asset['LastClosedEM'])) : 'None'; ?>
                                    </p>
                                    <p class="mb-1"><strong>Number of EMs:</strong> <?php echo $asset['NumberOfEMs']; ?></p>
                                    <?php if ($asset['OpenRequests'] > 0): ?>
                                        <span class="badge bg-danger">Open Request</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="mt-3">
                                <?php if ($asset['OpenRequests'] == 0): ?>
                                    <a href="create_request.php?asset_id=<?php echo $asset['ID']; ?>" 
                                       class="btn btn-success btn-sm">Send Emergency Maintenance Request</a>
                                <?php else: ?>
                                    <button class="btn btn-secondary btn-sm" disabled>Request Already Open</button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>