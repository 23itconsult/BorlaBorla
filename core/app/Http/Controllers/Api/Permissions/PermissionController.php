<?php

namespace App\Http\Controllers\Api\Permissions;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\JsonResponse;

class PermissionController extends Controller
{
    //

       public function CreatePermissons(request $request){
          $validator = Validator::make($request->all(), [
            'permission_human_text' => 'required',
            'name' => 'required',
        ]);

        if ($validator->fails()) {
            return apiResponse("validation_error", 'error', $validator->errors()->all());
        }
   
    $permission = Permission::updateOrCreate(
    ['name' => $request->input("name")], // only use "name" as the unique key
    [
      'name' => $request->input("name"),
      'guard_name'=>'api',
      'permission_human_text'=>$request->input("permission_human_text"),
      'user_id'=>1
    ]
);
if(!$permission){
    return apiResponse("validation_error", 'error', 
        ['message'=>'could not create permission']
        );
}

     return apiResponse("permission_created", 'success', 
    ['message'=>'permission creation successful']);

    }

    public function CreateRole(request $request){
      $validator = Validator::make($request->all(), [
            'name' => 'required',
        ]);
        if ($validator->fails()) {
            return apiResponse("validation_error", 'error', $validator->errors()->all());
        }
        $CreateRole=Role::updateOrcreate([
            "name"=>$request->input('name')
        ],

        [
        "name"=>$request->input('name'),
        'guard_name'=>'api',
        'user_id'=>1]);

        if(!$CreateRole){
         return apiResponse("validation_error", 'error', 
        ['message'=>'could not create permission']
        );
    }

    return apiResponse("Role_created", 'success', 
    ['message'=>'Role creation successful']);
    } 

    public function AssignRoleToUser(request $request){
       $validator = Validator::make($request->all(), [
            'role_name' => 'required',
        ]);

        if ($validator->fails()) {
            return apiResponse("validation_error", 'error', $validator->errors()->all());
        }
                $role = Role::updateOrCreate(
                ['role_name' => $request->input('role_name')],
                ['user_id' => 1, 
                'guard_name' => ''
                ]);

            if(!$role){
            return apiResponse("role_error", 'error', 
            ["Role could not be created"]);
        }

           return apiResponse("success", 'success', 
            ["Role created"]);
    }


      public function getMenu(): JsonResponse
    {
        $user = (object)["id"=>1];
        $menu = [];

        // Fetch all menu groups
        $groups = DB::table('menu_groups')->get();

        foreach ($groups as $group) {
            $groupItems = [];

            // Fetch top-level menu items for this group (parent_id is null)
            $menuItems = DB::table('menu_items')
                ->where('group_id', $group->id)
                ->whereNull('parent_id')
                ->get();

            foreach ($menuItems as $menuItem) {
                // Check if the menu item or any of its children are accessible
                if ($this->canAccessMenuItem($menuItem, $user)) {
                    $itemData = $this->formatMenuItem($menuItem, $user);
                    if ($itemData) {
                        $groupItems[] = $itemData;
                    }
                }
            }

            // Only add the group if it has accessible items
            if (!empty($groupItems)) {
                $menu[$group->name] = $groupItems;
            }
        }

        return response()->json($menu);
    }

    private function canAccessMenuItem($menuItem, $user): bool
    {
        // If the menu item has a permission_id and route_name, check user permission
        if ($menuItem->permission_id && $menuItem->route_name) {
            return $user && $user->hasPermissionTo($menuItem->route_name);
        }

        // Check if any child menu items are accessible
        $children = DB::table('menu_items')
            ->where('parent_id', $menuItem->id)
            ->get();

        foreach ($children as $child) {
            if ($this->canAccessMenuItem($child, $user)) {
                return true;
            }
        }

        // If no permission_id and no accessible children, deny access
        return false;
    }

    private function formatMenuItem($menuItem, $user): ?array
    {
        $data = [
            'title' => $menuItem->title,
            'icon' => $menuItem->icon,
        ];

        // Add route_name if present
        if ($menuItem->route_name) {
            $data['route_name'] = $menuItem->route_name;
        }

        // Decode and add menu_active if present
        if ($menuItem->menu_active) {
            $menuActive = json_decode($menuItem->menu_active, true);
            $data['menu_active'] = is_array($menuActive) ? $menuActive : [$menuActive];
        }

        // Decode and add keywords if present (output as 'keyword' to match format)
        if ($menuItem->keywords) {
            $data['keyword'] = json_decode($menuItem->keywords, true);
        }

        // Decode and add counters if present
        if ($menuItem->counters) {
            $counters = json_decode($menuItem->counters, true);
            $data['counters'] = $counters;
        }

        // Add counter (singular) for submenu items if present
        if ($menuItem->parent_id && $menuItem->counters) {
            $counters = json_decode($menuItem->counters, true);
            if (count($counters) === 1) {
                $data['counter'] = $counters[0];
            }
        }

        // Fetch and add submenu if there are accessible children
        $children = DB::table('menu_items')
            ->where('parent_id', $menuItem->id)
            ->get();

        $submenu = [];
        foreach ($children as $child) {
            if ($this->canAccessMenuItem($child, $user)) {
                $childData = $this->formatMenuItem($child, $user);
                if ($childData) {
                    $submenu[] = $childData;
                }
            }
        }
        if (!empty($submenu)) {
            $data['submenu'] = $submenu;
        }

        // Only return the item if it has a route_name and permission or has accessible children
        if (isset($data['route_name']) || isset($data['submenu'])) {
            return $data;
        }

        return null;
    }
}



