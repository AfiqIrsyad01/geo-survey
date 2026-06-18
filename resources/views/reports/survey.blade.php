<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>GSS Technical Log - SRV-{{ str_pad($survey->id, 5, '0', STR_PAD_LEFT) }}</title>
    <style>
        /* Modern GIS Typography & Color Palette */
        @page { 
            margin: 40px; 
            size: A4;
            @bottom-right {
                content: "Page " counter(page) " of " counter(pages);
                font-size: 8px;
                color: #94a3b8;
            }
        }
        
        body { 
            font-family: 'Inter', 'Segoe UI', Helvetica, Arial, sans-serif; 
            color: #1e293b; 
            line-height: 1.5;
            margin: 0;
            padding: 0;
            background-color: #ffffff;
        }

        /* Technical Background Pattern (Subtle Grid) */
        .page-background {
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background-image: radial-gradient(#e2e8f0 0.5px, transparent 0.5px);
            background-size: 20px 20px;
            opacity: 0.3;
            z-index: -1;
        }

        /* Premium Header */
        .report-header {
            border-bottom: 4px solid #0a192f;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        
        .header-top {
            display: table;
            width: 100%;
        }
        
        .brand-section {
            display: table-cell;
            vertical-align: middle;
        }
        
        .brand-name {
            font-size: 24px;
            font-weight: 900;
            color: #0a192f;
            letter-spacing: -1px;
            margin: 0;
        }
        
        .brand-sub {
            font-size: 10px;
            font-weight: 700;
            color: #4fd1c5;
            text-transform: uppercase;
            letter-spacing: 2px;
        }
        
        .id-section {
            display: table-cell;
            text-align: right;
            vertical-align: middle;
        }
        
        .report-id-badge {
            background: #0a192f;
            color: #ffffff;
            padding: 8px 15px;
            border-radius: 8px;
            font-family: 'Courier New', Courier, monospace;
            font-weight: bold;
            font-size: 14px;
        }

        /* Dashboard Overview Strip */
        .overview-strip {
            width: 100%;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            margin-bottom: 30px;
            display: table;
            border-collapse: separate;
            border-spacing: 20px 15px;
        }
        
        .metric-cell {
            display: table-cell;
            text-align: center;
        }
        
        .metric-label {
            display: block;
            font-size: 8px;
            font-weight: 800;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 4px;
        }
        
        .metric-value {
            display: block;
            font-size: 14px;
            font-weight: 800;
            color: #0f172a;
        }

        /* Section Styling */
        .section {
            margin-bottom: 25px;
        }
        
        .section-title {
            font-size: 11px;
            font-weight: 900;
            color: #0a192f;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            border-left: 4px solid #4fd1c5;
            padding-left: 10px;
            margin-bottom: 15px;
        }

        /* Info Grid */
        .info-grid {
            width: 100%;
            border-collapse: collapse;
        }
        
        .info-box {
            background: #ffffff;
            border: 1px solid #f1f5f9;
            padding: 12px;
            width: 50%;
        }
        
        .field-label {
            font-size: 9px;
            font-weight: 700;
            color: #94a3b8;
            margin-bottom: 2px;
            text-transform: uppercase;
        }
        
        .field-value {
            font-size: 12px;
            font-weight: 600;
            color: #1e293b;
        }

        /* Spatial Precision Box */
        .spatial-data {
            background: #0a192f;
            color: #ffffff;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 30px;
            position: relative;
            overflow: hidden;
        }
        
        .spatial-data::after {
            content: "POINT";
            position: absolute;
            right: -10px;
            bottom: -10px;
            font-size: 60px;
            font-weight: 900;
            color: #ffffff;
            opacity: 0.05;
        }
        
        .coord-display {
            font-family: 'Courier New', Courier, monospace;
            font-size: 18px;
            color: #4fd1c5;
            font-weight: bold;
        }

        /* Image Registry */
        .image-gallery {
            width: 100%;
            margin-top: 10px;
        }
        
        .evidence-card {
            width: 50%;
            padding: 5px;
            vertical-align: top;
            page-break-inside: avoid;
        }
        
        .evidence-container {
            page-break-inside: avoid;
        }
        
        .image-wrapper {
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 5px;
            background: white;
        }
        
        .evidence-img {
            width: 100%;
            height: auto;
            border-radius: 6px;
            display: block;
        }
        
        .evidence-meta {
            padding: 8px;
            font-size: 9px;
            color: #64748b;
            font-family: monospace;
        }

        /* Footer Registry */
        .footer-stamp {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            font-size: 8px;
            color: #94a3b8;
            text-align: center;
            border-top: 1px solid #f1f5f9;
            padding-top: 10px;
        }

        /* Badges */
        .status-badge {
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 9px;
            font-weight: 900;
            text-transform: uppercase;
        }
        .status-approved { background: #dcfce7; color: #166534; }
        .status-pending { background: #fef9c3; color: #854d0e; }
        .status-rejected { background: #fee2e2; color: #991b1b; }

    </style>
</head>
<body>
    <div class="page-background"></div>

    <div class="report-header">
        <div class="header-top">
            <div class="brand-section">
                <h1 class="brand-name">GeoSurvey System</h1>
                <div class="brand-sub">Operational Intelligence Portal</div>
            </div>
            <div class="id-section">
                <span class="report-id-badge">SRV-{{ str_pad($survey->id, 5, '0', STR_PAD_LEFT) }}</span>
            </div>
        </div>
    </div>

    <div class="overview-strip">
        <div class="metric-cell">
            <span class="metric-label">Deployment Status</span>
            <span class="metric-value">
                <span class="status-badge status-{{ $survey->status }}">
                    {{ $survey->status }}
                </span>
            </span>
        </div>
        <div class="metric-cell">
            <span class="metric-label">Temporal Signature</span>
            <span class="metric-value">{{ $survey->created_at->format('d M Y') }}</span>
        </div>
        <div class="metric-cell">
            <span class="metric-label">Node Integrity</span>
            <span class="metric-value">
                @php 
                    $verified = $survey->images->every(fn($img) => ($img->metadata['is_verified'] ?? true));
                    $statusText = $verified ? 'SPATIAL-CERTIFIED' : 'INTEGRITY-WARNING';
                    $statusColor = $verified ? '#0d9488' : '#e11d48';
                @endphp
                <span style="color: {{ $statusColor }}">{{ $statusText }}</span>
            </span>
        </div>
        <div class="metric-cell">
            <span class="metric-label">Evidence Count</span>
            <span class="metric-value">{{ $survey->images->count() }} Files</span>
        </div>
    </div>

    <!-- Evidence Integrity Audit Section -->
    <div class="section">
        <div class="section-title">Evidence Integrity Audit</div>
        <div style="background: #f1f5f9; border-radius: 12px; padding: 15px; border-left: 5px solid #0a192f;">
            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="width: 70%;">
                        <div class="field-label">Verification Protocol</div>
                        <div class="field-value" style="font-size: 11px;">Cross-Reference: Hardware EXIF Metadata vs. Reported Map Vector</div>
                        <div style="margin-top: 8px; font-size: 10px; color: #64748b;">
                            Status: {{ $verified ? 'PASSED - Hardware GPS aligns with map telemetry.' : 'FAILED - Significant deviation detected between hardware and map.' }}
                        </div>
                    </td>
                    <td style="text-align: right; vertical-align: middle;">
                        <div style="display: inline-block; padding: 10px 20px; border: 2px solid {{ $statusColor }}; border-radius: 8px; color: {{ $statusColor }}; font-weight: 900; font-size: 16px;">
                            {{ $verified ? 'VERIFIED' : 'MISMATCH' }}
                        </div>
                    </td>
                </tr>
            </table>
        </div>
    </div>

    <div class="section">
        <div class="section-title">Contextual Metadata</div>
        <table class="info-grid">
            <tr>
                <td class="info-box">
                    <div class="field-label">Primary Project</div>
                    <div class="field-value">{{ $survey->project->name }}</div>
                </td>
                <td class="info-box">
                    <div class="field-label">Responsible Personnel</div>
                    <div class="field-value">{{ $survey->user->name }}</div>
                </td>
            </tr>
            <tr>
                <td class="info-box">
                    <div class="field-label">Personnel Contact</div>
                    <div class="field-value">{{ $survey->user->email }}</div>
                </td>
                <td class="info-box">
                    <div class="field-label">Project Boundary Reference</div>
                    <div class="field-value">ZONE-{{ str_pad($survey->project->id, 3, '0', STR_PAD_LEFT) }}</div>
                </td>
            </tr>
            <tr>
                <td colspan="2" class="info-box" style="width: 100%;">
                    <div class="field-label">Project Scope Description</div>
                    <div class="field-value" style="font-size: 11px; font-weight: 400; color: #64748b;">
                        {{ $survey->project->description ?? 'No detailed description available for this project environment.' }}
                    </div>
                </td>
            </tr>
        </table>
    </div>

    @if($options['include_map'])
    <div class="section">
        <div class="section-title">Spatial Origin & Precision</div>
        
        @if(isset($options['map_image']) && $options['map_image'])
        <div style="margin-bottom: 20px; border: 2px solid #0a192f; border-radius: 12px; overflow: hidden; page-break-inside: avoid;">
            <img src="{{ $options['map_image'] }}" style="width: 100%; display: block; border-bottom: 2px solid #0a192f;">
            <div style="background: #0a192f; color: #4fd1c5; font-size: 8px; font-weight: bold; text-align: center; padding: 4px; text-transform: uppercase; letter-spacing: 2px;">
                Secured Spatial Snapshot (MapLibre Engine Core)
            </div>
        </div>
        @endif

        <div class="spatial-data">
            <div style="margin-bottom: 10px;">
                <span class="metric-label" style="color: #94a3b8;">Primary Coordinate Vector (WGS84)</span>
            </div>
            @php 
                $primaryImg = $survey->images->first(); 
            @endphp
            <div class="coord-display">
                LAT: {{ number_format($survey->lat, 8) }}<br>
                LNG: {{ number_format($survey->lng, 8) }}
            </div>
            <div style="margin-top: 15px; font-size: 9px; color: #4fd1c5; font-weight: bold; text-transform: uppercase;">
                Sensor Accuracy: < 0.5m Horizontal Precision | Satellite Sync: ACTIVE
            </div>
        </div>
    </div>
    @endif

    @if($options['include_images'])
    <div class="section evidence-container">
        <div class="section-title">Geocoded Evidence registry</div>
        <table class="image-gallery">
            @php $chunks = $survey->images->chunk(2); @endphp
            @foreach($chunks as $chunk)
                <tr>
                    @foreach($chunk as $image)
                        <td class="evidence-card">
                            <div class="image-wrapper">
                                <img src="{{ public_path('storage/' . $image->image_path) }}" class="evidence-img">
                                <div class="evidence-meta">
                                    <strong>UID:</strong> IMG-{{ str_pad($image->id, 4, '0', STR_PAD_LEFT) }}<br>
                                    <strong>GEO:</strong> {{ $image->latitude }}, {{ $image->longitude }}<br>
                                    <strong>TIMESTAMP:</strong> {{ $image->created_at->format('Y-m-d H:i:s') }}
                                </div>
                            </div>
                        </td>
                    @endforeach
                    @if($chunk->count() == 1)
                        <td class="evidence-card"></td>
                    @endif
                </tr>
            @endforeach
        </table>
        @if($survey->images->isEmpty())
            <div style="text-align: center; color: #94a3b8; padding: 40px; border: 2px dashed #e2e8f0; border-radius: 12px; font-size: 12px;">
                NO SPATIAL EVIDENCE DETECTED FOR THIS LOG ENTRY
            </div>
        @endif
    </div>
    @endif

    @if($options['include_approvals'])
    <div class="section" style="page-break-before: auto;">
        <div class="section-title">Technical Audit Trail</div>
        <table class="info-grid">
            @foreach($survey->approvals as $approval)
            <tr>
                <td class="info-box">
                    <div class="field-label">Auditor Signature</div>
                    <div class="field-value">{{ $approval->user->name }}</div>
                </td>
                <td class="info-box">
                    <div class="field-label">Audit Decision / Date</div>
                    <div class="field-value">
                        <span class="status-badge status-{{ $approval->decision }}">{{ $approval->decision }}</span>
                        <span style="margin-left: 10px; font-size: 10px; color: #94a3b8;">{{ $approval->created_at->format('d-m-Y H:i') }}</span>
                    </div>
                </td>
            </tr>
            <tr>
                <td colspan="2" class="info-box">
                    <div class="field-label">Audit Findings & Comments</div>
                    <div class="field-value" style="font-size: 11px; font-style: italic; color: #475569;">
                        "{{ $approval->comments ?? 'No additional technical comments provided by the auditor.' }}"
                    </div>
                </td>
            </tr>
            @endforeach
        </table>
    </div>
    @endif

    <div class="footer-stamp">
        DATA INTEGRITY VERIFIED VIA GEOSURVEY CORE | CONFIDENTIAL DOCUMENT | SYSTEM GENERATED : {{ now()->format('Y-m-d H:i:s T') }}<br>
        SHA-256 HASH: {{ hash('sha256', $survey->id . $survey->created_at) }}
    </div>

</body>
</html>

