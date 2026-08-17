<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Cotización {{ $quotation->code() }}</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #0f172a; margin: 0; padding: 28px 32px; }
        .header-table { width: 100%; border-collapse: collapse; border-bottom: 2px solid #0f172a; padding-bottom: 8px; margin-bottom: 18px; }
        .company-cell { vertical-align: top; padding: 0 16px 12px 0; }
        .logo-cell { vertical-align: top; text-align: right; width: 180px; padding: 0 0 12px 12px; }
        .logo-company { max-width: 160px; max-height: 72px; width: auto; height: auto; }
        .brand { font-size: 18px; font-weight: 700; letter-spacing: 0.01em; }
        .company-line { color: #475569; margin-top: 3px; font-size: 11px; }
        .doc-title { text-align: center; font-size: 16px; font-weight: 700; margin: 10px 0 16px; letter-spacing: 0.04em; }
        .meta { width: 100%; border-collapse: collapse; margin-bottom: 18px; }
        .meta td { padding: 3px 12px 3px 0; vertical-align: top; }
        .meta .label { color: #64748b; width: 110px; }
        h2 { font-size: 11px; margin: 18px 0 6px; text-transform: uppercase; letter-spacing: 0.08em; color: #0f172a; border-bottom: 1px solid #e2e8f0; padding-bottom: 4px; }
        .section-kicker { color: #64748b; font-size: 10px; margin-bottom: 4px; }
        .description { line-height: 1.5; white-space: pre-wrap; }
        table.lines { width: 100%; border-collapse: collapse; margin-top: 8px; }
        table.lines th, table.lines td { border: 1px solid #cbd5e1; padding: 8px; text-align: left; }
        table.lines th { background: #f1f5f9; font-size: 10px; text-transform: uppercase; letter-spacing: 0.04em; }
        .num { text-align: right; white-space: nowrap; }
        .totals { width: 280px; margin-left: auto; margin-top: 16px; border-collapse: collapse; }
        .totals td { padding: 6px 8px; }
        .totals .label { color: #64748b; }
        .totals .grand { border-top: 2px solid #0f172a; font-weight: 700; }
        .footer { margin-top: 36px; padding-top: 10px; border-top: 1px solid #e2e8f0; color: #94a3b8; font-size: 10px; }
        .muted { color: #64748b; }
    </style>
</head>
<body>
    @php
        $companyName = trim((string) ($company['name'] ?? '')) !== '' ? $company['name'] : 'CCTV Manager';
    @endphp
    <table class="header-table">
        <tr>
            <td class="company-cell">
                <div class="brand">{{ $companyName }}</div>
                @if (! empty($company['nit']))
                    <div class="company-line">NIT: {{ $company['nit'] }}</div>
                @endif
                @if (! empty($company['phone']))
                    <div class="company-line">Teléfono: {{ $company['phone'] }}</div>
                @endif
                @if (! empty($company['email']))
                    <div class="company-line">Correo: {{ $company['email'] }}</div>
                @endif
            </td>
            <td class="logo-cell">
                @if (! empty($logoDataUri))
                    <img class="logo-company" src="{{ $logoDataUri }}" alt="Logo de la empresa">
                @endif
            </td>
        </tr>
    </table>

    <div class="doc-title">COTIZACIÓN {{ $quotation->code() }}</div>

    <table class="meta">
        <tr><td class="label">Fecha</td><td>{{ $quotation->createdAt()->format('Y-m-d') }}</td></tr>
        <tr><td class="label">Proyecto</td><td>{{ $projectName }}</td></tr>
        <tr><td class="label">Cliente</td><td>{{ $projectName }}</td></tr>
        <tr><td class="label">Estado</td><td>{{ $quotation->status()->value }}</td></tr>
        <tr><td class="label">IVA aplicado</td><td>{{ $quotation->vatRate()->percent() }}%</td></tr>
    </table>

    <h2>Solicitud</h2>
    <div class="section-kicker">¿Qué necesita el cliente?</div>
    <div class="description">{{ $quotation->workDescription() }}</div>

    <h2>Solución diseñada</h2>
    <div class="section-kicker">¿Qué solución proponemos?</div>
    <div class="description">
        @if ($quotation->designedSolution() !== '')
            {{ $quotation->designedSolution() }}
        @else
            <span class="muted">Sin solución diseñada registrada.</span>
        @endif
    </div>

    <h2>Propuesta económica</h2>
    <table class="lines">
        <thead>
            <tr>
                <th>Producto</th>
                <th class="num">Cantidad</th>
                <th class="num">Valor unitario</th>
                <th class="num">Valor subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($lines as $line)
                <tr>
                    <td>{{ $line->productName() }}</td>
                    <td class="num">{{ $line->quantity() }}</td>
                    <td class="num">{{ $line->unitPrice()->amount() }}</td>
                    <td class="num">{{ $line->lineSubtotal()->amount() }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <h2>Resumen económico</h2>
    <table class="totals">
        <tr><td class="label">Subtotal</td><td class="num">{{ $quotation->subtotal()->amount() }}</td></tr>
        <tr><td class="label">IVA ({{ $quotation->vatRate()->percent() }}%)</td><td class="num">{{ $quotation->vatAmount()->amount() }}</td></tr>
        <tr class="grand"><td>Total</td><td class="num">{{ $quotation->total()->amount() }}</td></tr>
    </table>

    <div class="footer">Documento generado automáticamente. Layout reutilizable: pdf.layouts.professional.</div>
</body>
</html>
