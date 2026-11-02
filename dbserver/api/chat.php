<?php
/**
 * Live Chat REST API Endpoint
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';
$userId = Database::getAuthUserId();
$input = Database::getJsonInput();

switch ($action) {
    case 'session':
        $department = trim($_GET['department'] ?? $input['department'] ?? 'general');
        $session = Database::fetchOne("SELECT * FROM `chat_sessions` WHERE `user_id` = ? AND `status` != 'closed' ORDER BY `created_at` DESC LIMIT 1", [$userId]);

        $user = Database::fetchOne("SELECT `username`, `vip_level`, `balance`, `phone` FROM `users` WHERE `id` = ?", [$userId]);
        $username = $user ? $user['username'] : 'Player';
        $userVip = $user ? (int)$user['vip_level'] : 1;
        $userBalance = $user ? (float)$user['balance'] : 0.00;
        $userPhone = $user ? $user['phone'] : '';

        if (!$session) {
            $sessionId = 'cs-' . uniqid();
            $session = [
                'id' => $sessionId,
                'user_id' => $userId,
                'username' => $username,
                'user_vip' => $userVip,
                'user_balance' => $userBalance,
                'user_phone' => $userPhone,
                'status' => 'active',
                'department' => $department,
                'unread_user_count' => 0,
                'unread_admin_count' => 0,
                'last_message' => 'চ্যাটে স্বাগতম! কীভাবে আপনাকে সহায়তা করতে পারি?',
                'last_message_at' => date('Y-m-d H:i:s'),
                'created_at' => date('Y-m-d H:i:s')
            ];
            Database::insert('chat_sessions', $session);

            // Add welcome message from bot
            Database::insert('chat_messages', [
                'id' => 'cmsg-' . uniqid(),
                'session_id' => $sessionId,
                'sender' => 'bot',
                'sender_name' => 'BengalBet সাপোর্ট এআই',
                'sender_avatar' => 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=100&h=100&fit=crop&crop=faces',
                'text' => 'নমস্কার! BengalBet ২৪/৭ গ্রাহক সেবায় আপনাকে স্বাগতম। ডিপোজিট, উত্তোলন বা গেম সংক্রান্ত কোনো সমস্যা থাকলে আমাদের জানান।',
                'is_read' => 1,
                'created_at' => date('Y-m-d H:i:s')
            ]);
        }

        $messages = Database::fetchAll("SELECT * FROM `chat_messages` WHERE `session_id` = ? ORDER BY `created_at` ASC", [$session['id']]);

        Database::jsonResponse([
            'success' => true,
            'session' => $session,
            'messages' => $messages
        ]);
        break;

    case 'messages':
        $sessionId = $_GET['sessionId'] ?? $input['sessionId'] ?? '';
        if (empty($sessionId)) Database::errorResponse('Session ID is required', 400);

        $session = Database::fetchOne("SELECT * FROM `chat_sessions` WHERE `id` = ?", [$sessionId]);
        $messages = Database::fetchAll("SELECT * FROM `chat_messages` WHERE `session_id` = ? ORDER BY `created_at` ASC", [$sessionId]);

        Database::jsonResponse([
            'success' => true,
            'session' => $session,
            'messages' => $messages
        ]);
        break;

    case 'send':
        if ($method !== 'POST') Database::errorResponse('Method Not Allowed', 405);
        $sessionId = trim($input['sessionId'] ?? '');
        $text = trim($input['text'] ?? '');
        $attachmentUrl = trim($input['attachmentUrl'] ?? '');
        $attachmentType = trim($input['attachmentType'] ?? '');

        if (empty($sessionId) || (empty($text) && empty($attachmentUrl))) {
            Database::errorResponse('Session ID and message text are required', 400);
        }

        $session = Database::fetchOne("SELECT * FROM `chat_sessions` WHERE `id` = ?", [$sessionId]);
        if (!$session) Database::errorResponse('Chat session not found', 404);

        $msgId = 'cmsg-' . uniqid();
        $msgData = [
            'id' => $msgId,
            'session_id' => $sessionId,
            'sender' => 'user',
            'sender_name' => $session['username'],
            'text' => $text,
            'attachment_url' => $attachmentUrl ?: null,
            'attachment_type' => $attachmentType ?: null,
            'is_read' => 0,
            'created_at' => date('Y-m-d H:i:s')
        ];
        Database::insert('chat_messages', $msgData);

        // Update session
        Database::update('chat_sessions', [
            'last_message' => $text ?: '[সংযুক্তি]',
            'last_message_at' => date('Y-m-d H:i:s'),
            'unread_admin_count' => (int)$session['unread_admin_count'] + 1,
            'status' => 'waiting'
        ], "`id` = ?", [$sessionId]);

        $updatedSession = Database::fetchOne("SELECT * FROM `chat_sessions` WHERE `id` = ?", [$sessionId]);

        Database::jsonResponse([
            'success' => true,
            'message' => $msgData,
            'session' => $updatedSession
        ]);
        break;

    case 'department':
        if ($method !== 'POST') Database::errorResponse('Method Not Allowed', 405);
        $sessionId = trim($input['sessionId'] ?? '');
        $dept = trim($input['department'] ?? 'general');

        Database::update('chat_sessions', ['department' => $dept], "`id` = ?", [$sessionId]);
        $session = Database::fetchOne("SELECT * FROM `chat_sessions` WHERE `id` = ?", [$sessionId]);
        $messages = Database::fetchAll("SELECT * FROM `chat_messages` WHERE `session_id` = ? ORDER BY `created_at` ASC", [$sessionId]);

        Database::jsonResponse([
            'success' => true,
            'session' => $session,
            'messages' => $messages
        ]);
        break;

    case 'rate':
        if ($method !== 'POST') Database::errorResponse('Method Not Allowed', 405);
        $sessionId = trim($input['sessionId'] ?? '');
        $rating = (int)($input['rating'] ?? 5);
        $comment = trim($input['comment'] ?? '');

        Database::update('chat_sessions', [
            'rating' => $rating,
            'rating_comment' => $comment,
            'status' => 'resolved'
        ], "`id` = ?", [$sessionId]);

        $session = Database::fetchOne("SELECT * FROM `chat_sessions` WHERE `id` = ?", [$sessionId]);
        Database::jsonResponse([
            'success' => true,
            'message' => 'মতামত প্রদানের জন্য ধন্যবাদ!',
            'session' => $session
        ]);
        break;

    case 'close':
        if ($method !== 'POST') Database::errorResponse('Method Not Allowed', 405);
        $sessionId = trim($input['sessionId'] ?? '');
        Database::update('chat_sessions', ['status' => 'closed'], "`id` = ?", [$sessionId]);
        $session = Database::fetchOne("SELECT * FROM `chat_sessions` WHERE `id` = ?", [$sessionId]);
        Database::jsonResponse([
            'success' => true,
            'session' => $session
        ]);
        break;

    default:
        Database::errorResponse("Unknown chat action: {$action}", 404);
        break;
}
