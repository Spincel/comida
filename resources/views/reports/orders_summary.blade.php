<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>{{ $reportConfig['main_title'] ?? 'Reporte de Pedidos' }} - {{ $mealType }}</title>
    <style>
        @page {
            margin: 0.5cm;
        }
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            color: #333;
            line-height: 1.1;
            font-size: {{ $reportConfig['font_size'] ?? '9px' }};
            margin: 0;
            padding: 0;
        }
        .header {
            text-align: center;
            margin-bottom: 15px;
            border-bottom: 2px solid {{ $reportConfig['report_color'] ?? '#4f46e5' }};
            padding-bottom: 8px;
        }
        .header h1 {
            margin: 0;
            color: {{ $reportConfig['report_color'] ?? '#4f46e5' }};
            text-transform: uppercase;
            font-size: 14pt;
        }
        .header h2 {
            margin: 3px 0;
            color: #666;
            font-size: 10pt;
        }
        .header p {
            margin: 0;
            font-size: 7pt;
            color: #999;
            text-transform: uppercase;
            font-weight: bold;
        }
        .area-section {
            margin-bottom: 15px;
            page-break-inside: avoid;
        }
        .area-title {
            background-color: #f8fafc;
            padding: 6px 10px;
            border-left: 4px solid {{ $reportConfig['report_color'] ?? '#4f46e5' }};
            margin-bottom: 8px;
        }
        .area-title h3 {
            margin: 0;
            text-transform: uppercase;
            font-size: 9pt;
            color: {{ $reportConfig['report_color'] ?? '#4f46e5' }};
        }
        .individual-orders {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        .individual-orders th {
            text-align: left;
            padding: 5px;
            background-color: {{ $reportConfig['report_color'] ?? '#4f46e5' }};
            color: white;
            font-size: 7pt;
            text-transform: uppercase;
        }
        .individual-orders td {
            padding: 5px;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: middle;
        }
        .avatar {
            width: 16px;
            height: 16px;
            border-radius: 50%;
            margin-right: 4px;
        }
        .evidence-box {
            margin-top: 10px; 
            padding: 10px; 
            border: 1px dashed #cbd5e1; 
            background-color: #f8fafc; 
            border-radius: 8px;
            text-align: center;
        }
        .evidence-img {
            max-width: 300px; 
            max-height: 200px;
            height: auto; 
            border-radius: 4px; 
            border: 2px solid white; 
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .footer {
            text-align: center;
            font-size: 6pt;
            color: #94a3b8;
            margin-top: 20px;
        }
        .signature-box {
            border-bottom: 1px solid #ccc;
            height: 25px;
            width: 80px;
        }
    </style>
</head>
<body>
    <div class="header">
        <p>{{ $reportConfig['main_title'] ?? 'Reporte de Control de Alimentos' }}</p>
        <h1>{{ $mealType }}</h1>
        <h2>{{ $provider->name }}</h2>
        <p>{{ \Carbon\Carbon::parse($date)->locale('es')->isoFormat('LL') }}</p>
    </div>

    @foreach($ordersSummary as $summary)
        <div class="area-section">
            <div class="area-title">
                <h3>{{ $summary['group_name'] }} ({{ $summary['total_count'] }} pedidos)</h3>
            </div>

            <table class="individual-orders">
                <thead>
                    <tr>
                        <th style="width: 30%;">Comensal</th>
                        @if($reportConfig['show_area'] ?? true) <th style="width: 15%;">Dependencia</th> @endif
                        @if($reportConfig['show_platillo'] ?? true) <th style="width: 20%;">Platillo</th> @endif
                        @if($reportConfig['show_preferences'] ?? true) <th style="width: 15%;">Obs.</th> @endif
                        @if($reportConfig['show_activity'] ?? true) <th style="width: 20%;">Actividad</th> @endif
                        @if($reportConfig['show_signature'] ?? false) <th style="width: 15%; text-align: center;">Firma</th> @endif
                    </tr>
                </thead>
                <tbody>
                    @foreach($summary['individual_orders'] as $order)
                        <tr>
                            <td>
                                @if(($reportConfig['show_avatar'] ?? true) && ($order['avatar_url'] ?? null))
                                    <img src="{{ $order['avatar_url'] }}" class="avatar">
                                @endif
                                <strong>{{ $order['user_name'] }}</strong>
                            </td>
                            @if($reportConfig['show_area'] ?? true) <td>{{ $order['area_name'] }}</td> @endif
                            @if($reportConfig['show_platillo'] ?? true) <td>{{ $order['platillo_name'] }}</td> @endif
                            @if($reportConfig['show_preferences'] ?? true) <td style="font-style: italic; color: #666;">{{ $order['preferences'] ?: '-' }}</td> @endif
                            @if($reportConfig['show_activity'] ?? true) <td>{{ $order['activity_performed'] ?: '' }}</td> @endif
                            @if($reportConfig['show_signature'] ?? false) <td><div class="signature-box"></div></td> @endif
                        </tr>
                    @endforeach
                </tbody>
            </table>

            @if($summary['evidence_path'] ?? null)
                <div class="evidence-box">
                    <p style="margin: 0 0 8px 0; font-size: 7pt; font-weight: bold; color: #64748b; text-transform: uppercase;">Evidencia de Entrega:</p>
                    <img src="{{ $summary['evidence_path'] }}" class="evidence-img">
                </div>
            @endif
        </div>
    @endforeach

    <div class="footer">
        Generado el {{ date('d/m/Y H:i') }}
    </div>
</body>
</html>
