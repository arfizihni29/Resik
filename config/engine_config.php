<?php

define('ENGINE_PROVIDER', 'engine_v1');
define('ENGINE_BASE_URL', 'https://generativelanguage.googleapis.com/v1beta/models');

define('ENGINE_KEY', $_ENV['ENGINE_DEFAULT_KEY'] ?? 'KEY_FALLBACK'); 

$envKeys = [];
for ($i = 1; $i <= 10; $i++) {
    if (!empty($_ENV['ENGINE_KEY_' . $i])) {
        $envKeys[] = $_ENV['ENGINE_KEY_' . $i];
    }
}


if (empty($envKeys)) {
    $envKeys = [$_ENV['ENGINE_DEFAULT_KEY'] ?? 'KEY_UNDEFINED'];
}

define('ENGINE_KEYS', $envKeys);

define('ENGINE_MODEL', 'engine-model-v1');


define('ENGINE_SYSTEM_PROMPT', "
You are a waste classification expert.
Classify the image into exactly one of these categories:
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
