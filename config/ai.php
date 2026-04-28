<?php

return [
    "provider" => env("AI_PROVIDER", "gemini"),
    "api_key" => env("AI_API_KEY"),
    "model" => env("AI_MODEL", "gemini-2.5-flash"),
    "timeout" => (int) env("AI_TIMEOUT", 120),
];