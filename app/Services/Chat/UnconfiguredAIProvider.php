<?php

namespace App\Services\Chat;

use App\Contracts\Chat\AIProviderInterface;
use App\Data\Chat\ChatCompletion;
use App\Data\Chat\ChatPrompt;
use App\Exceptions\Chat\ProviderNotConfiguredException;

class UnconfiguredAIProvider implements AIProviderInterface
{
    public function isConfigured(): bool
    {
        return false;
    }

    public function generate(ChatPrompt $prompt): ChatCompletion
    {
        throw new ProviderNotConfiguredException('The DORA language model provider is not configured.');
    }
}
