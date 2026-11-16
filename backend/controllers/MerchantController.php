<?php

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/middleware/auth.php';

class MerchantController {

    /**
     * List Merchants by Category
     */
    public static function listMerchants() {
        $category = trim($_GET['category'] ?? '');
        $all = isset($_GET['all']) && $_GET['all'] === 'true';

        $pdo = getDBConnection();
        $sql = "SELECT * FROM merchants";
        $params = [];
        $where = [];

        if (!empty($category) && $category !== 'all') {
            $where[] = "category_slug = ?";
            $params[] = $category;
        }

        // Ordinary users only see enabled merchants
        if (!$all) {
            $where[] = "is_enabled = 1";
        }

        if (!empty($where)) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }

        $sql .= " ORDER BY display_order ASC, id ASC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $merchants = $stmt->fetchAll();

        // Fetch categories list
        $catStmt = $pdo->query("SELECT * FROM service_categories WHERE is_active = 1 ORDER BY display_order ASC");
        $categories = $catStmt->fetchAll();

        jsonResponse([
            'success' => true,
            'count' => count($merchants),
            'categories' => $categories,
            'merchants' => $merchants
        ]);
    }

    /**
     * Log and authorize an outbound merchant redirect
     */
    public static function redirect() {
        $slug = trim($_GET['slug'] ?? '');
        if (empty($slug)) {
            jsonResponse(['success' => false, 'message' => 'Merchant slug required.'], 400);
        }

        $pdo = getDBConnection();
        $stmt = $pdo->prepare("SELECT * FROM merchants WHERE slug = ? LIMIT 1");
        $stmt->execute([$slug]);
        $merchant = $stmt->fetch();

        if (!$merchant) {
            jsonResponse(['success' => false, 'message' => 'Merchant not found.'], 404);
        }

        if (!$merchant['is_enabled']) {
            jsonResponse([
                'success' => false,
                'message' => "{$merchant['name']} is currently inactive or under maintenance. External access is disabled."
            ], 403);
        }

        jsonResponse([
            'success' => true,
            'name' => $merchant['name'],
            'official_url' => $merchant['official_url'],
            'integration_type' => $merchant['integration_type'],
            'message' => 'Redirecting to official verified partner platform.'
        ]);
    }

    /**
     * Admin: Toggle merchant status
     */
    public static function toggleStatus() {
        $user = requireAdmin();
        $body = getJsonBody();
        $slug = trim($body['slug'] ?? '');
        $enabled = (int)($body['is_enabled'] ?? 1);

        if (empty($slug)) {
            jsonResponse(['success' => false, 'message' => 'Merchant slug required.'], 400);
        }

        $pdo = getDBConnection();
        $stmt = $pdo->prepare("UPDATE merchants SET is_enabled = ? WHERE slug = ?");
        $stmt->execute([$enabled, $slug]);

        jsonResponse([
            'success' => true,
            'message' => "Merchant {$slug} status updated to " . ($enabled ? 'enabled' : 'disabled')
        ]);
    }
}
