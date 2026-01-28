<?php
require_once 'includes/auth.php';
require_once 'includes/functions.php';
requireLogin();

if (!isAdmin()) {
    header('Location: ap_dashboard.php');
    exit();
}

$openRequests = getOpenEmergencyMaintenances();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manager Dashboard - Emergency Maintenance System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <style>

        a{
            text-decoration: none;
        }

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

        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            padding: 1.5rem;
            text-align: center;
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            border-color: #888;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            color: #1a1a1a;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: #888;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 600;
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

        .legend-indicator {
            width: 30px;
            height: 30px;
            border-left: 5px solid;
            border-radius: 3px;
            margin-right: 15px;
            flex-shrink: 0;
            background: white;
        }

        .priority-very-high {
            border-left-color: #b71c1c;
            background: #ffebee;
        }

        .priority-high {
            border-left-color: #d32f2f;
            background: #fff5f5;
        }

        .priority-general {
            border-left-color: #888;
            background: #fafafa;
        }

        .legend-text {
            color: #333;
            font-weight: 500;
            font-size: 14px;
        }

        .request-card {
            background: white;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            transition: all 0.3s ease;
        }

        .request-card:hover {
            border-color: #888;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .request-card.priority-very-high {
            border-left: 5px solid #b71c1c;
            background: linear-gradient(135deg, #ffebee 0%, white 100%);
        }

        .request-card.priority-high {
            border-left: 5px solid #d32f2f;
            background: linear-gradient(135deg, #fff5f5 0%, white 100%);
        }

        .request-card.priority-general {
            border-left: 5px solid #888;
            background: white;
        }

        .request-card h5 {
            color: #1a1a1a;
            font-weight: 700;
            margin-bottom: 1rem;
        }

        .request-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-bottom: 1.5rem;
            font-size: 14px;
        }

        .request-info-item {
            display: flex;
            flex-direction: column;
        }

        .request-info-label {
            color: #1a1a1a;
            font-weight: 600;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.25rem;
        }

        .request-info-value {
            color: #555;
        }

        .priority-badge {
            display: inline-block;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            font-weight: 600;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .priority-badge.very-high {
            background: #b71c1c;
            color: white;
        }

        .priority-badge.high {
            background: #d32f2f;
            color: white;
        }

        .priority-badge.general {
            background: #888;
            color: white;
        }

        .description-box {
            background: #f9f9f9;
            border: 1px solid #e0e0e0;
            border-radius: 6px;
            padding: 1rem;
            margin: 1rem 0;
            font-size: 14px;
            color: #555;
            line-height: 1.6;
        }

        .btn-manage {
            background: #1a1a1a;
            color: white;
            border: none;
            padding: 10px 20px;
            font-weight: 600;
            border-radius: 6px;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-size: 13px;
            display: inline-block;
        }

        .btn-manage:hover {
            background: #333;
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(26, 26, 26, 0.2);
            color: white;
            text-decoration: none;
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
            .request-info {
                grid-template-columns: 1fr;
            }

            .stats-container {
                grid-template-columns: 1fr;
            }

            .page-header {
                padding: 1.5rem 0;
                margin-bottom: 1.5rem;
            }

            .request-card {
                padding: 1rem;
            }

            .stat-number {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark navbar-custom">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">
                <i class="fas fa-shield-alt me-2"></i>Kazan Neft EMS - Manager
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <span class="nav-link">
                            <i class="fas fa-user-shield me-2"></i>
                            <?php echo htmlspecialchars($_SESSION['first_name']); ?>
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
            <h1><i class="fas fa-tasks me-2"></i>Open Emergency Maintenance Requests</h1>
            <p>Manage and track maintenance requests sorted by priority and report date</p>
        </div>
    </div>

    <div class="container-fluid">
        <div class="stats-container">
            <div class="stat-card">
                <div class="stat-number"><?php echo count(array_filter($openRequests, function($r) { return $r['PriorityName'] == 'Very High'; })); ?></div>
                <div class="stat-label">Very High Priority</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo count(array_filter($openRequests, function($r) { return $r['PriorityName'] == 'High'; })); ?></div>
                <div class="stat-label">High Priority</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo count(array_filter($openRequests, function($r) { return $r['PriorityName'] == 'General'; })); ?></div>
                <div class="stat-label">General Priority</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo count($openRequests); ?></div>
                <div class="stat-label">Total Open Requests</div>
            </div>
        </div>

        <div class="color-legend-box">
            <h6><i class="fas fa-info-circle me-2"></i>Priority Indicators</h6>
            <div class="row">
                <div class="col-md-4">
                    <div class="legend-row">
                        <div class="legend-indicator priority-very-high"></div>
                        <span class="legend-text">Very High - Urgent action required</span>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="legend-row">
                        <div class="legend-indicator priority-high"></div>
                        <span class="legend-text">High - Priority action</span>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="legend-row">
                        <div class="legend-indicator priority-general"></div>
                        <span class="legend-text">General - Routine maintenance</span>
                    </div>
                </div>
            </div>
        </div>

        <?php if (empty($openRequests)): ?>
            <div class="empty-state">
                <i class="fas fa-check-circle"></i>
                <p>No open emergency maintenance requests.</p>
                <small class="text-muted">All requests have been completed!</small>
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($openRequests as $request): ?>
                    <div class="col-lg-6 col-md-12">
                        <div class="request-card priority-<?php echo strtolower(str_replace(' ', '-', $request['PriorityName'])); ?>">
                            <h5>
                                <i class="fas fa-wrench me-2"></i>
                                <?php echo htmlspecialchars($request['AssetName']); ?>
                            </h5>

                            <div class="request-info">
                                <div class="request-info-item">
                                    <span class="request-info-label">Asset SN</span>
                                    <span class="request-info-value">
                                        <code><?php echo htmlspecialchars($request['AssetSN']); ?></code>
                                    </span>
                                </div>
                                <div class="request-info-item">
                                    <span class="request-info-label">Report Date</span>
                                    <span class="request-info-value">
                                        <?php echo date('Y-m-d', strtotime($request['EMReportDate'])); ?>
                                    </span>
                                </div>
                                <div class="request-info-item">
                                    <span class="request-info-label">Employee</span>
                                    <span class="request-info-value">
                                        <?php echo htmlspecialchars($request['EmployeeFullName']); ?>
                                    </span>
                                </div>
                                <div class="request-info-item">
                                    <span class="request-info-label">Department</span>
                                    <span class="request-info-value">
                                        <?php echo htmlspecialchars($request['DepartmentName']); ?>
                                    </span>
                                </div>
                            </div>

                            <div>
                                <span class="priority-badge <?php echo strtolower(str_replace(' ', '-', $request['PriorityName'])); ?>">
                                    <i class="fas fa-flag me-1"></i><?php echo htmlspecialchars($request['PriorityName']); ?>
                                </span>
                            </div>

                            <div class="description-box">
                                <strong>Description:</strong><br>
                                <?php echo nl2br(htmlspecialchars($request['DescriptionEmergency'])); ?>
                            </div>

                            <a href="manage_request.php?id=<?php echo $request['ID']; ?>" 
                               class="btn-manage">
                                <i class="fas fa-edit me-2"></i>Manage Request
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>