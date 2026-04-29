<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Http\Traits;

use Illuminate\Support\Str;

/**
 * Trait для управления идемпотентностью запросов
 *
 * CommerceJSON API поддерживает заголовок X-Idempotency-Key
 * для защиты от дублирования операций при retry
 */
trait HandlesIdempotency
{
    protected ?string $idempotencyKey = null;

    protected array $idempotencyKeysStack = [];

    /**
     * Установить ключ идемпотентности для следующего запроса
     */
    public function withIdempotencyKey(string $key): self
    {
        $this->idempotencyKey = $key;

        return $this;
    }

    /**
     * Сгенерировать и установить ключ идемпотентности
     */
    public function withGeneratedIdempotencyKey(): self
    {
        $this->idempotencyKey = $this->generateIdempotencyKey();

        return $this;
    }

    /**
     * Получить текущий ключ идемпотентности
     */
    public function getIdempotencyKey(): ?string
    {
        return $this->idempotencyKey;
    }

    /**
     * Сбросить ключ идемпотентности
     */
    public function resetIdempotencyKey(): self
    {
        $this->idempotencyKey = null;

        return $this;
    }

    /**
     * Сохранить ключ в стек (для отслеживания)
     */
    protected function pushIdempotencyKey(string $key): void
    {
        $this->idempotencyKeysStack[] = [
            'key' => $key,
            'timestamp' => time(),
        ];

        // Очищаем старые ключи (старше 24 часов)
        $this->cleanupIdempotencyKeys();
    }

    /**
     * Очистить старые ключи идемпотентности
     */
    protected function cleanupIdempotencyKeys(): void
    {
        $oneDayAgo = time() - (24 * 60 * 60);

        $this->idempotencyKeysStack = array_filter(
            $this->idempotencyKeysStack,
            fn ($entry) => $entry['timestamp'] > $oneDayAgo
        );
    }

    /**
     * Сгенерировать уникальный ключ идемпотентности
     *
     * Формат: {timestamp}-{random}-{counter}
     */
    protected function generateIdempotencyKey(): string
    {
        return sprintf(
            '%s-%s-%s',
            date('YmdHis'),
            Str::random(16),
            Str::uuid()
        );
    }

    /**
     * Проверить, используется ли уже ключ идемпотентности
     */
    public function isIdempotencyKeyUsed(string $key): bool
    {
        foreach ($this->idempotencyKeysStack as $entry) {
            if ($entry['key'] === $key) {
                return true;
            }
        }

        return false;
    }
}
