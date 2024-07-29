<?php
/**
 * Copyright (c) Since 2024 InnoShop - All Rights Reserved
 *
 * @link       https://www.innoshop.com
 * @author     InnoShop <team@innoshop.com>
 * @license    https://opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
 */

namespace InnoShop\Front;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\FileViewFinder;
use InnoShop\Common\Middleware\ContentFilterHook;
use InnoShop\Common\Middleware\EventActionHook;
use InnoShop\Common\Models\Customer;
use InnoShop\Front\Middleware\CustomerAuthentication;
use InnoShop\Front\Middleware\GlobalFrontData;
use InnoShop\Front\Middleware\SetFrontLocale;

class FrontServiceProvider extends ServiceProvider
{
    /**
     * Boot front service provider.
     *
     * @return void
     * @throws \Exception
     */
    public function boot(): void
    {
        $this->loadTranslations();

        if (! installed()) {
            return;
        }

        load_settings();
        $this->registerGuard();
        $this->registerWebRoutes();
        $this->registerApiRoutes();
        $this->publishViewTemplates();
        $this->loadThemeViewPath();
        $this->loadViewComponents();
    }

    /**
     * @return void
     */
    public function register(): void
    {
        app('router')->aliasMiddleware('customer_auth', CustomerAuthentication::class);
    }

    /**
     * Register guard for frontend.
     */
    protected function registerGuard(): void
    {
        Config::set('auth.guards.customer', [
            'driver'   => 'session',
            'provider' => 'customer',
        ]);

        Config::set('auth.providers.customer', [
            'driver' => 'eloquent',
            'model'  => Customer::class,
        ]);
    }

    /**
     * Register admin front routes.
     *
     * @return void
     * @throws \Exception
     */
    protected function registerWebRoutes(): void
    {
        $middlewares = ['web', SetFrontLocale::class, EventActionHook::class, ContentFilterHook::class, GlobalFrontData::class];

        Route::middleware($middlewares)
            ->get('/', [Controllers\HomeController::class, 'index'])
            ->name('front.home.index');

        $locales = locales();
        if (count($locales) == 1) {
            Route::middleware($middlewares)
                ->name('front.')
                ->group(function () {
                    $this->loadRoutesFrom(realpath(__DIR__.'/../routes/web.php'));
                });
        } else {
            foreach ($locales as $locale) {
                Route::middleware($middlewares)
                    ->prefix($locale->code)
                    ->name($locale->code.'.front.')
                    ->group(function () {
                        $this->loadRoutesFrom(realpath(__DIR__.'/../routes/web.php'));
                    });
            }
        }
    }

    /**
     * Register frontend api routes.
     *
     * @return void
     */
    protected function registerApiRoutes(): void
    {
        $middlewares = ['api', EventActionHook::class, ContentFilterHook::class];
        Route::prefix('api')
            ->middleware($middlewares)
            ->name('api.')
            ->group(function () {
                $this->loadRoutesFrom(realpath(__DIR__.'/../routes/api.php'));
            });
    }

    /**
     * Register front language
     * @return void
     */
    protected function loadTranslations(): void
    {
        $this->loadTranslationsFrom(__DIR__.'/../lang', 'front');
        $this->publishes([
            __DIR__.'/../lang' => $this->app->langPath('vendor/front'),
        ], 'lang');
    }

    /**
     * Publish view as default theme.
     * php artisan vendor:publish --provider='InnoShop\Front\FrontServiceProvider' --tag=views
     *
     * @return void
     */
    protected function publishViewTemplates(): void
    {
        $originViewPath = __DIR__.'/../resources';
        $customViewPath = base_path('themes/default');

        $this->publishes([
            $originViewPath => $customViewPath,
        ], 'views');
    }

    /**
     * Load theme view path.
     *
     * @return void
     */
    protected function loadThemeViewPath(): void
    {
        $this->app->singleton('view.finder', function ($app) {
            $themePaths = [];
            if ($theme = system_setting('theme')) {
                $themeViewPath = base_path("themes/{$theme}/views");
                if (is_dir($themeViewPath)) {
                    $themePaths[] = $themeViewPath;
                }
            }
            $themePaths[] = realpath(__DIR__.'/../resources/views');

            $viewPaths = $app['config']['view.paths'];
            $viewPaths = array_merge($themePaths, $viewPaths);

            return new FileViewFinder($app['files'], $viewPaths);
        });
    }

    /**
     * Load view components.
     *
     * @return void
     */
    protected function loadViewComponents(): void
    {
        $this->loadViewComponentsAs('front', [
            'breadcrumb' => Components\Breadcrumb::class,
        ]);
    }
}
