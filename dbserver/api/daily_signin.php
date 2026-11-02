<?php
/**
 * Daily Sign-In REST API Endpoint
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? 'status';
$userId = Database::getAuthUserId();
$input = Database::getJsonInput();
$today = date('Y-m-d');

switch ($action) {
    case 'status':
        $lastRecord = Database::fetchOne("SELECT * FROM `daily_signin_records` WHERE `user_id` = ? ORDER BY `date` DESC LIMIT 1", [$userId]);
        $hasSignedToday = ($lastRecord && $lastRecord['date'] === $today);
        $currentStreak = $lastRecord ? (int)$lastRecord['streak'] : 0;
        $todayDayNumber = ($currentStreak % 30) + 1;
        $todayBonus = 1.00 + ($todayDayNumber * 1.5);

        $history = Database::fetchAll("SELECT * FROM `daily_signin_records` WHERE `user_id` = ? ORDER BY `date` DESC LIMIT 30", [$userId]);
        $totalClaimed = 0;
        foreach ($history as $h) {
            $totalClaimed += (float)$h['bonus'];
        }

        Database::jsonResponse([
            'success' => true,
            'currentStreak' => $currentStreak,
            'totalClaimedBonus' => round($totalClaimed, 2),
            'hasSignedToday' => $hasSignedToday,
            'todayDayNumber' => $todayDayNumber,
            'todayBonus' => round($todayBonus, 2),
            'lastSignDate' => $lastRecord ? $lastRecord['date'] : null,
            'history' => $history,
            'config' => [
                'enabled' => true,
                'cycleDays' => 30
            ]
        ]);
        break;

    case 'claim':
        if ($method !== 'POST') Database::errorResponse('Method Not Allowed', 405);

        $already = Database::fetchOne("SELECT `id` FROM `daily_signin_records` WHERE `user_id` = ? AND `date` = ?", [$userId, $today]);
        if ($already) {
            Database::errorResponse('আপনি আজকের সাইন-ইন বোনাস ইতিমধ্যে গ্রহণ করেছেন। আগামীকাল পুনরায় আসুন!', 400);
        }

        $lastRecord = Database::fetchOne("SELECT * FROM `daily_signin_records` WHERE `user_id` = ? ORDER BY `date` DESC LIMIT 1", [$userId]);
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        $streak = ($lastRecord && $lastRecord['date'] === $yesterday) ? (int)$lastRecord['streak'] + 1 : 1;
        $dayNumber = (($streak - 1) % 30) + 1;
        $bonus = round(2.00 + ($dayNumber * 1.5), 2);

        $user = Database::fetchOne("SELECT `username`, `balance` FROM `users` WHERE `id` = ?", [$userId]);
        $username = $user ? $user['username'] : 'Player';

        $recId = 'ds-' . uniqid();
        Database::insert('daily_signin_records', [
            'id' => $recId,
            'user_id' => $userId,
            'username' => $username,
            'day' => $dayNumber,
            'bonus' => $bonus,
            'date' => $today,
            'streak' => $streak,
            'status' => 'claimed',
            'claimed_at' => date('Y-m-d H:i:s')
        ]);

        $newBal = (float)$user['balance'] + $bonus;
        Database::execute("UPDATE `users` SET `balance` = `balance` + ? WHERE `id` = ?", [$bonus, $userId]);

        // Transaction record
        Database::insert('transactions', [
            'id' => 'tx-' . uniqid(),
            'user_id' => $userId,
            'type' => 'bonus',
            'amount' => $bonus,
            'channel' => 'system',
            'trx_id' => 'DS' . date('YmdHis') . rand(100, 999),
            'status' => 'success',
            'description' => "দিন {$dayNumber} দৈনিক সাইন-ইন বোনাস",
            'created_at' => date('Y-m-d H:i:s')
        ]);

        Database::jsonResponse([
            'success' => true,
            'message' => "দিন {$dayNumber} সাইন-ইন সফল! ৳{$bonus} অ্যাকাউন্টে জমা হয়েছে।",
            'bonus' => $bonus,
            'streak' => $streak,
            'dayNumber' => $dayNumber,
            'newBalance' => $newBal
        ]);
        break;

    default:
        Database::errorResponse("Unknown daily signin action: {$action}", 404);
        break;
}
