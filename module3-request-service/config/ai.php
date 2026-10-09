<?php

return [
    'groq_key' => env('GROQ_API_KEY'),
    'model' => env('GROQ_MODEL', 'llama-3.3-70b-versatile'),
    'requests_per_minute' => 12,
];
