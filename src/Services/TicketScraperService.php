<?php

declare(strict_types=1);

namespace VividseatsSearch\Services;

use VividseatsSearch\Contracts\TicketScraperInterface;
use VividseatsSearch\DTOs\ScrapeResult;
use VividseatsSearch\Models\Ticket;
use DOMDocument;
use DOMElement;
use DOMXPath;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\GuzzleException;

/**
 * Servicio responsable de extraer entradas desde Vivid Seats.
 */
final class TicketScraperService implements TicketScraperInterface
{
    public function __construct(private readonly Client $httpClient)
    {
    }

    /**
     * Ejecuta el scraping y maneja escenarios de error esperados.
     */
    public function scrape(string $url): ScrapeResult
    {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return new ScrapeResult([], ScrapeResult::ERROR_INVALID_URL, 'La URL ingresada no es valida.');
        }

        try {
            $response = $this->httpClient->get($url);
            $html = (string) $response->getBody();
        } catch (ConnectException $e) {
            return new ScrapeResult([], ScrapeResult::ERROR_CONNECTION, 'No fue posible conectar con la pagina.');
        } catch (GuzzleException $e) {
            return new ScrapeResult([], ScrapeResult::ERROR_CONNECTION, 'Error de conexion o respuesta HTTP invalida.');
        } catch (\Throwable $e) {
            return new ScrapeResult([], ScrapeResult::ERROR_UNKNOWN, 'Ocurrio un error inesperado al acceder a la pagina.');
        }

        $eventInfo = $this->extractEventInfo($html);

        $nextData = $this->extractNextData($html);
        if ($nextData === null) {
            return new ScrapeResult([], ScrapeResult::ERROR_HTML_STRUCTURE, 'La estructura HTML es diferente y no contiene __NEXT_DATA__.');
        }

        $topDeals = $nextData['props']['pageProps']['initialTopDealListingsData']['data']['topDeals'] ?? null;
        if (!is_array($topDeals)) {
            return new ScrapeResult([], ScrapeResult::ERROR_HTML_STRUCTURE, 'La estructura del JSON de la pagina no coincide con la esperada.');
        }

        $tickets = [];
        foreach ($topDeals as $deal) {
            $seccion = trim((string) ($deal['section'] ?? ''));
            $fila = trim((string) ($deal['row'] ?? ''));
            $precio = $this->formatUsdPrice((string) ($deal['price'] ?? ''));

            if ($seccion === '' || $fila === '' || $precio === '') {
                continue;
            }

            $tickets[] = new Ticket($seccion, $fila, $precio);
        }

        if ($tickets === []) {
            return new ScrapeResult([], ScrapeResult::ERROR_NO_TICKETS, 'La pagina no tiene entradas disponibles en este momento.');
        }

        return new ScrapeResult($tickets, ScrapeResult::ERROR_NONE, '', $eventInfo);
    }

    /**
     * Obtiene y decodifica el bloque __NEXT_DATA__ de una pagina Next.js.
     *
     * @return array<string, mixed>|null
     */
    private function extractNextData(string $html): ?array
    {
        libxml_use_internal_errors(true);

        $dom = new DOMDocument();
        $loaded = $dom->loadHTML($html);
        if ($loaded === false) {
            return null;
        }

        $nextDataNode = $dom->getElementById('__NEXT_DATA__');
        if (!$nextDataNode instanceof DOMElement) {
            return null;
        }

        $json = trim($nextDataNode->textContent);
        $decoded = json_decode($json, true);

        return is_array($decoded) ? $decoded : null;
    }

    /**
     * Formatea precios en USD para mostrar en interfaz.
     */
    private function formatUsdPrice(string $price): string
    {
        $clean = str_replace([',', '$'], '', trim($price));
        if (!is_numeric($clean)) {
            return trim($price);
        }

        return '$' . number_format((float) $clean, 2, '.', '');
    }

    /**
     * Extrae informacion resumida del evento desde data-testid.
     */
    private function extractEventInfo(string $html): string
    {
        libxml_use_internal_errors(true);

        $dom = new DOMDocument();
        if ($dom->loadHTML($html) === false) {
            return '';
        }

        $xpath = new DOMXPath($dom);
        $node = $xpath->query('//*[@data-testid="production-details-source-text"]')?->item(0);
        if (!$node instanceof DOMElement) {
            return '';
        }

        return trim($node->textContent ?? '');
    }
}
