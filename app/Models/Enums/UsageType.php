<?php

namespace App\Models\Enums;

enum UsageType: string
{
    case AdaEmbedding = 'ada_embedding';
    case Gpt35TurboChat = 'gpt_35_turbo_chat';
    case Gpt4ChatPrompt = 'gpt_4_chat_prompt';
    case Gpt4ChatResponse = 'gpt_4_chat_response';
    case SubscriptionCredits = 'subscription_credits';
    case ManualCredits = 'manual_credits';

    public static function adaPer1000Tokens(): float
    {
        return 0.0004;
    }

    public static function gpt35TurboChatPer1000Tokens(): float
    {
        return 0.002;
    }

    public static function gpt4ChatPer1000PromptTokens(): float
    {
        return 0.03;
    }

    public static function gpt4ChatPer1000ResponseTokens(): float
    {
        return 0.06;
    }

    public function cost(int $tokens): float
    {
        return match ($this) {
            self::AdaEmbedding => self::adaPer1000Tokens(),
            self::Gpt35TurboChat => self::gpt35TurboChatPer1000Tokens(),
            self::Gpt4ChatPrompt => self::gpt4ChatPer1000PromptTokens(),
            self::Gpt4ChatResponse => self::gpt4ChatPer1000ResponseTokens(),
            default => throw new \Exception('Invalid usage type'),
        } / 100 * $tokens;
    }
}
