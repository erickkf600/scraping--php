<?php

declare(strict_types=1);

namespace Dever\Estudo\Contracts;

use Dever\Estudo\DTOs\ScrapeResult;

/**
 * Contrato para servicios que extraen entradas desde una URL.
 */
interface TicketScraperInterface
{
    /**
     * Ejecuta el scraping de una pagina y devuelve un resultado estructurado.
     */
    public function scrape(string $url): ScrapeResult;
}
