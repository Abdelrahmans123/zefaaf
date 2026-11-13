<?php

return ['driver' => env('SESSION_DRIVER', 'file'), 'lifetime' => 120, 'expire_on_close' => false, 'encrypt' => false, 'files' => storage_path('framework/sessions'), 'cookie' => 'zefaaf_session', 'path' => '/', 'domain' => null, 'secure' => env('SESSION_SECURE_COOKIE', false), 'http_only' => true, 'same_site' => 'lax', 'lottery' => [2, 100]];
