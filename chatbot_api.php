<?php
header('Content-Type: application/json');
// --- Gemini API Key (replace with your actual key) ---
$GEMINI_API_KEY = 'AIzaSyCCBxjXfdqk3D1koTizKVeQm3BI-i3Mv3Y'; // TODO: Replace this with your real key!

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['reply' => 'Invalid request method.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!isset($input['message']) || trim($input['message']) === '') {
    echo json_encode(['reply' => 'Please enter a question.']);
    exit;
}

$project_context = "You are the Neighborhood Watch Assistant. You help users with community safety, crime reporting, lost & found, and local alerts. Answer all questions as a helpful assistant for the Neighborhood Watch portal. If you don't know something, suggest contacting local authorities or checking official resources. ";
$user_message = $project_context . $input['message'];

// Gemini API endpoint (chat)
$url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=' . $GEMINI_API_KEY;

$payload = [
    'contents' => [
        ['parts' => [ ['text' => $user_message] ]]
    ]
];

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
if ($response === false || $http_code !== 200) {
    echo json_encode(['reply' => 'Sorry, the chatbot is temporarily unavailable.']);
    exit;
}

$data = json_decode($response, true);
$reply = '';
if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
    $reply = $data['candidates'][0]['content']['parts'][0]['text'];
} else {
    $reply = 'Sorry, I could not understand the response.';
}
echo json_encode(['reply' => $reply]);
exit;
