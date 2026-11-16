<?php

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/middleware/auth.php';

class BookingController {

    /**
     * Create a new Sandbox Booking with Atomic Wallet Ledger Deduction
     */
    public static function create() {
        $user = requireAuth();
        $userId = (int)$user['id'];
        $body = getJsonBody();

        $category = trim($body['category'] ?? '');
        $providerName = trim($body['provider_name'] ?? 'Sandbox Travel Partner');
        $title = trim($body['title'] ?? 'Ticket Reservation');
        $sourceCity = trim($body['source_city'] ?? 'Delhi');
        $destinationCity = trim($body['destination_city'] ?? 'Varanasi');
        $travelDate = trim($body['travel_date'] ?? date('Y-m-d', strtotime('+1 day')));
        $classOrRoom = trim($body['class_or_room_type'] ?? 'Standard');
        $amount = (float)($body['amount'] ?? 0);
        $passengers = $body['passengers'] ?? [
            ['name' => $user['full_name'], 'age' => 28, 'gender' => 'MALE', 'seat_number' => '12A']
        ];

        if (!in_array($category, ['train', 'bus', 'flight', 'hotel'])) {
            jsonResponse(['success' => false, 'message' => 'Invalid booking category.'], 400);
        }

        if ($amount <= 0) {
            jsonResponse(['success' => false, 'message' => 'Invalid booking fare.'], 400);
        }

        $amountPaise = (int)round($amount * 100);
        $pdo = getDBConnection();

        try {
            $pdo->beginTransaction();

            // 1. Fetch & lock user wallet with strict row-level lock
            $stmt = $pdo->prepare("SELECT id, balance, balance_paise FROM wallets WHERE user_id = ? FOR UPDATE");
            $stmt->execute([$userId]);
            $wallet = $stmt->fetch();

            if (!$wallet) {
                $pdo->rollBack();
                jsonResponse(['success' => false, 'message' => 'Wallet not initialized.'], 404);
            }

            $currentPaise = (int)($wallet['balance_paise'] ?? round($wallet['balance'] * 100));
            if ($currentPaise < $amountPaise) {
                $pdo->rollBack();
                jsonResponse([
                    'success' => false,
                    'message' => 'Insufficient wallet balance. Please add cash to your PayBen wallet.',
                    'current_balance' => (float)($currentPaise / 100),
                    'required_amount' => $amount
                ], 400);
            }

            $newPaise = $currentPaise - $amountPaise;
            $newDecimal = (float)($newPaise / 100);

            // 2. Deduct wallet balance
            $upd = $pdo->prepare("UPDATE wallets SET balance_paise = ?, balance = ? WHERE id = ?");
            $upd->execute([$newPaise, $newDecimal, $wallet['id']]);

            // 3. Generate reference IDs
            $bookingRef = 'PB-TRV-' . strtoupper(bin2hex(random_bytes(4)));
            $pnr = ($category === 'train' ? 'PNR' : ($category === 'flight' ? 'FLT' : 'TKT')) . mt_rand(10000000, 99999999);
            $txId = 'TXN' . date('YmdHis') . mt_rand(1000, 9999);

            // 4. Immutable Double-Entry Ledger Entry
            $led = $pdo->prepare("
                INSERT INTO wallet_ledger 
                (transaction_id, wallet_id, user_id, entry_type, category, amount_paise, balance_after_paise, description) 
                VALUES (?, ?, ?, 'DEBIT', 'TRAVEL_BOOKING', ?, ?, ?)
            ");
            $led->execute([
                $bookingRef,
                $wallet['id'],
                $userId,
                $amountPaise,
                $newPaise,
                "Travel Booking ({$category}): {$title} [Ref: {$bookingRef}]"
            ]);

            // 5. Insert into transactions feed
            $txStmt = $pdo->prepare("
                INSERT INTO transactions 
                (user_id, transaction_id, type, mobile_or_consumer_number, operator_or_provider, amount, status, payment_method, description) 
                VALUES (?, ?, 'mobile_recharge', ?, ?, ?, 'SUCCESS', 'PayBen Wallet', ?)
            ");
            $txStmt->execute([
                $userId,
                $txId,
                $pnr,
                $providerName,
                $amount,
                "{$category} Ticket: {$title} ({$sourceCity} → {$destinationCity})"
            ]);

            // 6. Insert into bookings table
            $bkg = $pdo->prepare("
                INSERT INTO bookings 
                (user_id, booking_reference, category, provider_name, title, source_city, destination_city, travel_date, passengers_count, class_or_room_type, amount, amount_paise, status, is_sandbox, pnr_or_ticket_number, details_json) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'CONFIRMED', 1, ?, ?)
            ");
            $bkg->execute([
                $userId,
                $bookingRef,
                $category,
                $providerName,
                $title,
                $sourceCity,
                $destinationCity,
                $travelDate,
                count($passengers),
                $classOrRoom,
                $amount,
                $amountPaise,
                $pnr,
                json_encode($body)
            ]);
            $bookingId = $pdo->lastInsertId();

            // 7. Insert passengers
            if (!empty($passengers) && is_array($passengers)) {
                $pStmt = $pdo->prepare("
                    INSERT INTO booking_passengers 
                    (booking_id, name, age, gender, berth_or_seat_preference, seat_number) 
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                foreach ($passengers as $p) {
                    $pStmt->execute([
                        $bookingId,
                        trim($p['name'] ?? 'Passenger'),
                        (int)($p['age'] ?? 25),
                        strtoupper($p['gender'] ?? 'MALE'),
                        $p['berth_or_seat_preference'] ?? null,
                        $p['seat_number'] ?? ('S' . mt_rand(1, 60))
                    ]);
                }
            }

            $pdo->commit();

            jsonResponse([
                'success' => true,
                'message' => 'Travel reservation confirmed in Sandbox mode.',
                'data' => [
                    'booking_reference' => $bookingRef,
                    'pnr' => $pnr,
                    'category' => $category,
                    'title' => $title,
                    'source_city' => $sourceCity,
                    'destination_city' => $destinationCity,
                    'travel_date' => $travelDate,
                    'amount' => $amount,
                    'status' => 'CONFIRMED',
                    'is_sandbox' => true,
                    'new_wallet_balance' => $newDecimal
                ]
            ], 201);

        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            jsonResponse([
                'success' => false,
                'message' => 'Booking failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * List user bookings
     */
    public static function listUserBookings() {
        $user = requireAuth();
        $userId = (int)$user['id'];
        $category = trim($_GET['category'] ?? '');

        $pdo = getDBConnection();
        $sql = "SELECT * FROM bookings WHERE user_id = ?";
        $params = [$userId];

        if (!empty($category) && $category !== 'all') {
            $sql .= " AND category = ?";
            $params[] = $category;
        }

        $sql .= " ORDER BY created_at DESC LIMIT 50";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $bookings = $stmt->fetchAll();

        // Attach passenger lists
        foreach ($bookings as &$b) {
            $pStmt = $pdo->prepare("SELECT * FROM booking_passengers WHERE booking_id = ?");
            $pStmt->execute([$b['id']]);
            $b['passengers'] = $pStmt->fetchAll();
            $b['details'] = json_decode($b['details_json'] ?? '{}', true);
        }

        jsonResponse([
            'success' => true,
            'count' => count($bookings),
            'bookings' => $bookings
        ]);
    }

    /**
     * Booking Details
     */
    public static function details() {
        $user = requireAuth();
        $userId = (int)$user['id'];
        $ref = trim($_GET['ref'] ?? '');

        if (empty($ref)) {
            jsonResponse(['success' => false, 'message' => 'Booking reference required.'], 400);
        }

        $pdo = getDBConnection();
        $stmt = $pdo->prepare("SELECT * FROM bookings WHERE user_id = ? AND booking_reference = ? LIMIT 1");
        $stmt->execute([$userId, $ref]);
        $booking = $stmt->fetch();

        if (!$booking) {
            jsonResponse(['success' => false, 'message' => 'Booking not found.'], 404);
        }

        $pStmt = $pdo->prepare("SELECT * FROM booking_passengers WHERE booking_id = ?");
        $pStmt->execute([$booking['id']]);
        $booking['passengers'] = $pStmt->fetchAll();
        $booking['details'] = json_decode($booking['details_json'] ?? '{}', true);

        jsonResponse(['success' => true, 'data' => $booking]);
    }

    /**
     * Cancel Booking with Atomic Ledger Refund
     */
    public static function cancel() {
        $user = requireAuth();
        $userId = (int)$user['id'];
        $body = getJsonBody();
        $ref = trim($body['booking_reference'] ?? '');

        if (empty($ref)) {
            jsonResponse(['success' => false, 'message' => 'Booking reference required.'], 400);
        }

        $pdo = getDBConnection();

        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("SELECT * FROM bookings WHERE user_id = ? AND booking_reference = ? FOR UPDATE");
            $stmt->execute([$userId, $ref]);
            $booking = $stmt->fetch();

            if (!$booking) {
                $pdo->rollBack();
                jsonResponse(['success' => false, 'message' => 'Booking not found.'], 404);
            }

            if ($booking['status'] === 'CANCELLED') {
                $pdo->rollBack();
                jsonResponse(['success' => false, 'message' => 'Booking is already cancelled.'], 400);
            }

            // Lock user wallet
            $wStmt = $pdo->prepare("SELECT id, balance, balance_paise FROM wallets WHERE user_id = ? FOR UPDATE");
            $wStmt->execute([$userId]);
            $wallet = $wStmt->fetch();

            $refundPaise = (int)$booking['amount_paise'];
            $currentPaise = (int)($wallet['balance_paise'] ?? round($wallet['balance'] * 100));
            $newPaise = $currentPaise + $refundPaise;
            $newDecimal = (float)($newPaise / 100);

            // Credit wallet
            $upd = $pdo->prepare("UPDATE wallets SET balance_paise = ?, balance = ? WHERE id = ?");
            $upd->execute([$newPaise, $newDecimal, $wallet['id']]);

            // Update booking status
            $bUpd = $pdo->prepare("UPDATE bookings SET status = 'CANCELLED' WHERE id = ?");
            $bUpd->execute([$booking['id']]);

            // Compensating reversal ledger entry
            $led = $pdo->prepare("
                INSERT INTO wallet_ledger 
                (transaction_id, wallet_id, user_id, entry_type, category, amount_paise, balance_after_paise, description) 
                VALUES (?, ?, ?, 'CREDIT', 'BOOKING_REFUND', ?, ?, ?)
            ");
            $led->execute([
                'REF-' . $ref,
                $wallet['id'],
                $userId,
                $refundPaise,
                $newPaise,
                "Booking Refund ({$booking['category']}): {$booking['title']} [Ref: {$ref}]"
            ]);

            $pdo->commit();

            jsonResponse([
                'success' => true,
                'message' => 'Reservation cancelled. Refund has been credited to your PayBen wallet.',
                'refund_amount' => (float)$booking['amount'],
                'new_balance' => $newDecimal
            ]);

        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            jsonResponse(['success' => false, 'message' => 'Cancellation failed: ' . $e->getMessage()], 500);
        }
    }
}
