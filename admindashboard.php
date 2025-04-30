<?php
// Get analytics data for the dashboard
$pythonScript = "DataAnalytics/get_analytics_data.py";
$analyticsData = null;

// Try to get analytics data
if (file_exists($pythonScript)) {
    // Execute Python script with monthly period parameter
    $command = "python " . escapeshellarg($pythonScript) . " " . escapeshellarg("monthly");
    $output = shell_exec($command);
    
    // If shell_exec fails, try exec
    if (!$output) {
        $outputLines = [];
        exec($command, $outputLines);
        $output = implode("\n", $outputLines);
    }
    
    // Parse the JSON output
    if ($output) {
        $analyticsData = json_decode($output, true);
        if (!$analyticsData || json_last_error() !== JSON_ERROR_NONE) {
            // Log the error for debugging
            error_log("Failed to parse analytics data: " . json_last_error_msg());
            error_log("Raw output: " . $output);
            $analyticsData = null;
        }
    }
}

// Load tracking functionality
if (file_exists('track_ml_data.php')) {
    include 'track_ml_data.php';
}

// Load database connection
if (file_exists('config/db.php')) {
    include 'config/db.php';
} else {
    // Define a fallback function if db.php doesn't exist
    function getConnection()
    {
        return null;
    }
}

// Load metrics functions
if (file_exists('metrics.php')) {
    include 'metrics.php';
} else {
    // Define a fallback function if metrics.php doesn't exist
    function getSystemMetrics()
    {
        return [
            'total_items' => 0,
            'inventory_count' => 0,
            'po_count' => 0,
            'par_count' => 0
        ];
    }
}

// Start session for authentication
session_start();

// Check if user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit;
}

// Get username from session
$username = $_SESSION['username'];

// Get system metrics for the dashboard
$metrics = getSystemMetrics();

