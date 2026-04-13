<?php
require_once '../config/engine_config.php';

header('Content-Type: application/json');


ini_set('display_errors', 0);
error_reporting(E_ALL);

function sendJson($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJson(['error' => 'Method not allowed'], 405);
}


$input = json_decode(file_get_contents('php://input'), true);
$imageBase64 = $input['image'] ?? null;

if (!$imageBase64) {
    sendJson(['error' => 'No image data provided'], 400);
}


if (strpos($imageBase64, 'base64,') !== false) {
    $imageBase64 = explode('base64,', $imageBase64)[1];
}


$jsonFile = '../config/api_keys.json';
$keysData = [];


if (file_exists($jsonFile)) {
    $keysData = json_decode(file_get_contents($jsonFile), true);
}


$activeKeys = [];
$currentTime = time();

foreach ($keysData as $index => $k) {

    if ($k['status'] === 'rate_limited' && $k['limit_reset_at'] <= $currentTime) {

        $keysData[$index]['status'] = 'active';
        $keysData[$index]['limit_reset_at'] = 0;
        $k['status'] = 'active'; // Update local var for immediate use
    }

    if ($k['status'] === 'active') {
        $activeKeys[] = [
            'key' => $k['key'],
            'index' => $index // Keep track of original index to update JSON later
        ];
    }
}


if (empty($activeKeys) && defined('ENGINE_KEYS')) {
    foreach (ENGINE_KEYS as $k) {
        if (!empty($k) && strpos($k, 'PASTE') === false && strpos($k, 'KEY_') === false) {
            $activeKeys[] = ['key' => $k, 'index' => -1];
        }
    }
}

if (empty($activeKeys)) {
    sendJson(['error' => 'No active Access Keys available. System overloaded.'], 503);
}




usort($activeKeys, function ($a, $b) use ($keysData) {
    $statsA = $keysData[$a['index']] ?? ['error_count' => 0, 'last_used' => 0];
    $statsB = $keysData[$b['index']] ?? ['error_count' => 0, 'last_used' => 0];


    if ($statsA['error_count'] !== $statsB['error_count']) {
        return $statsA['error_count'] - $statsB['error_count'];
    }


    return $statsA['last_used'] - $statsB['last_used'];
});


$response = null;
$httpCode = 0;
$curlError = '';
$attempt = 0;
$successKeyIndex = -1;


$payload = [
    'contents' => [
        [
            'parts' => [
                ['text' => ENGINE_SYSTEM_PROMPT],
                [
                    'inlineData' => [  // camelCase
                        'mimeType' => 'image/jpeg', // camelCase
                        'data' => $imageBase64
                    ]
                ]
            ]
        ]
    ],
    'generationConfig' => [ // camelCase
        'maxOutputTokens' => 2000,
        'temperature' => 0.4,
        'responseMimeType' => 'application/json' // camelCase
    ]
];

foreach ($activeKeys as $keyInfo) {
    $currentKey = $keyInfo['key'];
    $originalIndex = $keyInfo['index'];
    
    $attempt++;
    

    $url = ENGINE_BASE_URL . '/' . ENGINE_MODEL . ':generateContent?key=' . $currentKey;


    $ch = curl_init($url);
    $jsonPayload = json_encode($payload);
    
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonPayload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);


    if ($httpCode === 200) {
        $successKeyIndex = $originalIndex;
        break; // Success!
    }
    

    if ($httpCode === 429 && $originalIndex >= 0) {

        $keysData[$originalIndex]['status'] = 'rate_limited';
        $keysData[$originalIndex]['limit_reset_at'] = time() + 300; // 5 Minute Cooldown (Prevent rapid retries)
        $keysData[$originalIndex]['error_count']++;
        

        file_put_contents($jsonFile, json_encode($keysData, JSON_PRETTY_PRINT));
        
        file_put_contents('debug_engine_rotator.log', date('Y-m-d H:i:s') . " - Key limited: " . substr($currentKey, -4) . "\n", FILE_APPEND);
    }
    
    if ($httpCode === 503) {
        continue; // Server overload, try next key
    }
    

    if ($httpCode >= 400 && $httpCode < 500 && $httpCode !== 429) {
        break;
    }
}


