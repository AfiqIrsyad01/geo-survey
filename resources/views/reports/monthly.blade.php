<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>GSS Monthly Operational Summary - {{ $month_name }}</title>
    <style>
        @page { 
            margin: 40px; 
            size: A4;
            @bottom-right {
                content: "Monthy Operational Report | Generated on " date();
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
        }

        .report-header {
            border-bottom: 4px solid #0a192f;
            padding-bottom: 20px;
            margin-bottom: 30px;
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
            color: #0d9488;
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        .section-title {
            font-size: 12px;
            font-weight: 900;
            color: #0a192f;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            border-left: 4px solid #0d9488;
            padding-left: 10px;
            margin-bottom: 20px;
            margin-top: 30px;
        }

        .stats-grid {
            width: 100%;
            border-collapse: separate;
            border-spacing: 10px;
            margin-bottom: 30px;
        }

        .stats-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            width: 25%;
        }

        .stats-label {
            display: block;
            font-size: 8px;
            font-weight: 800;
            color: #64748b;
            text-transform: uppercase;
            margin-bottom: 5px;
        }

        .stats-num {
            font-size: 20px;
            font-weight: 900;
            color: #0f172a;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            font-size: 11px;
        }

        .data-table th {
            background: #0a192f;
            color: white;
            text-align: left;
            padding: 8px 12px;
            text-transform: uppercase;
            font-size: 9px;
            font-weight: 900;
        }

        .data-table td {
            padding: 10px 12px;
            border-bottom: 1px solid #f1f5f9;
        }

        .status-badge {
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 8px;
            font-weight: 900;
            text-transform: uppercase;
        }
        .status-approved { background: #dcfce7; color: #166534; }
        .status-pending { background: #fef9c3; color: #854d0e; }
        .status-rejected { background: #fee2e2; color: #991b1b; }

        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            font-size: 8px;
            color: #94a3b8;
            text-align: center;
            padding: 10px 0;
        }
    </style>
</head>
<body>
    <div class="report-header">
        <h1 class="brand-name">GeoSurvey Corporate Dashboard</h1>
        <div class="brand-sub">Monthly Operational Audit & Performance Registry</div>
    </div>

    <div class="section-title">Operational Context: {{ $month_name }}</div>

    @php 
        $integrityPass = $surveys->filter(function($s) {
            return $s->images->every(fn($img) => ($img->metadata['is_verified'] ?? true));
        })->count();
        $integrityRate = $surveys->count() > 0 ? round(($integrityPass / $surveys->count()) * 100) : 100;
    @endphp

    <table class="stats-grid">
        <tr>
            <td class="stats-box">
                <span class="stats-label">Volume Metric</span>
                <span class="stats-num">{{ $stats['total'] }}</span>
                <div style="font-size: 8px; color: #64748b; margin-top: 5px;">Total Log entries</div>
            </td>
            <td class="stats-box" style="border-top: 4px solid #0d9488;">
                <span class="stats-label">Approvals</span>
                <span class="stats-num">{{ $stats['approved'] }}</span>
                <div style="font-size: 8px; color: #0d9488; margin-top: 5px;">Total successful surveys</div>
            </td>
            <td class="stats-box" style="border-top: 4px solid #0a192f;">
                <span class="stats-label">Rejections</span>
                <span class="stats-num">{{ $stats['rejected'] }}</span>
                <div style="font-size: 8px; color: #64748b; margin-top: 5px;">Total rejected surveys</div>
            </td>
            <td class="stats-box" style="border-top: 4px solid #854d0e;">
                <span class="stats-label">Open Review</span>
                <span class="stats-num">{{ $stats['pending'] }}</span>
                <div style="font-size: 8px; color: #64748b; margin-top: 5px;">Awaiting Clearance</div>
            </td>
        </tr>
    </table>

    <div class="section-title">Technical Performance Analysis</div>
    <div style="background: #0a192f; color: white; padding: 20px; border-radius: 12px; margin-bottom: 20px;">
        <p style="font-size: 10px; opacity: 0.7; margin: 0; text-transform: uppercase; letter-spacing: 1px;">Monthly Executive Summary</p>
        <p style="font-size: 13px; margin: 10px 0 0 0; line-height: 1.6;">
            Throughout <strong>{{ $month_name }}</strong>, the intelligence core processed <strong>{{ $stats['total'] }}</strong> unique survey nodes. 
            Validation audits confirmed <strong>{{ $stats['approved'] }}</strong> successful entries, while <strong>{{ $stats['rejected'] }}</strong> records were rejected due to data gaps or mission disconnects. 
            Operations were sustained across <strong>{{ $stats['projects']->count() }}</strong> strategic project zones, maintaining steady asset tracking flow.
        </p>
    </div>

    <div class="section-title">Project Performance Breakdown</div>
    <table class="data-table">
        <thead>
            <tr>
                <th>Project Identifier</th>
                <th style="text-align: center;">Zone Description</th>
                <th>Deadline Date</th>
                <th>Cost [RM]</th>
                <th>Operational Load</th>
            </tr>
        </thead>
        <tbody>
            @foreach($stats['projects'] as $project)
            <tr>
                <td style="font-weight: 700;">{{ $project->name }}</td>
                <td style="color: #64748b; font-size: 9px; text-align: center;">{{ \Illuminate\Support\Str::limit($project->description, 60) }}</td>
                <td>{{ $project->deadline_date ? \Carbon\Carbon::parse($project->deadline_date)->format('d-m-Y') : 'N/A' }}</td>
                <td style="font-family: monospace;">{{ $project->cost ? number_format($project->cost, 2) : '0.00' }}</td>
                <td style="font-weight: 900; color: #0a192f;">{{ $stats['by_project'][$project->id] ?? 0 }} Surveys</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    @if($options['include_registry'])
    <div class="section-title">Full Submission Log ({{ $month_name }})</div>
    <table class="data-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Personnel</th>
                <th>Date</th>
                <th>Status</th>
                <th>Project</th>
            </tr>
        </thead>
        <tbody>
            @foreach($surveys as $survey)
            <tr>
                <td style="font-family: monospace;">SRV-{{ str_pad($survey->id, 4, '0', STR_PAD_LEFT) }}</td>
                <td>{{ $survey->user->name }}</td>
                <td>{{ \Carbon\Carbon::parse($survey->survey_date)->format('d-m-Y') }}</td>
                <td>
                    <span class="status-badge status-{{ $survey->status }}">{{ $survey->status }}</span>
                </td>
                <td>{{ $survey->project->name }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    @if($options['include_trends'] || $options['include_distribution'])
    <div style="page-break-before: always;"></div>
    <div class="section-title">Strategic Intelligence Highlights</div>
    <table style="width: 100%; border-collapse: collapse; margin-bottom: 30px;">
        <tr>
            <!-- Line Chart: Submission Velocity -->
            <td style="width: 60%; padding-right: 15px; vertical-align: top;">
                @if($options['include_trends'])
                <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 15px;">
                    <p style="font-size: 8px; font-weight: 800; color: #0a192f; text-transform: uppercase; margin-bottom: 15px; letter-spacing: 1px;">Daily Operational Velocity</p>
                    @if($charts['line'])
                        <img src="{{ $charts['line'] }}" style="width: 100%; height: auto; border-radius: 8px;">
                    @else
                        <div style="height: 150px; background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 8px; text-align: center; line-height: 150px; color: #94a3b8; font-size: 8px;">Chart Service Unavailable</div>
                    @endif
                </div>
                @endif
            </td>

            <!-- Donut Chart: Status Distribution -->
            <td style="width: 40%; vertical-align: top;">
                @if($options['include_distribution'])
                <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 15px; text-align: center;">
                    <p style="font-size: 8px; font-weight: 800; color: #0a192f; text-transform: uppercase; margin: 0 0 15px 0; text-align: left; letter-spacing: 1px;">Audit Integrity</p>
                    @if($charts['donut'])
                        <img src="{{ $charts['donut'] }}" style="width: 100%; max-width: 180px; margin: 0 auto;">
                        <div style="margin-top: 15px; text-align: left; font-size: 9px; font-weight: bold;">
                            <div style="margin-bottom: 4px;">
                                <span style="display: inline-block; width: 7px; height: 7px; background: #166534; margin-right: 5px;"></span>
                                <span style="color: #166534">Approved:</span> {{ $stats['approved'] }}
                            </div>
                            <div style="margin-bottom: 4px;">
                                <span style="display: inline-block; width: 7px; height: 7px; background: #854d0e; margin-right: 5px;"></span>
                                <span style="color: #854d0e">Pending:</span> {{ $stats['pending'] }}
                            </div>
                            <div>
                                <span style="display: inline-block; width: 7px; height: 7px; background: #991b1b; margin-right: 5px;"></span>
                                <span style="color: #991b1b">Rejected:</span> {{ $stats['rejected'] }}
                            </div>
                        </div>
                    @else
                        <div style="height: 150px; background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 8px; text-align: center; line-height: 150px; color: #94a3b8; font-size: 8px;">Chart Service Unavailable</div>
                    @endif
                </div>
                @endif
            </td>
        </tr>
    </table>
    @endif

    <div class="footer">
        CONFIDENTIAL OPERATIONAL DATA | GENERATED BY GSS INTELLIGENCE CORE | HASH: {{ hash('sha256', now()) }}
    </div>
</body>
</html>
