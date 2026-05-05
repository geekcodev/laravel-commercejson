<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Http\Client\Exceptions;

/**
 * Исключение для ошибок аутентификации (401, 403)
 */
class AuthenticationException extends HttpClientException {}
