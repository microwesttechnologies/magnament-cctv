<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Cotización {{ $quotation->code() }}</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #1e293b; margin: 0; padding: 24px; }
        .header { border-bottom: 2px solid #0f172a; padding-bottom: 12px; margin-bottom: 24px; }
        .brand { font-size: 20px; font-weight: 700; }
        .brand-sub { color: #64748b; margin-top: 4px; }
        .meta td { padding: 2px 8px 2px 0; }
        h1 { font-size: 16px; margin: 0 0 8px; }
        table.lines { width: 100%; border-collapse: collapse; margin-top: 16px; }
        table.lines th, table.lines td { border: 1px solid #cbd5e1; padding: 8px; text-align: left; }
        table.lines th { background: #f1f5f9; font-size: 11px; text-transform: uppercase; }
        .num { text-align: right; white-space: nowrap; }
        .totals { width: 280px; margin-left: auto; margin-top: 16px; border-collapse: collapse; }
        .totals td { padding: 6px 8px; }
        .totals .label { color: #64748b; }
        .totals .grand { border-top: 2px solid #0f172a; font-weight: 700; }
        .footer { margin-top: 40px; padding-top: 12px; border-top: 1px solid #e2e8f0; color: #94a3b8; font-size: 10px; }
        .description { margin-top: 12px; line-height: 1.45; white-space: pre-wrap; }
    </style>
</head>
<body>
    <div class="header">
        <div class="brand">CCTV Manager</div>
        <div class="brand-sub">Documento comercial · formato genérico profesional</div>
    </div>

    <h1>Cotización {{ $quotation->code() }}</h1>

    <table class="meta">
        <tr><td><strong>Proyecto</strong></td><td>{{ $projectName }}</td></tr>
        <tr><td><strong>Estado</strong></td><td>{{ $quotation->status()->value }}</td></tr>
        <tr><td><strong>Fecha</strong></td><td>{{ $quotation->createdAt()->format('Y-m-d') }}</td></tr>
        <tr><td><strong>IVA aplicado</strong></td><td>{{ $quotation->vatRate()->percent() }}%</td></tr>
    </table>

    <div>
        <strong>Descripción del trabajo</strong>
        <div class="description">{{ $quotation->workDescription() }}</div>
    </div>

    <table class="lines">
        <thead>
            <tr>
                <th>#</th>
                <th>Producto / servicio</th>
                <th>Marca</th>
                <th>Serie</th>
                <th class="num">Cant.</th>
                <th class="num">P. unitario</th>
                <th class="num">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($lines as $index => $line)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $line->productName() }}</td>
                    <td>{{ $line->brand() ?? '—' }}</td>
                    <td>{{ $line->serial() ?? '—' }}</td>
                    <td class="num">{{ $line->quantity() }}</td>
                    <td class="num">{{ $line->unitPrice()->amount() }}</td>
                    <td class="num">{{ $line->lineSubtotal()->amount() }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals">
        <tr><td class="label">Subtotal</td><td class="num">{{ $quotation->subtotal()->amount() }}</td></tr>
        <tr><td class="label">IVA ({{ $quotation->vatRate()->percent() }}%)</td><td class="num">{{ $quotation->vatAmount()->amount() }}</td></tr>
        <tr class="grand"><td>Total</td><td class="num">{{ $quotation->total()->amount() }}</td></tr>
    </table>

    <div class="footer">Documento generado automáticamente. Layout reutilizable: pdf.layouts.professional.</div>
</body>
</html>
