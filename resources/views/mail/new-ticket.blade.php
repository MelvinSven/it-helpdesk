<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="x-apple-disable-message-reformatting">
<meta name="format-detection" content="telephone=no">
<title>{{ $ticketCode }}</title>
<style>
/* Progressive enhancement: clients that ignore <style> keep the fluid 600px
   table layout below, which already shrinks to the viewport width. */
@media only screen and (max-width: 600px) {
    .pad { padding: 16px 20px !important; }
    .header { font-size: 20px !important; padding: 16px 20px !important; line-height: 1.3 !important; }
    .lead { font-size: 15px !important; }
    /* Stack each label above its value instead of squeezing two columns. */
    .label, .value { display: block !important; width: 100% !important; box-sizing: border-box !important; }
    .label { border-bottom: 0 !important; padding-bottom: 4px !important; }
    .value { border-top: 0 !important; padding-top: 4px !important; }
    .cta-link { display: block !important; text-align: center !important; padding: 14px 16px !important; }
    .cta-table { width: 100% !important; }
    .footer { padding: 0 16px !important; }
}
</style>
</head>
<body style="margin:0; padding:0; width:100%; background-color:#f4f4f5; font-family:Arial, Helvetica, sans-serif; color:#1f2937; -webkit-text-size-adjust:100%; -ms-text-size-adjust:100%;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f4f4f5; padding:24px 0; width:100%;">
<tr>
<td align="center" style="padding:0 8px;">
<table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px; width:100%; background-color:#ffffff; overflow:hidden; border:1px solid #e5e7eb;">
<tr>
<td class="header" style="background-color:#2563eb; padding:20px 32px; text-align:center; color:#ffffff; font-size:25px;">Notifikasi dari IT Helpdesk Lixicon</td>
</tr>
<tr>
<td class="pad" style="padding:24px 32px;">
<p class="lead" style="margin:0 0 16px; font-size:16px; line-height:1.5;">{{ $leadLine }}</p>
<p class="lead" style="margin:0 0 16px; font-size:16px; line-height:1.5; color: #2563eb; font-weight:bold; word-break:break-word;">{{ $requestorName }}</p>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse; border:1px solid #d1d5db; margin-bottom:24px; font-size:14px; width:100%;">
<tr>
<td class="label" style="border:1px solid #d1d5db; padding:10px 12px; background-color:#f9fafb; font-weight:bold; width:120px;">Tanggal</td>
<td class="value" style="border:1px solid #d1d5db; padding:10px 12px; word-break:break-word;">{{ $ticketDate }}</td>
</tr>
<tr>
<td class="label" style="border:1px solid #d1d5db; padding:10px 12px; background-color:#f9fafb; font-weight:bold; width:120px;">Kode Tiket</td>
<td class="value" style="border:1px solid #d1d5db; padding:10px 12px; word-break:break-word;">{{ $ticketCode }}</td>
</tr>
<tr>
<td class="label" style="border:1px solid #d1d5db; padding:10px 12px; background-color:#f9fafb; font-weight:bold;">Nama</td>
<td class="value" style="border:1px solid #d1d5db; padding:10px 12px; word-break:break-word;">{{ $requestorName }}</td>
</tr>
<tr>
<td class="label" style="border:1px solid #d1d5db; padding:10px 12px; background-color:#f9fafb; font-weight:bold;">Proyek</td>
<td class="value" style="border:1px solid #d1d5db; padding:10px 12px; word-break:break-word;">{{ $requestorProyek ?? '-' }}</td>
</tr>
<tr>
<td class="label" style="border:1px solid #d1d5db; padding:10px 12px; background-color:#f9fafb; font-weight:bold;">Status Tiket</td>
<td class="value" style="border:1px solid #d1d5db; padding:10px 12px; word-break:break-word;">{{ $ticketStatus ?? '-' }}</td>
</tr>
<tr>
<td class="label" style="border:1px solid #d1d5db; padding:10px 12px; background-color:#f9fafb; font-weight:bold;">Penangan</td>
<td class="value" style="border:1px solid #d1d5db; padding:10px 12px; word-break:break-word;">{{ $assigneeName ?? 'Belum ditugaskan' }}</td>
</tr>
<tr>
<td class="label" style="border:1px solid #d1d5db; padding:10px 12px; background-color:#f9fafb; font-weight:bold;">Deskripsi</td>
<td class="value" style="border:1px solid #d1d5db; padding:10px 12px; word-break:break-word;">{{ $ticketDescription }}</td>
</tr>
@if (! empty($commentBody))
<tr>
<td class="label" style="border:1px solid #d1d5db; padding:10px 12px; background-color:#f9fafb; font-weight:bold;">Balasan{{ ! empty($commentAuthor) ? " ({$commentAuthor})" : '' }}</td>
<td class="value" style="border:1px solid #d1d5db; padding:10px 12px; word-break:break-word;">{{ $commentBody }}</td>
</tr>
@endif
</table>

@if (! empty($noticeLine))
<p style="margin:0 0 24px; font-size:14px; line-height:1.5;">{{ $noticeLine }}</p>
@endif

<table role="presentation" class="cta-table" cellpadding="0" cellspacing="0" style="margin-bottom:24px;">
<tr>
<td style="background-color:#2563eb;">
<a href="{{ $ticketUrl }}" class="cta-link" style="display:inline-block; padding:12px 24px; color:#ffffff; text-decoration:none; font-size:14px; font-weight:bold;">{{ $ctaLabel ?? 'Lihat Tiket' }}</a>
</td>
</tr>
</table>

<p style="margin:0; font-size:14px; line-height:1.5; color:#6b7280;">Terima kasih,<br>{{ config('app.name') }}</p>
</td>
</tr>
</table>
<p class="footer" style="margin:16px 0 0; font-size:12px; line-height:1.5; color:#9ca3af; text-align:center;">&copy; {{ date('Y') }} IT Helpdesk Lixicon. All rights reserved.</p>
</td>
</tr>
</table>
</body>
</html>
