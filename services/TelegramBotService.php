<?php

namespace app\services;

use Yii;

class TelegramBotService
{
    public function isConfigured(): bool
    {
        return $this->getBotToken() !== '';
    }

    public function sendMessage(int $chatId, string $text, ?string $parseMode = null): bool
    {
        $token = $this->getBotToken();
        if ($token === '' || $chatId <= 0) {
            return false;
        }

        $payload = [
            'chat_id' => $chatId,
            'text' => $text,
            'disable_web_page_preview' => true,
        ];
        if ($parseMode !== null) {
            $payload['parse_mode'] = $parseMode;
        }

        return $this->apiRequest($token, 'sendMessage', $payload);
    }

    public function getBotUsername(): ?string
    {
        $token = $this->getBotToken();
        if ($token === '') {
            return null;
        }
        $cacheKey = 'telegram_bot_username';
        $cached = Yii::$app->cache->get($cacheKey);
        if (is_string($cached) && $cached !== '') {
            return $cached;
        }

        $url = 'https://api.telegram.org/bot' . $token . '/getMe';
        $response = $this->httpGet($url);
        if ($response === false) {
            return Yii::$app->params['telegramBotUsername'] ?? null;
        }
        $data = json_decode($response, true);
        $username = $data['result']['username'] ?? null;
        if ($username) {
            Yii::$app->cache->set($cacheKey, $username, 86400);
        }
        return $username ?: (Yii::$app->params['telegramBotUsername'] ?? null);
    }

    private function getBotToken(): string
    {
        return trim((string) (Yii::$app->params['telegramBotToken'] ?? getenv('TELEGRAM_BOT_TOKEN') ?: ''));
    }

    private function apiRequest(string $token, string $method, array $payload): bool
    {
        $url = 'https://api.telegram.org/bot' . $token . '/' . $method;
        $body = json_encode($payload, JSON_UNESCAPED_UNICODE);
        $response = $this->httpPost($url, $body);
        if ($response === false) {
            Yii::error("Telegram API {$method} request failed", __METHOD__);
            return false;
        }

        $data = json_decode($response, true);
        if (!($data['ok'] ?? false)) {
            Yii::error('Telegram API error: ' . ($data['description'] ?? $response), __METHOD__);
            return false;
        }
        return true;
    }

    /**
     * @return string|false
     */
    private function httpGet(string $url)
    {
        return $this->httpRequest('GET', $url);
    }

    /**
     * @return string|false
     */
    private function httpPost(string $url, string $body)
    {
        return $this->httpRequest('POST', $url, $body);
    }

    /**
     * @return string|false
     */
    private function httpRequest(string $method, string $url, ?string $body = null)
    {
        if (function_exists('curl_init')) {
            return $this->httpRequestCurl($method, $url, $body);
        }

        $options = [
            'http' => [
                'method' => $method,
                'header' => "Content-Type: application/json\r\n",
                'timeout' => 8,
                'ignore_errors' => true,
            ],
        ];
        if ($body !== null) {
            $options['http']['content'] = $body;
        }

        $proxy = $this->getProxy();
        if ($proxy !== '' && stripos($proxy, 'socks') !== 0) {
            $options['http']['proxy'] = $proxy;
            $options['http']['request_fulluri'] = true;
        }

        $context = stream_context_create($options);
        return @file_get_contents($url, false, $context);
    }

    /**
     * @return string|false
     */
    private function httpRequestCurl(string $method, string $url, ?string $body = null)
    {
        $ch = curl_init($url);
        if ($ch === false) {
            return false;
        }

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 8,
            CURLOPT_CONNECTTIMEOUT => 8,
            CURLOPT_CUSTOMREQUEST => $method,
        ]);

        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        }

        $this->applyCurlProxy($ch, $this->getProxy());

        $response = curl_exec($ch);
        if ($response === false) {
            Yii::error('Telegram curl error: ' . curl_error($ch), __METHOD__);
            curl_close($ch);
            return false;
        }
        curl_close($ch);
        return $response;
    }

    private function getProxy(): string
    {
        return trim((string) (
            Yii::$app->params['telegramProxy']
            ?? getenv('TELEGRAM_PROXY')
            ?: ''
        ));
    }

    private function applyCurlProxy($ch, string $proxy): void
    {
        if ($proxy === '') {
            return;
        }

        if (preg_match('#^socks5h?://(.+)$#i', $proxy, $m)) {
            curl_setopt($ch, CURLOPT_PROXYTYPE, defined('CURLPROXY_SOCKS5_HOSTNAME') ? CURLPROXY_SOCKS5_HOSTNAME : 7);
            curl_setopt($ch, CURLOPT_PROXY, $m[1]);
            return;
        }

        if (preg_match('#^socks4://(.+)$#i', $proxy, $m)) {
            curl_setopt($ch, CURLOPT_PROXYTYPE, defined('CURLPROXY_SOCKS4A') ? CURLPROXY_SOCKS4A : 6);
            curl_setopt($ch, CURLOPT_PROXY, $m[1]);
            return;
        }

        curl_setopt($ch, CURLOPT_PROXY, $proxy);
    }
}
