<?php
// Admin manage-bills

$pageTitle = 'Manage Utility Bills';
$currentPage = 'bills';

require_once __DIR__ . '/includes/admin-header.php';

$pdo = getDBConnection();

// Handle POST Operations: Add, Edit, Toggle Status, Delete Bill
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submittedToken = $_POST['csrf_token'] ?? '';
    if (!validateCSRFToken($submittedToken)) {
        setFlashMessage('danger', 'Security validation failed (Invalid CSRF Token). Operation aborted.');
        header('Location: ' . APP_URL . '/admin/manage-bills.php');
        exit;
    }

    $action = sanitize($_POST['action'] ?? '');

    if ($action === 'add_bill') {
        $providerId = (int)($_POST['provider_id'] ?? 0);
        $consumerNumber = sanitize($_POST['consumer_number'] ?? '');
        $consumerName = sanitize($_POST['consumer_name'] ?? '');
        $billAmount = (float)($_POST['bill_amount'] ?? 0.00);
        $dueDate = sanitize($_POST['due_date'] ?? '');
        $billDate = sanitize($_POST['bill_date'] ?? date('Y-m-d'));
        $status = in_array($_POST['status'] ?? '', ['unpaid', 'paid'], true) ? $_POST['status'] : 'unpaid';

        if ($providerId <= 0 || empty($consumerNumber) || empty($consumerName) || $billAmount <= 0 || empty($dueDate)) {
            setFlashMessage('danger', 'All bill fields are required with a positive amount.');
        } else {
            try {
                $ins = $pdo->prepare("
                    INSERT INTO sample_bills (provider_id, consumer_number, consumer_name, bill_amount, due_date, bill_date, status)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $ins->execute([$providerId, $consumerNumber, $consumerName, $billAmount, $dueDate, $billDate, $status]);
                setFlashMessage('success', "Utility bill for {$consumerName} ({$consumerNumber}) added successfully.");
            } catch (PDOException $e) {
                error_log("Add bill error: " . $e->getMessage());
                setFlashMessage('danger', 'Database error occurred while adding bill.');
            }
        }
        header('Location: ' . APP_URL . '/admin/manage-bills.php');
        exit;
    }

    if ($action === 'edit_bill') {
        $id = (int)($_POST['bill_id'] ?? 0);
        $providerId = (int)($_POST['provider_id'] ?? 0);
        $consumerNumber = sanitize($_POST['consumer_number'] ?? '');
        $consumerName = sanitize($_POST['consumer_name'] ?? '');
        $billAmount = (float)($_POST['bill_amount'] ?? 0.00);
        $dueDate = sanitize($_POST['due_date'] ?? '');
        $billDate = sanitize($_POST['bill_date'] ?? '');
        $status = in_array($_POST['status'] ?? '', ['unpaid', 'paid'], true) ? $_POST['status'] : 'unpaid';

        if ($id <= 0 || $providerId <= 0 || empty($consumerNumber) || empty($consumerName) || $billAmount <= 0) {
            setFlashMessage('danger', 'Valid Bill ID, Provider, Consumer, and positive amount are required.');
        } else {
            try {
                $upd = $pdo->prepare("
                    UPDATE sample_bills
                    SET provider_id = ?, consumer_number = ?, consumer_name = ?, bill_amount = ?, due_date = ?, bill_date = ?, status = ?
                    WHERE id = ?
                ");
                $upd->execute([$providerId, $consumerNumber, $consumerName, $billAmount, $dueDate, $billDate, $status, $id]);
                setFlashMessage('success', "Utility bill #{$id} updated successfully.");
            } catch (PDOException $e) {
                error_log("Edit bill error: " . $e->getMessage());
                setFlashMessage('danger', 'Database error occurred while updating bill.');
            }
        }
        header('Location: ' . APP_URL . '/admin/manage-bills.php');
        exit;
    }

    if ($action === 'toggle_bill_status') {
        $id = (int)($_POST['bill_id'] ?? 0);
        $newStatus = in_array($_POST['new_status'] ?? '', ['unpaid', 'paid'], true) ? $_POST['new_status'] : 'unpaid';

        if ($id > 0) {
            try {
                $stmt = $pdo->prepare("UPDATE sample_bills SET status = ? WHERE id = ?");
                $stmt->execute([$newStatus, $id]);
                setFlashMessage('success', "Bill #{$id} status changed to {$newStatus}.");
            } catch (PDOException $e) {
                error_log("Toggle bill status error: " . $e->getMessage());
                setFlashMessage('danger', 'Database error updating bill status.');
            }
        }
        header('Location: ' . APP_URL . '/admin/manage-bills.php');
        exit;
    }

    if ($action === 'delete_bill') {
        $id = (int)($_POST['bill_id'] ?? 0);
        if ($id > 0) {
            try {
                $del = $pdo->prepare("DELETE FROM sample_bills WHERE id = ?");
                $del->execute([$id]);
                setFlashMessage('success', "Utility bill record #{$id} deleted successfully.");
            } catch (PDOException $e) {
                error_log("Delete bill error: " . $e->getMessage());
                setFlashMessage('danger', 'Database error deleting bill record.');
            }
        }
        header('Location: ' . APP_URL . '/admin/manage-bills.php');
        exit;
    }
}

// Fetch All Utility Providers for Filter & Modals
$providersStmt = $pdo->query("SELECT * FROM utility_providers ORDER BY type ASC, name ASC");
$allProviders = $providersStmt->fetchAll();

// Filtering Parameters
$search = trim($_GET['search'] ?? '');
$providerFilter = (int)($_GET['provider_id'] ?? 0);
$statusFilter = trim($_GET['status'] ?? 'all');
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 10;
$offset = ($page - 1) * $perPage;

$whereClauses = [];
$params = [];

if (!empty($search)) {
    $whereClauses[] = "(b.consumer_number LIKE ? OR b.consumer_name LIKE ?)";
    $wildcard = "%{$search}%";
    $params[] = $wildcard;
    $params[] = $wildcard;
}

if ($providerFilter > 0) {
    $whereClauses[] = "b.provider_id = ?";
    $params[] = $providerFilter;
}

if ($statusFilter === 'unpaid' || $statusFilter === 'paid') {
    $whereClauses[] = "b.status = ?";
    $params[] = $statusFilter;
}

$whereSql = !empty($whereClauses) ? 'WHERE ' . implode(' AND ', $whereClauses) : '';

// Count Total
$countStmt = $pdo->prepare("SELECT COUNT(*) AS total FROM sample_bills b {$whereSql}");
$countStmt->execute($params);
$totalRecords = (int)$countStmt->fetch()['total'];
$totalPages = max(1, (int)ceil($totalRecords / $perPage));

// Fetch Filtered Bills
$billsStmt = $pdo->prepare("
    SELECT b.*, p.name AS provider_name, p.type AS provider_type, p.state AS provider_state
    FROM sample_bills b
    LEFT JOIN utility_providers p ON b.provider_id = p.id
    {$whereSql}
    ORDER BY b.id DESC
    LIMIT {$perPage} OFFSET {$offset}
");
$billsStmt->execute($params);
$bills = $billsStmt->fetchAll();
?>

<!-- Page Header & Action Controls -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h2 class="fw-bold text-white mb-1 tracking-tight">Utility Bills Management</h2>
        <p class="text-secondary small mb-0">Manage simulated consumer utility bills (Electricity, Water, Piped Gas) for test settlements.</p>
    </div>
    <div class="d-flex align-items-center gap-2">
        <button type="button" class="btn btn-sm btn-primary rounded-pill px-3" style="background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%); border: none;" data-bs-toggle="modal" data-bs-target="#addBillModal">
            <i class="bi bi-plus-circle-fill me-1"></i> Add Utility Bill
        </button>
    </div>
</div>

<!-- Search & Filtering Toolbar -->
<div class="admin-card p-3 mb-4">
    <form method="GET" action="<?php echo APP_URL; ?>/admin/manage-bills.php" class="row g-2 align-items-center">
        <!-- Search Input -->
        <div class="col-md-5">
            <div class="input-group">
                <span class="input-group-text bg-dark border-secondary border-opacity-50 text-secondary">
                    <i class="bi bi-search"></i>
                </span>
                <input type="text" name="search" class="form-control" placeholder="Search Consumer Number or Name..." value="<?php echo htmlspecialchars($search, ENT_QUOTES, 'UTF-8'); ?>">
            </div>
        </div>

        <!-- Provider Filter -->
        <div class="col-md-4">
            <select name="provider_id" class="form-select">
                <option value="0">All Utility Providers</option>
                <?php foreach ($allProviders as $prov): ?>
                    <option value="<?php echo $prov['id']; ?>" <?php echo ($providerFilter === (int)$prov['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($prov['name'], ENT_QUOTES, 'UTF-8'); ?> (<?php echo strtoupper($prov['type']); ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Status Filter -->
        <div class="col-md-3 d-flex gap-2">
            <select name="status" class="form-select">
                <option value="all" <?php echo ($statusFilter === 'all') ? 'selected' : ''; ?>>All Statuses</option>
                <option value="unpaid" <?php echo ($statusFilter === 'unpaid') ? 'selected' : ''; ?>>Unpaid Only</option>
                <option value="paid" <?php echo ($statusFilter === 'paid') ? 'selected' : ''; ?>>Paid Only</option>
            </select>
            <button type="submit" class="btn btn-primary rounded-3 px-3" style="background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%); border: none;">
                <i class="bi bi-funnel-fill"></i>
            </button>
        </div>
    </form>
</div>

<!-- Bills Table Card -->
<div class="admin-card overflow-hidden mb-4">
    <div class="p-3 px-4 border-bottom border-secondary border-opacity-10 d-flex justify-content-between align-items-center">
        <div>
            <h5 class="text-white fw-bold mb-0 d-flex align-items-center gap-2">
                <i class="bi bi-receipt-cutoff text-primary"></i> Consumer Ledger Bills
            </h5>
            <div class="text-secondary small">Sample billing records for instant bill fetch</div>
        </div>
        <span class="badge bg-secondary bg-opacity-25 text-light px-3 py-1">
            <?php echo number_format($totalRecords); ?> Bills
        </span>
    </div>

    <div class="table-responsive">
        <table class="table admin-table mb-0">
            <thead>
                <tr>
                    <th style="width: 60px;">ID</th>
                    <th>Consumer Name</th>
                    <th>Consumer #</th>
                    <th>Provider / Board</th>
                    <th>Bill Amount</th>
                    <th>Bill Date</th>
                    <th>Due Date</th>
                    <th>Status</th>
                    <th class="text-end" style="width: 170px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($bills)): ?>
                    <tr>
                        <td colspan="9" class="text-center py-5 text-secondary">
                            <i class="bi bi-receipt fs-1 d-block mb-2 text-muted"></i>
                            <div class="fw-semibold">No bills found matching your criteria.</div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($bills as $bill): ?>
                        <tr>
                            <td>
                                <span class="font-mono text-secondary">#<?php echo $bill['id']; ?></span>
                            </td>
                            <td>
                                <div class="fw-bold text-white"><?php echo htmlspecialchars($bill['consumer_name'], ENT_QUOTES, 'UTF-8'); ?></div>
                            </td>
                            <td>
                                <span class="font-mono text-primary fw-semibold"><?php echo htmlspecialchars($bill['consumer_number'], ENT_QUOTES, 'UTF-8'); ?></span>
                            </td>
                            <td>
                                <div class="text-light small"><?php echo htmlspecialchars($bill['provider_name'] ?? '—', ENT_QUOTES, 'UTF-8'); ?></div>
                                <span class="badge bg-dark border border-secondary border-opacity-25" style="font-size: 0.65rem;">
                                    <?php echo strtoupper($bill['provider_type'] ?? ''); ?>
                                </span>
                            </td>
                            <td>
                                <span class="fw-bold text-white font-mono"><?php echo formatCurrency($bill['bill_amount']); ?></span>
                            </td>
                            <td>
                                <span class="text-secondary small font-mono"><?php echo date('d M Y', strtotime($bill['bill_date'])); ?></span>
                            </td>
                            <td>
                                <span class="text-light small font-mono"><?php echo date('d M Y', strtotime($bill['due_date'])); ?></span>
                            </td>
                            <td>
                                <?php if ($bill['status'] === 'paid'): ?>
                                    <span class="badge-status-success"><i class="bi bi-check-circle-fill me-1"></i> Paid</span>
                                <?php else: ?>
                                    <span class="badge-status-pending"><i class="bi bi-exclamation-circle-fill me-1"></i> Unpaid</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <div class="d-flex justify-content-end align-items-center gap-1">
                                    <!-- Edit Trigger -->
                                    <button type="button" 
                                            class="btn btn-sm btn-outline-info rounded-pill px-2 py-1 edit-bill-btn"
                                            data-bs-toggle="modal" 
                                            data-bs-target="#editBillModal"
                                            data-id="<?php echo $bill['id']; ?>"
                                            data-provider="<?php echo $bill['provider_id']; ?>"
                                            data-number="<?php echo htmlspecialchars($bill['consumer_number'], ENT_QUOTES, 'UTF-8'); ?>"
                                            data-name="<?php echo htmlspecialchars($bill['consumer_name'], ENT_QUOTES, 'UTF-8'); ?>"
                                            data-amount="<?php echo $bill['bill_amount']; ?>"
                                            data-duedate="<?php echo $bill['due_date']; ?>"
                                            data-billdate="<?php echo $bill['bill_date']; ?>"
                                            data-status="<?php echo $bill['status']; ?>"
                                            title="Edit bill record">
                                        <i class="bi bi-pencil-square"></i>
                                    </button>

                                    <!-- Toggle Paid / Unpaid Status -->
                                    <form method="POST" action="<?php echo htmlspecialchars($_SERVER['REQUEST_URI'], ENT_QUOTES, 'UTF-8'); ?>" class="d-inline">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(generateCSRFToken(), ENT_QUOTES, 'UTF-8'); ?>">
                                        <input type="hidden" name="action" value="toggle_bill_status">
                                        <input type="hidden" name="bill_id" value="<?php echo $bill['id']; ?>">
                                        <?php if ($bill['status'] === 'paid'): ?>
                                            <input type="hidden" name="new_status" value="unpaid">
                                            <button type="submit" class="btn btn-sm btn-outline-warning rounded-pill px-2 py-1" title="Mark as Unpaid">
                                                <i class="bi bi-arrow-counterclockwise"></i>
                                            </button>
                                        <?php else: ?>
                                            <input type="hidden" name="new_status" value="paid">
                                            <button type="submit" class="btn btn-sm btn-outline-success rounded-pill px-2 py-1" title="Mark as Paid">
                                                <i class="bi bi-check2"></i>
                                            </button>
                                        <?php endif; ?>
                                    </form>

                                    <!-- Delete Bill -->
                                    <form method="POST" action="<?php echo htmlspecialchars($_SERVER['REQUEST_URI'], ENT_QUOTES, 'UTF-8'); ?>" class="d-inline" onsubmit="return confirm('Delete bill for #<?php echo $bill['consumer_number']; ?>?');">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(generateCSRFToken(), ENT_QUOTES, 'UTF-8'); ?>">
                                        <input type="hidden" name="action" value="delete_bill">
                                        <input type="hidden" name="bill_id" value="<?php echo $bill['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill px-2 py-1" title="Delete record">
                                            <i class="bi bi-trash3"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
        <div class="p-3 border-top border-secondary border-opacity-10 d-flex justify-content-between align-items-center">
            <span class="text-secondary small">Page <?php echo $page; ?> of <?php echo $totalPages; ?></span>
            <nav>
                <ul class="pagination pagination-sm mb-0">
                    <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                        <a class="page-link bg-dark border-secondary border-opacity-25 text-light" href="<?php echo APP_URL; ?>/admin/manage-bills.php?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">Prev</a>
                    </li>
                    <li class="page-item <?php echo ($page >= $totalPages) ? 'disabled' : ''; ?>">
                        <a class="page-link bg-dark border-secondary border-opacity-25 text-light" href="<?php echo APP_URL; ?>/admin/manage-bills.php?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>">Next</a>
                    </li>
                </ul>
            </nav>
        </div>
    <?php endif; ?>
</div>

<!-- Modal: Add Utility Bill -->
<div class="modal fade" id="addBillModal" tabindex="-1" aria-labelledby="addBillModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="<?php echo APP_URL; ?>/admin/manage-bills.php">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(generateCSRFToken(), ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="action" value="add_bill">

                <div class="modal-header">
                    <h5 class="modal-title fw-bold text-white d-flex align-items-center gap-2" id="addBillModalLabel">
                        <i class="bi bi-receipt text-primary"></i> Create Sample Utility Bill
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label text-light small fw-semibold">Utility Provider Board <span class="text-danger">*</span></label>
                        <select name="provider_id" class="form-select" required>
                            <option value="">Select Provider</option>
                            <?php foreach ($allProviders as $prov): ?>
                                <option value="<?php echo $prov['id']; ?>">
                                    <?php echo htmlspecialchars($prov['name'], ENT_QUOTES, 'UTF-8'); ?> (<?php echo strtoupper($prov['type']); ?> - <?php echo $prov['state']; ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label text-light small fw-semibold">Consumer Number <span class="text-danger">*</span></label>
                            <input type="text" name="consumer_number" class="form-control font-mono" placeholder="e.g. 1001234567" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label text-light small fw-semibold">Consumer Name <span class="text-danger">*</span></label>
                            <input type="text" name="consumer_name" class="form-control" placeholder="e.g. Rahul Sharma" required>
                        </div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label text-light small fw-semibold">Bill Amount (₹) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="1" name="bill_amount" class="form-control font-mono" placeholder="1250.00" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label text-light small fw-semibold">Status</label>
                            <select name="status" class="form-select">
                                <option value="unpaid">Unpaid</option>
                                <option value="paid">Paid</option>
                            </select>
                        </div>
                    </div>
                    <div class="row g-2 mb-2">
                        <div class="col-6">
                            <label class="form-label text-light small fw-semibold">Bill Date</label>
                            <input type="date" name="bill_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label text-light small fw-semibold">Due Date <span class="text-danger">*</span></label>
                            <input type="date" name="due_date" class="form-control" value="<?php echo date('Y-m-d', strtotime('+15 days')); ?>" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4" style="background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%); border: none;">
                        Save Bill
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Edit Utility Bill -->
<div class="modal fade" id="editBillModal" tabindex="-1" aria-labelledby="editBillModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="<?php echo APP_URL; ?>/admin/manage-bills.php">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(generateCSRFToken(), ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="action" value="edit_bill">
                <input type="hidden" name="bill_id" id="editBillId" value="0">

                <div class="modal-header">
                    <h5 class="modal-title fw-bold text-white d-flex align-items-center gap-2" id="editBillModalLabel">
                        <i class="bi bi-pencil-square text-info"></i> Edit Sample Bill
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label text-light small fw-semibold">Utility Provider Board <span class="text-danger">*</span></label>
                        <select name="provider_id" id="editBillProvider" class="form-select" required>
                            <?php foreach ($allProviders as $prov): ?>
                                <option value="<?php echo $prov['id']; ?>">
                                    <?php echo htmlspecialchars($prov['name'], ENT_QUOTES, 'UTF-8'); ?> (<?php echo strtoupper($prov['type']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label text-light small fw-semibold">Consumer Number <span class="text-danger">*</span></label>
                            <input type="text" name="consumer_number" id="editBillNumber" class="form-control font-mono" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label text-light small fw-semibold">Consumer Name <span class="text-danger">*</span></label>
                            <input type="text" name="consumer_name" id="editBillName" class="form-control" required>
                        </div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label text-light small fw-semibold">Bill Amount (₹) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="1" name="bill_amount" id="editBillAmount" class="form-control font-mono" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label text-light small fw-semibold">Status</label>
                            <select name="status" id="editBillStatus" class="form-select">
                                <option value="unpaid">Unpaid</option>
                                <option value="paid">Paid</option>
                            </select>
                        </div>
                    </div>
                    <div class="row g-2 mb-2">
                        <div class="col-6">
                            <label class="form-label text-light small fw-semibold">Bill Date</label>
                            <input type="date" name="bill_date" id="editBillDate" class="form-control" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label text-light small fw-semibold">Due Date <span class="text-danger">*</span></label>
                            <input type="date" name="due_date" id="editBillDueDate" class="form-control" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-info rounded-pill px-4 text-white">Update Bill</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const editModal = document.getElementById('editBillModal');
    if (editModal) {
        editModal.addEventListener('show.bs.modal', function (e) {
            const btn = e.relatedTarget;
            if (!btn) return;
            document.getElementById('editBillId').value = btn.getAttribute('data-id');
            document.getElementById('editBillProvider').value = btn.getAttribute('data-provider');
            document.getElementById('editBillNumber').value = btn.getAttribute('data-number');
            document.getElementById('editBillName').value = btn.getAttribute('data-name');
            document.getElementById('editBillAmount').value = btn.getAttribute('data-amount');
            document.getElementById('editBillDate').value = btn.getAttribute('data-billdate');
            document.getElementById('editBillDueDate').value = btn.getAttribute('data-duedate');
            document.getElementById('editBillStatus').value = btn.getAttribute('data-status');
        });
    }
});
</script>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
