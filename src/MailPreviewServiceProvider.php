<?php

namespace Spatie\MailPreview;

use Illuminate\Session\Middleware\StartSession;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class MailPreviewServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-mail-preview')
            ->hasConfigFile();
    }

    public function boot()
    {
        $this->app['mail.manager']->extend('preview', function () {
            return new PreviewTransport(
                $this->app->make('Illuminate\Filesystem\Filesystem'),
                $this->app['config']['mail-preview.path'],
                $this->app['config']['mail-preview.maximum_lifetime']
            );
        });

        if ($this->app['config']['mail-preview.show_link_to_preview']) {
            $this->app['router']->group(['middleware' => $this->middleware()], function ($router) {
                $router->get('/themsaid/mail-preview')->uses(MailPreviewController::class.'@preview');
            });

            if ($this->app['config']['mail-preview.middleware_groups']) {
                foreach ($this->app['config']['mail-preview.middleware_groups'] as $groupName) {
                    $this->app['router']->pushMiddlewareToGroup(
                        $groupName,
                        MailPreviewMiddleware::class
                    );
                }
            }
        }
    }

    protected function middleware(): array
    {
        return array_merge(
            (array) $this->app['config']['mail-preview.middleware'],
            [StartSession::class]
        );
    }
}
