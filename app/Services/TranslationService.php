<?php

namespace App\Services;

use App\Models\Message;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class TranslationService
{
    // Nhận diện ngôn ngữ, trả mã ISO 639-1 (vi, en, ko...) hoặc null nếu lỗi
    public function detect(string $text): ?string
    {
        $result = $this->ask(
            "Detect the language of the chat message below. "
            . 'Reply only JSON: {"lang": "<ISO 639-1 code>"}.'
            . "\n\nMessage:\n" . $text
        );

        $lang = strtolower(trim($result['lang'] ?? ''));

        return preg_match('/^[a-z]{2}$/', $lang) ? $lang : null;
    }

    // Dịch sang ngôn ngữ đích, trả null nếu lỗi
    public function translate(string $text, string $target): ?string
    {
        $result = $this->ask(
            "Translate the chat message below into the language with ISO 639-1 code \"{$target}\". "
            . "Context: chat between a motorbike rental shop in Da Nang, Vietnam and its customer; "
            . "Vietnamese \"xe\" means motorbike/scooter, not car. "
            . "Keep the tone, emojis, names, numbers and license plates unchanged. "
            . 'Reply only JSON: {"text": "<translation>"}.'
            . "\n\nMessage:\n" . $text
        );

        $translated = trim($result['text'] ?? '');

        return $translated !== '' ? $translated : null;
    }

    // Lấy bản dịch của 1 tin nhắn: dùng bản đã lưu nếu có, chưa có thì dịch rồi lưu lại
    public function forMessage(Message $message, string $target): string
    {
        if ($message->lang === null || $message->lang === $target) {
            return $message->body;
        }

        $saved = $message->translations()->where('lang', $target)->value('body');
        if ($saved !== null) {
            return $saved;
        }

        $translated = $this->translate($message->body, $target);
        if ($translated === null) {
            return $message->body;
        }

        $message->translations()->create(['lang' => $target, 'body' => $translated]);

        return $translated;
    }

    // Gọi Gemini, yêu cầu trả JSON; lỗi thì ghi log và trả mảng rỗng
    private function ask(string $prompt): array
    {
        $key = config('services.gemini.key');
        if (! $key) {
            return [];
        }

        $model = config('services.gemini.model');

        try {
            $response = Http::timeout(10)
                ->withHeaders(['x-goog-api-key' => $key])
                ->post("https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent", [
                    'contents' => [['parts' => [['text' => $prompt]]]],
                    'generationConfig' => [
                        'temperature' => 0,
                        'responseMimeType' => 'application/json',
                    ],
                ]);

            if ($response->failed()) {
                Log::warning('Gemini lỗi', ['status' => $response->status(), 'body' => $response->json('error.message')]);
                return [];
            }

            $json = $response->json('candidates.0.content.parts.0.text');

            return json_decode($json ?? '', true) ?: [];
        } catch (Throwable $e) {
            Log::warning('Gemini không kết nối được', ['error' => $e->getMessage()]);
            return [];
        }
    }
}