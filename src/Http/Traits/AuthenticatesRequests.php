<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Http\Traits;

/**
 * Trait для управления авторизацией в CommerceJSON API
 */
trait AuthenticatesRequests
{
    protected string $authType;

    protected string $authToken;

    protected ?string $login = null;

    protected ?string $password = null;

    /**
     * Построить заголовок Authorization
     */
    protected function buildAuthHeader(): ?string
    {
        return match ($this->authType) {
            'bearer' => $this->buildBearerHeader(),
            'basic' => $this->buildBasicHeader(),
            default => null,
        };
    }

    /**
     * Bearer токен
     */
    protected function buildBearerHeader(): ?string
    {
        if (empty($this->authToken)) {
            return null;
        }

        return 'Bearer '.$this->authToken;
    }

    /**
     * Basic Auth
     */
    protected function buildBasicHeader(): ?string
    {
        if (empty($this->login) || empty($this->password)) {
            return null;
        }

        return 'Basic '.base64_encode("{$this->login}:{$this->password}");
    }

    /**
     * Установить Bearer токен
     */
    public function setAuthToken(string $token): self
    {
        $this->authToken = $token;
        $this->authType = 'bearer';

        return $this;
    }

    /**
     * Установить Basic Auth credentials
     */
    public function setBasicAuth(string $login, string $password): self
    {
        $this->login = $login;
        $this->password = $password;
        $this->authType = 'basic';

        return $this;
    }

    /**
     * Установить Session токен (полученный из handshake)
     */
    public function setSessionToken(string $sessionToken): self
    {
        // Session token используется в заголовке X-Session-Token
        // Реализуется в buildHeaders() основного класса
        $this->sessionToken = $sessionToken;

        return $this;
    }
}
