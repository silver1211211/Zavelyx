<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your NexaHub Verification Code</title>
</head>
<body style="margin:0;padding:0;background-color:#f1f5f9;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="background-color:#f1f5f9;padding:40px 16px;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" role="presentation" style="max-width:600px;width:100%;">

                    <!-- Header -->
                    <tr>
                        <td style="background:linear-gradient(135deg,#0369a1 0%,#0284c7 100%);border-radius:20px 20px 0 0;padding:36px 48px;text-align:center;">
                            <table cellpadding="0" cellspacing="0" role="presentation" style="margin:0 auto;">
                                <tr>
                                    <td style="vertical-align:middle;padding-right:10px;">
                                        <div style="width:38px;height:38px;background:rgba(255,255,255,0.15);border-radius:10px;display:inline-block;text-align:center;line-height:38px;font-size:18px;">⬡</div>
                                    </td>
                                    <td style="vertical-align:middle;">
                                        <span style="font-size:22px;font-weight:900;color:#ffffff;letter-spacing:-0.5px;">Nexa<span style="color:#7dd3fc;">Hub</span></span>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Body -->
                    <tr>
                        <td style="background:#ffffff;padding:48px 48px 40px;text-align:center;">

                            <!-- Icon circle -->
                            <div style="width:72px;height:72px;background:#f0f9ff;border:2px solid #bae6fd;border-radius:20px;margin:0 auto 24px;text-align:center;line-height:72px;font-size:32px;">✉️</div>

                            <h1 style="margin:0 0 12px;font-size:26px;font-weight:900;color:#0f172a;letter-spacing:-0.5px;">Verify your email</h1>
                            <p style="margin:0 0 8px;font-size:15px;color:#64748b;line-height:1.6;">
                                Hi <strong style="color:#0f172a;">{{ $user->name }}</strong>,
                            </p>
                            <p style="margin:0 0 36px;font-size:15px;color:#64748b;line-height:1.6;">
                                Use the code below to verify your NexaHub account.<br>
                                This code expires in <strong style="color:#0f172a;">10 minutes</strong>.
                            </p>

                            <!-- OTP Code box -->
                            <div style="display:inline-block;background:#f0f9ff;border:2px solid #7dd3fc;border-radius:18px;padding:28px 48px;margin-bottom:36px;">
                                <div style="font-size:52px;font-weight:900;letter-spacing:14px;color:#0369a1;font-family:'Courier New',Courier,monospace;line-height:1;">{{ $code }}</div>
                                <p style="margin:14px 0 0;font-size:12px;color:#94a3b8;letter-spacing:0.5px;text-transform:uppercase;font-weight:600;">One-time verification code</p>
                            </div>

                            <!-- Security note -->
                            <div style="background:#fafafa;border:1px solid #e2e8f0;border-radius:12px;padding:16px 20px;margin-bottom:32px;text-align:left;">
                                <p style="margin:0 0 6px;font-size:13px;font-weight:700;color:#374151;">🔒 Security reminder</p>
                                <p style="margin:0;font-size:13px;color:#6b7280;line-height:1.6;">
                                    Never share this code with anyone — NexaHub will never ask for it. If you didn't request this, you can safely ignore this email.
                                </p>
                            </div>

                            <p style="margin:0;font-size:13px;color:#94a3b8;">
                                This code will expire at {{ now()->addMinutes(10)->format('H:i T') }} on {{ now()->addMinutes(10)->format('M j, Y') }}.
                            </p>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="background:#f8fafc;border-radius:0 0 20px 20px;padding:24px 48px;text-align:center;border-top:1px solid #e2e8f0;">
                            <p style="margin:0 0 6px;font-size:12px;color:#94a3b8;">
                                © {{ date('Y') }} NexaHub. All rights reserved.
                            </p>
                            <p style="margin:0;font-size:12px;color:#cbd5e1;">
                                SMM &amp; Digital Services &amp; Payment Ecosystem
                            </p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>
