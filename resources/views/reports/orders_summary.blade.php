<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte de Pedidos - {{ $mealType }}</title>
    <style>
        @page {
            margin: 1.5cm;
        }
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            color: #333;
            line-height: 1.4;
            font-size: 10pt;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 3px solid #4f46e5;
            padding-bottom: 15px;
        }
        .header h1 {
            margin: 0;
            color: #4f46e5;
            text-transform: uppercase;
            font-size: 18pt;
            letter-spacing: -1px;
        }
        .header h2 {
            margin: 5px 0;
            color: #666;
            font-size: 12pt;
        }
        .header p {
            margin: 0;
            font-size: 9pt;
            color: #999;
            font-weight: bold;
            text-transform: uppercase;
        }
        .area-section {
            margin-bottom: 40px;
            page-break-inside: avoid;
        }
        .area-title {
            background-color: #f8fafc;
            padding: 10px 15px;
            border-left: 5px solid #4f46e5;
            margin-bottom: 15px;
        }
        .area-title h3 {
            margin: 0;
            text-transform: uppercase;
            font-size: 12pt;
            color: #1e293b;
        }
        .area-title span {
            float: right;
            font-size: 9pt;
            color: #64748b;
        }
        .dish-summary {
            width: 100%;
            margin-bottom: 15px;
            background-color: #fff;
        }
        .dish-summary td {
            padding: 5px 0;
            border-bottom: 1px dashed #e2e8f0;
        }
        .dish-name {
            font-weight: bold;
            color: #4f46e5;
        }
        .dish-count {
            text-align: right;
            font-weight: bold;
        }
        .individual-orders {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        .individual-orders th {
            text-align: left;
            padding: 8px;
            background-color: #4f46e5;
            color: white;
            font-size: 8pt;
            text-transform: uppercase;
        }
        .individual-orders td {
            padding: 8px;
            border-bottom: 1px solid #f1f5f9;
            font-size: 9pt;
        }
        .avatar {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            vertical-align: middle;
            margin-right: 8px;
        }
        .preferences {
            font-style: italic;
            color: #64748b;
            font-size: 8pt;
        }
        .footer {
            position: fixed;
            bottom: 0;
            width: 100%;
            text-align: center;
            font-size: 8pt;
            color: #94a3b8;
            border-top: 1px solid #f1f5f9;
            padding-top: 10px;
        }
        .badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 7pt;
            font-weight: bold;
            background: #eef2ff;
            color: #4f46e5;
            text-transform: uppercase;
        }
        /* Meal Type Colors */
        .meal-desayuno { background-color: #fffbeb; color: #b45309; border: 1px solid #fde68a; }
        .meal-comida { background-color: #eef2ff; color: #4338ca; border: 1px solid #c7d2fe; }
        .meal-cena { background-color: #f3e8ff; color: #7e22ce; border: 1px solid #e9d5ff; }
        .meal-extra { background-color: #f0fdf4; color: #047857; border: 1px solid #bbf7d0; }
    </style>
</head>
<body>
    <div class="header">
        <p>Reporte de Control de Alimentos</p>
        <h1 class="meal-{{ strtolower($mealType) }}" style="display: inline-block; padding: 10px 20px; border-radius: 15px;">{{ $mealType }}</h1>
        <h2 style="margin-top: 15px;">{{ $provider->name }}</h2>
        <p>Fecha: {{ \Carbon\Carbon::parse($date)->locale('es')->isoFormat('dddd, D [de] MMMM [de] YYYY') }}</p>
        <div style="margin-top: 10px;">
            <span class="badge">Acomodo: Por {{ $sortBy === 'area' ? 'Área' : ($sortBy === 'platillo' ? 'Platillo' : 'Nombre') }}</span>
        </div>
    </div>

    @foreach($ordersSummary as $summary)
        <div class="area-section">
            <div class="area-title">
                <h3>
                    {{ $summary['group_name'] }}
                    <span class="badge meal-{{ strtolower($mealType) }}">{{ $mealType }}</span>
                    <span>{{ $summary['total_count'] }} Platillos en total</span>
                </h3>
            </div>

            @if($viewMode === 'dishes')
                <table class="individual-orders">
                    <thead>
                        <tr>
                            <th style="width: 40%;">Platillo</th>
                            <th style="width: 15%; text-align: center;">Cantidad</th>
                            <th style="width: 45%;">Observaciones Consolidadas</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($summary['platillos'] as $platillo)
                            <tr>
                                <td class="dish-name">{{ $platillo['platillo_name'] }}</td>
                                <td style="text-align: center;"><strong>{{ $platillo['total_count'] }}</strong></td>
                                <td class="preferences">
                                    @if(count($platillo['observations']) > 0)
                                        <ul style="margin: 0; padding-left: 15px;">
                                            @foreach($platillo['observations'] as $obs)
                                                <li>{{ $obs }}</li>
                                            @endforeach
                                        </ul>
                                    @else
                                        -
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif

            @if($viewMode === 'detailed')
                <table class="individual-orders">
                    <thead>
                        <tr>
                            <th style="width: 35%;">Nombre Comensal</th>
                            <th style="width: 30%;">Platillo</th>
                            <th style="width: 35%;">Observaciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($summary['individual_orders'] as $order)
                            <tr>
                                <td>
                                    @if($order['avatar_url'])
                                        <img src="{{ $order['avatar_url'] }}" class="avatar" alt="">
                                    @endif
                                    <strong>{{ $order['user_name'] }}</strong>
                                    <br><small style="color: #999; font-size: 7pt;">{{ $order['area_name'] }}</small>
                                </td>
                                <td>{{ $order['platillo_name'] }}</td>
                                <td class="preferences">{{ $order['preferences'] ?: '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif

            @if($viewMode === 'names')
                <table class="individual-orders">
                    <thead>
                        <tr>
                            <th style="width: 35%;">Nombre Comensal</th>
                            <th style="width: 45%;">Actividad Realizada (Justificación)</th>
                            <th style="width: 20%; text-align: center;">Firma</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($summary['individual_orders'] as $order)
                            <tr style="height: 50px;">
                                <td style="vertical-align: middle;">
                                    <strong>{{ $order['user_name'] }}</strong>
                                    <br><small style="color: #999; font-size: 7pt;">{{ $order['area_name'] }}</small>
                                </td>
                                <td style="font-size: 8pt; vertical-align: middle; border-bottom: 1px solid #eee;">
                                    {{ $order['activity_performed'] ?: '' }}
                                </td>
                                <td style="border-bottom: 1px solid #ccc; vertical-align: bottom; text-align: center;">
                                    <span style="font-size: 6pt; color: #ccc; display: block; margin-bottom: 2px;">Firma del Comensal</span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    @endforeach

    <div class="footer">
        Generado automáticamente por el Sistema de Gestión de Comidas.
        Página <script type="text/php">echo $FONT_METRICS->get_font("Arial", "bold"); {PAGE_NUM} de {PAGE_COUNT}</script>
        | {{ \Carbon\Carbon::now()->format('d/m/Y H:i') }}
    </div>
</body>
</html>
