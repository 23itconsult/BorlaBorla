<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Permission;

class SyncAdminPermissions extends Command
{
    protected $signature = 'permissions:sync-admin';
    protected $description = 'Extract only admin routes and save them as permissions';

public function handle()
{
    $routes = Route::getRoutes();

    foreach ($routes as $route) {
        $uri = $route->uri();
        $name = $route->getName();

        // Remove route parameters like {id}, {user}, etc.
        $cleanUri = preg_replace('/\{.*?\}/', '', $uri);
        // Ensure leading slash
        $cleanUri = '/' . ltrim($cleanUri, '/');

        // filter only admin routes
        if ($name && str_starts_with($name, 'admin.')) {
            Permission::firstOrCreate(
                ['name' => $cleanUri], // store cleaned URL
                [
                    'guard_name' => 'web',
                    'permission_human_text' => ucfirst($name),
                    'route_name' => $name, // store route name like "admin.dashboard"
                ]
            );

            $this->info("Added permission for: " . $cleanUri);
        }
    }

    $this->info('Admin permissions synced successfully!');
}


}
