<?php
require_once 'config.php';

function getAssetsByEmployee($employeeId) {
    global $pdo;
    
    $query = "
        SELECT 
            a.*,
            ag.Name as AssetGroupName,
            dl.DepartmentID,
            d.Name as DepartmentName,
            l.Name as LocationName,
            (SELECT MAX(EMEndDate) FROM emergencymaintenances em 
             WHERE em.AssetID = a.ID AND em.EMEndDate IS NOT NULL) as LastClosedEM,
            (SELECT COUNT(*) FROM emergencymaintenances em 
             WHERE em.AssetID = a.ID AND em.EMEndDate IS NOT NULL) as NumberOfEMs,
            (SELECT COUNT(*) FROM emergencymaintenances em 
             WHERE em.AssetID = a.ID AND em.EMEndDate IS NULL) as OpenRequests
        FROM assets a
        JOIN assetgroups ag ON a.AssetGroupID = ag.ID
        JOIN departmentlocations dl ON a.DepartmentLocationID = dl.ID
        JOIN departments d ON dl.DepartmentID = d.ID
        JOIN locations l ON dl.LocationID = l.ID
        WHERE a.EmployeeID = ?
        ORDER BY a.AssetName
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute([$employeeId]);
    return $stmt->fetchAll();
}

function getOpenEmergencyMaintenances() {
    global $pdo;
    
    $query = "
        SELECT 
            em.*,
            a.AssetSN,
            a.AssetName,
            CONCAT(e.FirstName, ' ', e.LastName) as EmployeeFullName,
            d.Name as DepartmentName,
            p.Name as PriorityName
        FROM emergencymaintenances em
        JOIN assets a ON em.AssetID = a.ID
        JOIN employees e ON a.EmployeeID = e.ID
        JOIN departmentlocations dl ON a.DepartmentLocationID = dl.ID
        JOIN departments d ON dl.DepartmentID = d.ID
        JOIN priorities p ON em.PriorityID = p.ID
        WHERE em.EMEndDate IS NULL
        ORDER BY 
            CASE p.Name 
                WHEN 'Very High' THEN 1
                WHEN 'High' THEN 2
                WHEN 'General' THEN 3
                ELSE 4
            END,
            em.EMReportDate ASC
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    return $stmt->fetchAll();
}

function getEmergencyMaintenanceDetails($emId) {
    global $pdo;
    
    $query = "
        SELECT 
            em.*,
            a.AssetSN,
            a.AssetName,
            CONCAT(e.FirstName, ' ', e.LastName) as EmployeeFullName,
            d.Name as DepartmentName,
            p.Name as PriorityName
        FROM emergencymaintenances em
        JOIN assets a ON em.AssetID = a.ID
        JOIN employees e ON a.EmployeeID = e.ID
        JOIN departmentlocations dl ON a.DepartmentLocationID = dl.ID
        JOIN departments d ON dl.DepartmentID = d.ID
        JOIN priorities p ON em.PriorityID = p.ID
        WHERE em.ID = ?
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute([$emId]);
    return $stmt->fetch();
}

function getChangedParts($emId) {
    global $pdo;
    
    $query = "
        SELECT cp.*, p.Name as PartName, p.EffectiveLife
        FROM changedparts cp
        JOIN parts p ON cp.PartID = p.ID
        WHERE cp.EmergencyMaintenanceID = ?
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute([$emId]);
    return $stmt->fetchAll();
}

function getAllParts() {
    global $pdo;
    
    $query = "SELECT * FROM parts ORDER BY Name";
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    return $stmt->fetchAll();
}

function getAllPriorities() {
    global $pdo;
    
    $query = "SELECT * FROM priorities ORDER BY 
              CASE Name 
                WHEN 'Very High' THEN 1
                WHEN 'High' THEN 2
                WHEN 'General' THEN 3
                ELSE 4
              END";
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    return $stmt->fetchAll();
}

function checkPartReplacement($assetId, $partId, $effectiveLife) {
    global $pdo;
    
    $query = "
        SELECT cp.EmergencyMaintenanceID, em.EMEndDate
        FROM changedparts cp
        JOIN emergencymaintenances em ON cp.EmergencyMaintenanceID = em.ID
        WHERE em.AssetID = ? AND cp.PartID = ? AND em.EMEndDate IS NOT NULL
        ORDER BY em.EMEndDate DESC
        LIMIT 1
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute([$assetId, $partId]);
    $lastReplacement = $stmt->fetch();
    
    if ($lastReplacement && $effectiveLife) {
        $lastDate = new DateTime($lastReplacement['EMEndDate']);
        $currentDate = new DateTime();
        $daysSinceReplacement = $currentDate->diff($lastDate)->days;
        
        return $daysSinceReplacement < $effectiveLife;
    }
    
    return false;
}
?>