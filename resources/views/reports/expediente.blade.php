<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Expediente de Alimentación - {{ $mealType }} - {{ $date }}</title>
    <style>
        @page {
            margin: 1cm;
        }
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            color: #333;
            line-height: 1.3;
            font-size: 10px;
            margin: 0;
            padding: 0;
        }
        .header-table {
            width: 100%;
            border-bottom: 2px solid #1e293b;
            margin-bottom: 20px;
            padding-bottom: 10px;
        }
        .header-logo {
            width: 150px;
        }
        .header-title {
            text-align: right;
            text-transform: uppercase;
        }
        .header-title h1 {
            margin: 0;
            font-size: 16pt;
            color: #1e293b;
        }
        .header-title p {
            margin: 0;
            font-size: 8pt;
            color: #64748b;
            font-weight: bold;
        }

        .section-title {
            background-color: #f1f5f9;
            padding: 8px 12px;
            border-left: 5px solid #4f46e5;
            margin: 20px 0 10px 0;
            text-transform: uppercase;
            font-weight: bold;
            color: #1e293b;
            font-size: 11px;
        }

        .info-grid {
            width: 100%;
            margin-bottom: 20px;
            border-collapse: collapse;
        }
        .info-grid td {
            padding: 5px;
            border: 1px solid #e2e8f0;
        }
        .info-label {
            background-color: #f8fafc;
            font-weight: bold;
            width: 25%;
            color: #475569;
        }

        .main-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        .main-table th {
            background-color: #1e293b;
            color: white;
            padding: 8px;
            text-align: left;
            text-transform: uppercase;
            font-size: 8px;
        }
        .main-table td {
            padding: 8px;
            border: 1px solid #e2e8f0;
            vertical-align: middle;
        }
        .signature-cell {
            width: 100px;
            height: 40px;
        }

        .evidence-gallery {
            width: 100%;
            margin-top: 20px;
        }
        .evidence-item {
            display: inline-block;
            width: 48%;
            margin-bottom: 15px;
            text-align: center;
            border: 1px solid #e2e8f0;
            padding: 10px;
            border-radius: 5px;
            page-break-inside: avoid;
        }
        .evidence-img {
            max-width: 100%;
            height: 180px;
            object-fit: contain;
            border: 1px solid #cbd5e1;
            margin-bottom: 5px;
        }

        .approval-section {
            margin-top: 50px;
            width: 100%;
            page-break-inside: avoid;
        }
        .approval-table {
            width: 100%;
        }
        .approval-table td {
            width: 50%;
            text-align: center;
            padding-top: 40px;
        }
        .signature-line {
            border-top: 1px solid #000;
            width: 80%;
            margin: 0 auto;
            padding-top: 5px;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 8px;
        }

        .footer {
            position: fixed;
            bottom: 0;
            width: 100%;
            text-align: center;
            font-size: 8px;
            color: #94a3b8;
            border-top: 1px solid #e2e8f0;
            padding-top: 5px;
        }
    </style>
</head>
<body>
    <!-- HEADER / SOLICITUD -->
    <table class="header-table">
        <tr>
            <td>
                @if($logo_base64)
                    <img src="{{ $logo_base64 }}" class="header-logo">
                @else
                    <div style="font-weight: bold; font-size: 20px; color: #4f46e5;">SICOA</div>
                @endif
            </td>
            <td class="header-title">
                <h1>Expediente de Alimentación</h1>
                <p>Sistema de Control y Gestión Administrativa</p>
            </td>
        </tr>
    </table>

    <div class="section-title">1. Datos Generales de la Solicitud</div>
    <table class="info-grid">
        <tr>
            <td class="info-label">ID Sesión:</td>
            <td>#{{ $session->id ?? 'N/A' }}</td>
            <td class="info-label">Fecha de Servicio:</td>
            <td>{{ \Carbon\Carbon::parse($date)->locale('es')->isoFormat('dddd D [de] MMMM [de] YYYY') }}</td>
        </tr>
        <tr>
            <td class="info-label">Tipo de Alimento:</td>
            <td>{{ strtoupper($mealType) }}</td>
            <td class="info-label">Proveedor Asignado:</td>
            <td>{{ $provider->name }}</td>
        </tr>
        <tr>
            <td class="info-label">Estado de Sesión:</td>
            <td>FINALIZADA / CERRADA</td>
            <td class="info-label">Total de Pedidos:</td>
            <td>{{ $totalOrders }}</td>
        </tr>
    </table>

    <!-- CUERPO / LISTA DE CONTROL -->
    <div class="section-title">2. Relación de Comensales y Justificación de Actividades</div>
    <table class="main-table">
        <thead>
            <tr>
                <th style="width: 5%;">#</th>
                <th style="width: 25%;">Nombre del Comensal</th>
                <th style="width: 20%;">Platillo Solicitado</th>
                <th style="width: 30%;">Justificación de Actividad</th>
                <th style="width: 20%; text-align: center;">Firma de Conformidad</th>
            </tr>
        </thead>
        <tbody>
            @php $count = 1; @endphp
            @foreach($ordersSummary as $group)
                @foreach($group['individual_orders'] as $order)
                    <tr>
                        <td style="text-align: center;">{{ $count++ }}</td>
                        <td><strong>{{ $order['user_name'] }}</strong><br><small style="color: #64748b;">{{ $order['area_name'] }}</small></td>
                        <td>{{ $order['platillo_name'] }}</td>
                        <td>{{ $order['activity_performed'] ?: 'SIN JUSTIFICACIÓN REGISTRADA' }}</td>
                        <td class="signature-cell"></td>
                    </tr>
                @endforeach
            @endforeach
        </tbody>
    </table>

    <!-- ANEXO / EVIDENCIA -->
    <div style="page-break-before: always;"></div>
    <div class="section-title">3. Anexo de Evidencia Fotográfica</div>
    <div class="evidence-gallery">
        @php $hasEvidence = false; @endphp
        @foreach($ordersSummary as $group)
            @if($group['evidence_path'])
                @php $hasEvidence = true; @endphp
                <div class="evidence-item">
                    <img src="{{ $group['evidence_path'] }}" class="evidence-img">
                    <p style="font-weight: bold; margin: 5px 0 0 0;">{{ $group['group_name'] }}</p>
                    <p style="font-size: 8px; color: #64748b; margin: 0;">Evidencia capturada durante la sesión</p>
                </div>
            @endif
        @endforeach

        @if(!$hasEvidence)
            <div style="text-align: center; padding: 50px; color: #94a3b8; border: 1px dashed #cbd5e1;">
                No se registraron evidencias fotográficas para esta sesión.
            </div>
        @endif
    </div>

    <!-- CUADRO DE FIRMAS -->
    <div class="approval-section">
        <div class="section-title" style="background-color: #cbd5e1; border-left-color: #1e293b;">4. Validación y Firmas de Autorización</div>
        <table class="approval-table">
            <tr>
                <td>
                    <div class="signature-line">
                        FIRMA DEL GERENTE DE ÁREA<br>
                        <span style="font-size: 7px; font-weight: normal;">Nombre y Sello de la Dependencia</span>
                    </div>
                </td>
                <td>
                    <div class="signature-line">
                        FIRMA DE ADQUISICIONES / COORDINACIÓN<br>
                        <span style="font-size: 7px; font-weight: normal;">Validación de Entrega del Servicio</span>
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <div class="footer">
        Expediente Digital SICOA - UTICS ® - Generado el {{ date('d/m/Y H:i') }} - Hoja {PAGENO} de {nbpg}
    </div>
</body>
</html>