// Handle form submission for tracking data
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if we're saving inventory, PO, or PAR data
    if (isset($_POST['track_for_prediction']) && $_POST['track_for_prediction'] === 'true') {
        // Track data for ML predictions
        $trackingData = isset($_POST['tracking_data']) ? json_decode($_POST['tracking_data'], true) : null;
        
        if ($trackingData) {
            if (isset($_POST['item_name'])) {
                // Track inventory item
                if (function_exists('trackInventoryForML')) {
                    $result = trackInventoryForML($trackingData);
                    if ($result['success']) {
                        // Tracking successful
                    }
                }
            } else if (isset($_POST['po_no'])) {
                // Track PO
                if (function_exists('trackPOForML')) {
                    $result = trackPOForML($trackingData);
                    if ($result['success']) {
                        // Tracking successful
                    }
                }
            } else if (isset($_POST['par_no'])) {
                // Track PAR
                if (function_exists('trackPARForML')) {
                    $result = trackPARForML($trackingData);
                    if ($result['success']) {
                        // Tracking successful
                    }
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">  
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ICTD</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/chart.js@3.7.0/dist/chart.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.7.0/dist/chart.min.js"></script>
    <link rel="stylesheet" href="script.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.all.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/ml-regression@4.4.1/dist/ml-regression.min.js"></script>
    <link rel="icon" type="image/x-icon" href="image.jpg">
</head>

<body>  
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <img src="crypto.jpg" alt="Logo">
            <div class="logo-text">
                <i class="bi bi-laptop"></i> STI SYSTEM
            </div>
        </div>
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link active" href="#dashboard" id="dashboard-link" onclick="updateURL('dashboard')">
                    <i class="bi bi-speedometer2"></i> <span>Dashboard</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#inventory" id="inventory-link" onclick="updateURL('inventory')">
                    <i class="bi bi-box-seam"></i> <span>Inventory</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#po" id="po-link" onclick="updateURL('po')">
                    <i class="bi bi-cart3"></i> <span>PO</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#par" id="par-link" onclick="updateURL('par')">
                    <i class="bi bi-receipt"></i> <span>PAR</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#received" id="received-link" onclick="updateURL('received')">
                    <i class="bi bi-box-seam"></i> <span>Received</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#reports" id="Reports-link" onclick="updateURL('reports')">
                    <i class="bi bi-file-earmark-bar-graph"></i> <span>Reports</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#users" id="users-link" onclick="updateURL('users')">
                    <i class="bi bi-people"></i> <span>User</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#settings" onclick="updateURL('settings')">
                    <i class="bi bi-gear"></i> <span>Settings</span>
                </a>
            </li>
            <li class="nav-item mt-5">
                <a class="nav-link" href="logout.php">
                    <i class="bi bi-box-arrow-left"></i> <span>Logout</span>
                </a>
            </li>
        </ul>
    </div>

    <!-- Main Content Wrapper -->
    <div class="content-wrapper">
        <!-- Inventory Section -->
        <div class="inventory-section">
            <div class="d-flex justify-content-between align-items-center mb-4">
            </div>

            <div class="card shadow-sm">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 text-primary"><i class="bi bi-box-seam"></i> Inventory Items</h5>
                    <div class="btn-group">
                        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addInventoryModal">
                            <i class="bi bi-plus-circle"></i> Add New Item
                        </button>
                        <button class="btn btn-success btn-sm" id="exportBtn">
                            <i class="bi bi-file-earmark-excel"></i> Export
                        </button>
                        <button class="btn btn-info btn-sm text-white" id="printBtn">
                            <i class="bi bi-printer"></i> Print
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="print-section">
                        <div class="print-header d-none">
                            <h2>ICTD Inventory Management System</h2>
                            <p>Inventory Report</p>
                            <p>Date: <span id="printDate"></span></p>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div class="search-box d-flex align-items-center gap-3">
                                <div class="position-relative">
                                    <i class="bi bi-search position-absolute top-50 start-0 translate-middle-y ms-2"></i>
                                    <input type="text" id="inventorySearchInput" class="form-control ps-4" placeholder="Search inventory..." style="width: 250px;">
                                </div>
                                <div class="filter-group d-flex gap-2">
                                    <select class="form-select" id="conditionFilter" style="width: 150px;">
                                        <option value="">All Conditions</option>
                                        <option value="New">New</option>
                                        <option value="Good">Good</option>
                                        <option value="Fair">Fair</option>
                                        <option value="Poor">Poor</option>
                                    </select>
                                    <select class="form-select" id="locationFilter" style="width: 150px;">
                                        <option value="">All Locations</option>
                                        <option value="Office">Office</option>
                                        <option value="Storage">Storage</option>
                                    </select>
                                </div>
                            </div>
                            <div class="admin-actions">
                                <button class="btn btn-sm btn-outline-secondary" id="setupConditionTables" title="Install condition monitoring tables">
                                    <i class="bi bi-wrench"></i> Setup Condition Monitoring
                                </button>
                                <button class="btn btn-sm btn-outline-primary" id="setupRequiredTables" title="Install required database tables">
                                    <i class="bi bi-database-gear"></i> Setup Required Tables
                                </button>
                            </div>
                        </div>

                        <!-- Serial Number Scanner Button - Moved to its own row -->
                        <div class="scan-button-container mb-3">
                            <!-- Scan result notification area -->
                            <div id="scanResultNotification" class="scan-result ms-auto d-none">
                                <span class="scan-result-status"></span>
                                <span class="scan-result-text"></span>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-striped table-bordered table-hover align-middle shadow-sm">
                                <thead class="table-light">
                                    <tr>
                                        <th class="text-center" style="width: 100px;">Actions</th>
                                        <th>Item ID</th>
                                        <th>Item Name</th>
                                        <th>Brand/Model</th>
                                        <th>Serial Number</th>
                                        <th>Purchase Date</th>
                                        <th>Warranty</th>
                                        <th>Assigned To</th>
                                        <th>Location</th>
                                        <th>Condition</th>
                                        <th>Notes</th>
                                    </tr>
                                </thead>
                                <tbody id="inventoryTableBody">
                                    <!-- Data will be loaded dynamically -->
                                </tbody>
                            </table>
                        </div>
                        <!-- Add pagination controls for inventory -->
                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <div>
                                <span id="inventoryPageInfo">Showing 1-7 of 0 items</span>
                            </div>
                            <div class="pagination-controls">
                                <button id="inventoryPrevBtn" class="btn btn-sm btn-outline-primary" disabled>
                                    <i class="bi bi-chevron-left"></i> Previous
                                </button>
                                <button id="inventoryNextBtn" class="btn btn-sm btn-outline-primary ms-2">
                                    Next <i class="bi bi-chevron-right"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Purchase Order Section -->
        <div class="po-section d-none">
            <div class="card shadow-sm">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 text-primary"><i class="bi bi-cart3"></i> Purchase Orders</h5>
                    <div class="btn-group">
                        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addPOModal">
                            <i class="bi bi-plus-circle"></i> Create New PO
                        </button>
                        <button class="btn btn-success btn-sm" id="exportPOBtn">
                            <i class="bi bi-file-earmark-excel"></i> Export
                        </button>
                        <button class="btn btn-info btn-sm text-white" id="printPOListBtn">
                            <i class="bi bi-printer"></i> Print
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="print-section">
                        <div class="print-header d-none">
                            <h2>ICTD Inventory Management System</h2>
                            <p>Purchase Orders Report</p>
                            <p>Date: <span id="printPODate"></span></p>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div class="search-box d-flex align-items-center gap-3">
                                <div class="position-relative">
                                    <i class="bi bi-search position-absolute top-50 start-0 translate-middle-y ms-2"></i>
                                    <input type="text" id="poSearchInput" class="form-control ps-4" placeholder="Search PO..." style="width: 250px;">
                                </div>
                                <div class="filter-group d-flex gap-2">
                                    <select class="form-select" id="poSupplierFilter" style="width: 150px;">
                                        <option value="">All Suppliers</option>
                                        <!-- Will be populated dynamically -->
                                    </select>
                                    <select class="form-select" id="poDateFilter" style="width: 150px;">
                                        <option value="">All Dates</option>
                                        <option value="30">Last 30 Days</option>
                                        <option value="90">Last 90 Days</option>
                                        <option value="180">Last 6 Months</option>
                                        <option value="365">Last Year</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-striped table-bordered table-hover align-middle shadow-sm" id="poTable">
                                <thead class="table-light">
                                    <tr>
                                        <th class="po-number-col">PO NO.</th>
                                        <th class="supplier-col">SUPPLIER</th>
                                        <th class="date-col">DATE</th>
                                        <th class="amount-col">TOTAL AMOUNT</th>
                                        <th class="actions-col">ACTIONS</th>
                                    </tr>
                                </thead>
                                <tbody id="poTableBody">
                                    <!-- Data will be loaded dynamically -->
                                </tbody>
                            </table>
                        </div>
                        <!-- Add pagination controls for PO -->
                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <div>
                                <span id="poPageInfo">Showing 1-7 of 0 purchase orders</span>
                            </div>
                            <div class="pagination-controls">
                                <button id="poPrevBtn" class="btn btn-sm btn-outline-primary" disabled>
                                    <i class="bi bi-chevron-left"></i> Previous
                                </button>
                                <button id="poNextBtn" class="btn btn-sm btn-outline-primary ms-2">
                                    Next <i class="bi bi-chevron-right"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Dashboard Section -->
        <div class="dashboard-section d-none">
            <div class="dashboard-header mb-4 d-flex justify-content-between align-items-center">
                <h4><i class="bi bi-speedometer2"></i> Dashboard Overview</h4>
                <div class="d-flex align-items-center gap-2">
                    <!-- Updated Notification Dropdown -->
                    <div class="dropdown">
                        <button class="btn position-relative shadow-sm" type="button" id="notificationDropdown" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Notifications">
                            <i class="bi bi-bell"></i>
                            <span id="notificationBadge" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                0
                            </span>
                        </button>
                        <div class="dropdown-menu dropdown-menu-end notification-menu p-0 border-0 shadow" aria-labelledby="notificationDropdown">
                            <div class="notification-header border-bottom p-3 d-flex justify-content-between align-items-center">
                                <h6 class="mb-0"><i class="bi bi-bell me-2"></i>Notifications</h6>
                                <button id="refreshActivitiesBtn" class="btn btn-sm btn-link text-decoration-none p-0">
                                    <i class="bi bi-arrow-clockwise"></i>
                                </button>
                            </div>
                            <div class="notification-body">
                                <div class="expiring-soon p-3 border-bottom">
                                    <h6 class="text-warning mb-3">
                                        <i class="bi bi-exclamation-triangle me-1"></i> Expiring Soon
                                    </h6>
                                    <div id="expiringItems" class="notification-list">
                                        <!-- Dynamically populated -->
                                        <div class="text-muted small py-2">No items expiring soon</div>
                                    </div>
                                </div>
                                <div class="expired p-3 border-bottom">
                                    <h6 class="text-danger mb-3">
                                        <i class="bi bi-x-circle me-1"></i> Expired
                                    </h6>
                                    <div id="expiredItems" class="notification-list">
                                        <!-- Dynamically populated -->
                                        <div class="text-muted small py-2">No expired items</div>
                                    </div>
                                </div>
                                <div class="recently-added p-3">
                                    <h6 class="text-primary mb-3">
                                        <i class="bi bi-plus-circle me-1"></i> Recently Added Inventory
                                    </h6>
                                    <div id="recentInventoryItems" class="notification-list">
                                        <!-- Dynamically populated -->
                                        <div id="noRecentInventoryNotif" class="text-muted small py-2">No recently added items</div>
                                        <div id="recentInventoryListNotif"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="notification-footer border-top p-2 text-center">
                                <small class="text-muted">Click on any notification to view item details</small>
                            </div>
                        </div>
                    </div>

                    <!-- IoT Blockchain Integration Button -->
                    <button class="btn btn-primary position-relative shadow-sm" type="button" data-bs-toggle="modal" data-bs-target="#iotBlockchainModal">
                        <i class="bi bi-hdd-network"></i> IoT Tracker
                        <span id="iotStatusBadge" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-success">
                            <i class="bi bi-wifi"></i>
                        </span>
                    </button>
                </div>
            </div>

            <!-- Warranty Bills container hidden by default -->
            <div id="warrantyBills" class="d-none">
                <div class="warranty-bills-container">
                    <h6 class="mb-3 text-primary"><i class="bi bi-receipt-cutoff me-2"></i>Bills & Warranty Notifications</h6>
                    <div class="warranty-bills-list">
                        <!-- This will be dynamically populated -->
                        <div class="text-muted small py-2">No pending bills or warranty notifications</div>
                    </div>
                </div>
            </div>

            <!-- Stats grid -->
            <div class="stats-grid">
                <!-- Total Items -->
                <div class="dashboard-stats total-items">
                    <div class="stats-icon">
                        <i class="bi bi-box-seam"></i>
                    </div>
                    <div class="stats-number"><?php echo isset($metrics['total_items']) ? htmlspecialchars($metrics['total_items']) : '0'; ?></div>
                    <div class="stats-label">Total Items</div>
                </div>
                <!-- Inventory -->
                <div class="dashboard-stats inventory">
                    <div class="stats-icon">
                        <i class="bi bi-calendar-check"></i>
                    </div>
                    <div class="stats-number"><?php echo isset($metrics['inventory_count']) ? htmlspecialchars($metrics['inventory_count']) : '0'; ?></div>
                    <div class="stats-label">Inventory</div>
                </div>
                <!-- PO -->
                <div class="dashboard-stats po">
                    <div class="stats-icon">
                        <i class="bi bi-cart3"></i>
                    </div>
                    <div class="stats-number"><?php echo isset($metrics['po_count']) ? htmlspecialchars($metrics['po_count']) : '0'; ?></div>
                    <div class="stats-label">PO</div>
                </div>
                <!-- PAR -->
                <div class="dashboard-stats par">
                    <div class="stats-icon">
                        <i class="bi bi-receipt"></i>
                    </div>
                    <div class="stats-number"><?php echo isset($metrics['par_count']) ? htmlspecialchars($metrics['par_count']) : '0'; ?></div>
                    <div class="stats-label">PAR</div>
                </div>
            </div>
            
            <!-- Dashboard Charts and ML Predictions -->
            <div class="row g-3 mt-2">
                <!-- Performance Analytics Section - Full Width -->
                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                            <h6 class="mb-0 text-primary fw-semibold"><i class="bi bi-bar-chart-line me-2"></i>Performance Analytics</h6>
                            <div class="btn-group">
                                <button class="btn btn-sm btn-outline-primary" id="weeklyChartBtn">Weekly</button>
                                <button class="btn btn-sm btn-outline-primary active" id="monthlyChartBtn">Monthly</button>
                                <button class="btn btn-sm btn-outline-primary" id="quarterlyChartBtn">Quarterly</button>
                            </div>
                        </div>
                        <div class="card-body p-3">
                            <div id="analyticsChartContainer" class="chart-container" style="position: relative; height:250px; width:100%">
                                <canvas id="analyticsChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Forecast Row - PO and PAR side by side -->
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                            <h6 class="mb-0 text-primary fw-semibold"><i class="bi bi-graph-up me-2"></i>PO Forecast</h6>
                            <button id="refreshPoPredictionBtn" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-arrow-clockwise"></i> Refresh
                            </button>
                        </div>
                        <div class="card-body">
                            <div id="poChartContainer" class="chart-container" style="position: relative; height:180px; width:100%">
                                <canvas id="poForecastChart"></canvas>
                            </div>
                            <div class="mt-3">
                                <div class="row g-2">
                                    <div class="col-md-6">
                                        <div class="p-3 bg-light rounded-3 text-center shadow-sm">
                                            <div class="text-muted small fw-medium">Next Month</div>
                                            <div id="poNextMonthPrediction" class="fs-5 fw-bold text-primary">₱3,400,549</div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="p-3 bg-light rounded-3 text-center shadow-sm">
                                            <div class="text-muted small fw-medium">Accuracy</div>
                                            <div id="poAccuracyScore" class="fs-5 fw-bold text-success">89%</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                            <h6 class="mb-0 text-primary fw-semibold"><i class="bi bi-graph-up me-2"></i>PAR Forecast</h6>
                            <button id="refreshParPredictionBtn" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-arrow-clockwise"></i> Refresh
                            </button>
                        </div>
                        <div class="card-body">
                            <div id="parChartContainer" class="chart-container" style="position: relative; height:180px; width:100%">
                                <canvas id="parForecastChart"></canvas>
                            </div>
                            <div class="mt-3">
                                <div class="row g-2">
                                    <div class="col-md-6">
                                        <div class="p-3 bg-light rounded-3 text-center shadow-sm">
                                            <div class="text-muted small fw-medium">Next Month</div>
                                            <div id="parNextMonthPrediction" class="fs-5 fw-bold text-primary">₱1,800,000</div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="p-3 bg-light rounded-3 text-center shadow-sm">
                                            <div class="text-muted small fw-medium">Accuracy</div>
                                            <div id="parAccuracyScore" class="fs-5 fw-bold text-success">92%</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ML Prediction Dashboard Row -->
                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                            <h6 class="mb-0 text-primary fw-semibold"><i class="bi bi-robot me-2"></i>ML Prediction Dashboard</h6>
                            <button id="refreshPredictions" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-arrow-clockwise"></i> Refresh
                            </button>
                        </div>
                        <div class="card-body p-0">
                            <div class="row g-0">
                                <!-- Left side: Maintenance Predictions -->
                                <div class="col-md-6 border-end">
                                    <div class="p-3">
                                        <h6 class="fw-semibold mb-3 text-primary"><i class="bi bi-tools me-2"></i>Maintenance Predictions</h6>
                                        <div id="maintenancePredictions" class="maintenance-predictions">
                                            <div class="text-center text-muted py-3 d-none" id="maintenanceLoading">
                                                <div class="spinner-border spinner-border-sm text-primary" role="status">
                                                    <span class="visually-hidden">Loading...</span>
                                                </div>
                                                <p class="mt-2">Loading predictions...</p>
                                            </div>
                                            <div id="maintenanceItems">
                                                <div class="list-group list-group-flush maintenance-list">
                                                    <!-- Enhanced design for maintenance predictions -->
                                                    <div class="maintenance-item p-3 mb-2 rounded-3 bg-light border-start border-warning border-4">
                                                        <div class="d-flex align-items-center">
                                                            <div class="flex-shrink-0">
                                                                <span class="badge bg-warning text-dark rounded-pill px-3 py-2">Medium</span>
                                                            </div>
                                                          
                                                            <div class="flex-shrink-0 ms-3">
                                                                <button class="btn btn-sm btn-outline-warning">
                                                                    <i class="bi bi-wrench"></i> Schedule
                                                                </button>
                                                            </div>
                                                        </div>
                                                        <div class="progress mt-2" style="height: 6px;">
                                                            <div class="progress-bar bg-warning" role="progressbar" style="width: 45%" aria-valuenow="45" aria-valuemin="0" aria-valuemax="100"></div>
                                                        </div>
                                                    </div>
                                                    <div class="maintenance-item p-3 mb-2 rounded-3 bg-light border-start border-danger border-4">
                                            
                                                        <div class="progress mt-2" style="height: 6px;">
                                                            <div class="progress-bar bg-danger" role="progressbar" style="width: 85%" aria-valuenow="85" aria-valuemin="0" aria-valuemax="100"></div>
                                                        </div>
                                                    </div>
                                                    <div class="maintenance-item p-3 mb-2 rounded-3 bg-light border-start border-info border-4">
                                                        <div class="d-flex align-items-center">
                                                            <div class="flex-shrink-0">
                                                                <span class="badge bg-info text-dark rounded-pill px-3 py-2">Low</span>
                                                            </div>
                                                            <div class="flex-grow-1 ms-3">
                                                                <h6 class="mb-1 fw-semibold">Monitor Dell P2419H</h6>
                                                                <div class="d-flex align-items-center mb-1">
                                                                    <i class="bi bi-upc-scan text-muted me-2"></i>
                                                                    <span class="text-muted small">Serial: MN74650</span>
                                                                </div>
                                                                <div class="d-flex align-items-center">
                                                                    <i class="bi bi-calendar-check text-muted me-2"></i>
                                                                    <span class="text-muted small">Predicted maintenance: 47 days</span>
                                                                </div>
                                                            </div>
                                                            <div class="flex-shrink-0 ms-3">
                                                                <button class="btn btn-sm btn-outline-info">
                                                                    <i class="bi bi-wrench"></i> Schedule
                                                                </button>
                                                            </div>
                                                        </div>
                                                        <div class="progress mt-2" style="height: 6px;">
                                                            <div class="progress-bar bg-info" role="progressbar" style="width: 25%" aria-valuenow="25" aria-valuemin="0" aria-valuemax="100"></div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div id="noMaintenanceItems" class="d-none">
                                                <div class="text-center py-3">
                                                    <i class="bi bi-check-circle-fill text-success mb-2" style="font-size: 1.5rem;"></i>
                                                    <p>No maintenance required at this time</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Right side: Inventory Suggestions -->
                                <div class="col-md-6">
                                    <div class="p-3">
                                        <h6 class="fw-semibold mb-3 text-primary"><i class="bi bi-box-seam me-2"></i>Inventory Suggestions</h6>
                                        <div id="inventorySuggestions" class="inventory-suggestions">
                                            <div class="text-center text-muted py-3 d-none" id="suggestionsLoading">
                                                <div class="spinner-border spinner-border-sm text-primary" role="status">
                                                    <span class="visually-hidden">Loading...</span>
                                                </div>
                                                <p class="mt-2">Loading suggestions...</p>
                                            </div>
                                            <div id="inventoryItems">
                                                <!-- Enhanced design for inventory suggestions -->
                                                <div class="card border-0 shadow-sm mb-3">
                                                    <div class="card-body p-0">
                                                        <div class="recommendation-item p-3">
                    
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div id="noInventoryItems" class="d-none">
                                                <div class="text-center py-3">
                                                    <i class="bi bi-search text-primary mb-2" style="font-size: 1.5rem;"></i>
                                                    <p>No inventory suggestions available</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Property Acknowledgement Receipt Section -->
        <div class="par-section d-none">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="bi bi-receipt"></i> Property Acknowledgement Receipts</h5>
                    <button class="btn btn-primary" id="newParBtn" data-bs-toggle="modal" data-bs-target="#addPARModal">
                        <i class="bi bi-plus-circle"></i> Create New PAR
                    </button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="parTable" class="table par-table table-striped table-bordered table-hover align-middle shadow-sm">
                            <thead class="table-light">
                                <tr>
                                    <th>PAR No.</th>
                                    <th>Date Acquired</th>
                                    <th>Property Number</th>
                                    <th>Received By</th>
                                    <th>Amount</th>
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="parTableBody">
                                <!-- PARs will be dynamically added here -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Users Section -->
        <div class="users-section d-none">
            <div class="card shadow-sm">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 text-primary"><i class="bi bi-people"></i> User Management</h5>
                    <div class="btn-group">
                        <button class="btn btn-primary btn-sm" id="addNewUserBtn" data-bs-toggle="modal" data-bs-target="#userModal">
                            <i class="bi bi-plus-circle"></i> Add New User
                        </button>
                        <button class="btn btn-outline-secondary btn-sm" id="setupUsersBtn" title="Setup users database tables">
                            <i class="bi bi-database-gear"></i> Setup Users Table
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <!-- User form container - this was missing -->
                    <div id="userFormContainer" class="mb-4 d-none">
                        <div class="card border-0 shadow-sm">
                            <div class="card-header bg-light py-2 d-flex justify-content-between align-items-center">
                                <h6 class="mb-0 text-primary" id="userFormTitle">Add New User</h6>
                                <button type="button" class="btn-close" id="cancelUserBtn" aria-label="Close"></button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered table-hover align-middle shadow-sm">
                            <thead class="table-light">
                                <tr>
                                    <th>Username</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Status</th>
                                    <th>Created Date</th>
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="userTableBody">
                                <!-- Data will be loaded dynamically -->
                            </tbody>
                        </table>
                    </div>
                    <!-- Add pagination controls for users -->
                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <div>
                            <span id="userPageInfo">Showing 1-7 of 0 users</span>
                        </div>
                        <div class="pagination-controls">
                            <button id="userPrevBtn" class="btn btn-sm btn-outline-primary" disabled>
                                <i class="bi bi-chevron-left"></i> Previous
                            </button>
                            <button id="userNextBtn" class="btn btn-sm btn-outline-primary ms-2">
                                Next <i class="bi bi-chevron-right"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Inventory Item Modal -->
    <div class="modal fade" id="addInventoryModal" tabindex="-1" aria-labelledby="addInventoryModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-gradient-primary text-white">
                    <h5 class="modal-title" id="addInventoryModalLabel">Add New Inventory Item</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body py-3">
                    <form id="addInventoryForm">
                        <!-- Hidden fields for ML tracking -->
                        <input type="hidden" id="inventoryTrackPrediction" name="track_for_prediction" value="true">
                        <input type="hidden" id="inventoryTrackingData" name="tracking_data">
                        
                        <div class="row g-3">
                            <!-- Two column layout to reduce vertical space -->
                            <div class="col-md-6">
                                <!-- Item Basic Info Section -->
                                <div class="card border-0 shadow-sm h-100">
                                    <div class="card-header bg-light py-2">
                                        <h6 class="mb-0 text-primary">Basic Information</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row g-2">
                                            <div class="col-md-6">
                                                <label for="itemID" class="form-label">Item ID</label>
                                                <input type="text" class="form-control" id="itemID" name="itemID" placeholder="Enter item ID">
                                            </div>
                                            <div class="col-md-6">
                                                <label for="itemName" class="form-label">Item Name</label>
                                                <input type="text" class="form-control" id="itemName" name="item_name" placeholder="Enter item name" required>
                                            </div>

                                            <div class="col-md-6">
                                                <label for="Brand/model" class="form-label">Brand/Model</label>
                                                <input type="text" class="form-control" id="Brand/model" name="brand_model" placeholder="Enter Brand/Model">
                                            </div>
                                            <div class="col-md-6">
                                                <label for="serialNumber" class="form-label">Serial Number</label>
                                                <input type="text" class="form-control serial-number-field" id="serialNumber" name="serial_number" placeholder="Enter serial number">
                                                <div class="form-text text-muted small">Serial numbers must be unique.</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <!-- Purchase & Warranty Info Section -->
                                <div class="card border-0 shadow-sm h-100">
                                    <div class="card-header bg-light py-2">
                                        <h6 class="mb-0 text-primary">Purchase & Warranty</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row g-2">
                                            <div class="col-md-6">
                                                <label for="purchaseDate" class="form-label">Purchase Date</label>
                                                <input type="date" class="form-control" id="purchaseDate" name="purchase_date">
                                            </div>
                                            <div class="col-md-6">
                                                <label for="warrantyDate" class="form-label">Warranty Expiration</label>
                                                <input type="date" class="form-control" id="warrantyDate" name="warranty_expiration">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Second row for assignment & notes -->
                            <div class="col-md-6">
                                <!-- Assignment & Status Section -->
                                <div class="card border-0 shadow-sm h-100">
                                    <div class="card-header bg-light py-2">
                                        <h6 class="mb-0 text-primary">Assignment & Status</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row g-2">
                                            <div class="col-md-6">
                                                <label for="assignedTo" class="form-label">Assigned To</label>
                                                <input type="text" class="form-control" id="assignedTo" name="assigned_to" placeholder="Enter person name">
                                            </div>
                                            <div class="col-md-6">
                                                <label for="location" class="form-label">Location</label>
                                                <input type="text" class="form-control" id="location" name="location" placeholder="Enter location">
                                            </div>
                                            <div class="col-md-6">
                                                <label for="condition" class="form-label">Condition</label>
                                                <select class="form-select" id="condition" name="condition">
                                                    <option selected disabled value="">Select condition</option>
                                                    <option value="New">New</option>
                                                    <option value="Good">Good</option>
                                                    <option value="Fair">Fair</option>
                                                    <option value="Poor">Poor</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <!-- Notes Section -->
                                <div class="card border-0 shadow-sm h-100">
                                    <div class="card-header bg-light py-2">
                                        <h6 class="mb-0 text-primary">Additional Information</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-0">
                                            <label for="notes" class="form-label">Notes</label>
                                            <textarea class="form-control" id="notes" name="notes" rows="3" placeholder="Enter additional notes"></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        Cancel
                    </button>
                    <button type="button" class="btn btn-primary" id="saveItemBtn" onclick="saveInventoryItem()">
                        Save Item
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Purchase Order Modal -->
    <div class="modal fade" id="addPOModal" tabindex="-1" aria-labelledby="addPOModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-gradient-primary text-white">
                    <h5 class="modal-title" id="addPOModalLabel">Create New Purchase Order</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body py-3">
                    <form id="poForm">
                        <!-- Hidden fields for ML tracking -->
                        <input type="hidden" id="poTrackPrediction" name="track_for_prediction" value="true">
                        <input type="hidden" id="poTrackingData" name="tracking_data">

                        <div class="row g-3">
                            <!-- Left column - Main information -->
                            <div class="col-md-6">
                                <!-- PO Details Section -->
                                <div class="card border-0 shadow-sm mb-3">
                                    <div class="card-header bg-light py-2">
                                        <h6 class="mb-0 text-primary">PO Information</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row g-2">
                                            <div class="col-md-6">
                                                <label for="poNo" class="form-label">PO NO.</label>
                                                <input type="text" class="form-control" id="poNo" name="po_no" placeholder="Enter PO number" required>
                                            </div>
                                            <div class="col-md-6">
                                                <label for="supplier" class="form-label">SUPPLIER</label>
                                                <input type="text" class="form-control" id="supplier" name="supplier_name" placeholder="Enter supplier name">
                                            </div>
                                            <div class="col-md-6">
                                                <label for="poDate" class="form-label">DATE</label>
                                                <input type="date" class="form-control" id="poDate" name="po_date">
                                            </div>
                                            <div class="col-md-6">
                                                <label for="refNo" class="form-label">Ref. No.</label>
                                                <input type="text" class="form-control" id="refNo" name="ref_no" placeholder="Enter reference number">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Supplier Information Section -->
                                <div class="card border-0 shadow-sm-3">
                                    <div class="card-header bg-light py-2">
                                        <h6 class="mb-0 text-primary">Supplier Information</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row g-2">
                                            <div class="col-md-6">
                                                <label for="modeOfProcurement" class="form-label">Mode of Procurement</label>
                                                <input type="text" class="form-control" id="modeOfProcurement" name="mode_of_procurement" placeholder="Enter mode of procurement">
                                            </div>
                                            <div class="col-md-6">
                                                <label for="emailAddress" class="form-label">E-Mail Address</label>
                                                <input type="email" class="form-control" id="emailAddress" name="email" placeholder="Enter email address">
                                            </div>
                                            <div class="col-12">
                                                <label for="supplierAddress" class="form-label">Supplier Address</label>
                                                <input type="text" class="form-control" id="supplierAddress" name="supplier_address" placeholder="Enter supplier address">
                                            </div>
                                            <div class="col-md-6">
                                                <label for="telephoneNo" class="form-label">Tel.</label>
                                                <input type="text" class="form-control" id="telephoneNo" name="tel" placeholder="Enter telephone number">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Right column - Additional information -->
                            <div class="col-md-6">
                                <!-- PR Information Section -->
                                <div class="card border-0 shadow-sm mb-3">
                                    <div class="card-header bg-light py-2">
                                        <h6 class="mb-0 text-primary">PR Information</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row g-2">
                                            <div class="col-md-6">
                                                <label for="prNo" class="form-label">PR No.</label>
                                                <input type="text" class="form-control" id="prNo" name="pr_no" placeholder="Enter PR number">
                                            </div>
                                            <div class="col-md-6">
                                                <label for="prDate" class="form-label">Date</label>
                                                <input type="date" class="form-control" id="prDate" name="pr_date">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Delivery Information Section -->
                                <div class="card border-0 shadow-sm mb-3">
                                    <div class="card-header bg-light py-2">
                                        <h6 class="mb-0 text-primary">Delivery Information</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row g-2">
                                            <div class="col-md-6">
                                                <label for="placeOfDelivery" class="form-label">Place of Delivery</label>
                                                <input type="text" class="form-control" id="placeOfDelivery" name="place_of_delivery" placeholder="Enter delivery place">
                                            </div>
                                            <div class="col-md-6">
                                                <label for="deliveryDate" class="form-label">Date of Delivery</label>
                                                <input type="date" class="form-control" id="deliveryDate" name="delivery_date">
                                            </div>
                                            <div class="col-md-6">
                                                <label for="paymentTerm" class="form-label">Payment Term</label>
                                                <input type="text" class="form-control" id="paymentTerm" name="payment_term" placeholder="Enter payment term">
                                            </div>
                                            <div class="col-md-6">
                                                <label for="deliveryTerm" class="form-label">Delivery Term</label>
                                                <input type="text" class="form-control" id="deliveryTerm" name="delivery_term" placeholder="Enter delivery term">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Obligation Information Section -->
                                <div class="card border-0 shadow-sm mb-3">
                                    <div class="card-header bg-light py-2">
                                        <h6 class="mb-0 text-primary">Obligation Information</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row g-2">
                                            <div class="col-md-6">
                                                <label for="obligationRequestNo" class="form-label">Obligation Request No.</label>
                                                <input type="text" class="form-control" id="obligationRequestNo" name="obligation_request_no" placeholder="Enter obligation request number">
                                            </div>
                                            <div class="col-md-6">
                                                <label for="obligationAmount" class="form-label">Obligation Amount</label>
                                                <div class="input-group">
                                                    <span class="input-group-text">₱</span>
                                                    <input type="text" class="form-control" id="obligationAmount" name="obligation_amount" placeholder="0.00">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Full width - Gentlemen's note and item details -->
                            <div class="col-12">
                                <div class="alert alert-light rounded-3 border mb-3">
                                    <p class="mb-1 fw-medium">Gentlemen:</p>
                                    <p class="mb-0 small">Please furnish this office the following articles subject to the terms and conditions contained herein:</p>
                                </div>
                            </div>

                            <!-- Item Details Section -->
                            <div class="col-12">
                                <div class="card border-0 shadow-sm">
                                    <div class="card-header bg-light py-2 d-flex justify-content-between align-items-center">
                                        <h6 class="mb-0 text-primary">Item Details</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-bordered enhanced-po-table" id="poItemsTable">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th>Item</th>
                                                        <th>Unit</th>
                                                        <th style="width: 30%;">Description</th>
                                                        <th>QTY</th>
                                                        <th>Unit Cost</th>
                                                        <th>Amount</th>
                                                        <th>Action</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr>
                                                        <td>
                                                            <input type="text" class="form-control form-control-sm item-name" name="item_name[]" placeholder="Item">
                                                        </td>
                                                        <td>
                                                            <input type="text" class="form-control form-control-sm item-unit" name="unit[]" placeholder="Unit">
                                                        </td>
                                                        <td>
                                                            <textarea class="form-control form-control-sm item-description" name="item_description[]" placeholder="Description" rows="2" style="min-height: 60px;"></textarea>
                                                            <span class="truncated-indicator" style="display: none;">more</span>
                                                        </td>
                                                        <td>
                                                            <input type="number" class="form-control form-control-sm qty" name="quantity[]" placeholder="0">
                                                        </td>
                                                        <td>
                                                            <input type="number" class="form-control form-control-sm unit-cost" name="unit_cost[]" placeholder="0.00" min="0">
                                                        </td>
                                                        <td>
                                                            <input type="text" class="form-control form-control-sm amount" name="amount[]" placeholder="0.00" readonly>
                                                        </td>
                                                        <td class="text-center">
                                                            <button type="button" class="btn btn-sm btn-danger remove-row">
                                                                <i class="bi bi-trash"></i>
                                                            </button>
                                                        </td>
                                                    </tr>
                                                </tbody>
                                                <tfoot>
                                                    <tr>
                                                        <td colspan="7">
                                                            <button type="button" class="btn btn-sm btn-success" id="addRow">
                                                                <i class="bi bi-plus-circle"></i> Add Item
                                                            </button>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td colspan="5" class="text-end fw-bold">Total Amount:</td>
                                                        <td>
                                                            <input type="text" class="form-control form-control-sm" id="totalAmount" value="₱0.00" readonly>
                                                        </td>
                                                        <td></td>
                                                    </tr>
                                                </tfoot>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        Cancel
                    </button>
                    <button type="button" class="btn btn-primary" id="savePoBtn">
                        Save PO
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Property Acknowledgement Receipt Modal -->
    <div class="modal fade" id="addPARModal" tabindex="-1" aria-labelledby="addPARModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content border-0 shadow">
                <div class="modal-body py-3">
                    <form id="parForm">
                        <!-- Hidden fields for ML tracking -->
                        <input type="hidden" id="parTrackPrediction" name="track_for_prediction" value="true">
                        <input type="hidden" id="parTrackingData" name="tracking_data">
                        <input type="hidden" id="par_id" name="par_id" value="">
                        <input type="hidden" id="received_by_id" name="received_by_id" value="1">
                        <input type="hidden" id="default_user_id" name="default_user_id" value="1">

                        <!-- Rest of the form content -->
                        <div class="row g-3">
                            <!-- Left column -->
                            <div class="col-md-6">
                                <!-- PAR Basic Information Section -->
                                <div class="card border-0 shadow-sm mb-3">
                                    <div class="card-header bg-light py-2">
                                        <h6 class="mb-0 text-primary">PAR Information</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row g-2">
                                            <div class="col-md-6">
                                                <label for="par_no" class="form-label">PAR No.</label>
                                                <input type="text" class="form-control" id="par_no" name="par_no">
                                            </div>
                                            <div class="col-md-6">
                                                <label for="entity_name" class="form-label">Entity Name</label>
                                                <input type="text" class="form-control" id="entity_name" name="entity_name">
                                            </div>
                                            <div class="col-md-6">
                                                <label for="date_acquired" class="form-label">Date Acquired</label>
                                                <input type="date" class="form-control" id="date_acquired" name="date_acquired">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Remarks Section -->
                                <div class="card border-0 shadow-sm mb-3">
                                    <div class="card-header bg-light py-2">
                                        <h6 class="mb-0 text-primary">Additional Information</h6>
                                    </div>
                                    <div class="card-body">
                                        <div>
                                            <label for="remarks" class="form-label">Remarks</label>
                                            <textarea class="form-control" id="remarks" name="remarks" rows="3" placeholder="Enter remarks (optional)"></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Right column -->
                            <div class="col-md-6">
                                <!-- Recipient Information Section -->
                                <div class="card border-0 shadow-sm mb-3">
                                    <div class="card-header bg-light py-2">
                                        <h6 class="mb-0 text-primary">Recipient Information</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row g-2">
                                            <div class="col-md-6">
                                                <label for="received_by" class="form-label">Received by</label>
                                                <input type="text" class="form-control" id="received_by" name="received_by" placeholder="Enter employee name" onchange="document.getElementById('received_by_id').value = '';">
                                            </div>
                                            <div class="col-md-6">
                                                <label for="position" class="form-label">Position</label>
                                                <input type="text" class="form-control" id="position" name="position" placeholder="Enter position">
                                            </div>
                                            <div class="col-md-6">
                                                <label for="department" class="form-label">Department</label>
                                                <input type="text" class="form-control" id="department" name="department" placeholder="Enter department">
                                            </div>
                                            <div class="col-md-6">
                                                <label for="expiry_date" class="form-label">Expiry Date</label>
                                                <input type="date" class="form-control" id="expiry_date" name="expiry_date">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Item Details Section - Full width -->
                            <div class="col-12">
                                <div class="card border-0 shadow-sm">
                                    <div class="card-header bg-light py-2">
                                        <h6 class="mb-0 text-primary">Item Details</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-striped table-bordered table-hover align-middle shadow-sm" id="parItemsTable">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th>QTY</th>
                                                        <th>Unit</th>
                                                        <th>Description</th>
                                                        <th>Property Number</th>
                                                        <th>Date Acquired</th>
                                                        <th>Amount</th>
                                                        <th>Action</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <!-- Table rows will be dynamically added by JavaScript -->
                                                </tbody>
                                                <tfoot>
                                                    <tr>
                                                        <td colspan="7">
                                                            <button type="button" class="btn btn-sm btn-success" id="addParRowBtn">
                                                                <i class="bi bi-plus-circle"></i> Add Item
                                                            </button>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td colspan="5" class="text-end fw-bold">Total Amount:</td>
                                                        <td class="fw-bold">
                                                            <span id="parTotal">0.00</span>
                                                            <input type="hidden" id="parTotalAmount" name="total_amount" value="0.00">
                                                        </td>
                                                        <td></td>
                                                    </tr>
                                                </tfoot>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        Cancel
                    </button>
                    <button type="button" class="btn btn-primary" id="saveParBtn">
                        Save PAR
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- View Purchase Order Modal -->
    <div class="modal fade" id="viewPOModal" tabindex="-1" aria-labelledby="viewPOModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="viewPOModalLabel"><i class="bi bi-eye"></i> View Purchase Order</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="poLoading" class="text-center p-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2">Loading purchase order details...</p>
                    </div>
                    <div id="poContent"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" onclick="printPO()">Print</button>
                </div>
                </div>
            </div>
        </div>

        <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
        <script src="updateURL.js"></script>
        <script src="admindashboard.js"></script>
        <script src="par.js"></script>
        <script src="amindinventory.js"></script>
        <script src="dashboard_analytics.js"></script>
        <script src="dashboard_ml_prediction.js"></script>
        <script src="inventory_ml_tracking.js"></script>
        <script src="user_dashboard_client.js"></script>
        <script src="user_management.js"></script>
        <script src="user_navigation.js"></script>

     
        
        <!-- IoT Blockchain Integration Modal -->
        <div class="modal fade" id="iotBlockchainModal" tabindex="-1" aria-labelledby="iotBlockchainModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-xl">
                <div class="modal-content border-0 shadow">
                    <div class="modal-header bg-gradient-primary text-white">
                        <h5 class="modal-title" id="iotBlockchainModalLabel"><i class="bi bi-hdd-network"></i> IoT Blockchain Integration</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-0">
                        <ul class="nav nav-tabs" id="iotBlockchainTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="dashboard-tab" data-bs-toggle="tab" data-bs-target="#iot-dashboard" type="button" role="tab" aria-controls="iot-dashboard" aria-selected="true">
                                    <i class="bi bi-speedometer2"></i> Overview
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="po-par-tracker-tab" data-bs-toggle="tab" data-bs-target="#po-par-tracker" type="button" role="tab" aria-controls="po-par-tracker" aria-selected="false">
                                    <i class="bi bi-graph-up"></i> PO/PAR Tracker
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="sensors-tab" data-bs-toggle="tab" data-bs-target="#sensors-data" type="button" role="tab" aria-controls="sensors-data" aria-selected="false">
                                    <i class="bi bi-cpu"></i> Sensors
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="blockchain-tab" data-bs-toggle="tab" data-bs-target="#blockchain-data" type="button" role="tab" aria-controls="blockchain-data" aria-selected="false">
                                    <i class="bi bi-link-45deg"></i> Blockchain
                                </button>
                            </li>
                        </ul>

                        <div class="tab-content p-3" id="iotBlockchainTabContent">
                            <!-- Overview Tab -->
                            <div class="tab-pane fade show active" id="iot-dashboard" role="tabpanel" aria-labelledby="dashboard-tab">
                                <div class="row g-3">
                                    <!-- IoT Stats -->
                                    <div class="col-md-6">
                                        <div class="card border-0 shadow-sm h-100">
                                            <div class="card-header bg-light py-2 d-flex justify-content-between align-items-center">
                                                <h6 class="mb-0 text-primary"><i class="bi bi-cpu"></i> IoT Status</h6>
                                                <button id="refreshIoTData" class="btn btn-sm btn-outline-primary">
                                                    <i class="bi bi-arrow-clockwise"></i> Refresh
                                                </button>
                                            </div>
                                            <div class="card-body">
                                                <div class="row g-3">
                                                    <div class="col-md-4">
                                                        <div class="p-3 bg-light rounded">
                                                            <div class="d-flex align-items-center">
                                                                <div class="icon-wrapper bg-primary text-white rounded-circle p-2 me-3">
                                                                    <i class="bi bi-broadcast"></i>
                                                                </div>
                                                                <div>
                                                                    <div class="text-muted small">Active Sensors</div>
                                                                    <div id="activeSensors" class="fs-5 fw-bold">0</div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <div class="p-3 bg-light rounded">
                                                            <div class="d-flex align-items-center">
                                                                <div class="icon-wrapper bg-success text-white rounded-circle p-2 me-3">
                                                                    <i class="bi bi-bar-chart"></i>
                                                                </div>
                                                                <div>
                                                                    <div class="text-muted small">Data Points</div>
                                                                    <div id="dataPoints" class="fs-5 fw-bold">0</div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <div class="p-3 bg-light rounded">
                                                            <div class="d-flex align-items-center">
                                                                <div class="icon-wrapper bg-info text-white rounded-circle p-2 me-3">
                                                                    <i class="bi bi-clock-history"></i>
                                                                </div>
                                                                <div>
                                                                    <div class="text-muted small">Last Update</div>
                                                                    <div id="lastUpdate" class="fs-5 fw-bold">N/A</div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-12">
                                                        <div class="mt-2">
                                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                                <span class="text-muted small">System Health</span>
                                                            </div>
                                                            <div class="progress" style="height: 8px;">
                                                                <div id="iotHealthStatus" class="progress-bar bg-success" role="progressbar" style="width: 85%" aria-valuemin="0" aria-valuemax="100"></div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Blockchain Stats -->
                                    <div class="col-md-6">
                                        <div class="card border-0 shadow-sm h-100">
                                            <div class="card-header bg-light py-2 d-flex justify-content-between align-items-center">
                                                <h6 class="mb-0 text-primary"><i class="bi bi-link-45deg"></i> Blockchain Status</h6>
                                                <button id="viewBlockchainDetails" class="btn btn-sm btn-outline-primary">
                                                    <i class="bi bi-eye"></i> View Explorer
                                                </button>
                                            </div>
                                            <div class="card-body">
                                                <div class="row g-3">
                                                    <div class="col-md-4">
                                                        <div class="p-3 bg-light rounded">
                                                            <div class="d-flex align-items-center">
                                                                <div class="icon-wrapper bg-primary text-white rounded-circle p-2 me-3">
                                                                    <i class="bi bi-boxes"></i>
                                                                </div>
                                                                <div>
                                                                    <div class="text-muted small">Transactions</div>
                                                                    <div id="totalTransactions" class="fs-5 fw-bold">0</div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <div class="p-3 bg-light rounded">
                                                            <div class="d-flex align-items-center">
                                                                <div class="icon-wrapper bg-success text-white rounded-circle p-2 me-3">
                                                                    <i class="bi bi-shield-check"></i>
                                                                </div>
                                                                <div>
                                                                    <div class="text-muted small">Chain Health</div>
                                                                    <div id="chainHealth" class="fs-5 fw-bold">0%</div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <div class="p-3 bg-light rounded">
                                                            <div class="d-flex align-items-center">
                                                                <div class="icon-wrapper bg-info text-white rounded-circle p-2 me-3">
                                                                    <i class="bi bi-box"></i>
                                                                </div>
                                                                <div>
                                                                    <div class="text-muted small">Last Block</div>
                                                                    <div id="lastBlock" class="fs-5 fw-bold">#0</div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-12">
                                                        <div class="mt-2">
                                                            <div class="text-muted small mb-2">Recent Transactions</div>
                                                            <div id="recentTransactions" class="recent-transactions-list">
                                                                <div class="text-muted small py-2">No transactions</div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- PO/PAR Tracker Tab -->
                            <div class="tab-pane fade" id="po-par-tracker" role="tabpanel" aria-labelledby="po-par-tracker-tab">
                                <div class="row g-4">
                                    <!-- PO Prediction Panel -->
                                    <div class="col-md-6">
                                        <div class="card border-0 shadow-sm">
                                            <div class="card-header bg-light py-3">
                                                <h6 class="mb-0 text-primary"><i class="bi bi-cart3"></i> PO to PAR Prediction</h6>
                                            </div>
                                            <div class="card-body">
                                                <div class="prediction-section p-3 mb-3 bg-light rounded">
                                                    <div class="row g-3">
                                                        <div class="col-md-6">
                                                            <div class="form-floating">
                                                                <input type="number" class="form-control" id="poAmountInput" placeholder="0.00" min="0">
                                                                <label for="poAmountInput">PO Amount (₱)</label>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-6 d-flex align-items-center">
                                                            <button id="calculatePoPrediction" class="btn btn-primary">
                                                                <i class="bi bi-calculator"></i> Calculate
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="results-section">
                                                    <div class="row g-3">
                                                        <div class="col-md-6">
                                                            <div class="p-3 bg-light rounded">
                                                                <div class="text-muted small">PO Amount</div>
                                                                <div id="currentPOAmount" class="fs-5 fw-bold">₱0.00</div>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <div class="p-3 bg-light rounded">
                                                                <div class="text-muted small">Predicted PAR Amount</div>
                                                                <div id="predictedPARAmount" class="fs-5 fw-bold">₱0.00</div>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <div class="p-3 bg-light rounded">
                                                                <div class="text-muted small">PAR/PO Ratio</div>
                                                                <div id="poPARRatioValue" class="fs-5 fw-bold">0.00</div>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <div class="p-3 bg-light rounded">
                                                                <div class="text-muted small">Health Status</div>
                                                                <div class="progress mt-2" style="height: 8px;">
                                                                    <div id="poHealthIndicator" class="progress-bar bg-success" role="progressbar" style="width: 0%" aria-valuemin="0" aria-valuemax="100"></div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="blockchain-verification mt-3 p-3 bg-light rounded">
                                                        <div class="d-flex align-items-center">
                                                            <i class="bi b  i-check-circle-fill text-success me-2"></i>
                                                            <div>
                                                                <div class="small fw-bold">Verification Status</div>
                                                                <div class="verification-hash small text-muted">
                                                                    <code id="poVerificationHash">Not verified</code>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- PAR Prediction Panel -->
                                    <div class="col-md-6">
                                        <div class="card border-0 shadow-sm">
                                            <div class="card-header bg-light py-3">
                                                <h6 class="mb-0 text-primary"><i class="bi bi-receipt"></i> PAR to PO Prediction</h6>
                                            </div>
                                            <div class="card-body">
                                                <div class="prediction-section p-3 mb-3 bg-light rounded">
                                                    <div class="row g-3">
                                                        <div class="col-md-6">
                                                            <div class="form-floating">
                                                                <input type="number" class="form-control" id="parAmountInput" placeholder="0.00" min="0">
                                                                <label for="parAmountInput">PAR Amount (₱)</label>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-6 d-flex align-items-center">
                                                            <button id="calculateParPrediction" class="btn btn-primary">
                                                                <i class="bi bi-calculator"></i> Calculate
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="results-section">
                                                    <div class="row g-3">
                                                        <div class="col-md-6">
                                                            <div class="p-3 bg-light rounded">
                                                                <div class="text-muted small">PAR Amount</div>
                                                                <div id="currentPARAmount" class="fs-5 fw-bold">₱0.00</div>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <div class="p-3 bg-light rounded">
                                                                <div class="text-muted small">Related PO Amount</div>
                                                                <div id="relatedPOAmount" class="fs-5 fw-bold">₱0.00</div>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <div class="p-3 bg-light rounded">
                                                                <div class="text-muted small">PAR/PO Utilization</div>
                                                                <div id="parPOUtilization" class="fs-5 fw-bold">0%</div>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <div class="p-3 bg-light rounded">
                                                                <div class="text-muted small">Health Status</div>
                                                                <div class="progress mt-2" style="height: 8px;">
                                                                    <div id="parHealthIndicator" class="progress-bar bg-success" role="progressbar" style="width: 0%" aria-valuemin="0" aria-valuemax="100"></div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="blockchain-verification mt-3 p-3 bg-light rounded">
                                                        <div class="d-flex align-items-center">
                                                            <i class="bi bi-check-circle-fill text-success me-2"></i>
                                                            <div>
                                                                <div class="small fw-bold">Verification Status</div>
                                                                <div class="verification-hash small text-muted">
                                                                    <code id="parVerificationHash">Not verified</code>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- Inventory Condition Monitoring -->
                                    <div class="col-12">    
                                        <div class="card border-0 shadow-sm">
                                            <div class="card-header bg-light py-3 d-flex justify-content-between align-items-center">
                                                <h6 class="mb-0 text-primary"><i class="bi bi-shield"></i> Inventory Condition Monitoring</h6>
                                                <button id="refreshConditionData" class="btn btn-sm btn-outline-primary">
                                                    <i class="bi bi-arrow-clockwise"></i> Refresh
                                                </button>
                                            </div>
                                            <div class="card-body">
                                                <div class="table-responsive">
                                                    <table class="table table-hover align-middle">
                                                        <thead class="table-light">
                                                            <tr>
                                                                <th>Item</th>
                                                                <th>Serial Number</th>
                                                                <th>Location</th>
                                                                <th>Condition</th>
                                                                <th>Status</th>
                                                                <th>Prediction</th>
                                                                <th>Last Updated</th>
                                                                <th>Actions</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody id="inventoryConditionTable">
                                                            <tr>
                                                                <td colspan="8" class="text-center py-3">
                                                                    <div class="text-muted">No items to display</div>
                                                                </td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                            </div>  
                                        </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>  
                     </div>
             </div>
       </div>
    </div>

    <!-- User Modal -->
    <div class="modal fade" id="userModal" tabindex="-1" aria-labelledby="userModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-gradient-primary text-white">
                    <h5 class="modal-title" id="userModalLabel">Add New User</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body py-3">
                    <form id="userForm">
                        <input type="hidden" id="userId" name="user_id" value="">
                        
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="username" class="form-label">Username</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-person"></i></span>
                                    <input type="text" class="form-control" id="username" name="username" placeholder="Enter username" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label for="email" class="form-label">Email</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                                    <input type="email" class="form-control" id="email" name="email" placeholder="Enter email" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label for="password" class="form-label">Password</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-key"></i></span>
                                    <input type="password" class="form-control" id="password" name="password" placeholder="Enter password" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label for="confirmPassword" class="form-label">Confirm Password</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-check-circle"></i></span>
                                    <input type="password" class="form-control" id="confirmPassword" name="confirm_password" placeholder="Confirm password" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label for="userRole" class="form-label">Role</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-shield"></i></span>
                                    <select class="form-select" id="userRole" name="role">
                                        <option value="admin">Administrator</option>
                                        <option value="manager">Manager</option>
                                        <option value="staff">Staff</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label for="status" class="form-label">Status</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-toggle-on"></i></span>
                                    <select class="form-select" id="status" name="status">
                                        <option value="active">Active</option>
                                        <option value="inactive">Inactive</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        Cancel
                    </button>
                    <button type="button" class="btn btn-primary" id="saveUserBtn">
                        Save User
                    </button>
                </div>
            </div>
        </div>
    </div>
</body>
</html>