<?php
require_once '../config/config.php';
require_once '../config/ai_config.php';

header('Content-Type: application/json');


error_reporting(0);
ini_set('display_errors', 0);


session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}


$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    echo json_encode(['error' => 'Invalid data']);
    exit;
}

$stats = $input['stats'] ?? [];
$topItems = $input['topItems'] ?? [];


$prompt = "You are an environmental consultant for a village in Indonesia. 
Data:
- Organic Waste: {$stats['organik']} items ({$stats['organik_percent']}%)
- Inorganic Waste: {$stats['anorganik']} items ({$stats['anorganik_percent']}%)
- Hazardous (B3): {$stats['b3']} items ({$stats['b3_percent']}%)
- Top 3 Most Reported Items: " . implode(', ', array_slice($topItems, 0, 3));

$prompt .= "\n\nPlease provide ONE short, high-impact, actionable recommendation (max 2 sentences) for the village government to improve waste management based on this specific data. Focus on the most critical problem. Use Indonesian language. Be professional but encouraging. Add relevant emojis.";


$data = [
    "contents" => [
        [
            "parts" => [
                ["text" => $prompt]
            ]
        ]
    ]
];

$apiKey = AI_KEY;
$url = AI_BASE_URL . '/' . AI_MODEL . ':generateContent?key=' . $apiKey;

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200) {
    $result = json_decode($response, true);
    $text = $result['candidates'][0]['content']['parts'][0]['text'] ?? 'Gagal membuat rekomendasi.';
    echo json_encode(['success' => true, 'insight' => trim($text)]);
} else {
    echo json_encode(['error' => 'AI Service Unavailable', 'details' => $response]);
}
?>