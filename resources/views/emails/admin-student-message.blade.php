<!DOCTYPE html>
<html>
<head><meta charset="utf-8"></head>
<body style="font-family:Arial,sans-serif;background:#f4f6f8;padding:24px;margin:0">
  <div style="max-width:560px;margin:0 auto;background:#fff;border-radius:8px;overflow:hidden;border:1px solid #e5e7eb">
    <div style="background:#111827;color:#fff;padding:18px 24px;font-size:16px;font-weight:700">
      {{ config('app.name') }}
    </div>
    <div style="padding:24px">
      <p style="margin-top:0">Dear {{ $recipientName }},</p>
      <h2 style="font-size:17px;margin:0 0 12px">{{ $messageTitle }}</h2>
      <div style="white-space:pre-line;color:#374151;font-size:14px;line-height:1.6">{{ $messageBody }}</div>
    </div>
    <div style="padding:14px 24px;background:#f9fafb;color:#9ca3af;font-size:11px;border-top:1px solid #e5e7eb">
      This is an automated message from {{ config('app.name') }}. Please do not reply directly to this email.
    </div>
  </div>
</body>
</html>
