<?php

namespace App\Providers;

use App\Contracts\ExternalMessagingGateway;
use App\Services\Auth\HashedDatabaseTokenRepository;
use App\Services\Notifications\LogMessagingGateway;
use Fouladgar\OTP\Contracts\TokenRepositoryInterface;
use Fouladgar\OTP\Token\TokenRepositoryManager;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(ExternalMessagingGateway::class, LogMessagingGateway::class);

        $this->app->bind(TokenRepositoryInterface::class, function ($app) {
            return new HashedDatabaseTokenRepository(
                $app->make(ConnectionInterface::class),
                (int) config('otp.token_lifetime', 5),
                (int) config('otp.token_length', 6),
                (string) config('otp.token_table', 'otp_tokens')
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->app->extend('token.repository', function (TokenRepositoryManager $manager, $app) {
            $manager->extend('database', function () use ($app) {
                return new HashedDatabaseTokenRepository(
                    $app->make(ConnectionInterface::class),
                    (int) config('otp.token_lifetime', 5),
                    (int) config('otp.token_length', 6),
                    (string) config('otp.token_table', 'otp_tokens')
                );
            });

            return $manager;
        });
    }
}
