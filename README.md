# vividseatsSearch

Aplicacion web simple para un desafio tecnico junior/pleno.

El usuario ingresa una URL de evento de Vivid Seats y el sistema intenta extraer entradas disponibles para mostrar:

- Seccion
- Fila
- Precio

La interfaz esta hecha con Tailwind (CDN) y la logica de scraping esta separada por capas para facilitar lectura y mantenimiento.

## Estructura del proyecto

```text
/src
  /Contracts
    TicketScraperInterface.php
  /DTOs
    ScrapeResult.php
  /Models
    Ticket.php
  /Services
    TicketScraperService.php

index.php
composer.json
README.md
```

## Arquitectura (resumen)

- `Contracts`: define el contrato del scraper.
- `Services`: contiene la implementacion del scraping.
- `Models`: representa entidades de dominio (entrada).
- `DTOs`: transporta resultados y errores de forma clara.
- `index.php`: solo orquesta la entrada/salida (UI) y delega la logica al servicio.

## Escenarios de error manejados

1. **URL invalida**
2. **Pagina sin entradas**
3. **Falla de conexion**
4. **Estructura HTML/JSON diferente**

Cada caso devuelve un mensaje claro al usuario.

## Requisitos

- PHP 8.1+ (recomendado 8.2+)
- Composer

## Instalacion

1. Instalar dependencias:

```bash
composer install
```

## Ejecucion

Iniciar servidor local PHP:

```bash
php -S localhost:8000
```

Abrir en navegador:

```text
http://localhost:8000
```

## Como usar

1. Pegar una URL de evento de Vivid Seats (ejemplo abajo).
2. Hacer clic en **Buscar**.
3. Revisar tabla de resultados o mensaje de error.

## Ejemplos de URL

- URL valida de ejemplo:

```text
https://www.vividseats.com/hamilton-tickets-new-york-richard-rodgers-theatre-new-york-6-23-2026/production/6204797
```

- URL invalida de ejemplo:

```text
no-es-una-url
```

## Notas importantes

- Este scraper depende de la estructura actual de Vivid Seats.
- Si el sitio cambia su HTML/JSON, puede aparecer el error de estructura.
- En entorno local se usa `verify => false` para evitar bloqueos SSL comunes en Windows; en produccion se recomienda configurar certificados correctamente.
