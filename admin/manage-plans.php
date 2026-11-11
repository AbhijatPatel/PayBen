<?php
// Admin manage-plans

$pageTitle = 'Manage Plans & Operators';
$activeTab = in_array(trim($_GET['tab'] ?? 'plans'), ['plans', 'operators'], true) ? trim($_GET['tab'] ?? 'plans') : 'plans';
$currentPage = ($activeTab === 'operators') ? 'operators' : 'plans';

require_once __DIR__ . '/includes/admin-header.php';

$pdo = getDBConnection();

// Handle All POST Actions (Add, Edit, Toggle Status, Delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submittedToken = $_POST['csrf_token'] ?? '';
    if (!validateCSRFToken($submittedToken)) {
        setFlashMessage('danger', 'Security validation failed (Invalid CSRF Token). Operation aborted.');
        header('Location: ' . APP_URL . '/admin/manage-plans.php?tab=' . $activeTab);
        exit;
    }

    $action = sanitize($_POST['action'] ?? '');

    // -------------------------------------------------------------
    // OPERATOR ACTIONS
    // -------------------------------------------------------------
    if ($action === 'add_operator') {
        $name = sanitize($_POST['name'] ?? '');
        $type = in_array($_POST['type'] ?? '', ['mobile', 'dth'], true) ? $_POST['type'] : 'mobile';
        $code = strtoupper(preg_replace('/[^A-Za-z0-9_]/', '', (string)($_POST['code'] ?? '')));
        $logo = sanitize($_POST['logo'] ?? '');
        $status = in_array($_POST['status'] ?? '', ['active', 'inactive'], true) ? $_POST['status'] : 'active';

        if (empty($name) || empty($code)) {
            setFlashMessage('danger', 'Operator Name and unique Code are required fields.');
        } else {
            try {
                // Verify unique operator code
                $chk = $pdo->prepare("SELECT id FROM operators WHERE code = ? LIMIT 1");
                $chk->execute([$code]);
                if ($chk->fetch()) {
                    setFlashMessage('danger', "Operator Code '{$code}' already exists. Please choose a unique code.");
                } else {
                    $ins = $pdo->prepare("INSERT INTO operators (name, type, code, logo, status) VALUES (?, ?, ?, ?, ?)");
                    $ins->execute([$name, $type, $code, $logo ?: null, $status]);
                    setFlashMessage('success', "Operator '{$name}' created successfully.");
                }
            } catch (PDOException $e) {
                error_log("Add operator error: " . $e->getMessage());
                setFlashMessage('danger', 'Database error occurred while creating operator.');
            }
        }
        header('Location: ' . APP_URL . '/admin/manage-plans.php?tab=operators');
        exit;
    }

    if ($action === 'edit_operator') {
        $id = (int)($_POST['operator_id'] ?? 0);
        $name = sanitize($_POST['name'] ?? '');
        $type = in_array($_POST['type'] ?? '', ['mobile', 'dth'], true) ? $_POST['type'] : 'mobile';
        $code = strtoupper(preg_replace('/[^A-Za-z0-9_]/', '', (string)($_POST['code'] ?? '')));
        $logo = sanitize($_POST['logo'] ?? '');
        $status = in_array($_POST['status'] ?? '', ['active', 'inactive'], true) ? $_POST['status'] : 'active';

        if ($id <= 0 || empty($name) || empty($code)) {
            setFlashMessage('danger', 'Valid Operator ID, Name, and Code are required.');
        } else {
            try {
                $chk = $pdo->prepare("SELECT id FROM operators WHERE code = ? AND id != ? LIMIT 1");
                $chk->execute([$code, $id]);
                if ($chk->fetch()) {
                    setFlashMessage('danger', "Operator Code '{$code}' is already used by another operator.");
                } else {
                    $upd = $pdo->prepare("UPDATE operators SET name = ?, type = ?, code = ?, logo = ?, status = ? WHERE id = ?");
                    $upd->execute([$name, $type, $code, $logo ?: null, $status, $id]);
                    setFlashMessage('success', "Operator '{$name}' updated successfully.");
                }
            } catch (PDOException $e) {
                error_log("Edit operator error: " . $e->getMessage());
                setFlashMessage('danger', 'Database error occurred while updating operator.');
            }
        }
        header('Location: ' . APP_URL . '/admin/manage-plans.php?tab=operators');
        exit;
    }

    if ($action === 'toggle_operator_status') {
        $id = (int)($_POST['operator_id'] ?? 0);
        $newStatus = in_array($_POST['new_status'] ?? '', ['active', 'inactive'], true) ? $_POST['new_status'] : 'active';

        if ($id > 0) {
            try {
                $stmt = $pdo->prepare("UPDATE operators SET status = ? WHERE id = ?");
                $stmt->execute([$newStatus, $id]);
                setFlashMessage('success', "Operator status updated to {$newStatus}.");
            } catch (PDOException $e) {
                error_log("Toggle operator status error: " . $e->getMessage());
                setFlashMessage('danger', 'Database error updating operator status.');
            }
        }
        header('Location: ' . APP_URL . '/admin/manage-plans.php?tab=operators');
        exit;
    }

    // -------------------------------------------------------------
    // PLAN ACTIONS
    // -------------------------------------------------------------
    if ($action === 'add_plan') {
        $operatorId = (int)($_POST['operator_id'] ?? 0);
        $planName = sanitize($_POST['plan_name'] ?? '');
        $amount = (float)($_POST['amount'] ?? 0.00);
        $validity = sanitize($_POST['validity'] ?? '');
        $dataLimit = sanitize($_POST['data_limit'] ?? 'N/A');
        $planType = in_array($_POST['plan_type'] ?? '', ['prepaid', 'postpaid'], true) ? $_POST['plan_type'] : 'prepaid';
        $description = sanitize($_POST['description'] ?? '');
        $status = in_array($_POST['status'] ?? '', ['active', 'inactive'], true) ? $_POST['status'] : 'active';

        if ($operatorId <= 0 || empty($planName) || $amount <= 0 || empty($validity)) {
            setFlashMessage('danger', 'Please provide Operator, Plan Name, positive Amount, and Validity.');
        } else {
            try {
                $ins = $pdo->prepare("
                    INSERT INTO recharge_plans (operator_id, plan_name, amount, validity, data_limit, plan_type, description, status) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $ins->execute([$operatorId, $planName, $amount, $validity, $dataLimit ?: null, $planType, $description, $status]);
                setFlashMessage('success', "Plan '{$planName}' (₹{$amount}) created successfully.");
            } catch (PDOException $e) {
                error_log("Add plan error: " . $e->getMessage());
                setFlashMessage('danger', 'Database error occurred while creating recharge plan.');
            }
        }
        header('Location: ' . APP_URL . '/admin/manage-plans.php?tab=plans');
        exit;
    }

    if ($action === 'edit_plan') {
        $id = (int)($_POST['plan_id'] ?? 0);
        $operatorId = (int)($_POST['operator_id'] ?? 0);
        $planName = sanitize($_POST['plan_name'] ?? '');
        $amount = (float)($_POST['amount'] ?? 0.00);
        $validity = sanitize($_POST['validity'] ?? '');
        $dataLimit = sanitize($_POST['data_limit'] ?? 'N/A');
        $planType = in_array($_POST['plan_type'] ?? '', ['prepaid', 'postpaid'], true) ? $_POST['plan_type'] : 'prepaid';
        $description = sanitize($_POST['description'] ?? '');
        $status = in_array($_POST['status'] ?? '', ['active', 'inactive'], true) ? $_POST['status'] : 'active';

        if ($id <= 0 || $operatorId <= 0 || empty($planName) || $amount <= 0 || empty($validity)) {
            setFlashMessage('danger', 'Valid Plan ID, Operator, Plan Name, Amount, and Validity are required.');
        } else {
            try {
                $upd = $pdo->prepare("
                    UPDATE recharge_plans 
                    SET operator_id = ?, plan_name = ?, amount = ?, validity = ?, data_limit = ?, plan_type = ?, description = ?, status = ?
                    WHERE id = ?
                ");
                $upd->execute([$operatorId, $planName, $amount, $validity, $dataLimit ?: null, $planType, $description, $status, $id]);
                setFlashMessage('success', "Plan '{$planName}' updated successfully.");
            } catch (PDOException $e) {
                error_log("Edit plan error: " . $e->getMessage());
                setFlashMessage('danger', 'Database error occurred while updating recharge plan.');
            }
        }
        header('Location: ' . APP_URL . '/admin/manage-plans.php?tab=plans');
        exit;
    }

    if ($action === 'toggle_plan_status') {
        $id = (int)($_POST['plan_id'] ?? 0);
        $newStatus = in_array($_POST['new_status'] ?? '', ['active', 'inactive'], true) ? $_POST['new_status'] : 'active';

        if ($id > 0) {
            try {
                $stmt = $pdo->prepare("UPDATE recharge_plans SET status = ? WHERE id = ?");
                $stmt->execute([$newStatus, $id]);
                setFlashMessage('success', "Plan status toggled to {$newStatus}.");
            } catch (PDOException $e) {
                error_log("Toggle plan status error: " . $e->getMessage());
                setFlashMessage('danger', 'Database error updating plan status.');
            }
        }
        header('Location: ' . APP_URL . '/admin/manage-plans.php?tab=plans');
        exit;
    }

    if ($action === 'delete_plan') {
        $id = (int)($_POST['plan_id'] ?? 0);
        if ($id > 0) {
            try {
                $del = $pdo->prepare("DELETE FROM recharge_plans WHERE id = ?");
                $del->execute([$id]);
                setFlashMessage('success', "Recharge plan #{$id} has been removed from catalog.");
            } catch (PDOException $e) {
                error_log("Delete plan error: " . $e->getMessage());
                setFlashMessage('danger', 'Cannot delete this plan as it is linked to historical transactions.');
            }
        }
        header('Location: ' . APP_URL . '/admin/manage-plans.php?tab=plans');
        exit;
    }
}

// Fetch Operators List (for both operators table and plan dropdowns)
$operatorsStmt = $pdo->query("SELECT * FROM operators ORDER BY type ASC, name ASC");
$allOperators = $operatorsStmt->fetchAll();

// Recharge Plans Filtering
$operatorFilter = (int)($_GET['operator_id'] ?? 0);
$planTypeFilter = trim($_GET['plan_type'] ?? 'all');

$planWhereClauses = [];
$planParams = [];

if ($operatorFilter > 0) {
    $planWhereClauses[] = "p.operator_id = ?";
    $planParams[] = $operatorFilter;
}

if ($planTypeFilter === 'prepaid' || $planTypeFilter === 'postpaid') {
    $planWhereClauses[] = "p.plan_type = ?";
    $planParams[] = $planTypeFilter;
}

$planWhereSql = !empty($planWhereClauses) ? 'WHERE ' . implode(' AND ', $planWhereClauses) : '';

$plansStmt = $pdo->prepare("
    SELECT p.*, o.name AS operator_name, o.type AS operator_type, o.code AS operator_code
    FROM recharge_plans p
    LEFT JOIN operators o ON p.operator_id = o.id
    {$planWhereSql}
    ORDER BY p.operator_id ASC, p.amount ASC
");
$plansStmt->execute($planParams);
$allPlans = $plansStmt->fetchAll();
?>

<!-- Page Header & Action Controls -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h2 class="fw-bold text-white mb-1 tracking-tight">Catalog & Plans Management</h2>
        <p class="text-secondary small mb-0">Configure telecom and DTH operators, manage prepaid and postpaid tariff plans.</p>
    </div>
    <div class="d-flex align-items-center gap-2">
        <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#addOperatorModal">
            <i class="bi bi-broadcast-pin me-1"></i> Add Operator
        </button>
        <button type="button" class="btn btn-sm btn-primary rounded-pill px-3" style="background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%); border: none;" data-bs-toggle="modal" data-bs-target="#addPlanModal">
            <i class="bi bi-plus-circle-fill me-1"></i> Add Recharge Plan
        </button>
    </div>
</div>

<!-- Section Navigation Tabs -->
<ul class="nav nav-pills mb-4 gap-2 border-bottom border-secondary border-opacity-10 pb-3">
    <li class="nav-item">
        <a class="nav-link rounded-pill px-4 fw-semibold <?php echo ($activeTab === 'plans') ? 'active bg-primary' : 'text-light bg-dark border border-secondary border-opacity-25'; ?>" 
           href="<?php echo APP_URL; ?>/admin/manage-plans.php?tab=plans">
            <i class="bi bi-journal-text me-1"></i> Recharge Plans (<?php echo count($allPlans); ?>)
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link rounded-pill px-4 fw-semibold <?php echo ($activeTab === 'operators') ? 'active bg-primary' : 'text-light bg-dark border border-secondary border-opacity-25'; ?>" 
           href="<?php echo APP_URL; ?>/admin/manage-plans.php?tab=operators">
            <i class="bi bi-broadcast-pin me-1"></i> Operators Directory (<?php echo count($allOperators); ?>)
        </a>
    </li>
</ul>

<?php if ($activeTab === 'operators'): ?>
    <!-- ============================================================= -->
    <!-- SECTION 1: OPERATORS MANAGEMENT -->
    <!-- ============================================================= -->
    <div class="admin-card overflow-hidden mb-4">
        <div class="p-3 px-4 border-bottom border-secondary border-opacity-10 d-flex justify-content-between align-items-center">
            <div>
                <h5 class="text-white fw-bold mb-0 d-flex align-items-center gap-2">
                    <i class="bi bi-broadcast text-primary"></i> Service Providers & Operators
                </h5>
                <div class="text-secondary small">Mobile telecom carriers and Direct-to-Home satellite providers</div>
            </div>
            <button type="button" class="btn btn-sm btn-primary rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#addOperatorModal">
                <i class="bi bi-plus-lg me-1"></i> New Operator
            </button>
        </div>

        <div class="table-responsive">
            <table class="table admin-table mb-0">
                <thead>
                    <tr>
                        <th style="width: 70px;">ID</th>
                        <th>Operator Name</th>
                        <th>Category Type</th>
                        <th>Unique Code</th>
                        <th>Catalog Status</th>
                        <th>Created At</th>
                        <th class="text-end" style="width: 190px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($allOperators)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-4 text-secondary">
                                <i class="bi bi-broadcast fs-2 d-block mb-1"></i>
                                No operators configured in the system.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($allOperators as $op): ?>
                            <tr>
                                <td>
                                    <span class="font-mono text-secondary">#<?php echo $op['id']; ?></span>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="admin-avatar" style="width: 32px; height: 32px; font-size: 0.85rem; background: rgba(79, 70, 229, 0.15); color: #818cf8;">
                                            <i class="bi <?php echo ($op['type'] === 'mobile') ? 'bi-phone' : 'bi-tv'; ?>"></i>
                                        </div>
                                        <span class="fw-bold text-white"><?php echo htmlspecialchars($op['name'], ENT_QUOTES, 'UTF-8'); ?></span>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($op['type'] === 'mobile'): ?>
                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle">
                                            <i class="bi bi-phone me-1"></i> Mobile
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-purple-subtle text-light border border-secondary-subtle" style="background: rgba(147, 51, 234, 0.2);">
                                            <i class="bi bi-tv me-1"></i> DTH
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-dark border border-secondary border-opacity-25 font-mono text-light">
                                        <?php echo htmlspecialchars($op['code'], ENT_QUOTES, 'UTF-8'); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($op['status'] === 'active'): ?>
                                        <span class="badge-status-active"><i class="bi bi-check-circle-fill me-1"></i> Active</span>
                                    <?php else: ?>
                                        <span class="badge-status-inactive"><i class="bi bi-slash-circle-fill me-1"></i> Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="text-secondary small font-mono"><?php echo date('d M Y', strtotime($op['created_at'])); ?></span>
                                </td>
                                <td class="text-end">
                                    <div class="d-flex justify-content-end align-items-center gap-2">
                                        <!-- Edit Operator Trigger -->
                                        <button type="button" 
                                                class="btn btn-sm btn-outline-info rounded-pill px-2 py-1 edit-operator-btn"
                                                data-bs-toggle="modal" 
                                                data-bs-target="#editOperatorModal"
                                                data-id="<?php echo $op['id']; ?>"
                                                data-name="<?php echo htmlspecialchars($op['name'], ENT_QUOTES, 'UTF-8'); ?>"
                                                data-type="<?php echo $op['type']; ?>"
                                                data-code="<?php echo htmlspecialchars($op['code'], ENT_QUOTES, 'UTF-8'); ?>"
                                                data-logo="<?php echo htmlspecialchars($op['logo'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                                data-status="<?php echo $op['status']; ?>"
                                                title="Edit operator metadata">
                                            <i class="bi bi-pencil-square me-1"></i> Edit
                                        </button>

                                        <!-- Toggle Status Form -->
                                        <form method="POST" action="<?php echo htmlspecialchars($_SERVER['REQUEST_URI'], ENT_QUOTES, 'UTF-8'); ?>" class="d-inline">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(generateCSRFToken(), ENT_QUOTES, 'UTF-8'); ?>">
                                            <input type="hidden" name="action" value="toggle_operator_status">
                                            <input type="hidden" name="operator_id" value="<?php echo $op['id']; ?>">
                                            <?php if ($op['status'] === 'active'): ?>
                                                <input type="hidden" name="new_status" value="inactive">
                                                <button type="submit" class="btn btn-sm btn-outline-warning rounded-pill px-2 py-1" title="Deactivate operator">
                                                    <i class="bi bi-pause-circle"></i> Disable
                                                </button>
                                            <?php else: ?>
                                                <input type="hidden" name="new_status" value="active">
                                                <button type="submit" class="btn btn-sm btn-outline-success rounded-pill px-2 py-1" title="Activate operator">
                                                    <i class="bi bi-play-circle"></i> Enable
                                                </button>
                                            <?php endif; ?>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

<?php else: ?>
    <!-- ============================================================= -->
    <!-- SECTION 2: RECHARGE PLANS MANAGEMENT -->
    <!-- ============================================================= -->
    <!-- Plans Filter Toolbar -->
    <div class="admin-card p-3 mb-4">
        <form method="GET" action="<?php echo APP_URL; ?>/admin/manage-plans.php" class="row g-2 align-items-center">
            <input type="hidden" name="tab" value="plans">
            
            <div class="col-md-5">
                <label class="form-label text-secondary small fw-semibold mb-1">Filter by Operator</label>
                <select name="operator_id" class="form-select">
                    <option value="0">All Operators & Carriers</option>
                    <?php foreach ($allOperators as $op): ?>
                        <option value="<?php echo $op['id']; ?>" <?php echo ($operatorFilter === (int)$op['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($op['name'], ENT_QUOTES, 'UTF-8'); ?> (<?php echo strtoupper($op['type']); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-3">
                <label class="form-label text-secondary small fw-semibold mb-1">Plan Type</label>
                <select name="plan_type" class="form-select">
                    <option value="all" <?php echo ($planTypeFilter === 'all') ? 'selected' : ''; ?>>All Plan Types</option>
                    <option value="prepaid" <?php echo ($planTypeFilter === 'prepaid') ? 'selected' : ''; ?>>Prepaid Only</option>
                    <option value="postpaid" <?php echo ($planTypeFilter === 'postpaid') ? 'selected' : ''; ?>>Postpaid Only</option>
                </select>
            </div>

            <div class="col-md-4 d-flex align-items-end gap-2">
                <button type="submit" class="btn btn-primary px-3 rounded-pill" style="background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%); border: none;">
                    <i class="bi bi-funnel-fill me-1"></i> Filter Plans
                </button>
                <?php if ($operatorFilter > 0 || $planTypeFilter !== 'all'): ?>
                    <a href="<?php echo APP_URL; ?>/admin/manage-plans.php?tab=plans" class="btn btn-outline-secondary px-3 rounded-pill">
                        <i class="bi bi-x-circle me-1"></i> Reset
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Plans Table Card -->
    <div class="admin-card overflow-hidden mb-4">
        <div class="p-3 px-4 border-bottom border-secondary border-opacity-10 d-flex justify-content-between align-items-center">
            <div>
                <h5 class="text-white fw-bold mb-0 d-flex align-items-center gap-2">
                    <i class="bi bi-journal-bookmark text-primary"></i> Tariff Catalog & Recharge Packs
                </h5>
                <div class="text-secondary small">Tariffs displayed to customers during mobile and DTH recharge</div>
            </div>
            <button type="button" class="btn btn-sm btn-primary rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#addPlanModal">
                <i class="bi bi-plus-lg me-1"></i> Add New Plan
            </button>
        </div>

        <div class="table-responsive">
            <table class="table admin-table mb-0">
                <thead>
                    <tr>
                        <th style="width: 60px;">ID</th>
                        <th>Operator</th>
                        <th>Plan Name</th>
                        <th>Price Amount</th>
                        <th>Validity</th>
                        <th>Data Limit</th>
                        <th>Plan Type</th>
                        <th>Status</th>
                        <th class="text-end" style="width: 200px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($allPlans)): ?>
                        <tr>
                            <td colspan="9" class="text-center py-5 text-secondary">
                                <i class="bi bi-journal-x fs-1 d-block mb-2 text-muted"></i>
                                <div class="fw-semibold">No recharge plans found.</div>
                                <div class="small text-muted mt-1">Use "Add New Plan" to publish a tariff.</div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($allPlans as $plan): ?>
                            <tr>
                                <td>
                                    <span class="font-mono text-secondary">#<?php echo $plan['id']; ?></span>
                                </td>
                                <td>
                                    <div class="fw-semibold text-white small"><?php echo htmlspecialchars($plan['operator_name'] ?? '—', ENT_QUOTES, 'UTF-8'); ?></div>
                                    <span class="badge bg-dark border border-secondary border-opacity-25" style="font-size: 0.65rem;">
                                        <?php echo strtoupper($plan['operator_code'] ?? ''); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="fw-bold text-white"><?php echo htmlspecialchars($plan['plan_name'], ENT_QUOTES, 'UTF-8'); ?></div>
                                    <div class="text-muted small text-truncate" style="max-width: 220px;" title="<?php echo htmlspecialchars($plan['description'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                        <?php echo htmlspecialchars($plan['description'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
                                    </div>
                                </td>
                                <td>
                                    <span class="fw-bold text-success font-mono fs-6"><?php echo formatCurrency($plan['amount']); ?></span>
                                </td>
                                <td>
                                    <span class="badge bg-secondary bg-opacity-25 text-light font-mono">
                                        <i class="bi bi-calendar-event me-1"></i><?php echo htmlspecialchars($plan['validity'], ENT_QUOTES, 'UTF-8'); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="text-light small font-mono"><?php echo htmlspecialchars($plan['data_limit'] ?? 'N/A', ENT_QUOTES, 'UTF-8'); ?></span>
                                </td>
                                <td>
                                    <span class="badge bg-dark border border-secondary border-opacity-25 text-capitalize text-light">
                                        <?php echo htmlspecialchars($plan['plan_type'], ENT_QUOTES, 'UTF-8'); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($plan['status'] === 'active'): ?>
                                        <span class="badge-status-active"><i class="bi bi-check-circle-fill me-1"></i> Active</span>
                                    <?php else: ?>
                                        <span class="badge-status-inactive"><i class="bi bi-slash-circle-fill me-1"></i> Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <div class="d-flex justify-content-end align-items-center gap-1">
                                        <!-- Edit Plan Button -->
                                        <button type="button" 
                                                class="btn btn-sm btn-outline-info rounded-pill px-2 py-1 edit-plan-btn"
                                                data-bs-toggle="modal" 
                                                data-bs-target="#editPlanModal"
                                                data-id="<?php echo $plan['id']; ?>"
                                                data-operator="<?php echo $plan['operator_id']; ?>"
                                                data-name="<?php echo htmlspecialchars($plan['plan_name'], ENT_QUOTES, 'UTF-8'); ?>"
                                                data-amount="<?php echo $plan['amount']; ?>"
                                                data-validity="<?php echo htmlspecialchars($plan['validity'], ENT_QUOTES, 'UTF-8'); ?>"
                                                data-datalimit="<?php echo htmlspecialchars($plan['data_limit'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                                data-plantype="<?php echo $plan['plan_type']; ?>"
                                                data-desc="<?php echo htmlspecialchars($plan['description'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                                data-status="<?php echo $plan['status']; ?>"
                                                title="Edit plan details">
                                            <i class="bi bi-pencil-square"></i>
                                        </button>

                                        <!-- Toggle Status Form -->
                                        <form method="POST" action="<?php echo htmlspecialchars($_SERVER['REQUEST_URI'], ENT_QUOTES, 'UTF-8'); ?>" class="d-inline">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(generateCSRFToken(), ENT_QUOTES, 'UTF-8'); ?>">
                                            <input type="hidden" name="action" value="toggle_plan_status">
                                            <input type="hidden" name="plan_id" value="<?php echo $plan['id']; ?>">
                                            <?php if ($plan['status'] === 'active'): ?>
                                                <input type="hidden" name="new_status" value="inactive">
                                                <button type="submit" class="btn btn-sm btn-outline-warning rounded-pill px-2 py-1" title="Hide plan from catalog">
                                                    <i class="bi bi-pause-circle"></i>
                                                </button>
                                            <?php else: ?>
                                                <input type="hidden" name="new_status" value="active">
                                                <button type="submit" class="btn btn-sm btn-outline-success rounded-pill px-2 py-1" title="Publish plan">
                                                    <i class="bi bi-play-circle"></i>
                                                </button>
                                            <?php endif; ?>
                                        </form>

                                        <!-- Delete Plan Form -->
                                        <form method="POST" action="<?php echo htmlspecialchars($_SERVER['REQUEST_URI'], ENT_QUOTES, 'UTF-8'); ?>" class="d-inline" onsubmit="return confirm('Delete plan \'<?php echo addslashes($plan['plan_name']); ?>\' permanently?');">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(generateCSRFToken(), ENT_QUOTES, 'UTF-8'); ?>">
                                            <input type="hidden" name="action" value="delete_plan">
                                            <input type="hidden" name="plan_id" value="<?php echo $plan['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill px-2 py-1" title="Delete plan">
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
    </div>
<?php endif; ?>

<!-- ===================================================================== -->
<!-- MODALS: OPERATORS & PLANS ADD/EDIT -->
<!-- ===================================================================== -->

<!-- Modal: Add New Operator -->
<div class="modal fade" id="addOperatorModal" tabindex="-1" aria-labelledby="addOperatorModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="<?php echo APP_URL; ?>/admin/manage-plans.php">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(generateCSRFToken(), ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="action" value="add_operator">

                <div class="modal-header">
                    <h5 class="modal-title fw-bold text-white d-flex align-items-center gap-2" id="addOperatorModalLabel">
                        <i class="bi bi-broadcast-pin text-primary"></i> Add Service Operator
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label text-light small fw-semibold">Operator Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" placeholder="e.g. Reliance Jio or Tata Play" required>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label text-light small fw-semibold">Type <span class="text-danger">*</span></label>
                            <select name="type" class="form-select" required>
                                <option value="mobile">Mobile Telecom</option>
                                <option value="dth">DTH Satellite</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label text-light small fw-semibold">Unique Code <span class="text-danger">*</span></label>
                            <input type="text" name="code" class="form-control font-mono text-uppercase" placeholder="e.g. JIO, AIRTEL" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-light small fw-semibold">Logo Asset Path</label>
                        <input type="text" name="logo" class="form-control" placeholder="assets/images/operators/brand.png">
                    </div>
                    <div class="mb-2">
                        <label class="form-label text-light small fw-semibold">Initial Status</label>
                        <select name="status" class="form-select">
                            <option value="active">Active (Visible)</option>
                            <option value="inactive">Inactive (Hidden)</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4" style="background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%); border: none;">
                        Save Operator
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Edit Operator -->
<div class="modal fade" id="editOperatorModal" tabindex="-1" aria-labelledby="editOperatorModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="<?php echo APP_URL; ?>/admin/manage-plans.php">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(generateCSRFToken(), ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="action" value="edit_operator">
                <input type="hidden" name="operator_id" id="editOpId" value="0">

                <div class="modal-header">
                    <h5 class="modal-title fw-bold text-white d-flex align-items-center gap-2" id="editOperatorModalLabel">
                        <i class="bi bi-pencil-square text-info"></i> Edit Service Operator
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label text-light small fw-semibold">Operator Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="editOpName" class="form-control" required>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label text-light small fw-semibold">Type <span class="text-danger">*</span></label>
                            <select name="type" id="editOpType" class="form-select" required>
                                <option value="mobile">Mobile Telecom</option>
                                <option value="dth">DTH Satellite</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label text-light small fw-semibold">Unique Code <span class="text-danger">*</span></label>
                            <input type="text" name="code" id="editOpCode" class="form-control font-mono text-uppercase" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-light small fw-semibold">Logo Asset Path</label>
                        <input type="text" name="logo" id="editOpLogo" class="form-control">
                    </div>
                    <div class="mb-2">
                        <label class="form-label text-light small fw-semibold">Status</label>
                        <select name="status" id="editOpStatus" class="form-select">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-info rounded-pill px-4 text-white">Update Operator</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Add New Recharge Plan -->
<div class="modal fade" id="addPlanModal" tabindex="-1" aria-labelledby="addPlanModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <form method="POST" action="<?php echo APP_URL; ?>/admin/manage-plans.php">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(generateCSRFToken(), ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="action" value="add_plan">

                <div class="modal-header">
                    <h5 class="modal-title fw-bold text-white d-flex align-items-center gap-2" id="addPlanModalLabel">
                        <i class="bi bi-journal-plus text-primary"></i> Create Tariff Recharge Plan
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label text-light small fw-semibold">Target Operator <span class="text-danger">*</span></label>
                            <select name="operator_id" class="form-select" required>
                                <option value="">Select Operator</option>
                                <?php foreach ($allOperators as $op): ?>
                                    <option value="<?php echo $op['id']; ?>">
                                        <?php echo htmlspecialchars($op['name'], ENT_QUOTES, 'UTF-8'); ?> (<?php echo strtoupper($op['type']); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-light small fw-semibold">Plan Name <span class="text-danger">*</span></label>
                            <input type="text" name="plan_name" class="form-control" placeholder="e.g. Jio Bestseller Daily" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-light small fw-semibold">Amount (INR) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-dark border-secondary border-opacity-50 text-secondary">₹</span>
                                <input type="number" step="0.01" min="1" name="amount" class="form-control font-mono" placeholder="249.00" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-light small fw-semibold">Validity <span class="text-danger">*</span></label>
                            <input type="text" name="validity" class="form-control" placeholder="e.g. 28 Days or 1 Month" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-light small fw-semibold">Data Limit</label>
                            <input type="text" name="data_limit" class="form-control" placeholder="e.g. 1.5 GB/Day or N/A">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-light small fw-semibold">Plan Type</label>
                            <select name="plan_type" class="form-select">
                                <option value="prepaid">Prepaid</option>
                                <option value="postpaid">Postpaid</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-light small fw-semibold">Status</label>
                            <select name="status" class="form-select">
                                <option value="active">Active (Visible to users)</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label text-light small fw-semibold">Plan Benefits & Description</label>
                            <textarea name="description" class="form-control" rows="3" placeholder="Unlimited Voice Calls, 100 SMS/Day, High-speed True 5G Unlimited trial..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4" style="background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%); border: none;">
                        Save Plan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Edit Recharge Plan -->
<div class="modal fade" id="editPlanModal" tabindex="-1" aria-labelledby="editPlanModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <form method="POST" action="<?php echo APP_URL; ?>/admin/manage-plans.php">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(generateCSRFToken(), ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="action" value="edit_plan">
                <input type="hidden" name="plan_id" id="editPlanId" value="0">

                <div class="modal-header">
                    <h5 class="modal-title fw-bold text-white d-flex align-items-center gap-2" id="editPlanModalLabel">
                        <i class="bi bi-pencil-square text-info"></i> Edit Recharge Plan
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label text-light small fw-semibold">Target Operator <span class="text-danger">*</span></label>
                            <select name="operator_id" id="editPlanOperator" class="form-select" required>
                                <?php foreach ($allOperators as $op): ?>
                                    <option value="<?php echo $op['id']; ?>">
                                        <?php echo htmlspecialchars($op['name'], ENT_QUOTES, 'UTF-8'); ?> (<?php echo strtoupper($op['type']); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-light small fw-semibold">Plan Name <span class="text-danger">*</span></label>
                            <input type="text" name="plan_name" id="editPlanName" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-light small fw-semibold">Amount (INR) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-dark border-secondary border-opacity-50 text-secondary">₹</span>
                                <input type="number" step="0.01" min="1" name="amount" id="editPlanAmount" class="form-control font-mono" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-light small fw-semibold">Validity <span class="text-danger">*</span></label>
                            <input type="text" name="validity" id="editPlanValidity" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-light small fw-semibold">Data Limit</label>
                            <input type="text" name="data_limit" id="editPlanDataLimit" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-light small fw-semibold">Plan Type</label>
                            <select name="plan_type" id="editPlanType" class="form-select">
                                <option value="prepaid">Prepaid</option>
                                <option value="postpaid">Postpaid</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-light small fw-semibold">Status</label>
                            <select name="status" id="editPlanStatus" class="form-select">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label text-light small fw-semibold">Plan Benefits & Description</label>
                            <textarea name="description" id="editPlanDescription" class="form-control" rows="3"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-info rounded-pill px-4 text-white">Update Plan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Populate Edit Operator Modal
    const editOpModal = document.getElementById('editOperatorModal');
    if (editOpModal) {
        editOpModal.addEventListener('show.bs.modal', function (e) {
            const btn = e.relatedTarget;
            if (!btn) return;
            document.getElementById('editOpId').value = btn.getAttribute('data-id');
            document.getElementById('editOpName').value = btn.getAttribute('data-name');
            document.getElementById('editOpType').value = btn.getAttribute('data-type');
            document.getElementById('editOpCode').value = btn.getAttribute('data-code');
            document.getElementById('editOpLogo').value = btn.getAttribute('data-logo');
            document.getElementById('editOpStatus').value = btn.getAttribute('data-status');
        });
    }

    // Populate Edit Plan Modal
    const editPlanModal = document.getElementById('editPlanModal');
    if (editPlanModal) {
        editPlanModal.addEventListener('show.bs.modal', function (e) {
            const btn = e.relatedTarget;
            if (!btn) return;
            document.getElementById('editPlanId').value = btn.getAttribute('data-id');
            document.getElementById('editPlanOperator').value = btn.getAttribute('data-operator');
            document.getElementById('editPlanName').value = btn.getAttribute('data-name');
            document.getElementById('editPlanAmount').value = btn.getAttribute('data-amount');
            document.getElementById('editPlanValidity').value = btn.getAttribute('data-validity');
            document.getElementById('editPlanDataLimit').value = btn.getAttribute('data-datalimit');
            document.getElementById('editPlanType').value = btn.getAttribute('data-plantype');
            document.getElementById('editPlanDescription').value = btn.getAttribute('data-desc');
            document.getElementById('editPlanStatus').value = btn.getAttribute('data-status');
        });
    }
});
</script>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
