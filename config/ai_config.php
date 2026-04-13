<?php

define('AI_PROVIDER', 'gemini');
define('AI_BASE_URL', 'https://generativelanguage.googleapis.com/v1beta/models');

define('AI_KEY', $_ENV['AI_DEFAULT_KEY'] ?? 'AIzaSy_YOUR_FALLBACK_KEY'); // Default/Fallback Key

$envKeys = [];
for ($i = 1; $i <= 10; $i++) {
    if (!empty($_ENV['AI_KEY_' . $i])) {
        $envKeys[] = $_ENV['AI_KEY_' . $i];
    }
}

// Fallback jika envKeys kosong
if (empty($envKeys)) {
    $envKeys = [$_ENV['AI_DEFAULT_KEY'] ?? 'PASTE_YOUR_KEY_HERE'];
}

define('AI_KEYS', $envKeys);

define('AI_MODEL', 'gemini-flash-latest');


define('AI_SYSTEM_PROMPT', "
You are a waste classification expert.
Classify the image into exactly one of these categories34:
1. 'organik' (biodegradable: food, leaves, wood, sisa makanan)
2. 'anorganik' (recyclable: plastic, paper, kertas, kardus, metal, iron, steel, besi, logam, baja, kaleng, stainless, glass, kaca, cans)
3. 'b3' (hazardous: electronics, batteries, chemicals, bulbs)

Return ONLY standard JSON:
{
  \"category\": \"organik\" | \"anorganik\" | \"b3\",
  \"item_name\": \"Specific item name in Indonesian (e.g. Botol Plastik, Kulit Pisang)\",
  \"confidence\": 0.95,
  \"objects\": [\"objek1 (Indonesian)\", \"objek2 (Indonesian)\"],
  \"reason\": \"Reason in Indonesian (max 1 sentence)\"
}
");
?>
