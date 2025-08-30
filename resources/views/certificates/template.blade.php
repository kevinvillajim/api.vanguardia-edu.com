<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Certificado - {{ $certificate->metadata['course_title'] ?? 'Curso' }}</title>
    <style>
        @page {
            margin: 0;
            size: A4 landscape;
        }
        
        body {
            margin: 0;
            padding: 0;
            font-family: 'Times New Roman', serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            width: 297mm;
            height: 210mm;
            position: relative;
            color: #333;
            overflow: hidden;
        }
        
        .certificate-container {
            width: 100%;
            height: 100%;
            position: relative;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 40px;
            box-sizing: border-box;
        }
        
        .certificate-border {
            position: absolute;
            top: 20px;
            left: 20px;
            right: 20px;
            bottom: 20px;
            border: 8px solid #fff;
            border-radius: 20px;
            box-shadow: inset 0 0 20px rgba(0,0,0,0.1);
        }
        
        .inner-border {
            position: absolute;
            top: 35px;
            left: 35px;
            right: 35px;
            bottom: 35px;
            border: 2px solid #fff;
            border-radius: 15px;
            opacity: 0.8;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
            z-index: 10;
        }
        
        .logo {
            font-size: 32px;
            font-weight: bold;
            color: #fff;
            margin-bottom: 10px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }
        
        .institution {
            font-size: 18px;
            color: #fff;
            opacity: 0.9;
            text-transform: uppercase;
            letter-spacing: 2px;
        }
        
        .certificate-title {
            font-size: 48px;
            font-weight: bold;
            color: #fff;
            text-align: center;
            margin: 30px 0;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
            text-transform: uppercase;
            letter-spacing: 3px;
            z-index: 10;
        }
        
        .certificate-type {
            font-size: 16px;
            color: #fff;
            text-align: center;
            margin-bottom: 40px;
            opacity: 0.9;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .content {
            text-align: center;
            z-index: 10;
            background: rgba(255, 255, 255, 0.95);
            padding: 40px 60px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            max-width: 600px;
            margin: 20px;
        }
        
        .recipient-text {
            font-size: 24px;
            color: #333;
            margin-bottom: 15px;
            font-style: italic;
        }
        
        .recipient-name {
            font-size: 36px;
            font-weight: bold;
            color: #764ba2;
            margin: 20px 0;
            text-transform: uppercase;
            letter-spacing: 2px;
            border-bottom: 3px solid #667eea;
            display: inline-block;
            padding-bottom: 10px;
        }
        
        .course-title {
            font-size: 28px;
            color: #333;
            margin: 25px 0;
            font-weight: bold;
            line-height: 1.3;
        }
        
        .details-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin: 30px 0;
            text-align: left;
        }
        
        .detail-item {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid #667eea;
        }
        
        .detail-label {
            font-weight: bold;
            color: #555;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 5px;
        }
        
        .detail-value {
            color: #333;
            font-size: 16px;
            font-weight: 600;
        }
        
        .signatures {
            display: flex;
            justify-content: space-between;
            margin-top: 50px;
            position: relative;
            z-index: 10;
        }
        
        .signature {
            text-align: center;
            flex: 1;
            margin: 0 30px;
        }
        
        .signature-line {
            border-top: 2px solid #333;
            margin-bottom: 10px;
            width: 150px;
            margin: 0 auto 10px;
        }
        
        .signature-title {
            font-size: 14px;
            color: #666;
            font-weight: bold;
        }
        
        .signature-name {
            font-size: 16px;
            color: #333;
            font-weight: bold;
            margin-top: 5px;
        }
        
        .certificate-number {
            position: absolute;
            bottom: 40px;
            right: 50px;
            font-size: 12px;
            color: #fff;
            opacity: 0.8;
            z-index: 10;
        }
        
        .date {
            position: absolute;
            bottom: 40px;
            left: 50px;
            font-size: 12px;
            color: #fff;
            opacity: 0.8;
            z-index: 10;
        }
        
        .decoration {
            position: absolute;
            top: 50px;
            right: 50px;
            width: 80px;
            height: 80px;
            border: 3px solid #fff;
            border-radius: 50%;
            opacity: 0.3;
            z-index: 1;
        }
        
        .decoration::before {
            content: "★";
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 40px;
            color: #fff;
        }
        
        .grade-badge {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: bold;
            margin: 10px auto;
            display: inline-block;
        }
    </style>
</head>
<body>
    <div class="certificate-container">
        <div class="certificate-border"></div>
        <div class="inner-border"></div>
        <div class="decoration"></div>
        
        <div class="header">
            <div class="logo">VanguardIA</div>
            <div class="institution">{{ $certificate->metadata['institution'] ?? 'Academia de Tecnología' }}</div>
        </div>
        
        <div class="certificate-title">Certificado</div>
        <div class="certificate-type">
            @if($certificate->type === 'complete')
                Certificado de Excelencia
            @else
                Certificado de Finalización
            @endif
        </div>
        
        <div class="content">
            <div class="recipient-text">Se otorga el presente certificado a:</div>
            <div class="recipient-name">{{ $certificate->metadata['student_name'] ?? 'Estudiante' }}</div>
            
            <div class="course-title">{{ $certificate->metadata['course_title'] ?? 'Curso Completado' }}</div>
            
            @if($certificate->final_score >= 90)
                <div class="grade-badge">
                    ⭐ {{ number_format($certificate->final_score, 1) }}% - 
                    @if($certificate->final_score >= 98)
                        Summa Cum Laude
                    @elseif($certificate->final_score >= 95)
                        Magna Cum Laude
                    @elseif($certificate->final_score >= 90)
                        Cum Laude
                    @endif
                </div>
            @endif
            
            <div class="details-grid">
                <div class="detail-item">
                    <div class="detail-label">Progreso del Curso</div>
                    <div class="detail-value">{{ number_format($certificate->course_progress, 1) }}%</div>
                </div>
                
                <div class="detail-item">
                    <div class="detail-label">Calificación Final</div>
                    <div class="detail-value">{{ number_format($certificate->final_score, 1) }}%</div>
                </div>
                
                @if($certificate->interactive_average)
                <div class="detail-item">
                    <div class="detail-label">Promedio Interactivo</div>
                    <div class="detail-value">{{ number_format($certificate->interactive_average, 1) }}%</div>
                </div>
                @endif
                
                @if($certificate->activities_average)
                <div class="detail-item">
                    <div class="detail-label">Promedio Actividades</div>
                    <div class="detail-value">{{ number_format($certificate->activities_average, 1) }}%</div>
                </div>
                @endif
            </div>
        </div>
        
        <div class="signatures">
            <div class="signature">
                <div class="signature-line"></div>
                <div class="signature-title">Director Académico</div>
                <div class="signature-name">Dr. {{ $certificate->metadata['director'] ?? 'María González' }}</div>
            </div>
            
            <div class="signature">
                <div class="signature-line"></div>
                <div class="signature-title">Instructor</div>
                <div class="signature-name">{{ $certificate->metadata['instructor'] ?? 'Instructor Académico' }}</div>
            </div>
        </div>
        
        <div class="date">
            Emitido el {{ \Carbon\Carbon::parse($certificate->issued_at)->format('d \\de F \\de Y') }}
        </div>
        
        <div class="certificate-number">
            Certificado N° {{ $certificate->certificate_number }}
        </div>
    </div>
</body>
</html>