<?php
// Admin users

$pageTitle = 'User Management';
$currentPage = 'users';

require_once __DIR__ . '/includes/admin-header.php';

$pdo = getDBConnection();

// Handle Account Status Toggle (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle_status') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        setFlashMessage('danger', 'Security validation failed (Invalid CSRF Token). Action aborted.');
    } else {
        $userId = (int)($_POST['user_id'] ?? 0);
        $newStatus = sanitize($_POST['new_status'] ?? '');

        if ($userId <= 0 || !in_array($newStatus, ['active', 'inactive'], true)) {
            setFlashMessage('danger', 'Invalid user identifier or status value.');
        } else {
            try {
                $updateStmt = $pdo->prepare("UPDATE users SET status = ? WHERE id = ?");
                $updateStmt->execute([$newStatus, $userId]);

                if ($updateStmt->rowCount() > 0) {
                    $statusText = $newStatus === 'active' ? 'enabled' : 'disabled';
                    setFlashMessage('success', "User account #{$userId} has been successfully {$statusText}.");
                } else {
                    setFlashMessage('warning', "User account #{$userId} was not modified or already set to {$newStatus}.");
                }
            } catch (PDOException $e) {
                error_log("User status update error: " . $e->getMessage());
                setFlashMessage('danger', 'Database error occurred while updating user status.');
            }
        }
    }

    // Preserve search & pagination query parameters on redirect
    $redirectQuery = http_build_query([
        'search' => $_GET['search'] ?? '',
        'status' => $_GET['status'] ?? 'all',
        'page'   => $_GET['page'] ?? 1
    ]);
    header('Location: ' . APP_URL . '/admin/users.php?' . $redirectQuery);
    exit;
}

// Search & Filtering Parameters
$search = trim($_GET['search'] ?? '');
$statusFilter = trim($_GET['status'] ?? 'all');
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 10;
$offset = ($page - 1) * $perPage;

// Build Dynamic SQL Query with Prepared Parameters
$whereClauses = [];
$params = [];

if (!empty($search)) {
    $whereClauses[] = "(u.full_name LIKE ? OR u.email LIKE ? OR u.mobile LIKE ?)";
    $searchWildcard = "%{$search}%";
    $params[] = $searchWildcard;
    $params[] = $searchWildcard;
    $params[] = $searchWildcard;
}

if ($statusFilter === 'active' || $statusFilter === 'inactive') {
    $whereClauses[] = "u.status = ?";
    $params[] = $statusFilter;
}

$whereSql = !empty($whereClauses) ? 'WHERE ' . implode(' AND ', $whereClauses) : '';

// Count Total Matching Records for Pagination
$countQuery = "SELECT COUNT(*) AS total FROM users u {$whereSql}";
$countStmt = $pdo->prepare($countQuery);
$countStmt->execute($params);
$totalRecords = (int)$countStmt->fetch()['total'];
$totalPages = max(1, (int)ceil($totalRecords / $perPage));

// Fetch Filtered Users with Wallet Balances
$dataQuery = "
    SELECT u.id, u.full_name, u.email, u.mobile, u.avatar, u.status, u.created_at, u.updated_at,
           COALESCE(w.balance, 0.00) AS wallet_balance
    FROM users u
    LEFT JOIN wallets w ON u.id = w.user_id
    {$whereSql}
    ORDER BY u.id DESC
    LIMIT {$perPage} OFFSET {$offset}
";
$dataStmt = $pdo->prepare($dataQuery);
$dataStmt->execute($params);
$users = $dataStmt->fetchAll();
?>

<!-- Page Header & Action Controls -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h2 class="fw-bold text-white mb-1 tracking-tight">User Management</h2>
        <p class="text-secondary small mb-0">Browse registered customer accounts, view digital wallet holdings, and toggle account access.</p>
    </div>
    <div class="d-flex align-items-center gap-2">
        <span class="badge bg-secondary bg-opacity-25 text-light px-3 py-2 border border-secondary border-opacity-25">
            <i class="bi bi-person-badge me-1 text-primary"></i> Total Users: <strong><?php echo number_format($totalRecords); ?></strong>
        </span>
    </div>
