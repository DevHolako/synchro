<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>@yield('title')</title>
    <style>
        @page { margin: 18mm 16mm; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 10.5pt; color: #111; }
        .header { border-bottom: 2px solid #111; padding-bottom: 6pt; margin-bottom: 14pt; }
        .institution { font-size: 13pt; font-weight: bold; }
        .subtitle { color: #444; font-size: 9.5pt; }
        h1 { font-size: 16pt; margin: 0 0 4pt; }
        h2 { font-size: 12pt; margin: 12pt 0 6pt; }
        table { width: 100%; border-collapse: collapse; }
        .facts td { padding: 4pt 6pt; vertical-align: top; }
        .facts td.label { width: 32%; color: #444; }
        .grid th, .grid td { border: 1px solid #555; padding: 5pt 6pt; text-align: left; }
        .grid th { background: #eee; font-size: 9.5pt; }
        .muted { color: #555; font-size: 9pt; }
        .page-break { page-break-after: always; }
        .keep-together { page-break-inside: avoid; }
        .signature-cell { width: 34%; height: 18pt; }
    </style>
</head>
<body>
    @yield('content')
</body>
</html>
