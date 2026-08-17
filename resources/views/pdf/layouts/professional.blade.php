<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>@yield('title', 'Documento')</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            color: #1e293b;
            margin: 0;
            padding: 24px;
        }
        .header {
            border-bottom: 2px solid #0f172a;
            padding-bottom: 12px;
            margin-bottom: 24px;
        }
        .brand {
            font-size: 20px;
            font-weight: 700;
            letter-spacing: 0.02em;
        }
        .brand-sub {
            color: #64748b;
            margin-top: 4px;
        }
        .meta {
            margin: 16px 0 24px;
        }
        .meta td {
            padding: 2px 8px 2px 0;
            vertical-align: top;
        }
        h1 {
            font-size: 16px;
            margin: 0 0 8px;
        }
        table.lines {
            width: 100%;
            border-collapse: collapse;
            margin-top: 16px;
        }
        table.lines th,
        table.lines td {
            border: 1px solid #cbd5e1;
            padding: 8px;
            text-align: left;
        }
        table.lines th {
            background: #f1f5f9;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }
        .num { text-align: right; white-space: nowrap; }
        .totals {
            width: 280px;
            margin-left: auto;
            margin-top: 16px;
            border-collapse: collapse;
        }
        .totals td {
            padding: 6px 8px;
        }
        .totals .label { color: #64748b; }
        .totals .grand {
            border-top: 2px solid #0f172a;
            font-weight: 700;
            font-size: 13px;
        }
        .footer {
            margin-top: 40px;
            padding-top: 12px;
            border-top: 1px solid #e2e8f0;
            color: #94a3b8;
            font-size: 10px;
        }
        .description {
            margin-top: 12px;
            line-height: 1.45;
            white-space: pre-wrap;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="brand">CCTV Manager</div>
        <div class="brand-sub">Documento comercial</div>
    </div>

    @yield('content')

    <div class="footer">
        Documento generado automáticamente. Formato genérico profesional reutilizable.
    </div>
</body>
</html>
