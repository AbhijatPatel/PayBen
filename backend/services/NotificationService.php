<?php

require_once dirname(__DIR__) . '/config/database.php';

class NotificationService {
    public static function create(int $userId, string $title, string $message, string $type = 'info', ?array $metadata = null): int {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("
            INSERT INTO notifications (user_id, title, message, type, is_read, metadata, created_at)
            VALUES (?, ?, ?, ?, 0, ?, NOW())
        ");
        $jsonMeta = $metadata ? json_encode($metadata) : null;
        $stmt->execute([$userId, $title, $message, $type, $jsonMeta]);
        return (int)$pdo->lastInsertId();
    }

    public static function getList(int $userId, int $limit = 20): array {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("
            SELECT id, title, message, type, is_read, metadata, created_at
            FROM notifications
            WHERE user_id = ?
            ORDER BY id DESC
            LIMIT ?
        ");
        $stmt->bindValue(1, $userId, PDO::PARAM_INT);
        $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll();

        $unreadCountStmt = $pdo->prepare("SELECT COUNT(*) AS c FROM notifications WHERE user_id = ? AND is_read = 0");
        $unreadCountStmt->execute([$userId]);
        $unreadCount = (int)($unreadCountStmt->fetch()['c'] ?? 0);

        return [
            'unread_count' => $unreadCount,
            'notifications' => array_map(function($n) {
                return [
                    'id' => (int)$n['id'],
                    'title' => $n['title'],
                    'message' => $n['message'],
                    'type' => $n['type'],
                    'is_read' => (bool)$n['is_read'],
                    'metadata' => $n['metadata'] ? json_decode($n['metadata'], true) : null,
                    'created_at' => $n['created_at']
                ];
            }, $rows)
        ];
    }

    public static function markAsRead(int $userId, ?int $notificationId = null): bool {
        $pdo = getDBConnection();
        if ($notificationId) {
            $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
            $stmt->execute([$notificationId, $userId]);
        } else {
            $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
            $stmt->execute([$userId]);
        }
        return true;
    }
}
