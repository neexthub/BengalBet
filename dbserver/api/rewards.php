<?php
/**
 * Rewards Claim REST API Endpoint
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? 'claim';
$input = Database::getJsonInput();
$userId = Database::getAuthUserId();

if ($method !== 'POST') {
    Database::errorResponse('Method Not Allowed', 405);
}

$type = trim($input['type'] ?? 'spin');
$amount = (float)($input['amount'] ?? 0);
$title = trim($input['title'] ?? 'ক্যাশ বোনাস রিওয়ার্ড');

if ($amount <= 0) {
    Database::errorResponse('পুরস্কারের পরিমাণ ০ হতে পারে না।', 400);
}

$user = Database::fetchOne("SELECT * FROM `users` WHERE `id` = ?", [$userId]);
if (!$user) Database::errorResponse('User not found', 404);

$newBalance = round((float)$user['balance'] + $amount, 2);

// Update user counters based on reward type
$userUpdates = ['balance' => $newBalance];
if ($type === 'spin' && (int)$user['free_spins'] > 0) {
    $userUpdates['free_spins'] = (int)$user['free_spins'] - 1;
} elseif ($type === 'golden_egg' && (int)$user['golden_eggs_available'] > 0) {
    $userUpdates['golden_eggs_available'] = (int)$user['golden_eggs_available'] - 1;
} elseif ($type === 'red_envelope' && (int)$user['red_packets_available'] > 0) {
    $userUpdates['red_packets_available'] = (int)$user['red_packets_available'] - 1;
}

Database::update('users', $userUpdates, "`id` = ?", [$userId]);

// Record transaction
$trxId = 'REW' . date('YmdHis') . rand(100, 999);
Database::insert('transactions', [
    'id' => 'tx-' . uniqid(),
    'user_id' => $userId,
    'username' => $user['username'],
    'user_phone' => $user['phone'],
    'type' => 'bonus',
    'amount' => $amount,
    'channel' => 'system',
    'trx_id' => $trxId,
    'status' => 'success',
    'description' => "{$title} (৳{$amount}) সফলভাবে দাবি করা হয়েছে",
    'created_at' => date('Y-m-d H:i:s')
]);

$updatedUser = Database::fetchOne("SELECT * FROM `users` WHERE `id` = ?", [$userId]);
unset($updatedUser['password_hash'], $updatedUser['transaction_password']);

Database::jsonResponse([
    'success' => true,
    'message' => "অভিনন্দন! ৳{$amount} আপনার ব্যালেন্সে যোগ হয়েছে।",
    'newBalance' => $newBalance,
    'user' => $updatedUser
]);
