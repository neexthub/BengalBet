<?php
/**
 * Promo Codes REST API Endpoint
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';
$userId = Database::getAuthUserId();
$input = Database::getJsonInput();

switch ($action) {
    case 'active':
    case '':
        $promos = Database::fetchAll("SELECT * FROM `promo_codes` WHERE `status` = 'active' ORDER BY `created_at` DESC");
        Database::jsonResponse([
            'success' => true,
            'promos' => $promos
        ]);
        break;

    case 'redeem':
        if ($method !== 'POST') Database::errorResponse('Method Not Allowed', 405);
        $code = strtoupper(trim($input['code'] ?? ''));

        if (empty($code)) Database::errorResponse('প্রোমো কোড প্রদান করুন।', 400);

        $promo = Database::fetchOne("SELECT * FROM `promo_codes` WHERE `code` = ? AND `status` = 'active'", [$code]);
        if (!$promo) {
            Database::errorResponse('অবৈধ বা মেয়াদোত্তীর্ণ প্রোমো কোড!', 400);
        }

        if ((int)$promo['current_usage_count'] >= (int)$promo['max_usage_count']) {
            Database::errorResponse('এই প্রোমো কোডের ব্যবহারের সর্বোচ্চ সীমা শেষ হয়েছে!', 400);
        }

        $already = Database::fetchOne("SELECT `id` FROM `promo_redemptions` WHERE `promo_id` = ? AND `user_id` = ?", [$promo['id'], $userId]);
        if ($already) {
            Database::errorResponse('আপনি ইতিমধ্যে এই প্রোমো কোডটি ব্যবহার করেছেন!', 400);
        }

        $user = Database::fetchOne("SELECT `username`, `balance`, `vip_level` FROM `users` WHERE `id` = ?", [$userId]);
        if (!$user) Database::errorResponse('User not found', 404);

        if ((int)$user['vip_level'] < (int)$promo['min_vip_level']) {
            Database::errorResponse("এই কোডটি শুধুমাত্র VIP{$promo['min_vip_level']} বা তদূর্ধ্ব সদস্যদের জন্য।", 400);
        }

        $bonus = (float)$promo['amount'];
        $redId = 'pred-' . uniqid();

        Database::insert('promo_redemptions', [
            'id' => $redId,
            'promo_id' => $promo['id'],
            'code' => $code,
            'user_id' => $userId,
            'username' => $user['username'],
            'bonus_amount' => $bonus,
            'redeemed_at' => date('Y-m-d H:i:s')
        ]);

        Database::execute("UPDATE `promo_codes` SET `current_usage_count` = `current_usage_count` + 1 WHERE `id` = ?", [$promo['id']]);
        Database::execute("UPDATE `users` SET `balance` = `balance` + ? WHERE `id` = ?", [$bonus, $userId]);

        // Transaction record
        Database::insert('transactions', [
            'id' => 'tx-' . uniqid(),
            'user_id' => $userId,
            'type' => 'bonus',
            'amount' => $bonus,
            'channel' => 'system',
            'trx_id' => 'PRM' . date('YmdHis') . rand(100, 999),
            'status' => 'success',
            'description' => "প্রোমো কোড রিডিম: {$code} (৳{$bonus})",
            'created_at' => date('Y-m-d H:i:s')
        ]);

        $newBal = (float)$user['balance'] + $bonus;

        Database::jsonResponse([
            'success' => true,
            'message' => "অভিনন্দন! প্রোমো কোড {$code} সফলভাবে রিডিম হয়েছে। ৳{$bonus} অ্যাকাউন্টে যুক্ত হয়েছে!",
            'bonusAmount' => $bonus,
            'code' => $code,
            'newBalance' => $newBal
        ]);
        break;

    case 'my-redemptions':
        $redemptions = Database::fetchAll("SELECT * FROM `promo_redemptions` WHERE `user_id` = ? ORDER BY `redeemed_at` DESC", [$userId]);
        Database::jsonResponse([
            'success' => true,
            'redemptions' => $redemptions
        ]);
        break;

    default:
        Database::errorResponse("Unknown promos action: {$action}", 404);
        break;
}
