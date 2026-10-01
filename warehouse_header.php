<?php
// warehouse_header.php - Shared header for all warehouse pages
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include_once 'access_control.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$viewer_name = trim($_SESSION['username'] ?? '');
$role_names = [
    'admin'   => 'Administrator',
    'manager' => 'Warehouse Manager',
    'staff'   => 'Warehouse Staff',
];
$viewer_role = $role_names[current_role()] ?? 'User';

$current_page = basename($_SERVER['PHP_SELF']);

// Logo target — role's own dashboard
$logo_href = 'login.php';
switch (current_role()) {
    case 'admin':   $logo_href = 'admin_dashboard.php';   break;
    case 'manager': $logo_href = 'manager_dashboard.php'; break;
    case 'staff':   $logo_href = 'staff_dashboard.php';   break;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title ?? 'WarehousePro'; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --primary: #f59e0b;
            --primary-dark: #d97706;
            --primary-light: #fbbf24;
            --bg: #fffbeb;
            --card-bg: #ffffff;
            --text: #1e293b;
            --text-muted: #64748b;
            --border: #e2e8f0;
            --sidebar-bg: #0f172a;
            --sidebar-hover: #1e293b;
            --shadow: 0 4px 20px rgba(0,0,0,0.06);
            --shadow-lg: 0 10px 40px rgba(0,0,0,0.1);
            --radius: 16px;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        [data-theme="dark"] {
            --bg: #0f172a;
            --card-bg: #1e293b;
            --text: #f1f5f9;
            --text-muted: #94a3b8;
            --border: #334155;
            --shadow: 0 4px 20px rgba(0,0,0,0.3);
            --shadow-lg: 0 10px 40px rgba(0,0,0,0.5);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg);
            color: var(--text);
            transition: var(--transition);
            overflow-x: hidden;
        }

        .navbar {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            padding: 14px 0;
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: 0 4px 20px rgba(245, 158, 11, 0.3);
        }

        .navbar-brand {
            font-weight: 700;
            font-size: 1.4rem;
            color: white !important;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .navbar .nav-link {
            color: rgba(255,255,255,0.9) !important;
            font-weight: 500;
            padding: 8px 16px !important;
            border-radius: 10px;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .navbar .nav-link:hover {
            background: rgba(255,255,255,0.15);
            color: white !important;
            transform: translateY(-2px);
        }

        .theme-toggle {
            background: rgba(255,255,255,0.15);
            border: none;
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: var(--transition);
            font-size: 1.1rem;
        }

        .theme-toggle:hover {
            background: rgba(255,255,255,0.25);
            transform: rotate(20deg) scale(1.1);
        }

        .main-container { display: flex; min-height: calc(100vh - 68px); }

        .sidebar {
            background: var(--sidebar-bg);
            color: white;
            width: 250px;
            padding: 25px 0;
            transition: var(--transition);
            box-shadow: 4px 0 20px rgba(0,0,0,0.08);
            position: sticky;
            top: 68px;
            height: calc(100vh - 68px);
            overflow-y: auto;
        }

        .sidebar::-webkit-scrollbar { width: 8px; }
        .sidebar::-webkit-scrollbar-track { background: rgba(0,0,0,0.1); border-radius: 4px; }
        .sidebar::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.35); border-radius: 4px; }
        .sidebar::-webkit-scrollbar-thumb:hover { background: rgba(255,255,255,0.55); }

        .sidebar-header {
            padding: 0 25px 20px;
            border-bottom: 1px solid rgba(255,255,255,0.08);
            margin-bottom: 15px;
        }

        .sidebar-header h4 {
            font-weight: 700;
            color: var(--primary-light);
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1.15rem;
        }

        .sidebar-menu { list-style: none; padding: 0 10px; }
        .sidebar-menu li { margin-bottom: 4px; }

        .sidebar-menu a {
            display: flex;
            align-items: center;
            color: #94a3b8;
            text-decoration: none;
            padding: 11px 18px;
            transition: var(--transition);
            border-radius: 10px;
            font-weight: 500;
            font-size: 0.9rem;
            position: relative;
            overflow: hidden;
        }

        .sidebar-menu a::before {
            content: '';
            position: absolute;
            left: 0; top: 0;
            height: 100%; width: 3px;
            background: var(--primary-light);
            transform: scaleY(0);
            transition: var(--transition);
            border-radius: 0 3px 3px 0;
        }

        .sidebar-menu a:hover {
            background: var(--sidebar-hover);
            color: white;
            transform: translateX(4px);
        }

        .sidebar-menu a:hover::before { transform: scaleY(1); }

        .sidebar-menu a.active {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            box-shadow: 0 4px 15px rgba(245, 158, 11, 0.3);
        }

        .sidebar-menu a.active::before { transform: scaleY(1); }

        .sidebar-menu i {
            margin-right: 12px;
            font-size: 1rem;
            width: 20px;
            text-align: center;
        }

        .sidebar-section {
            padding: 15px 25px 8px;
            color: #475569;
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .main-content {
            flex: 1;
            padding: 30px;
            overflow-y: auto;
            background: var(--bg);
            transition: var(--transition);
        }

        .welcome-banner {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            border-radius: var(--radius);
            padding: 30px;
            margin-bottom: 25px;
            color: white;
            position: relative;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(245, 158, 11, 0.3);
        }

        .welcome-banner::before {
            content: '';
            position: absolute;
            top: -50%; right: -10%;
            width: 300px; height: 300px;
            background: rgba(255,255,255,0.08);
            border-radius: 50%;
        }

        .welcome-banner h2 {
            font-weight: 800;
            font-size: 1.7rem;
            margin-bottom: 6px;
            position: relative;
            z-index: 1;
        }

        .welcome-banner p {
            opacity: 0.9;
            margin: 0;
            font-size: 1rem;
            position: relative;
            z-index: 1;
        }

        .welcome-banner .badge-role {
            display: inline-block;
            background: rgba(255,255,255,0.2);
            padding: 3px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            margin-left: 8px;
            vertical-align: middle;
        }

        .welcome-banner .clock {
            position: absolute;
            top: 30px; right: 30px;
            text-align: right;
            z-index: 1;
        }

        .welcome-banner .clock .time {
            font-size: 2rem;
            font-weight: 700;
            line-height: 1;
        }

        .welcome-banner .clock .date {
            font-size: 0.85rem;
            opacity: 0.85;
            margin-top: 4px;
        }

        .table-card {
            background: var(--card-bg);
            border-radius: var(--radius);
            padding: 22px;
            box-shadow: var(--shadow);
            border: 1px solid var(--border);
            margin-bottom: 25px;
        }

        .table-card h5 {
            font-weight: 700;
            font-size: 1rem;
            margin-bottom: 18px;
            color: var(--text);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .table-card h5 i { color: var(--primary); }

        .table-card table { width: 100%; border-collapse: collapse; }

        .table-card th {
            text-align: left;
            padding: 10px 12px;
            color: var(--text-muted);
            font-weight: 600;
            font-size: 0.78rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 2px solid var(--border);
        }

        .table-card td {
            padding: 12px;
            color: var(--text);
            font-size: 0.9rem;
            border-bottom: 1px solid var(--border);
        }

        .table-card tr:hover td { background: rgba(245, 158, 11, 0.04); }

        .btn-add, .btn-edit, .btn-delete, .btn-secondary-custom, .btn-primary-custom {
            border: none;
            border-radius: 6px;
            padding: 6px 12px;
            font-size: 0.85rem;
            color: white;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            transition: var(--transition);
            cursor: pointer;
        }

        .btn-add, .btn-primary-custom { background: var(--primary); }
        .btn-add:hover, .btn-primary-custom:hover { background: var(--primary-dark); color: white; }

        .btn-edit { background: #22c55e; }
        .btn-edit:hover { background: #16a34a; color: white; }

        .btn-delete { background: #ef4444; }
        .btn-delete:hover { background: #dc2626; color: white; }

        .btn-secondary-custom { background: #64748b; }
        .btn-secondary-custom:hover { background: #475569; color: white; }

        .status-badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            display: inline-block;
        }

        .status-pending   { background: rgba(245, 158, 11, 0.15); color: #d97706; }
        .status-active    { background: rgba(34, 197, 94, 0.15);  color: #16a34a; }
        .status-received  { background: rgba(34, 197, 94, 0.15);  color: #16a34a; }
        .status-shipped   { background: rgba(59, 130, 246, 0.15); color: #2563eb; }
        .status-delivered { background: rgba(16, 185, 129, 0.15); color: #059669; }
        .status-cancelled { background: rgba(239, 68, 68, 0.15);  color: #dc2626; }
        .status-expired   { background: rgba(107, 114, 128, 0.15); color: #4b5563; }
        .status-quarantine { background: rgba(139, 92, 246, 0.15); color: #7c3aed; }
        .status-available  { background: rgba(34, 197, 94, 0.15);  color: #16a34a; }
        .status-depleted   { background: rgba(107, 114, 128, 0.15); color: #4b5563; }
        .status-discontinued { background: rgba(107, 114, 128, 0.15); color: #4b5563; }

        .alert-soft {
            border-radius: 12px;
            padding: 12px 16px;
            margin-bottom: 15px;
            font-size: 0.9rem;
        }

        .search-box { max-width: 320px; }

        @media (max-width: 992px) {
            .sidebar { width: 200px; }
        }

        @media (max-width: 768px) {
            .main-container { flex-direction: column; }
            .sidebar { width: 100%; height: auto; position: relative; top: 0; padding: 15px 0; }
            .sidebar-menu { display: flex; overflow-x: auto; padding: 0 15px; gap: 5px; }
            .sidebar-menu li { flex: 0 0 auto; margin-bottom: 0; }
            .sidebar-menu a { padding: 10px 14px; white-space: nowrap; font-size: 0.82rem; }
            .sidebar-menu a::before { display: none; }
            .main-content { padding: 20px 15px; }
            .welcome-banner .clock { position: static; text-align: left; margin-top: 15px; }
            .sidebar-section { display: none; }
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg">
    <div class="container-fluid px-4">
        <a class="navbar-brand" href="<?= htmlspecialchars($logo_href) ?>">
            <i class="bi bi-box-seam"></i>
            <span>WarehousePro</span>
        </a>
        <div class="ms-auto d-flex align-items-center gap-2">
            <button class="theme-toggle" onclick="toggleTheme()" title="Toggle Dark Mode">
                <i class="bi bi-moon-stars" id="themeIcon"></i>
            </button>
            <a class="nav-link" href="logout.php">
                <i class="bi bi-box-arrow-right"></i> Logout
            </a>
        </div>
    </div>
</nav>

<div class="main-container">
    <div class="sidebar">
        <div class="sidebar-header">
            <h4><i class="bi bi-box-seam"></i> Warehouse Menu</h4>
        </div>

        <div class="sidebar-section">Dashboard</div>
        <ul class="sidebar-menu">
            <?php $__role = current_role(); ?>
            <?php if ($__role === 'admin'): ?>
                <li><a href="admin_dashboard.php" class="<?= $current_page == 'admin_dashboard.php' ? 'active' : '' ?>"><i class="bi bi-speedometer2"></i> Admin Dashboard</a></li>
            <?php elseif ($__role === 'manager'): ?>
                <li><a href="manager_dashboard.php" class="<?= $current_page == 'manager_dashboard.php' ? 'active' : '' ?>"><i class="bi bi-clipboard-data"></i> Manager Dashboard</a></li>
            <?php elseif ($__role === 'staff'): ?>
                <li><a href="staff_dashboard.php" class="<?= $current_page == 'staff_dashboard.php' ? 'active' : '' ?>"><i class="bi bi-person-badge"></i> Staff Dashboard</a></li>
            <?php endif; ?>
            <?php if ($__role === 'staff'): ?>
                <li><a href="my_tasks.php" class="<?= $current_page == 'my_tasks.php' ? 'active' : '' ?>"><i class="bi bi-list-check"></i> My Tasks</a></li>
            <?php endif; ?>
            <?php if (in_array($__role, ['admin','manager'], true)): ?>
                <li><a href="approvals.php" class="<?= $current_page == 'approvals.php' ? 'active' : '' ?>"><i class="bi bi-check2-square"></i> Approvals</a></li>
            <?php endif; ?>
            <?php if (my_access('shifts') !== 'no'): ?>
                <li><a href="shifts.php" class="<?= $current_page == 'shifts.php' ? 'active' : '' ?>"><i class="bi bi-calendar-week"></i> Shifts</a></li>
            <?php endif; ?>
        </ul>

        <div class="sidebar-section">Inventory</div>
        <ul class="sidebar-menu">
            <?php if (my_access('products') !== 'no'): ?>
            <li><a href="products.php" class="<?php echo $current_page == 'products.php' ? 'active' : ''; ?>"><i class="bi bi-box"></i> Products</a></li>
            <?php endif; ?>
            <?php if (my_access('categories') !== 'no'): ?>
            <li><a href="categories.php" class="<?php echo $current_page == 'categories.php' ? 'active' : ''; ?>"><i class="bi bi-tags"></i> Categories</a></li>
            <?php endif; ?>
            <?php if (my_access('suppliers') !== 'no'): ?>
            <li><a href="suppliers.php" class="<?php echo $current_page == 'suppliers.php' ? 'active' : ''; ?>"><i class="bi bi-truck"></i> Suppliers</a></li>
            <?php endif; ?>
            <?php if (my_access('warehouses') !== 'no'): ?>
            <li><a href="warehouses.php" class="<?php echo $current_page == 'warehouses.php' ? 'active' : ''; ?>"><i class="bi bi-building"></i> Warehouses</a></li>
            <?php endif; ?>
            <?php if (my_access('locations') !== 'no'): ?>
            <li><a href="locations.php" class="<?php echo $current_page == 'locations.php' ? 'active' : ''; ?>"><i class="bi bi-geo-alt"></i> Locations</a></li>
            <?php endif; ?>
            <?php if (my_access('inventory') !== 'no'): ?>
            <li><a href="inventory.php" class="<?php echo $current_page == 'inventory.php' ? 'active' : ''; ?>"><i class="bi bi-stack"></i> Inventory</a></li>
            <?php endif; ?>
            <?php if (my_access('batches') !== 'no'): ?>
            <li><a href="batches.php" class="<?php echo $current_page == 'batches.php' ? 'active' : ''; ?>"><i class="bi bi-calendar-week"></i> Batches &amp; Expiry</a></li>
            <?php endif; ?>
        </ul>

        <div class="sidebar-section">Operations</div>
        <ul class="sidebar-menu">
            <?php if (my_access('purchase_orders') !== 'no'): ?>
            <li><a href="purchase_orders.php" class="<?php echo $current_page == 'purchase_orders.php' ? 'active' : ''; ?>"><i class="bi bi-cart-plus"></i> Purchase Orders</a></li>
            <?php endif; ?>
            <?php if (my_access('shipments') !== 'no'): ?>
            <li><a href="shipments.php" class="<?php echo $current_page == 'shipments.php' ? 'active' : ''; ?>"><i class="bi bi-send"></i> Shipments</a></li>
            <?php endif; ?>
            <?php if (my_access('transfers') !== 'no'): ?>
            <li><a href="transfers.php" class="<?php echo $current_page == 'transfers.php' ? 'active' : ''; ?>"><i class="bi bi-arrow-left-right"></i> Transfers</a></li>
            <?php endif; ?>
            <?php if (my_access('adjustments') !== 'no'): ?>
            <li><a href="adjustments.php" class="<?php echo $current_page == 'adjustments.php' ? 'active' : ''; ?>"><i class="bi bi-sliders"></i> Adjustments</a></li>
            <?php endif; ?>
            <?php if (my_access('stock_movements') !== 'no'): ?>
            <li><a href="stock_movements.php" class="<?php echo $current_page == 'stock_movements.php' ? 'active' : ''; ?>"><i class="bi bi-arrow-repeat"></i> Movements</a></li>
            <?php endif; ?>
            <?php if (my_access('transfers') !== 'no'): ?>
            <li><a href="put_away.php" class="<?php echo $current_page == 'put_away.php' ? 'active' : ''; ?>"><i class="bi bi-box-seam"></i> Put-Away</a></li>
            <?php endif; ?>
            <?php if (my_access('adjustments') !== 'no'): ?>
            <li><a href="cycle_count.php" class="<?php echo $current_page == 'cycle_count.php' ? 'active' : ''; ?>"><i class="bi bi-clipboard-check"></i> Cycle Count</a></li>
            <?php endif; ?>
            <?php if (my_access('adjustments') !== 'no'): ?>
            <li><a href="quarantine.php" class="<?php echo $current_page == 'quarantine.php' ? 'active' : ''; ?>"><i class="bi bi-shield-exclamation"></i> Quarantine</a></li>
            <?php endif; ?>
        </ul>

        <div class="sidebar-section">Reports</div>
        <ul class="sidebar-menu">
            <?php if (my_access('reports') !== 'no'): ?>
            <li><a href="reports.php" class="<?php echo $current_page == 'reports.php' ? 'active' : ''; ?>"><i class="bi bi-bar-chart"></i> Reports</a></li>
            <?php endif; ?>
            <?php if (my_access('audit_logs') !== 'no'): ?>
            <li><a href="audit_logs.php" class="<?php echo $current_page == 'audit_logs.php' ? 'active' : ''; ?>"><i class="bi bi-clock-history"></i> Audit Logs</a></li>
            <?php endif; ?>
        </ul>

        <div class="sidebar-section">Admin</div>
        <ul class="sidebar-menu">
            <?php if (my_access('users') !== 'no'): ?>
            <li><a href="users.php" class="<?php echo $current_page == 'users.php' ? 'active' : ''; ?>"><i class="bi bi-people-fill"></i> Users</a></li>
            <?php endif; ?>
            <?php if (my_access('messages') !== 'no'): ?>
            <li><a href="messages.php" class="<?php echo $current_page == 'messages.php' ? 'active' : ''; ?>"><i class="bi bi-chat-dots"></i> Messages</a></li>
            <?php endif; ?>
        </ul>
    </div>

    <div class="main-content">
        <?php if (empty($hide_welcome_banner)): ?>
        <div class="welcome-banner">
            <h2>
                Welcome, <?php echo htmlspecialchars($viewer_name); ?>
                <span class="badge-role"><?php echo htmlspecialchars($viewer_role); ?></span> 👋
            </h2>
            <p><?php echo $page_title ?? 'Warehouse Management'; ?></p>
            <div class="clock">
                <div class="time" id="clock">--:--:--</div>
                <div class="date" id="date">Loading...</div>
            </div>
        </div>
        <?php endif; ?>
