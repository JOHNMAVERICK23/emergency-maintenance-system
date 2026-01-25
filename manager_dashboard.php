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
    <link rel="stylesheet" href="css/style.css">
    <style>
        .priority-high { border-left: 5px solid #dc3545; }
        .priority-very-high { border-left: 5px solid #8B0000; }
        .priority-general { border-left: 5px solid #2E7D32; }
        .request-card { margin-bottom: 15px; }
        .btn-danger { background-color: #dc3545; border-color: #dc3545; }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark" style="background-color: #2E7D32;">
        <div class="container">
            <a class="navbar-brand" href="#">Kazan Neft EMS - Manager</a>
            <div class="collapse navbar-collapse">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <span class="nav-link">Welcome, <?php echo $_SESSION['first_name']; ?></span>
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
                <div class="card shadow">
                    <div class="card-body">
                        <h3>Open Emergency Maintenance Requests</h3>
                        <p class="text-muted">Sorted by priority and report date</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <?php if (empty($openRequests)): ?>
                <div class="col-md-12">
                    <div class="alert alert-info">No open emergency maintenance requests.</div>
                </div>
            <?php else: ?>
                <?php foreach ($openRequests as $request): ?>
                    <div class="col-md-6">
                        <div class="card request-card 
                            <?php echo $request['PriorityName'] == 'Very High' ? 'priority-very-high' : 
                                   ($request['PriorityName'] == 'High' ? 'priority-high' : 'priority-general'); ?>">
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($request['AssetName']); ?></h5>
                                <h6 class="card-subtitle mb-2 text-muted">
                                    SN: <?php echo htmlspecialchars($request['AssetSN']); ?>
                                </h6>
                                <p class="card-text">
                                    <strong>Report Date:</strong> <?php echo date('Y-m-d', strtotime($request['EMReportDate'])); ?><br>
                                    <strong>Employee:</strong> <?php echo htmlspecialchars($request['EmployeeFullName']); ?><br>
                                    <strong>Department:</strong> <?php echo htmlspecialchars($request['DepartmentName']); ?><br>
                                    <strong>Priority:</strong> 
                                    <span class="badge 
                                        <?php echo $request['PriorityName'] == 'Very High' ? 'bg-danger' : 
                                               ($request['PriorityName'] == 'High' ? 'bg-warning text-dark' : 'bg-success'); ?>">
                                        <?php echo htmlspecialchars($request['PriorityName']); ?>
                                    </span>
                                </p>
                                <p class="card-text">
                                    <strong>Description:</strong><br>
                                    <?php echo nl2br(htmlspecialchars($request['DescriptionEmergency'])); ?>
                                </p>
                                <a href="manage_request.php?id=<?php echo $request['ID']; ?>" 
                                   class="btn btn-success">Manage Request</a>
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