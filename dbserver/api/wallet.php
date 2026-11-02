<?php
/**
 * Wallet & Transactions REST API Endpoint
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';
$input = Database::getJsonInput();
$userId = Database::getAuthUserId();

switch ($action) {
    case 'balance':
        $user = Database::fetchOne("SELECT `balance`, `vip_level`, `vip_exp`, `vip_max_exp` FROM `users` WHERE `id` = ?", [$userId]);
        if (!$user) {
            $user = ['balance' => 0.00, 'vip_level' => 1, 'vip_exp' => 0, 'vip_max_exp' => 2000];
        }
        Database::jsonResponse([
            'success' => true,
            'balance' => (float)$user['balance'],
            'vipLevel' => (int)$user['vip_level'],
            'vipExp' => (int)$user['vip_exp']
        ]);
        break;

    case 'deposit':
        if ($method !== 'POST') Database::errorResponse('Method Not Allowed', 405);
        $amount = (float)($input['amount'] ?? 0);
        $methodName = strtolower(trim($input['method'] ?? 'bkash'));
        $gateway = trim($input['gateway'] ?? 'পরিশোধ-DK');
        $senderAccount = trim($input['senderAccount'] ?? '');
        $trxId = trim($input['trxId'] ?? ('DP' . date('YmdHis') . rand(100, 999)));

        if ($amount < 200) {
            Database::errorResponse('ন্যূনতম ডিপোজিট পরিমাণ ৳২০০', 400);
        }

        // Calculate 5% deposit bonus
        $bonus = round($amount * 0.05, 2);
        $user = Database::fetchOne("SELECT * FROM `users` WHERE `id` = ?", [$userId]);
        if (!$user) {
            Database::errorResponse('User not found', 404);
        }

        $newBalance = (float)$user['balance'] + $amount + $bonus;
        $newVipExp = (int)$user['vip_exp'] + (int)$amount;

        // Create transaction record
        $txId = 'tx-' . uniqid();
        $txData = [
            'id' => $txId,
            'user_id' => $userId,
            'username' => $user['username'],
            'user_phone' => $user['phone'],
            'type' => 'deposit',
            'amount' => $amount,
            'bonus' => $bonus,
            'fee' => 0.00,
            'channel' => in_array($methodName, ['bkash', 'nagad', 'rocket', 'usdt']) ? $methodName : 'bkash',
            'gateway' => $gateway,
            'account_number' => $senderAccount ?: $user['phone'],
            'trx_id' => $trxId,
            'status' => 'success',
            'description' => "{$gateway} ডিপোজিট (৫% বোনাস ৳{$bonus} অন্তর্ভুক্ত)",
            'created_at' => date('Y-m-d H:i:s')
        ];
        Database::insert('transactions', $txData);

        // Update user balance
        Database::update('users', [
            'balance' => $newBalance,
            'vip_exp' => $newVipExp
        ], "`id` = ?", [$userId]);

        // If user was referred, credit referrer commission
        $ref = Database::fetchOne("SELECT * FROM `referrals` WHERE `referred_user_id` = ?", [$userId]);
        if ($ref) {
            $commission = round($amount * 0.007, 2); // 0.70%
            Database::update('referrals', [
                'total_deposit' => (float)$ref['total_deposit'] + $amount,
                'deposit_commission' => (float)$ref['deposit_commission'] + $commission,
                'total_commission_earned' => (float)$ref['total_commission_earned'] + $commission,
                'status' => 'active'
            ], "`id` = ?", [$ref['id']]);

            Database::execute("UPDATE `users` SET `balance` = `balance` + ?, `total_earnings` = `total_earnings` + ? WHERE `id` = ?", [$commission, $commission, $ref['referrer_id']]);
        }

        Database::jsonResponse([
            'success' => true,
            'message' => 'ডিপোজিট সফলভাবে সম্পন্ন হয়েছে!',
            'newBalance' => $newBalance,
            'bonusAdded' => $bonus,
            'transaction' => $txData
        ]);
        break;

    case 'withdraw':
        if ($method !== 'POST') Database::errorResponse('Method Not Allowed', 405);
        $amount = (float)($input['amount'] ?? 0);
        $walletType = strtolower(trim($input['walletType'] ?? 'bkash'));
        $accountNumber = trim($input['accountNumber'] ?? '');

        if ($amount < 500) {
            Database::errorResponse('ন্যূনতম উত্তোলন পরিমাণ ৳৫০০', 400);
        }

        $user = Database::fetchOne("SELECT * FROM `users` WHERE `id` = ?", [$userId]);
        if (!$user) Database::errorResponse('User not found', 404);

        if ((float)$user['balance'] < $amount) {
            Database::errorResponse('আপনার অ্যাকাউন্টে পর্যাপ্ত ব্যালেন্স নেই!', 400);
        }

        $newBalance = (float)$user['balance'] - $amount;
        $trxId = 'WD' . date('YmdHis') . rand(100, 999);

        // Insert transaction record (marked as pending/processing for security, balance deducted)
        $txId = 'tx-' . uniqid();
        $txData = [
            'id' => $txId,
            'user_id' => $userId,
            'username' => $user['username'],
            'user_phone' => $user['phone'],
            'type' => 'withdraw',
            'amount' => $amount,
            'bonus' => 0.00,
            'fee' => 0.00,
            'channel' => in_array($walletType, ['bkash', 'nagad', 'rocket', 'usdt']) ? $walletType : 'bkash',
            'account_number' => $accountNumber ?: $user['phone'],
            'trx_id' => $trxId,
            'status' => 'pending',
            'description' => "{$walletType} ওয়ালেট উত্তোলন অনুরোধ",
            'created_at' => date('Y-m-d H:i:s')
        ];
        Database::insert('transactions', $txData);

        // Deduct balance
        Database::update('users', ['balance' => $newBalance], "`id` = ?", [$userId]);

        Database::jsonResponse([
            'success' => true,
            'message' => 'উত্তোলন অনুরোধ সফলভাবে জমা হয়েছে! অ্যাডমিন অনুমোদনের পর টাকা পৌঁছে যাবে।',
            'newBalance' => $newBalance,
            'transaction' => $txData
        ]);
        break;

    case 'transactions':
        $type = $_GET['type'] ?? '';
        $sql = "SELECT * FROM `transactions` WHERE `user_id` = ?";
        $params = [$userId];

        if (!empty($type) && $type !== 'all') {
            $sql .= " AND `type` = ?";
            $params[] = $type;
        }

        $sql .= " ORDER BY `created_at` DESC LIMIT 100";
        $transactions = Database::fetchAll($sql, $params);

        Database::jsonResponse([
            'success' => true,
            'count' => count($transactions),
            'transactions' => $transactions
        ]);
        break;

    case 'cards':
        if ($method === 'GET') {
            $cards = Database::fetchAll("SELECT * FROM `wallets` WHERE `user_id` = ? ORDER BY `created_at` DESC", [$userId]);
            Database::jsonResponse([
                'success' => true,
                'wallets' => $cards
            ]);
        } elseif ($method === 'POST') {
            $type = trim($input['type'] ?? 'BKash');
            $accountName = trim($input['accountName'] ?? '');
            $accountNumber = trim($input['accountNumber'] ?? '');

            if (empty($accountNumber)) {
                Database::errorResponse('অ্যাকাউন্ট নম্বর আবশ্যক।', 400);
            }

            $masked = substr($accountNumber, 0, 3) . '****' . substr($accountNumber, -4);
            $cardId = 'wal-' . uniqid();
            $cardData = [
                'id' => $cardId,
                'user_id' => $userId,
                'type' => $type,
                'account_name' => $accountName ?: 'My Wallet',
                'account_number' => $masked,
                'full_number' => $accountNumber,
                'is_default' => 0,
                'created_at' => date('Y-m-d H:i:s')
            ];

            Database::insert('wallets', $cardData);

            Database::jsonResponse([
                'success' => true,
                'message' => 'ওয়ালেট সফলভাবে যুক্ত করা হয়েছে!',
                'wallet' => $cardData
            ]);
        }
        break;

    case 'delete-card':
        $cardId = $_GET['id'] ?? $input['id'] ?? '';
        if (empty($cardId)) Database::errorResponse('Card ID is required', 400);

        Database::delete('wallets', "`id` = ? AND `user_id` = ?", [$cardId, $userId]);
        Database::jsonResponse([
            'success' => true,
            'message' => 'ওয়ালেট সফলভাবে মুছে ফেলা হয়েছে!'
        ]);
        break;

    default:
        Database::errorResponse("Unknown wallet action: {$action}", 404);
        break;
}
