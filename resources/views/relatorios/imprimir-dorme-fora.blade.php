<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatório de Dorme Fora</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            background-color: #0f172a; /* slate-900 */
            color: #f8fafc; /* slate-50 */
            margin: 0;
            padding: 20px;
        }
        .header {
            display: flex;
            align-items: center;
            border-bottom: 2px solid #334155; /* slate-700 */
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .header img {
            width: 80px;
            height: auto;
            margin-right: 20px;
        }
        .header-info h1 {
            margin: 0;
            font-size: 20px;
            text-transform: uppercase;
        }
        .header-info p {
            margin: 5px 0 0 0;
            font-size: 14px;
            color: #94a3b8; /* slate-400 */
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            border: 1px solid #334155; /* slate-700 */
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #1e293b; /* slate-800 */
            color: #94a3b8; /* slate-400 */
            font-weight: bold;
            text-transform: uppercase;
        }
        .group-header td {
            background-color: #334155; /* slate-700 */
            color: #34d399; /* emerald-400 */
            font-weight: bold;
        }
        .uppercase {
            text-transform: uppercase;
        }
        @media print {
            body {
                padding: 0;
                /* Impressoras normalmente forçam fundo branco a menos que configurado */
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .no-print {
                display: none;
            }
        }
        .print-btn {
            padding: 10px 20px;
            background: #2563eb;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            margin-bottom: 20px;
        }
        .text-center {
            text-align: center;
        }
    </style>
</head>
<body>
    @php
        $logoPath = public_path('img/icons/icon-192x192.png');
        $logoData = file_exists($logoPath) ? base64_encode(file_get_contents($logoPath)) : '';
        $logoSrc = $logoData ? 'data:image/png;base64,' . $logoData : asset('img/icons/icon-192x192.png');
    @endphp

    <div class="no-print">
        <button class="print-btn" onclick="window.print()">Imprimir / Salvar PDF</button>
        <button class="print-btn" onclick="window.close()" style="background: #64748b; margin-left: 10px;">Fechar</button>
    </div>

    <div class="header">
        <img src="{{ $logoSrc }}" alt="Logo">
        <div class="header-info">
            <h1>Relatório de Dorme Fora</h1>
            <p><strong>Período:</strong> {{ $dataInicial->format('d/m/Y') }} a {{ $dataFinal->format('d/m/Y') }}</p>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Colaborador</th>
                <th>Cargo</th>
                <th>Data</th>
                <th>Código</th>
                <th>Local</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($dadosRelatorio as $nomeColaborador => $registros)
                <tr class="group-header">
                    <td colspan="5">
                        {{ $nomeColaborador }} - TOTAL: {{ $registros->count() }}
                    </td>
                </tr>
                
                @foreach($registros as $linha)
                    <tr>
                        <td>{{ $linha->nome_colaborador }}</td>
                        <td class="uppercase">{{ $linha->cargo }}</td>
                        <td class="uppercase">{{ $linha->data }}</td>
                        <td class="uppercase">{{ $linha->codigo_local }}</td>
                        <td class="uppercase">{{ $linha->nome_local }}</td>
                    </tr>
                @endforeach
            @empty
                <tr>
                    <td colspan="5" class="text-center" style="padding: 20px;">
                        Nenhum registro encontrado para o período selecionado.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

</body>
</html>
