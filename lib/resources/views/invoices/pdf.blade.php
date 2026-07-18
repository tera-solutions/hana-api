<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <title>{{ $invoice->code }}</title>
    <style>
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 12px; color: #1e293b; }
        h1 { font-size: 18px; margin-bottom: 4px; }
        .muted { color: #64748b; }
        .header { display: flex; justify-content: space-between; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { border: 1px solid #e2e8f0; padding: 6px 8px; text-align: left; }
        th { background: #f1f5f9; }
        .text-right { text-align: right; }
        .totals { width: 260px; margin-left: auto; margin-top: 12px; }
        .totals td { border: none; padding: 3px 0; }
        .totals .label { color: #64748b; }
        .grand-total { font-weight: bold; font-size: 14px; border-top: 1px solid #1e293b !important; }
        .status { display: inline-block; padding: 2px 8px; border-radius: 4px; background: #f1f5f9; }
    </style>
</head>
<body>
    <div class="header">
        <div>
            <h1>Hóa đơn {{ $invoice->code }}</h1>
            <p class="muted">
                {{ $invoice->invoice_type === 'payable' ? 'Hóa đơn chi' : 'Hóa đơn thu' }}
                &middot; Trạng thái: <span class="status">{{ $invoice->status }}</span>
            </p>
        </div>
        <div class="text-right">
            <strong>{{ $invoice->business?->name }}</strong><br>
            @if($invoice->branch)
                {{ $invoice->branch->name }}<br>
            @endif
            Ngày lập: {{ optional($invoice->invoice_date)->format('d/m/Y') }}<br>
            @if($invoice->due_date)
                Hạn thanh toán: {{ $invoice->due_date->format('d/m/Y') }}
            @endif
        </div>
    </div>

    @if($invoice->student)
        <p><strong>Khách hàng:</strong> {{ $invoice->student->name }} ({{ $invoice->student->code }})</p>
    @endif

    <table>
        <thead>
            <tr>
                <th>Nội dung</th>
                <th class="text-right">Số lượng</th>
                <th class="text-right">Đơn giá</th>
                <th class="text-right">Thành tiền</th>
            </tr>
        </thead>
        <tbody>
            @forelse($invoice->items as $item)
                <tr>
                    <td>{{ $item->name }}</td>
                    <td class="text-right">{{ $item->quantity }}</td>
                    <td class="text-right">{{ number_format($item->unit_price, 0, ',', '.') }}</td>
                    <td class="text-right">{{ number_format($item->total, 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr><td colspan="4" class="muted">Không có mục nào</td></tr>
            @endforelse
        </tbody>
    </table>

    <table class="totals">
        <tr><td class="label">Tạm tính</td><td class="text-right">{{ number_format($invoice->subtotal, 0, ',', '.') }}</td></tr>
        <tr><td class="label">Giảm giá</td><td class="text-right">-{{ number_format($invoice->discount, 0, ',', '.') }}</td></tr>
        <tr><td class="label">Thuế</td><td class="text-right">{{ number_format($invoice->tax, 0, ',', '.') }}</td></tr>
        <tr class="grand-total"><td>Tổng cộng</td><td class="text-right">{{ number_format($invoice->total, 0, ',', '.') }} đ</td></tr>
        <tr><td class="label">Đã thanh toán</td><td class="text-right">{{ number_format($invoice->paid_amount, 0, ',', '.') }}</td></tr>
        <tr><td class="label">Còn lại</td><td class="text-right">{{ number_format($invoice->balance_amount, 0, ',', '.') }}</td></tr>
    </table>

    @if($invoice->note)
        <p class="muted" style="margin-top: 20px;"><strong>Ghi chú:</strong> {{ $invoice->note }}</p>
    @endif
</body>
</html>
