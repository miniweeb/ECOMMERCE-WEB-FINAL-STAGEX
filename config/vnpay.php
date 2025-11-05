<?php
// Terminal ID (TmnCode) issued by VNPay for the merchant.  Provided via email.
define('VNPAY_TMN_CODE', '4G1QECGF');


// Secret key used to generate the checksum.  Provided via email.  Keep this value private!
define('VNPAY_HASH_SECRET', 'UQJUYYYZ155GHP69P5WJK1WI04ERIK2B');


// VNPay payment URL (sandbox). Do not modify unless VNPay changes their specification.
define('VNPAY_URL', 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html');


// Return URL after payment.  VNPay will redirect back to this address
// with the payment result.  It must be a fully qualified URL.  When
// running locally, update this to the actual host and port of your
// application (e.g. http://localhost:3000/public/index.php?pg=vnpay_return).
define('VNPAY_RETURN_URL', 'http://localhost:3000/public/index.php?pg=vnpay_return');