</div>

<!-- Search & Filtering Toolbar -->
<div class="admin-card p-3 mb-4">
    <form method="GET" action="<?php echo APP_URL; ?>/admin/users.php" class="row g-2 align-items-center">
        <!-- Search Input -->
        <div class="col-md-6 col-lg-5">
            <div class="input-group">
                <span class="input-group-text bg-dark border-secondary border-opacity-50 text-secondary">
                    <i class="bi bi-search"></i>
                </span>
                <input type="text" 
                       name="search" 
                       class="form-control" 
                       placeholder="Search by full name, email, or mobile number..." 
                       value="<?php echo htmlspecialchars($search, ENT_QUOTES, 'UTF-8'); ?>">
            </div>
        </div>

        <!-- Status Filter -->
        <div class="col-md-3 col-lg-3">
            <select name="status" class="form-select">
                <option value="all" <?php echo ($statusFilter === 'all') ? 'selected' : ''; ?>>All Statuses</option>
                <option value="active" <?php echo ($statusFilter === 'active') ? 'selected' : ''; ?>>Active Accounts Only</option>
                <option value="inactive" <?php echo ($statusFilter === 'inactive') ? 'selected' : ''; ?>>Inactive Accounts Only</option>
            </select>
        </div>

        <!-- Submit & Reset Buttons -->
        <div class="col-md-3 col-lg-4 d-flex gap-2">
            <button type="submit" class="btn btn-primary px-3 rounded-pill" style="background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%); border: none;">
                <i class="bi bi-funnel-fill me-1"></i> Apply Filter
            </button>
            <?php if (!empty($search) || $statusFilter !== 'all'): ?>
                <a href="<?php echo APP_URL; ?>/admin/users.php" class="btn btn-outline-secondary px-3 rounded-pill" title="Clear search and filters">
                    <i class="bi bi-x-circle me-1"></i> Clear
                </a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- Users Table Card -->
