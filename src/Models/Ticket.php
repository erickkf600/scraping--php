<?php

declare(strict_types=1);

namespace VividseatsSearch\Models;

/**
 * Modelo de una entrada de evento.
 */
final class Ticket
{
    public function __construct(
        public readonly string $seccion,
        public readonly string $fila,
        public readonly string $precio
    ) {
    }
}
