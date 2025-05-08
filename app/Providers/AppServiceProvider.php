<?php

namespace App\Providers;

use Illuminate\Support\Facades\Log;
use App\Services\MercadoPagoService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Contract\Auth as FirebaseAuth;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(MercadoPagoService::class, function ($app) {
            return new MercadoPagoService();
        });

        $this->app->singleton(FirebaseAuth::class, function ($app) {
            $credentialsPath = config('firebase.credentials') ?? storage_path('app/firebase/lumorahai-891ad-firebase-adminsdk-fbsvc-645b8f2ba6.json');
            $factory = (new Factory)->withServiceAccount($credentialsPath);
            return $factory->createAuth();
        });
    }

    public function boot(): void
    {
        try {
            if (!Storage::disk('public')->exists('chat_images')) {
                Storage::disk('public')->makeDirectory('chat_images');
            }
            if (!Storage::disk('public')->exists('chat_files')) {
                Storage::disk('public')->makeDirectory('chat_files');
            }
        } catch (\Exception $e) {
            \Log::error('Error al crear directorios de chat: ' . $e->getMessage());
        }
    }
}