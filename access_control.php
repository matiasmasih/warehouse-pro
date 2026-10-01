<?php
// access_control.php - Warehouse access rules

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Get current user's role name from session.
 * Warehouse roles: admin, manager, staff
 */
function current_role() {
    $role = $_SESSION['role'] ?? '';
    switch (strtolower($role)) {
        case 'admin':   return 'admin';
        case 'manager': return 'manager';
        case 'staff':   return 'staff';
        default:        return 'guest';
    }
}

/**
 * Check access for a page + role.
 * @return string 'full' | 'view' | 'no'
 */
function check_access($page, $role) {

    // ============================================
    // DASHBOARDS
    // ============================================
    $dashboards = [
        'admin_dashboard' => [
            'admin' => 'full', 'manager' => 'view', 'staff' => 'no',
        ],
        'manager_dashboard' => [
            'admin' => 'full', 'manager' => 'full', 'staff' => 'view',
        ],
        'staff_dashboard' => [
            'admin' => 'full', 'manager' => 'full', 'staff' => 'full',
        ],
    ];

    // ============================================
    // PAGES
    // ============================================
    $pages = [
        'products' => [
            'admin' => 'full', 'manager' => 'full', 'staff' => 'view',
        ],
        'categories' => [
            'admin' => 'full', 'manager' => 'full', 'staff' => 'view',
        ],
        'suppliers' => [
            'admin' => 'full', 'manager' => 'full', 'staff' => 'view',
        ],
        'warehouses' => [
            'admin' => 'full', 'manager' => 'view', 'staff' => 'no',
        ],
        'locations' => [
            'admin' => 'full', 'manager' => 'full', 'staff' => 'view',
        ],
        'inventory' => [
            'admin' => 'full', 'manager' => 'full', 'staff' => 'full',
        ],
        'stock_movements' => [
            'admin' => 'full', 'manager' => 'full', 'staff' => 'full',
        ],
        'purchase_orders' => [
            'admin' => 'full', 'manager' => 'full', 'staff' => 'view',
        ],
        'shipments' => [
            'admin' => 'full', 'manager' => 'full', 'staff' => 'full',
        ],
        'transfers' => [
            'admin' => 'full', 'manager' => 'full', 'staff' => 'full',
        ],
        'adjustments' => [
            'admin' => 'full', 'manager' => 'full', 'staff' => 'full',
        ],
        'batches' => [
            'admin' => 'full', 'manager' => 'full', 'staff' => 'view',
        ],
        'reports' => [
            'admin' => 'full', 'manager' => 'full', 'staff' => 'no',
        ],
        'users' => [
            'admin' => 'full', 'manager' => 'full', 'staff' => 'no',
        ],
        'shifts' => [
            'admin' => 'full', 'manager' => 'full', 'staff' => 'view',
        ],
        'failed_logins' => [
            'admin' => 'full', 'manager' => 'full', 'staff' => 'no',
        ],
        'timeclock' => [
            'admin' => 'full', 'manager' => 'full', 'staff' => 'full',
        ],
        'attendance' => [
            'admin' => 'full', 'manager' => 'full', 'staff' => 'no',
        ],
        'messages' => [
            'admin' => 'full', 'manager' => 'full', 'staff' => 'full',
        ],
        'audit_logs' => [
            'admin' => 'full', 'manager' => 'view', 'staff' => 'no',
        ],
    ];

    if (isset($dashboards[$page])) {
        return $dashboards[$page][$role] ?? 'no';
    }
    if (isset($pages[$page])) {
        return $pages[$page][$role] ?? 'no';
    }
    return 'no';
}

/** Current user's access to a page */
function my_access($page) {
    return check_access($page, current_role());
}

/** Require access — redirect if not allowed */
function require_access($page, $redirect = 'login.php') {
    $access = my_access($page);
    if ($access === 'no') {
        header("Location: $redirect");
        exit;
    }
    return $access; // 'full' or 'view'
}
?>
