<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>{{ $subject }}</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f4f4; margin: 0; padding: 20px; }
        .container { max-width: 480px; margin: 40px auto; background: #fff; border-radius: 8px; padding: 36px; box-shadow: 0 2px 8px rgba(0,0,0,.08); }
        h2 { color: #1e3a5f; margin-top: 0; }
        .otp-box { text-align: center; margin: 28px 0; padding: 20px; background: #f0f4ff; border-radius: 8px; }
        .otp { font-size: 36px; font-weight: bold; letter-spacing: 10px; color: #2563eb; }
        .note { font-size: 13px; color: #6b7280; margin-top: 20px; }
        .footer { margin-top: 28px; font-size: 12px; color: #9ca3af; text-align: center; }
    </style>
</head>
<body>
    <div class="container">
        <h2>{{ $subject }}</h2>
        <p>Xin chào <strong>{{ $recipientName }}</strong>,</p>

        @if($purpose === 'password_change')
        <p>Bạn đã yêu cầu đổi mật khẩu trên hệ thống E-Learning. Đây là mã xác minh của bạn:</p>
        @else
        <p>Bạn đã yêu cầu đổi địa chỉ email trên hệ thống E-Learning. Đây là mã xác minh được gửi đến email mới:</p>
        @endif

        <div class="otp-box">
            <div class="otp">{{ $otp }}</div>
        </div>

        <p>Mã có hiệu lực trong vòng <strong>10 phút</strong>. Không chia sẻ mã này với bất kỳ ai.</p>

        <p class="note">Nếu bạn không thực hiện yêu cầu này, vui lòng bỏ qua email này.</p>

        <div class="footer">
            &copy; {{ date('Y') }} E-Learning — Hệ thống giáo dục trực tuyến
        </div>
    </div>
</body>
</html>
