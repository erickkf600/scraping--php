<?php

declare(strict_types=1);

namespace Dever\Estudo\DTOs;

use Dever\Estudo\Models\Ticket;

/**
 * DTO del resultado del scraping.
 */
final class ScrapeResult
{
    public const ERROR_NONE = '';
    public const ERROR_INVALID_URL = 'INVALID_URL';
    public const ERROR_CONNECTION = 'CONNECTION_ERROR';
    public const ERROR_HTML_STRUCTURE = 'HTML_STRUCTURE_ERROR';
    public const ERROR_NO_TICKETS = 'NO_TICKETS';
    public const ERROR_UNKNOWN = 'UNKNOWN_ERROR';

    /**
     * @param array<int, Ticket> $tickets
     */
    public function __construct(
        public readonly array $tickets,
        public readonly string $errorCode = self::ERROR_NONE,
        public readonly string $errorMessage = '',
        public readonly string $eventInfo = ''
    ) {
    }

    /**
     * Indica si el proceso finalizo correctamente.
     */
    public function isSuccess(): bool
    {
        return $this->errorCode === self::ERROR_NONE;
    }
}
