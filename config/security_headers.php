<?php

$cspNonce = base64_encode(random_bytes(18));

header(
    "Content-Security-Policy: default-src 'self'; base-uri 'self'; object-src 'none'; "
    . "frame-src 'self'; frame-ancestors 'self'; form-action 'self'; "
    . "img-src 'self' data:; font-src 'self' https://cdnjs.cloudflare.com; "
    . "style-src 'self' 'nonce-$cspNonce' https://cdnjs.cloudflare.com; "
    . "script-src 'self' 'nonce-$cspNonce'"
);
header('X-Frame-Options: SAMEORIGIN');

return $cspNonce;
