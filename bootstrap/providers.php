<?php

return [
    Illuminate\Foundation\Providers\FoundationServiceProvider::class,
    Illuminate\Foundation\Providers\ConsoleSupportServiceProvider::class,
    Illuminate\Filesystem\FilesystemServiceProvider::class,
    Illuminate\Cache\CacheServiceProvider::class,
    Illuminate\Database\DatabaseServiceProvider::class,
    Illuminate\View\ViewServiceProvider::class,
    Illuminate\Queue\QueueServiceProvider::class,
    Illuminate\Cookie\CookieServiceProvider::class,
    Illuminate\Encryption\EncryptionServiceProvider::class,
    Illuminate\Session\SessionServiceProvider::class,
    Illuminate\Auth\AuthServiceProvider::class,
    Illuminate\Hashing\HashServiceProvider::class,
    Illuminate\Validation\ValidationServiceProvider::class,
    Illuminate\Translation\TranslationServiceProvider::class,  // 👈 Agregado aquí
    Laravel\Sanctum\SanctumServiceProvider::class,
    App\Providers\AppServiceProvider::class,
];
