<?php

namespace App\Contracts\Chat;

use App\Data\Chat\ChatCompletion;
use App\Data\Chat\ChatPrompt;

interface AIStreamingProviderInterface
{
    /**
     * @param  callable(string): void  $onText
     */
    public function generateStream(ChatPrompt $prompt, callable $onText): ChatCompletion;
}
