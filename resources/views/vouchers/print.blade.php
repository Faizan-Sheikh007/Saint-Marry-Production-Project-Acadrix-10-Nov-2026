<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Voucher {{ $voucher->voucher_no }}</title>
<link rel="stylesheet" href="{{ asset('css/app.css') }}">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
@include('vouchers.partials.modern-challan-styles')
</head>
<body>
  <div class="voucher-toolbar">
    <button type="button" onclick="window.print()" class="btn btn-primary"><i class="fas fa-print"></i> Print / Download</button>
  </div>

  @include('vouchers.partials.modern-challan', [
    'voucher' => $voucher,
    'lineItems' => $lineItems,
    'bankAccounts' => $bankAccounts,
    'feeNote' => $feeNote,
    'declaration' => $declaration,
    'qrUrl' => $qrUrl ?? null,
  ])
</body>
</html>
