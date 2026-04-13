<?php

define('AI_PROVIDER', 'gemini');
define('AI_BASE_URL', 'https://generativelanguage.googleapis.com/v1beta/models');

define('AI_KEY', 'AIzaSyCl4Y3cfCRsTIS1enQFXDQKVpGcn7Usl7U'); // Default/Fallback Key

define('AI_KEYS', [
    'AIzaSyBzayFvPgJ5Fdguv9CbIF5xz2WpvqavIek', // Key 1 (Primary - Updated)
    'AIzaSyBBQ1FjhZnSgDwCKc9fEO4fP8vVAmdWZLI',
    'AIzaSyBjdI1SVohj6buPd1HfpVH0GAnO4jTWAdE',
    'AIzaSyBWjspU3auwbsk0EmhY0hmTHeo04JVIG04',
    'AIzaSyCk9YIKog3L4usX_kdd27ORW0Zfsqivxjg',
    'AIzaSyDwbfMBrEm6c60CnnZo7zMG74-PlN9xB7Y',
    'AIzaSyCcH1xyom8Mj4gKaVkCcu_i4XBtjixYwkA',
    'AIzaSyDweT0zlCQ4u7Ti7-koUnbjdrwQcRthihc',
    'PASTE_YOUR_KEY_9_HERE',
    'PASTE_YOUR_KEY_10_HERE'
]);

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
