<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
        }

        .container {
            max-width: 600px;
            margin: 20px auto;
            border: 1px solid #eee;
            border-radius: 8px;
            overflow: hidden;
        }

        .header {
            background: #2563eb;
            color: #fff;
            padding: 20px;
            text-align: center;
        }

        .content {
            padding: 30px;
        }

        .details {
            background: #f8fafc;
            padding: 20px;
            border-radius: 6px;
            margin: 20px 0;
        }

        .ticket-id {
            font-size: 24px;
            font-weight: bold;
            color: #2563eb;
        }

        .footer {
            text-align: center;
            padding: 20px;
            font-size: 12px;
            color: #64748b;
            background: #f1f5f9;
        }

        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .priority-alta {
            background: #fee2e2;
            color: #991b1b;
        }

        .priority-media {
            background: #fef3c7;
            color: #92400e;
        }

        .priority-baja {
            background: #dcfce7;
            color: #166534;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h1>Confirmación de Incidencia</h1>
        </div>
        <div class="content">
            <p>Hola <strong>{{ $report->user->name }}</strong>,</p>
            <p>Hemos recibido tu reporte sobre un problema con un libro. Se ha generado un ticket en nuestro sistema de
                soporte (GLPI) para su revisión.</p>

            <div class="details">
                <div style="margin-bottom: 10px;">
                    <span class="ticket-id">#{{ $report->glpi_ticket_id ?? 'En proceso' }}</span>
                </div>
                <p><strong>Libro:</strong> {{ $report->book->title }}</p>
                <p><strong>Prioridad:</strong>
                    <span class="badge priority-{{ strtolower($report->priority) }}">
                        {{ $report->priority }}
                    </span>
                </p>
                <p><strong>Descripción:</strong><br>{{ $report->description }}</p>
            </div>

            <p>Estamos trabajando para resolverlo lo antes posible. Puedes hacer seguimiento con tu número de ticket.
            </p>

            <p>Saludos,<br>Soperte GLPI</p>
        </div>
        <div class="footer">
            &copy; {{ date('Y') }} Sistema de Gestión de Biblioteca. Este es un correo automático, por favor no
            respondas.
        </div>
    </div>
</body>

</html>