<?php

namespace App\Http\Controllers\Tenant;

use App\GoogleGSuite\GoogleDirectory;
use App\Http\Requests\ListGoogleUsers;
use App\Http\Requests\StoreGoogleGroups;
use Cache;

/**
 * Class GoogleUsersController.
 * 
 * @package App\Http\Controllers\Tenant
 */
class GoogleUsersController extends Controller
{
    /**
     * Show.
     *
     * @param ListGoogleGroups $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function show(ListGoogleUsers $request)
    {
        $users = collect([]);
        $users = Cache::rememberForever('users', function() use ($users){
            $directory = new GoogleDirectory();
            return collect($directory->users());
        });
        return view('tenants.google_users.show', compact('users'));
    }

    /**
     * Index.
     *
     * @param ListGoogleUsers $request
     * @return \Illuminate\Support\Collection
     */
    public function index(ListGoogleUsers $request)
    {
        $directory = new GoogleDirectory();
        return collect($directory->groups());
    }

}