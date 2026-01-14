<?php

return [
  'isProduction' => env('MIDTRANS_IS_PRODUCTION', false),
  'clientKeySb' => env('MIDTRANS_CLIENT_KEY_SB'),
  'serverKeySb' => env('MIDTRANS_SERVER_KEY_SB'),
  'clientKeyProd' => env('MIDTRANS_CLIENT_KEY_PROD'),
  'serverKeyProd' => env('MIDTRANS_SERVER_KEY_PROD'),
  'apiUrlSb' => env('MIDTRANS_API_URL_SB'),
  'apiUrlProd' => env('MIDTRANS_API_URL_PROD'),
];