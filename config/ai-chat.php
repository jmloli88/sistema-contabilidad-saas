<?php

return [
    'rate_limit' => env('AI_CHAT_RATE_LIMIT', 10), // per hour
    'query_timeout' => env('AI_CHAT_QUERY_TIMEOUT', 5), // seconds
    'cache_ttl' => env('AI_CHAT_CACHE_TTL', 300), // 5 minutes
    'daily_token_budget' => env('AI_CHAT_DAILY_TOKEN_BUDGET', 50000),
    'allowed_tables' => ['repases', 'clinicas', 'examenes', 'gastos', 'repase_examenes', 'agendas'],
    'blocked_columns' => ['password', 'remember_token', 'stripe_id'],
];
