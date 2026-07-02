<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalasi - SPMB Al-Furqon</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; }
        .wizard-container { max-width: 700px; margin: 50px auto; }
        .wizard-card { border-radius: 15px; box-shadow: 0 10px 40px rgba(0,0,0,0.2); }
        .wizard-header { background: #f8f9fa; border-radius: 15px 15px 0 0; padding: 20px; }
        .step-indicator { display: flex; justify-content: space-between; margin-bottom: 0; }
        .step { text-align: center; flex: 1; position: relative; }
        .step::after { content: ''; position: absolute; top: 15px; left: 50%; width: 100%; height: 2px; background: #dee2e6; z-index: 0; }
        .step:last-child::after { display: none; }
        .step-number { width: 30px; height: 30px; border-radius: 50%; background: #dee2e6; color: #6c757d; display: inline-flex; align-items: center; justify-content: center; font-weight: bold; position: relative; z-index: 1; }
        .step.active .step-number { background: #667eea; color: white; }
        .step.completed .step-number { background: #28a745; color: white; }
        .step-label { font-size: 12px; color: #6c757d; margin-top: 5px; }
        .wizard-body { padding: 30px; }
        .requirement-item { display: flex; align-items: center; padding: 10px; border-bottom: 1px solid #eee; }
        .requirement-item:last-child { border-bottom: none; }
        .requirement-icon { width: 30px; }
    </style>
</head>
<body>
    <div class="wizard-container">
        <div class="card wizard-card">
            <div class="wizard-header">
                <h4 class="text-center mb-4"><i class="bi bi-gear-fill"></i> Wizard Instalasi SPMB</h4>
                <div class="step-indicator">
                    <div class="step {{ $step >= 1 ? ($step > 1 ? 'completed' : 'active') : '' }}">
                        <span class="step-number">{{ $step > 1 ? '✓' : '1' }}</span>
                        <div class="step-label">Requirements</div>
                    </div>
                    <div class="step {{ $step >= 2 ? ($step > 2 ? 'completed' : 'active') : '' }}">
                        <span class="step-number">{{ $step > 2 ? '✓' : '2' }}</span>
                        <div class="step-label">Database</div>
                    </div>
                    <div class="step {{ $step >= 3 ? ($step > 3 ? 'completed' : 'active') : '' }}">
                        <span class="step-number">{{ $step > 3 ? '✓' : '3' }}</span>
                        <div class="step-label">Admin</div>
                    </div>
                    <div class="step {{ $step >= 4 ? ($step > 4 ? 'completed' : 'active') : '' }}">
                        <span class="step-number">{{ $step > 4 ? '✓' : '4' }}</span>
                        <div class="step-label">Instalasi</div>
                    </div>
                    <div class="step {{ $step >= 5 ? 'active' : '' }}">
                        <span class="step-number">5</span>
                        <div class="step-label">Selesai</div>
                    </div>
                </div>
            </div>
            <div class="wizard-body">
                @yield('content')
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    @stack('scripts')
</body>
</html>
