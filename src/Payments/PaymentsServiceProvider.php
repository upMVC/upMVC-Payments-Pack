<?php

namespace BitsHost\UpmvcPayments;

use App\Etc\Application;
use App\Etc\Router;

/**
 * Registers payments module + migration paths on the host upMVC app.
 * Requires saas-pack (SaasServiceProvider) for tenant middleware / SaasApiController.
 */
class PaymentsServiceProvider
{
    public function register(Application $app): void
    {
        $packRoot = dirname(__DIR__, 2);

        $app->addModulePath($packRoot . '/src/Modules');
        $app->addMigrationPath($packRoot . '/database/migrations');
    }

    public function boot(Application $app, Router $router): void
    {
        // Drivers / webhooks wired by host or later pack versions.
    }
}
