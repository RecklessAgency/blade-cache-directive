<?php

namespace RyanChandler\BladeCacheDirective;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Cache;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class BladeCacheDirectiveServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('blade-cache-directive')
            ->hasConfigFile();
    }

    public function packageBooted()
    {
        Blade::directive('cache', function ($expression) {
            return "<?php
                \$__cache_directive_arguments = [{$expression}];
                \$__cache_directive_tags = []; // Default empty tags
                \$__cache_directive_key = '';
                \$__cache_directive_ttl = config('blade-cache-directive.ttl');

                if (count(\$__cache_directive_arguments) >= 3) {
                    list(\$__cache_directive_key, \$__cache_directive_ttl, \$__cache_directive_tags) = \$__cache_directive_arguments;
                } elseif (count(\$__cache_directive_arguments) == 2) {
                    list(\$__cache_directive_key, \$__cache_directive_ttl) = \$__cache_directive_arguments;
                } elseif (count(\$__cache_directive_arguments) == 1) {
                    list(\$__cache_directive_key) = \$__cache_directive_arguments;
                }

                if (config('blade-cache-directive.enabled')) {
                    if (!empty(\$__cache_directive_tags)) {
                        \$cacheStore = Cache::tags(\$__cache_directive_tags);
                    } else {
                        \$cacheStore = Cache::store();
                    }

                    if (\$cacheStore->has(\$__cache_directive_key)) {
                        echo \$cacheStore->get(\$__cache_directive_key);
                        return;
                    }

                    ob_start();
                }
            ?>";
        });

        Blade::directive('endcache', function () {
            return "<?php
                    if (isset(\$__cache_directive_buffering) && \$__cache_directive_buffering) {
                        \$__cache_directive_buffer = ob_get_clean();

                        if (!empty(\$__cache_directive_tags)) {
                            \$cacheStore = Cache::tags(\$__cache_directive_tags);
                        } else {
                            \$cacheStore = Cache::store();
                        }

                        \$cacheStore->put(\$__cache_directive_key, \$__cache_directive_buffer, \$__cache_directive_ttl);
                        echo \$__cache_directive_buffer;
                    }

                    unset(\$__cache_directive_key, \$__cache_directive_ttl, \$__cache_directive_buffer, \$__cache_directive_buffering, \$__cache_directive_arguments, \$__cache_directive_tags, \$cacheStore);
                ?>";
        });
    }
}