<div class="admin-card overflow-hidden mb-4">
    <div class="table-responsive">
        <table class="table admin-table mb-0">
            <thead>
                <tr>
                    <th style="width: 70px;">ID</th>
                    <th>User / Customer</th>
                    <th>Email Address</th>
                    <th>Mobile Number</th>
                    <th>Wallet Balance</th>
                    <th>Account Status</th>
                    <th>Joined Date</th>
                    <th class="text-end" style="width: 180px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($users)): ?>
                    <tr>
                        <td colspan="8" class="text-center py-5 text-secondary">
                            <i class="bi bi-people fs-1 d-block mb-2 text-muted"></i>
                            <div class="fw-semibold">No users found matching your search criteria.</div>
                            <div class="small text-muted mt-1">Try adjusting the search query or status filter.</div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td>
                                <span class="font-mono text-secondary">#<?php echo $user['id']; ?></span>
                            </td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="admin-avatar" style="width: 34px; height: 34px; font-size: 0.85rem;">
                                        <?php echo strtoupper(substr($user['full_name'], 0, 1)); ?>
                                    </div>
                                    <div>
                                        <div class="fw-bold text-white"><?php echo htmlspecialchars($user['full_name'], ENT_QUOTES, 'UTF-8'); ?></div>
                                        <div class="text-muted small" style="font-size: 0.75rem;">Verified User</div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="text-light small"><?php echo htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8'); ?></span>
                            </td>
                            <td>
                                <span class="font-mono text-secondary small"><?php echo htmlspecialchars($user['mobile'], ENT_QUOTES, 'UTF-8'); ?></span>
                            </td>
                            <td>
                                <span class="fw-bold text-success font-mono"><?php echo formatCurrency($user['wallet_balance']); ?></span>
                            </td>
                            <td>
                                <?php if ($user['status'] === 'active'): ?>
                                    <span class="badge-status-active">
                                        <i class="bi bi-check-circle-fill me-1"></i> Active
                                    </span>
                                <?php else: ?>
                                    <span class="badge-status-inactive">
                                        <i class="bi bi-slash-circle-fill me-1"></i> Inactive
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="text-secondary small font-mono"><?php echo date('d M Y, h:i A', strtotime($user['created_at'])); ?></span>
                            </td>
                            <td class="text-end">
                                <div class="d-flex justify-content-end align-items-center gap-2">
                                    <!-- Status Toggle Form -->
                                    <form method="POST" action="<?php echo htmlspecialchars($_SERVER['REQUEST_URI'], ENT_QUOTES, 'UTF-8'); ?>" class="d-inline" onsubmit="return confirm('Are you sure you want to change the status of user #<?php echo $user['id']; ?>?');">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(generateCSRFToken(), ENT_QUOTES, 'UTF-8'); ?>">
                                        <input type="hidden" name="action" value="toggle_status">
                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                        
                                        <?php if ($user['status'] === 'active'): ?>
                                            <input type="hidden" name="new_status" value="inactive">
                                            <button type="submit" class="btn btn-sm btn-outline-warning rounded-pill px-2 py-1" title="Disable user access">
                                                <i class="bi bi-person-x-fill me-1"></i> Disable
                                            </button>
                                        <?php else: ?>
                                            <input type="hidden" name="new_status" value="active">
                                            <button type="submit" class="btn btn-sm btn-outline-success rounded-pill px-2 py-1" title="Enable user access">
                                                <i class="bi bi-person-check-fill me-1"></i> Enable
                                            </button>
                                        <?php endif; ?>
                                    </form>

                                    <!-- Inspect Details Button -->
                                    <button type="button" 
                                            class="btn btn-sm btn-outline-secondary rounded-pill px-2 py-1 view-user-btn" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#userDetailsModal"
                                            data-id="<?php echo $user['id']; ?>"
                                            data-name="<?php echo htmlspecialchars($user['full_name'], ENT_QUOTES, 'UTF-8'); ?>"
                                            data-email="<?php echo htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8'); ?>"
                                            data-mobile="<?php echo htmlspecialchars($user['mobile'], ENT_QUOTES, 'UTF-8'); ?>"
                                            data-balance="<?php echo formatCurrency($user['wallet_balance']); ?>"
                                            data-status="<?php echo $user['status']; ?>"
                                            data-created="<?php echo date('d M Y, h:i A', strtotime($user['created_at'])); ?>"
                                            title="View comprehensive customer record">
                                        <i class="bi bi-info-circle"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Table Footer with Pagination Controls -->
    <?php if ($totalPages > 1): ?>
        <div class="p-3 border-top border-secondary border-opacity-10 d-flex flex-column flex-md-row justify-content-between align-items-center gap-3">
            <div class="text-secondary small">
                Showing Page <strong class="text-white"><?php echo $page; ?></strong> of <strong class="text-white"><?php echo $totalPages; ?></strong> (<?php echo $totalRecords; ?> total users)
            </div>
            <nav aria-label="Users pagination">
                <ul class="pagination pagination-sm mb-0">
                    <!-- Previous Page -->
                    <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                        <a class="page-link bg-dark border-secondary border-opacity-25 text-light" href="<?php echo APP_URL; ?>/admin/users.php?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">
                            <i class="bi bi-chevron-left"></i> Prev
                        </a>
                    </li>

                    <!-- Page Numbers -->
                    <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                        <?php if ($p == 1 || $p == $totalPages || ($p >= $page - 2 && $p <= $page + 2)): ?>
                            <li class="page-item <?php echo ($p === $page) ? 'active' : ''; ?>">
                                <a class="page-link <?php echo ($p === $page) ? 'bg-primary border-primary text-white' : 'bg-dark border-secondary border-opacity-25 text-light'; ?>" 
                                   href="<?php echo APP_URL; ?>/admin/users.php?<?php echo http_build_query(array_merge($_GET, ['page' => $p])); ?>">
                                    <?php echo $p; ?>
                                </a>
                            </li>
                        <?php elseif ($p == $page - 3 || $p == $page + 3): ?>
                            <li class="page-item disabled"><span class="page-link bg-dark border-secondary border-opacity-25 text-secondary">&hellip;</span></li>
                        <?php endif; ?>
                    <?php endfor; ?>

                    <!-- Next Page -->
                    <li class="page-item <?php echo ($page >= $totalPages) ? 'disabled' : ''; ?>">
                        <a class="page-link bg-dark border-secondary border-opacity-25 text-light" href="<?php echo APP_URL; ?>/admin/users.php?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>">
                            Next <i class="bi bi-chevron-right"></i>
                        </a>
                    </li>
                </ul>
            </nav>
        </div>
    <?php endif; ?>
