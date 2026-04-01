<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="x-apple-disable-message-reformatting">
    <meta name="format-detection" content="telephone=no,address=no,email=no,date=no,url=no">
    <title>{{ $title ?? 'FAO FFS MIS' }}</title>
    <!--[if mso]>
    <noscript><xml><o:OfficeDocumentSettings><o:PixelsPerInch>96</o:PixelsPerInch></o:OfficeDocumentSettings></xml></noscript>
    <![endif]-->
</head>
<body style="margin:0;padding:0;word-spacing:normal;background-color:#f4f6f9;-webkit-text-size-adjust:100%;-ms-text-size-adjust:100%;">
    <div role="article" aria-roledescription="email" lang="en" style="text-size-adjust:100%;-webkit-text-size-adjust:100%;-ms-text-size-adjust:100%;">

        <!-- Preheader (hidden preview text for inbox) -->
        <div style="display:none;font-size:1px;color:#f4f6f9;line-height:1px;max-height:0;max-width:0;opacity:0;overflow:hidden;">
            {{ strip_tags(Str::limit($body ?? '', 120)) }}
        </div>

        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background-color:#f4f6f9;">
            <tr>
                <td align="center" style="padding:24px 12px;">

                    <!-- Main card -->
                    <table role="presentation" width="560" cellspacing="0" cellpadding="0" border="0" style="max-width:560px;width:100%;background:#ffffff;border-radius:8px;overflow:hidden;">

                        <!-- Header -->
                        <tr>
                            <td style="background-color:#05179F;padding:20px 28px;text-align:center;">
                                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                                    <tr>
                                        <td align="center">
                                            <p style="margin:0;font-family:Arial,Helvetica,sans-serif;font-size:18px;font-weight:700;color:#ffffff;letter-spacing:0.5px;">
                                                FAO FFS MIS
                                            </p>
                                            <p style="margin:4px 0 0;font-family:Arial,Helvetica,sans-serif;font-size:11px;color:rgba(255,255,255,0.7);text-transform:uppercase;letter-spacing:1px;">
                                                FOSTER Programme &mdash; Karamoja, Uganda
                                            </p>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>

                        <!-- Gold accent line -->
                        <tr>
                            <td style="height:3px;background-color:#F4A71D;font-size:0;line-height:0;">&nbsp;</td>
                        </tr>

                        <!-- Body content -->
                        <tr>
                            <td style="padding:28px 28px 24px;font-family:Arial,Helvetica,sans-serif;font-size:15px;line-height:1.6;color:#1e293b;">
                                {!! $body !!}
                            </td>
                        </tr>

                        <!-- Footer -->
                        <tr>
                            <td style="padding:16px 28px;background-color:#f8fafc;border-top:1px solid #e2e8f0;text-align:center;">
                                <p style="margin:0;font-family:Arial,Helvetica,sans-serif;font-size:11px;color:#94a3b8;line-height:1.5;">
                                    FAO &mdash; Food and Agriculture Organization of the United Nations
                                    <br>FOSTER Programme, Karamoja Subregion, Uganda
                                </p>
                                <p style="margin:8px 0 0;font-family:Arial,Helvetica,sans-serif;font-size:10px;color:#cbd5e1;">
                                    This is an automated message. Please do not reply.
                                </p>
                            </td>
                        </tr>

                    </table>
                    <!-- /Main card -->

                </td>
            </tr>
        </table>

    </div>
</body>
</html>
