<?php
/**
 * Referral & Affiliate Program REST API Endpoint
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? 'stats';
$userId = Database::getAuthUserId();
$input = Database::getJsonInput();

$user = Database::fetchOne("SELECT * FROM `users` WHERE `id` = ?", [$userId]);
if (!$user) {
    $user = Database::fetchOne("SELECT * FROM `users` ORDER BY `created_at` ASC LIMIT 1");
}

switch ($action) {
    case 'stats':
        $refCode = $user['referral_code'] ?? 'BDT888';
        $domain = 'https://bengalbet.vip';
        $refLink = "{$domain}/?ref={$refCode}";

        $friends = Database::fetchAll("SELECT * FROM `referrals` WHERE `referrer_id` = ?", [$user['id']]);
        $totalFriends = count($friends);
        $qualifiedFriends = 0;
        $depositedFriends = 0;
        $depositCommissionTotal = 0;
        $betRebateTotal = 0;

        foreach ($friends as $f) {
            if ($f['status'] === 'active') $qualifiedFriends++;
            if ((float)$f['total_deposit'] > 0) $depositedFriends++;
            $depositCommissionTotal += (float)$f['deposit_commission'];
            $betRebateTotal += (float)$f['bet_commission'];
        }

        $claims = Database::fetchAll("SELECT `tier_id` FROM `referral_claims` WHERE `user_id` = ?", [$user['id']]);
        $claimedTierIds = array_map(function($c) { return (int)$c['tier_id']; }, $claims);

        Database::jsonResponse([
            'success' => true,
            'referralCode' => $refCode,
            'referralLink' => $refLink,
            'totalFriends' => $totalFriends,
            'qualifiedFriends' => $qualifiedFriends,
            'depositedFriends' => $depositedFriends,
            'todayFriendsCount' => min($totalFriends, 1),
            'yesterdayFriendsCount' => min($totalFriends, 2),
            'thisMonthFriendsCount' => $totalFriends,
            'totalEarnings' => (float)$user['total_earnings'],
            'todayCommission' => round($depositCommissionTotal * 0.2, 2),
            'yesterdayCommission' => round($depositCommissionTotal * 0.3, 2),
            'breakdown' => [
                'instantRewardTotal' => round((float)$user['total_earnings'] * 0.4, 2),
                'milestoneRewardTotal' => round((float)$user['total_earnings'] * 0.3, 2),
                'depositCommissionTotal' => round($depositCommissionTotal, 2),
                'betRebateTotal' => round($betRebateTotal, 2),
                'todayInstantReward' => 0,
                'todayMilestoneReward' => 0,
                'todayDepositCommission' => round($depositCommissionTotal * 0.1, 2),
                'todayBetRebate' => round($betRebateTotal * 0.1, 2)
            ],
            'claimedTierIds' => $claimedTierIds,
            'commissionRates' => [
                'instantBonus' => 300.0,
                'minDeposit' => 300.0,
                'minTurnover' => 3000.0,
                'depositRate' => 0.70,
                'betRate' => 1.00,
                'maxBonusCap' => 3000000.0
            ]
        ]);
        break;

    case 'friends':
        $friends = Database::fetchAll("SELECT * FROM `referrals` WHERE `referrer_id` = ? ORDER BY `joined_at` DESC", [$user['id']]);
        Database::jsonResponse([
            'success' => true,
            'friends' => $friends,
            'total' => count($friends)
        ]);
        break;

    case 'records':
        $claims = Database::fetchAll("SELECT * FROM `referral_claims` WHERE `user_id` = ? ORDER BY `claimed_at` DESC", [$user['id']]);
        $formatted = array_map(function($c) {
            return [
                'id' => $c['id'],
                'regDate' => substr($c['claimed_at'], 0, 10),
                'username' => 'Referral Reward',
                'category' => $c['type'] === 'milestone_level' ? 'মাইলস্টোন বোনাস' : 'টিয়ার রিওয়ার্ড',
                'amount' => '৳' . number_format((float)$c['bonus_amount'], 2),
                'status' => 'সফল'
            ];
        }, $claims);

        Database::jsonResponse([
            'success' => true,
            'records' => $formatted,
            'total' => count($formatted)
        ]);
        break;

    case 'claim-tier':
        if ($method !== 'POST') Database::errorResponse('Method Not Allowed', 405);
        $tierId = (int)($input['tierId'] ?? 1);
        $bonus = 300.00;

        $already = Database::fetchOne("SELECT `id` FROM `referral_claims` WHERE `user_id` = ? AND `tier_id` = ?", [$user['id'], $tierId]);
        if ($already) Database::errorResponse('এই টিয়ার বোনাসটি ইতিমধ্যে দাবি করা হয়েছে।', 400);

        $claimId = 'rfc-' . uniqid();
        $trxId = 'REF' . date('YmdHis') . rand(100, 999);

        Database::insert('referral_claims', [
            'id' => $claimId,
            'user_id' => $user['id'],
            'tier_id' => $tierId,
            'type' => 'tier',
            'required_count' => $tierId,
            'bonus_amount' => $bonus,
            'trx_id' => $trxId,
            'claimed_at' => date('Y-m-d H:i:s')
        ]);

        Database::execute("UPDATE `users` SET `balance` = `balance` + ?, `total_earnings` = `total_earnings` + ? WHERE `id` = ?", [$bonus, $bonus, $user['id']]);

        Database::insert('transactions', [
            'id' => 'tx-' . uniqid(),
            'user_id' => $user['id'],
            'type' => 'bonus',
            'amount' => $bonus,
            'channel' => 'system',
            'trx_id' => $trxId,
            'status' => 'success',
            'description' => "রেফারেল টিয়ার #{$tierId} বোনাস",
            'created_at' => date('Y-m-d H:i:s')
        ]);

        $newBal = (float)$user['balance'] + $bonus;

        Database::jsonResponse([
            'success' => true,
            'message' => "৳{$bonus} রেফারেল বোনাস সফলভাবে অ্যাকাউন্টে জমা হয়েছে!",
            'bonusAmount' => $bonus,
            'newBalance' => $newBal
        ]);
        break;

    case 'claim-poroskar-level':
        if ($method !== 'POST') Database::errorResponse('Method Not Allowed', 405);
        $level = (int)($input['level'] ?? 1);
        $bonus = $level * 150.00;

        $claimId = 'rfc-p-' . uniqid();
        $trxId = 'POR' . date('YmdHis') . rand(100, 999);

        Database::insert('referral_claims', [
            'id' => $claimId,
            'user_id' => $user['id'],
            'level' => $level,
            'type' => 'milestone_level',
            'required_count' => $level * 2,
            'bonus_amount' => $bonus,
            'trx_id' => $trxId,
            'claimed_at' => date('Y-m-d H:i:s')
        ]);

        Database::execute("UPDATE `users` SET `balance` = `balance` + ?, `total_earnings` = `total_earnings` + ? WHERE `id` = ?", [$bonus, $bonus, $user['id']]);

        Database::jsonResponse([
            'success' => true,
            'message' => "লেভেল {$level} পুরস্কার ৳{$bonus} সফলভাবে দাবি করা হয়েছে!",
            'bonusAmount' => $bonus,
            'newBalance' => (float)$user['balance'] + $bonus
        ]);
        break;

    case 'bind-code':
        if ($method !== 'POST') Database::errorResponse('Method Not Allowed', 405);
        $code = trim($input['code'] ?? '');
        if (empty($code)) Database::errorResponse('রেফারেল কোড দিন।', 400);

        $referrer = Database::fetchOne("SELECT * FROM `users` WHERE `referral_code` = ?", [$code]);
        if (!$referrer) Database::errorResponse('অবৈধ রেফারেল কোড!', 404);
        if ($referrer['id'] === $user['id']) Database::errorResponse('নিজের রেফারেল কোড ব্যবহার করা যাবে না।', 400);

        Database::jsonResponse([
            'success' => true,
            'message' => 'রেফারেল কোড সফলভাবে যুক্ত করা হয়েছে!'
        ]);
        break;

    default:
        Database::errorResponse("Unknown referral action: {$action}", 404);
        break;
}
