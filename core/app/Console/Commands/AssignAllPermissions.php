<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Admin;
use Spatie\Permission\Models\Permission;

class AssignAllPermissions extends Command
{
    // Command signature and description
    protected $signature = 'user:assign-all-permissions {user_id}';
    protected $description = 'Assign all existing permissions to a user';

    public function handle(): int
    {
        $userId = $this->argument('user_id');
        $user = Admin::find($userId);
        if (!$user) {
            $this->error("User with ID {$userId} not found.");
            return Command::FAILURE;
        }
        // Fetch all permissions
        $permissions = Permission::all();
        // Assign all permissions to the user
        $user->syncPermissions($permissions);
        $this->info("All permissions have been assigned to user ID {$userId}.");
        return Command::SUCCESS;
    }
}
