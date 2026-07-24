<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <title>{{ $certificate->certificate_no }}</title>
    <style>
        body { font-family: 'DejaVu Sans', sans-serif; color: #1e293b; }
        .frame { border: 6px double #1e293b; padding: 48px; text-align: center; margin-top: 40px; }
        h1 { font-size: 14px; letter-spacing: 4px; color: #64748b; text-transform: uppercase; margin-bottom: 8px; }
        h2 { font-size: 28px; margin: 24px 0 8px; }
        .name { font-size: 24px; font-weight: bold; margin: 16px 0; }
        .muted { color: #64748b; }
        .meta { margin-top: 40px; font-size: 11px; color: #64748b; }
    </style>
</head>
<body>
    <div class="frame">
        <h1>Chứng nhận hoàn thành khóa học</h1>
        <p class="muted">Chứng nhận số {{ $certificate->certificate_no }}</p>
        <h2>Trân trọng chứng nhận</h2>
        <p class="name">{{ $certificate->student->name ?? '' }}</p>
        <p>đã hoàn thành khóa học</p>
        <p class="name">{{ $certificate->course->name ?? ($certificate->classRoom->name ?? '') }}</p>
        @if($certificate->final_score !== null)
            <p class="muted">Điểm tổng kết: {{ $certificate->final_score }}</p>
        @endif
        <p class="muted">Ngày cấp: {{ optional($certificate->issued_at)->format('d/m/Y') }}</p>
        <div class="meta">Mã xác thực: {{ $certificate->verify_token }}</div>
    </div>
</body>
</html>
