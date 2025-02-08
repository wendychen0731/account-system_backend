<?php

namespace App\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to your "home" route.
     *
     * 通常用於 Auth 登入後導向的路徑
     * 例如: /home
     */
    public const HOME = '/home';

    /**
     * 在這裡載入路由設定
     */
    public function boot()
    {
        parent::boot();

        $this->mapApiRoutes();
        $this->mapWebRoutes();
    }

    /**
     * 定義 "web" 路由
     *
     * 使用 "web" middleware group
     */
    protected function mapWebRoutes()
    {
        Route::middleware('web')
             ->group(base_path('routes/web.php'));
    }

    /**
     * 定義 "api" 路由
     *
     * 使用 "api" middleware group
     * 前綴 "api"
     */
    protected function mapApiRoutes()
    {
        Route::prefix('api')
             ->middleware('api')
             ->group(base_path('routes/api.php'));
    }
}