</div>

<!-- Modal: User Account Details -->
<div class="modal fade" id="userDetailsModal" tabindex="-1" aria-labelledby="userDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold text-white d-flex align-items-center gap-2" id="userDetailsModalLabel">
                    <i class="bi bi-person-badge-fill text-primary"></i> Customer Profile Dossier
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="text-center py-3 border-bottom border-secondary border-opacity-10 mb-3">
                    <div class="admin-avatar mx-auto mb-2" id="modalUserAvatar" style="width: 54px; height: 54px; font-size: 1.5rem; background: rgba(79, 70, 229, 0.25); color: #818cf8;">
                        U
                    </div>
                    <h5 class="text-white fw-bold mb-0" id="modalUserName">—</h5>
                    <div class="text-muted small" id="modalUserEmail">—</div>
                </div>

                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <div class="p-2 rounded bg-dark border border-secondary border-opacity-25">
                            <div class="text-secondary small">User ID</div>
                            <div class="text-white fw-bold font-mono" id="modalUserId">#—</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-2 rounded bg-dark border border-secondary border-opacity-25">
                            <div class="text-secondary small">Wallet Balance</div>
                            <div class="text-success fw-bold font-mono" id="modalUserBalance">—</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-2 rounded bg-dark border border-secondary border-opacity-25">
                            <div class="text-secondary small">Mobile Number</div>
                            <div class="text-white fw-bold font-mono" id="modalUserMobile">—</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-2 rounded bg-dark border border-secondary border-opacity-25">
                            <div class="text-secondary small">Account Status</div>
                            <div id="modalUserStatus">—</div>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="p-2 rounded bg-dark border border-secondary border-opacity-25">
                            <div class="text-secondary small">Registration Timestamp</div>
                            <div class="text-white small font-mono" id="modalUserCreated">—</div>
                        </div>
                    </div>
                </div>

                <div class="alert alert-info py-2 px-3 small border-0 mb-0 d-flex align-items-center gap-2">
                    <i class="bi bi-shield-check fs-5"></i>
                    <div>Customer deletion is disabled by policy. You can safely enable or disable access at any time.</div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const userModal = document.getElementById('userDetailsModal');
    if (userModal) {
        userModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            if (!button) return;

            const id = button.getAttribute('data-id');
            const name = button.getAttribute('data-name');
            const email = button.getAttribute('data-email');
            const mobile = button.getAttribute('data-mobile');
            const balance = button.getAttribute('data-balance');
            const status = button.getAttribute('data-status');
            const created = button.getAttribute('data-created');

            document.getElementById('modalUserId').textContent = '#' + id;
            document.getElementById('modalUserName').textContent = name;
            document.getElementById('modalUserEmail').textContent = email;
            document.getElementById('modalUserMobile').textContent = mobile;
            document.getElementById('modalUserBalance').textContent = balance;
            document.getElementById('modalUserCreated').textContent = created;
            document.getElementById('modalUserAvatar').textContent = name.charAt(0).toUpperCase();

            const statusContainer = document.getElementById('modalUserStatus');
            if (status === 'active') {
                statusContainer.innerHTML = '<span class="badge-status-active"><i class="bi bi-check-circle-fill me-1"></i> Active</span>';
            } else {
                statusContainer.innerHTML = '<span class="badge-status-inactive"><i class="bi bi-slash-circle-fill me-1"></i> Inactive</span>';
            }
        });
    }
});
</script>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
