<?php

namespace App\Contracts\Chat;

use App\Data\Chat\ChatCompletion;
use App\Data\Chat\ChatPrompt;

interface AIProviderInterface
{
    public function isConfigured(): bool;

    public function generate(ChatPrompt $prompt): ChatCompletion;
}
