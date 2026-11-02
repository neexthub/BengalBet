<?php
/**
 * Feedback & Support Tickets REST API Endpoint
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';
$ticketId = $_GET['id'] ?? '';
$userId = Database::getAuthUserId();
$input = Database::getJsonInput();

switch ($action) {
    case 'submit':
    case 'create':
        if ($method !== 'POST') Database::errorResponse('Method Not Allowed', 405);
        $type = trim($input['type'] ?? 'সাধারণ অনুসন্ধান');
        $content = trim($input['content'] ?? '');
        $contact = trim($input['contact'] ?? '');
        $subject = trim($input['subject'] ?? $type);
        $priority = in_array($input['priority'] ?? '', ['normal', 'high', 'urgent']) ? $input['priority'] : 'normal';

        if (empty($content)) {
            Database::errorResponse('ফিডব্যাক বিবরণ প্রদান করুন।', 400);
        }

        $user = Database::fetchOne("SELECT `username`, `phone` FROM `users` WHERE `id` = ?", [$userId]);
        $username = $user ? $user['username'] : 'Player';
        $contact = $contact ?: ($user ? $user['phone'] : '');

        $id = 'fb-' . uniqid();
        $ticketNum = 'TK' . date('ymd') . rand(1000, 9999);

        $fbData = [
            'id' => $id,
            'ticket_number' => $ticketNum,
            'user_id' => $userId,
            'username' => $username,
            'type' => $type,
            'subject' => $subject,
            'content' => $content,
            'contact' => $contact,
            'priority' => $priority,
            'status' => 'pending',
            'created_at' => date('Y-m-d H:i:s')
        ];

        Database::insert('feedbacks', $fbData);

        // Add first thread message
        Database::insert('feedback_messages', [
            'id' => 'fbm-' . uniqid(),
            'feedback_id' => $id,
            'sender' => 'user',
            'sender_name' => $username,
            'text' => $content,
            'created_at' => date('Y-m-d H:i:s')
        ]);

        Database::jsonResponse([
            'success' => true,
            'message' => "আপনার সহায়তা টিকেট (#{$ticketNum}) সফলভাবে তৈরি হয়েছে!",
            'feedback' => $fbData
        ]);
        break;

    case 'list':
    case '':
        $feedbacks = Database::fetchAll("SELECT * FROM `feedbacks` WHERE `user_id` = ? ORDER BY `created_at` DESC", [$userId]);
        $pending = 0;
        $replied = 0;
        foreach ($feedbacks as $f) {
            if ($f['status'] === 'pending') $pending++;
            if ($f['status'] === 'replied') $replied++;
        }
        Database::jsonResponse([
            'success' => true,
            'feedbacks' => $feedbacks,
            'pendingCount' => $pending,
            'repliedCount' => $replied
        ]);
        break;

    case 'ticket':
        if (empty($ticketId)) Database::errorResponse('Ticket ID is required', 400);
        $ticket = Database::fetchOne("SELECT * FROM `feedbacks` WHERE `id` = ?", [$ticketId]);
        if (!$ticket) Database::errorResponse('টিকেট খুঁজে পাওয়া যায়নি।', 404);

        $messages = Database::fetchAll("SELECT * FROM `feedback_messages` WHERE `feedback_id` = ? ORDER BY `created_at` ASC", [$ticketId]);
        $ticket['messages'] = $messages;

        Database::jsonResponse([
            'success' => true,
            'feedback' => $ticket
        ]);
        break;

    case 'message':
        if ($method !== 'POST') Database::errorResponse('Method Not Allowed', 405);
        if (empty($ticketId)) Database::errorResponse('Ticket ID is required', 400);
        $text = trim($input['text'] ?? '');
        $senderName = trim($input['senderName'] ?? '');

        if (empty($text)) Database::errorResponse('বার্তা খালি হতে পারে না।', 400);

        $msgId = 'fbm-' . uniqid();
        Database::insert('feedback_messages', [
            'id' => $msgId,
            'feedback_id' => $ticketId,
            'sender' => 'user',
            'sender_name' => $senderName ?: 'Player',
            'text' => $text,
            'created_at' => date('Y-m-d H:i:s')
        ]);

        Database::update('feedbacks', ['status' => 'pending', 'updated_at' => date('Y-m-d H:i:s')], "`id` = ?", [$ticketId]);

        $ticket = Database::fetchOne("SELECT * FROM `feedbacks` WHERE `id` = ?", [$ticketId]);
        $ticket['messages'] = Database::fetchAll("SELECT * FROM `feedback_messages` WHERE `feedback_id` = ? ORDER BY `created_at` ASC", [$ticketId]);

        Database::jsonResponse([
            'success' => true,
            'message' => 'বার্তা পাঠানো হয়েছে',
            'feedback' => $ticket
        ]);
        break;

    case 'resolve':
        if (empty($ticketId)) Database::errorResponse('Ticket ID is required', 400);
        Database::update('feedbacks', ['status' => 'resolved', 'updated_at' => date('Y-m-d H:i:s')], "`id` = ?", [$ticketId]);
        $ticket = Database::fetchOne("SELECT * FROM `feedbacks` WHERE `id` = ?", [$ticketId]);

        Database::jsonResponse([
            'success' => true,
            'message' => 'টিকেট সফলভাবে বন্ধ করা হয়েছে।',
            'feedback' => $ticket
        ]);
        break;

    default:
        Database::errorResponse("Unknown feedback action: {$action}", 404);
        break;
}
