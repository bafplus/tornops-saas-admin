<?php

use Illuminate\Support\Facades\Facade;
use Illuminate\Support\ServiceProvider;

return [
    'name' => env('APP_NAME', 'TornOps SaaS'),
    'env' => env('APP_ENV', 'production'),
    'debug' => (bool) env('APP_DEBUG', false),
    'url' => env('APP_URL', 'http://localhost'),
    'timezone' => 'UTC',
    'locale' => 'en',
    'fallback_locale' => 'en',
    'faker_locale' => 'en_US',
    'key' => env('APP_KEY'),
    'cipher' => 'AES-256-CBC',

    // TornOps SaaS specific
    'tornops_image' => env('TORNOPS_IMAGE', 'ghcr.io/bafplus/tornops/tornops:latest'),
    'data_volume_path' => env('DATA_VOLUME_PATH', '/data/tornops'),
    'default_master_key' => env('DEFAULT_MASTER_KEY', ''),

    // Cloudflare Tunnel
    'tunnel_token' => env('TUNNEL_TOKEN', ''),

    // Torn API
    'torn_api_key' => env('TORN_API_KEY', ''),
    'torn_api_id' => env('TORN_API_ID', ''),
    'payment_item_id' => env('PAYMENT_ITEM_ID', ''),

    // Providers
    'providers' => [
        Illuminate\Auth\AuthServiceProvider::class,
        Illuminate\Broadcasting\BroadcastServiceProvider::class,
        Illuminate\Bus\BusServiceProvider::class,
        Illuminate\Cache\CacheServiceProvider::class,
        Illuminate\Database\DatabaseServiceProvider::class,
        Illuminate\Encryption\EncryptionServiceProvider::class,
        Illuminate\Filesystem\FilesystemServiceProvider::class,
        Illuminate\Foundation\Providers\FoundationServiceProvider::class,
        Illuminate\Hashing\HashServiceProvider::class,
        Illuminate\Pipeline\PipelineServiceProvider::class,
        Illuminate\Queue\QueueServiceProvider::class,
        Illuminate\Session\SessionServiceProvider::class,
        Illuminate\Translation\TranslationServiceProvider::class,
        Illuminate\Validation\ValidationServiceProvider::class,
        Illuminate\View\ViewServiceProvider::class,
        App\Providers\AppServiceProvider::class,
    ],
];