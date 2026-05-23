<?php

declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

function formatUsdPrice(string $price): string
{
    $clean = str_replace([',', '$'], '', trim($price));

    if (!is_numeric($clean)) {
        return trim($price);
    }

    return '$' . number_format((float) $clean, 2, '.', '');
}

/**
 * @return array<int, array{setor:string,fila:string,preco:string}>
 */
function scrapeTickets(string $pageUrl, Client $client): array
{
    $response = $client->get($pageUrl);
    $html = (string) $response->getBody();

    libxml_use_internal_errors(true);

    $dom = new DOMDocument();
    $dom->loadHTML($html);

    $tickets = [];

    $nextDataNode = $dom->getElementById('__NEXT_DATA__');
    if ($nextDataNode instanceof DOMElement) {
        $json = trim($nextDataNode->textContent);
        $decoded = json_decode($json, true);

        $topDeals = $decoded['props']['pageProps']['initialTopDealListingsData']['data']['topDeals'] ?? null;

        if (is_array($topDeals)) {
            foreach ($topDeals as $deal) {
                $section = trim((string) ($deal['section'] ?? ''));
                $row = trim((string) ($deal['row'] ?? ''));
                $price = formatUsdPrice((string) ($deal['price'] ?? ''));

                if ($section === '' || $row === '' || $price === '') {
                    continue;
                }

                $tickets[] = [
                    'setor' => $section,
                    'fila' => $row,
                    'preco' => $price,
                ];
            }
        }
    }

    return $tickets;
}

$url = '';
$error = '';
$tickets = [];

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $url = trim((string) ($_GET['url'] ?? ''));

    if ($url === '') {
        $error = '';
    } elseif (!filter_var($url, FILTER_VALIDATE_URL)) {
        $error = 'La URL ingresada no es valida.';
    } else {
        try {
            $client = new Client([
                'headers' => [
                    'User-Agent' => 'Mozilla/5.0 (compatible; PHP Guzzle Scraper)',
                ],
                'verify' => false,
                'timeout' => 20,
                'http_errors' => true,
            ]);

            $tickets = scrapeTickets($url, $client);

            if ($tickets === []) {
                $error = 'No se encontraron entradas. La estructura de la pagina puede haber cambiado o no contener los datos esperados.';
            }
        } catch (GuzzleException $e) {
            $error = 'Error al acceder a la pagina: ' . $e->getMessage();
        } catch (Throwable $e) {
            $error = 'Error inesperado: ' . $e->getMessage();
        }
    }
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scraping de Entradas</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-slate-100 text-slate-900">
    <main class="mx-auto w-full max-w-5xl px-4 py-10">
        <section class="rounded-2xl bg-white p-6 shadow-sm md:p-8">
            <h1 class="text-2xl font-bold">Buscar entradas de Vivid Seats</h1>
            <p class="mt-2 text-sm text-slate-600">
                Pega la URL de la pagina del evento y muestra seccion, fila y precio en una tabla.
            </p>

            <form method="get" class="mt-6 grid gap-3 md:grid-cols-[1fr_auto]">
                <input
                    type="url"
                    name="url"
                    placeholder="https://www.vividseats.com/..."
                    value="<?= htmlspecialchars($url, ENT_QUOTES, 'UTF-8') ?>"
                    class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none ring-0 transition focus:border-slate-500"
                    required
                >
                <button
                    type="submit"
                    class="rounded-xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white transition hover:bg-slate-700"
                >
                    Buscar
                </button>
            </form>

            <?php if ($error !== ''): ?>
                <div class="mt-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php endif; ?>
        </section>

        <?php if ($tickets !== []): ?>
            <section class="mt-6 rounded-2xl bg-white p-6 shadow-sm md:p-8">
                <div class="mb-4 flex items-center justify-between gap-4">
                    <h2 class="text-lg font-semibold">Resultados encontrados</h2>
                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-700">
                        <?= count($tickets) ?> entradas
                    </span>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full border-collapse">
                        <thead>
                            <tr class="border-b border-slate-200 text-left text-xs uppercase tracking-wide text-slate-500">
                                <th class="px-3 py-3">Seccion</th>
                                <th class="px-3 py-3">Fila</th>
                                <th class="px-3 py-3">Precio</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tickets as $ticket): ?>
                                <tr class="border-b border-slate-100 text-sm">
                                    <td class="px-3 py-3"><?= htmlspecialchars($ticket['setor'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td class="px-3 py-3"><?= htmlspecialchars($ticket['fila'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td class="px-3 py-3 font-semibold"><?= htmlspecialchars($ticket['preco'], ENT_QUOTES, 'UTF-8') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        <?php endif; ?>
    </main>
</body>
</html>
