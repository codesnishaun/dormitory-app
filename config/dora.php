<?php

return [
    'ai_enabled' => filter_var(env('DORA_AI_ENABLED', false), FILTER_VALIDATE_BOOL),
    'llm' => [
        'provider' => env('DORA_LLM_PROVIDER', 'gemini'),
        'model' => env('DORA_LLM_MODEL'),
        'api_key' => env('DORA_LLM_API_KEY'),
        'base_url' => env('DORA_LLM_BASE_URL') ?: 'https://generativelanguage.googleapis.com/v1beta',
        'timeout' => (int) env('DORA_LLM_TIMEOUT', 30),
    ],
    'embedding' => [
        'provider' => env('DORA_EMBEDDING_PROVIDER', 'gemini'),
        'model' => env('DORA_EMBEDDING_MODEL'),
        'api_key' => env('DORA_EMBEDDING_API_KEY'),
        'base_url' => env('DORA_EMBEDDING_BASE_URL')
            ?: (env('DORA_LLM_BASE_URL') ?: 'https://generativelanguage.googleapis.com/v1beta'),
        'timeout' => (int) env('DORA_EMBEDDING_TIMEOUT', 30),
        'dimensions' => filter_var(env('DORA_EMBEDDING_DIMENSIONS'), FILTER_VALIDATE_INT) ?: 0,
    ],
    'vector' => [
        'provider' => env('DORA_VECTOR_PROVIDER', 'qdrant'),
        'endpoint' => env('DORA_VECTOR_ENDPOINT', 'http://localhost:6333'),
        'api_key' => env('DORA_VECTOR_API_KEY'),
        'index' => env('DORA_VECTOR_INDEX', 'dora_knowledge'),
        'namespace' => env('DORA_VECTOR_NAMESPACE'),
    ],
    'retrieval_limit' => (int) env('DORA_RETRIEVAL_LIMIT', 5),
    'history_limit' => (int) env('DORA_CHAT_HISTORY_LIMIT', 12),
    'max_tool_rounds' => (int) env('DORA_MAX_TOOL_ROUNDS', 3),
];