if ($successKeyIndex >= 0 && isset($keysData[$successKeyIndex])) {
    $keysData[$successKeyIndex]['last_used'] = time();
    file_put_contents($jsonFile, json_encode($keysData, JSON_PRETTY_PRINT));
}


if ($curlError) {
    sendJson(['error' => 'Connection error: ' . $curlError], 500);
}

if ($httpCode !== 200) {
    $err = json_decode($response, true);
    $msg = $err['error']['message'] ?? "Unknown API Error (Code: $httpCode)";
    
    if ($httpCode === 429) {
        $msg = "All Access keys are currently rate limited. Please try again in 1 minute.";
    }
    
    sendJson(['error' => 'Service API Error: ' . $msg], 500);
}


$result = json_decode($response, true);


if (isset($result['promptFeedback']['blockReason'])) {
    sendJson(['error' => 'Image Blocked by Safety Filters: ' . $result['promptFeedback']['blockReason']], 500);
}


$text = $result['candidates'][0]['content']['parts'][0]['text'] ?? null;

if (!$text) {
    $errorMsg = isset($result['error']) ? $result['error']['message'] : 'Invalid structure';
    sendJson(['error' => 'Engine API Error: ' . $errorMsg, 'raw_response' => $result], 500);
}



$start = strpos($text, '{');
$end = strrpos($text, '}');

if ($start !== false && $end !== false) {
    $jsonCandidate = substr($text, $start, $end - $start + 1);
    $jsonResult = json_decode($jsonCandidate, true);
} else {

    $cleanText = preg_replace('/^```json\s*/i', '', $text);
    $cleanText = preg_replace('/^```\s*/', '', $cleanText);
    $cleanText = preg_replace('/\s*```$/', '', $cleanText);
    $jsonResult = json_decode($cleanText, true);
}

if (!$jsonResult) {

    sendJson(['error' => 'Failed to parse JSON', 'raw_text' => $text], 500);
}


$finalResult = [
    'category' => $jsonResult['category'] ?? 'anorganik', // Default fallback
    'item_name' => $jsonResult['item_name'] ?? '', // Specific item name
    'confidence' => $jsonResult['confidence'] ?? 0.8,
    'objects' => $jsonResult['objects'] ?? [], // New field
    'reason' => $jsonResult['reason'] ?? 'Analysis completed (Auto-parsed)'
];



$itemLower = strtolower($finalResult['item_name'] . ' ' . implode(' ', $finalResult['objects']));


$anorganikKeywords = ['besi', 'baja', 'logam', 'stainless', 'aluminium', 'kaca', 'glass', 'plastik', 'botol', 'kaleng', 'kabel', 'kawat', 'pipa', 'paralon', 'pralon', 'seng', 'paku', 'tutup', 'cap', 'lid', 'gelas', 'cup', 'sedotan', 'straw', 'sachet', 'bungkus', 'kresek', 'kantong', 'kertas', 'paper', 'kardus', 'karton', 'amplop'];
foreach ($anorganikKeywords as $keyword) {
    if (strpos($itemLower, $keyword) !== false) {
        $finalResult['category'] = 'anorganik';
        break; // Stop after first match
    }
}


$b3Keywords = ['baterai', 'aki', 'oli', 'minyak', 'kimia', 'racun', 'pestisida', 'obat', 'lampu', 'bohlam', 'elektronik', 'kabel data', 'charger'];
foreach ($b3Keywords as $keyword) {
    if (strpos($itemLower, $keyword) !== false) {
        $finalResult['category'] = 'b3';
        break;
    }
}



sendJson($finalResult);
?>
