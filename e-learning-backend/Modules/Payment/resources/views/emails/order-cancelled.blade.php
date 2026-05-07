<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đơn hàng đã bị hủy — E-Learning</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f0f4f8; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color: #f0f4f8; padding: 40px 20px;">
        <tr>
            <td align="center">
                <table role="presentation" width="600" cellspacing="0" cellpadding="0" style="background-color: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 24px rgba(0, 0, 0, 0.08);">

                    {{-- Header --}}
                    <tr>
                        <td style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); padding: 40px 40px 30px; text-align: center;">
                            <div style="width: 64px; height: 64px; background-color: rgba(255, 255, 255, 0.2); border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 16px;">
                                <span style="font-size: 32px;">❌</span>
                            </div>
                            <h1 style="margin: 0; color: #ffffff; font-size: 24px; font-weight: 700; letter-spacing: -0.5px;">
                                Đơn hàng đã bị hủy
                            </h1>
                            <p style="margin: 8px 0 0; color: rgba(255, 255, 255, 0.85); font-size: 14px;">
                                Đơn hàng của bạn đã hết thời gian thanh toán
                            </p>
                        </td>
                    </tr>

                    {{-- Body --}}
                    <tr>
                        <td style="padding: 36px 40px;">
                            <p style="margin: 0 0 24px; color: #334155; font-size: 16px; line-height: 1.6;">
                                Xin chào <strong style="color: #1e293b;">{{ $order->student->name }}</strong>,
                            </p>

                            <p style="margin: 0 0 24px; color: #64748b; font-size: 15px; line-height: 1.7;">
                                Đơn hàng <strong style="color: #ef4444;">{{ $order->order_code }}</strong>
                                của bạn đã bị hủy tự động do không hoàn tất thanh toán trong vòng <strong>15 phút</strong>.
                            </p>

                            {{-- Order info --}}
                            <div style="background-color: #fef2f2; border-radius: 12px; padding: 20px 24px; margin-bottom: 28px; border-left: 4px solid #ef4444;">
                                <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
                                    <tr>
                                        <td style="color: #64748b; font-size: 13px; padding-bottom: 8px;">Mã đơn hàng</td>
                                        <td style="text-align: right; color: #1e293b; font-size: 13px; font-weight: 600; padding-bottom: 8px;">{{ $order->order_code }}</td>
                                    </tr>
                                    <tr>
                                        <td style="color: #64748b; font-size: 13px; padding-bottom: 8px;">Ngày tạo</td>
                                        <td style="text-align: right; color: #1e293b; font-size: 13px; font-weight: 600; padding-bottom: 8px;">
                                            {{ $order->created_at->setTimezone('Asia/Ho_Chi_Minh')->format('d/m/Y H:i') }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td colspan="2" style="border-top: 1px solid #fecaca; padding-top: 12px;"></td>
                                    </tr>
                                    <tr>
                                        <td style="color: #1e293b; font-size: 15px; font-weight: 700;">Tổng tiền</td>
                                        <td style="text-align: right; color: #ef4444; font-size: 18px; font-weight: 700;">
                                            {{ number_format($order->total_amount, 0, ',', '.') }}đ
                                        </td>
                                    </tr>
                                </table>
                            </div>

                            {{-- Course list --}}
                            <h3 style="margin: 0 0 16px; color: #1e293b; font-size: 15px; font-weight: 700;">
                                Khóa học trong đơn ({{ $order->items->count() }})
                            </h3>

                            @foreach ($order->items as $item)
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0"
                                   style="margin-bottom: 12px; background-color: #f8fafc; border-radius: 10px; overflow: hidden;">
                                <tr>
                                    @if ($item->course?->thumbnail)
                                    <td width="80" style="padding: 12px 0 12px 12px;">
                                        <img src="{{ str_starts_with($item->course->thumbnail, 'http') ? $item->course->thumbnail : config('app.url') . '/storage/' . $item->course->thumbnail }}"
                                             alt="{{ $item->course->name }}"
                                             width="68" height="50"
                                             style="border-radius: 6px; object-fit: cover; display: block;">
                                    </td>
                                    @endif
                                    <td style="padding: 12px 16px;">
                                        <p style="margin: 0 0 4px; color: #1e293b; font-size: 14px; font-weight: 600; line-height: 1.4;">
                                            {{ $item->course?->name ?? 'Khóa học' }}
                                        </p>
                                        <p style="margin: 0; color: #64748b; font-size: 13px;">
                                            {{ number_format($item->final_price, 0, ',', '.') }}đ
                                        </p>
                                    </td>
                                </tr>
                            </table>
                            @endforeach

                            <p style="margin: 28px 0 24px; color: #64748b; font-size: 14px; line-height: 1.7;">
                                Bạn vẫn có thể đặt lại đơn hàng bất kỳ lúc nào. Nhấn vào nút bên dưới để quay lại trang khóa học.
                            </p>

                            {{-- CTA --}}
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin: 0 0 0;">
                                <tr>
                                    <td align="center">
                                        <a href="{{ config('app.frontend_url', config('app.url')) }}/courses"
                                           target="_blank"
                                           style="display: inline-block; background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); color: #ffffff; text-decoration: none; font-size: 15px; font-weight: 600; padding: 14px 48px; border-radius: 50px; letter-spacing: 0.3px; box-shadow: 0 4px 16px rgba(239, 68, 68, 0.35);">
                                            🔄 Đặt lại đơn hàng
                                        </a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Footer --}}
                    <tr>
                        <td style="background-color: #f8fafc; padding: 24px 40px; border-top: 1px solid #e2e8f0; text-align: center;">
                            <p style="margin: 0 0 4px; color: #94a3b8; font-size: 12px;">
                                Nếu bạn có bất kỳ câu hỏi nào, vui lòng liên hệ hỗ trợ.
                            </p>
                            <p style="margin: 0; color: #cbd5e1; font-size: 12px;">
                                © {{ date('Y') }} E-Learning. All rights reserved.
                            </p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>
