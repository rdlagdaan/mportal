<?php

use Illuminate\Support\Facades\Broadcast;

/**
 * Registers the default broadcasting auth endpoint.
 * Not used by Echo in our setup (we use /app/broadcasting/user-auth),
 * but Laravel expects this file when withBroadcasting() is configured.
 */
Broadcast::routes([
    'middleware' => ['web', 'auth:sanctum'],
    // no prefix; default path is POST /broadcasting/auth
]);
