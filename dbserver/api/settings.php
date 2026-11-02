<?php
/**
 * Public Settings REST API Endpoint
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';

$settings = Database::fetchOne("SELECT * FROM `site_settings` WHERE `id` = 1");
if (!$settings) {
    Database::jsonResponse([
        'success' => true,
        'settings' => [
            'bkashNumber' => '01712345678',
            'nagadNumber' => '01912345678',
            'rocketNumber' => '01812345678',
            'usdtAddress' => 'TQn9Y2khEsLJW1ChVWFMSMeSTow5KAnsP5',
            'siteNotice' => [
                'enabled' => true,
                'titleBn' => 'BengalBet মেগা ক্যাসিনো ও বোনাস ঘোষণা',
                'titleEn' => 'BengalBet Mega Casino & Bonus Announcement',
                'contentBn' => 'প্রতিটি ডিপোজিটে ৪৭% পর্যন্ত স্বাগতম বোনাস!',
                'contentEn' => 'Up to 47% welcome bonus on every deposit!'
            ],
            'maintenanceMode' => false,
            'minDeposit' => 200,
            'maxDeposit' => 50000,
            'minWithdraw' => 500,
            'maxWithdraw' => 100000
        ]
    ]);
}

$siteNotice = json_decode($settings['site_notice_json'] ?? '{}', true) ?: [];
$gameEngine = json_decode($settings['game_engine_json'] ?? '{}', true) ?: [];

Database::jsonResponse([
    'success' => true,
    'settings' => [
        'bkashNumber' => $settings['bkash_number'],
        'nagadNumber' => $settings['nagad_number'],
        'rocketNumber' => $settings['rocket_number'],
        'usdtAddress' => $settings['usdt_address'],
        'siteNotice' => $siteNotice,
        'maintenanceMode' => (bool)$settings['maintenance_mode'],
        'minDeposit' => (float)$settings['min_deposit'],
        'maxDeposit' => (float)$settings['max_deposit'],
        'minWithdraw' => (float)$settings['min_withdraw'],
        'maxWithdraw' => (float)$settings['max_withdraw'],
        'gameEngine' => $gameEngine
    ]
]);
