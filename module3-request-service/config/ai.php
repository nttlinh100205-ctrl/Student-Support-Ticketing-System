<?php

return [
    'groq_key' => env('GROQ_API_KEY'),
    'model' => env('GROQ_MODEL', 'openai/gpt-oss-20b'),
    'requests_per_minute' => 12,
];
