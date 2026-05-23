<?php

declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

use VividseatsSearch\DTOs\ScrapeResult;
use VividseatsSearch\Services\TicketScraperService;
use GuzzleHttp\Client;

/**
 * Crea el servicio de scraping con una configuracion HTTP simple.
 */
function buildScraperService(): TicketScraperService
{
    $client = new Client([
        'headers' => [
            'User-Agent' => 'Mozilla/5.0 (compatible; PHP Guzzle Scraper)',
        ],
        'verify' => false,
        'timeout' => 20,
        'http_errors' => true,
    ]);

    return new TicketScraperService($client);
}

$url = trim((string) ($_GET['url'] ?? ''));
$result = null;

if ($url !== '') {
    $result = buildScraperService()->scrape($url);
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VividSeats Search</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-slate-100 text-slate-900">
    <main class="mx-auto w-full max-w-5xl px-4 py-10">
        <section class="rounded-2xl bg-white p-6 shadow-sm md:p-8">
            <h1 class="text-2xl font-bold">Buscador de entradas</h1>
            <p class="mt-2 text-sm text-slate-600">
                Ingresa la URL del evento y obtendras una tabla con seccion, fila y precio.
            </p>

            <form method="get" class="mt-6 grid gap-3 md:grid-cols-[1fr_auto]" id="search-form">
                <input
                    type="url"
                    name="url"
                    placeholder="https://www.vividseats.com/..."
                    value="<?= htmlspecialchars($url, ENT_QUOTES, 'UTF-8') ?>"
                    class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-slate-500"
                    required
                >
                <button
                    type="submit"
                    id="search-button"
                    class="rounded-xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white transition hover:bg-slate-700"
                >
                    <span id="button-label">Buscar</span>
                    <span id="button-loading" class="hidden items-center gap-2">
                        <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-90" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                        </svg>
                        Buscando...
                    </span>
                </button>
            </form>

            <?php if ($result instanceof ScrapeResult && !$result->isSuccess()): ?>
                <div class="mt-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    <?= htmlspecialchars($result->errorMessage, ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php endif; ?>
        </section>

        <?php if ($result instanceof ScrapeResult && $result->isSuccess()): ?>
            <section class="mt-6 rounded-2xl bg-white p-6 shadow-sm md:p-8">
                <?php if ($result->eventInfo !== ''): ?>
                    <div class="mb-4 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700">
                        <span class="font-semibold">Evento:</span>
                        <?= htmlspecialchars($result->eventInfo, ENT_QUOTES, 'UTF-8') ?>
                    </div>
                <?php endif; ?>

                <div class="mb-4 flex items-center justify-between gap-4">
                    <h2 class="text-lg font-semibold">Resultados encontrados</h2>
                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-700">
                        <?= count($result->tickets) ?> entradas
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
                            <?php foreach ($result->tickets as $ticket): ?>
                                <tr class="border-b border-slate-100 text-sm">
                                    <td class="px-3 py-3"><?= htmlspecialchars($ticket->seccion, ENT_QUOTES, 'UTF-8') ?></td>
                                    <td class="px-3 py-3"><?= htmlspecialchars($ticket->fila, ENT_QUOTES, 'UTF-8') ?></td>
                                    <td class="px-3 py-3 font-semibold"><?= htmlspecialchars($ticket->precio, ENT_QUOTES, 'UTF-8') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        <?php endif; ?>
    </main>

    <script>
        const form = document.getElementById('search-form');
        const button = document.getElementById('search-button');
        const label = document.getElementById('button-label');
        const loading = document.getElementById('button-loading');

        form?.addEventListener('submit', () => {
            if (!button || !label || !loading) {
                return;
            }

            button.disabled = true;
            button.classList.add('opacity-80', 'cursor-not-allowed');
            label.classList.add('hidden');
            loading.classList.remove('hidden');
            loading.classList.add('inline-flex');
        });
    </script>
</body>
</html>
