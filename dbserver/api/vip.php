<?php
/**
 * VIP Program REST API Endpoint
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? 'tiers';
$userId = Database::getAuthUserId();
$input = Database::getJsonInput();

switch ($action) {
    case 'tiers':
        $tiers = Database::fetchAll("SELECT * FROM `vip_tiers` ORDER BY `level` ASC");
        Database::jsonResponse([
            'success' => true,
            'count' => count($tiers),
            'tiers' => $tiers
        ]);
        break;

    case 'history':
        $history = Database::fetchAll("SELECT * FROM `vip_claims` WHERE `user_id` = ? ORDER BY `timestamp` DESC LIMIT 50", [$userId]);
        Database::jsonResponse([
            'success' => true,
            'count' => count($history),
            'history' => $history
        ]);
        break;

    case 'claim':
        if ($method !== 'POST') Database::errorResponse('Method Not Allowed', 405);
        $type = trim($input['type'] ?? 'upgrade');
        $level = (int)($input['level'] ?? 1);

        $tier = Database::fetchOne("SELECT * FROM `vip_tiers` WHERE `level` = ?", [$level]);
        if (!$tier) Database::errorResponse('VIP স্তর পাওয়া যায়নি।', 404);

        $bonus = 0.00;
        $eventName = '';
        if ($type === 'upgrade') {
            $bonus = (float)$tier['upgrade_bonus'];
            $eventName = "ভিআইপি {$level} আপগ্রেড রিওয়ার্ড";
        } elseif ($type === 'weekly') {
            $bonus = (float)$tier['weekly_bonus'];
            $eventName = "ভিআইপি {$level} সাপ্তাহিক বোনাস";
        } elseif ($type === 'monthly') {
            $bonus = (float)$tier['monthly_bonus'];
            $eventName = "ভিআইপি {$level} মাসিক রিওয়ার্ড";
        } elseif ($type === 'birthday') {
            $bonus = (float)$tier['birthday_bonus'];
            $eventName = "ভিআইপি জন্মদিন স্পেশাল বোনাস";
        }

        if ($bonus <= 0) $bonus = 50.00;

        $user = Database::fetchOne("SELECT * FROM `users` WHERE `id` = ?", [$userId]);
        if (!$user) Database::errorResponse('User not found', 404);

        $claimId = 'vipc-' . uniqid();
        $now = date('Y-m-d H:i:s');
        Database::insert('vip_claims', [
            'id' => $claimId,
            'user_id' => $userId,
            'username' => $user['username'],
            'level' => $level,
            'type' => $type,
            'event_name' => $eventName,
            'bonus' => $bonus,
            'points' => (int)($bonus * 10),
            'ticket_name' => "VIP{$level} রিওয়ার্ড ভাউচার",
            'date' => date('Y-m-d'),
            'timestamp' => round(microtime(true) * 1000),
            'status' => 'claimed',
            'created_at' => $now
        ]);

        $newBal = (float)$user['balance'] + $bonus;
        Database::update('users', ['balance' => $newBal], "`id` = ?", [$userId]);

        // Record transaction
        Database::insert('transactions', [
            'id' => 'tx-' . uniqid(),
            'user_id' => $userId,
            'type' => 'bonus',
            'amount' => $bonus,
            'channel' => 'system',
            'trx_id' => 'VIP' . date('YmdHis') . rand(100, 999),
            'status' => 'success',
            'description' => $eventName,
            'created_at' => $now
        ]);

        Database::jsonResponse([
            'success' => true,
            'message' => "অভিনন্দন! {$eventName} (৳{$bonus}) সফলভাবে জমা হয়েছে!",
            'newBalance' => $newBal
        ]);
        break;

    default:
        Database::errorResponse("Unknown vip action: {$action}", 404);
        break;
}
