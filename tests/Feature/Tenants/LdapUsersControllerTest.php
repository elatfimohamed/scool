<?php

namespace Tests\Feature\Tenants;

use App\Models\User;
use App\Models\UserType;
use Config;
use Spatie\Permission\Models\Role;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\BaseTenantTest;

/**
 * Class LdapusersControllerTest.
 *
 * @package Tests\Feature
 */
class LdapusersControllerTest extends BaseTenantTest
{
    use RefreshDatabase;

    /**
     * Refresh the in-memory database.
     *
     * @return void
     */
    protected function refreshInMemoryDatabase()
    {
        $this->artisan('migrate',[
            '--path' => 'database/migrations/tenant'
        ]);

        $this->app[Kernel::class]->setArtisan(null);
    }

    /** @test */
    public function user_manager_can_see_ldap_users()
    {
        $this->withoutExceptionHandling();
        $manager = create(User::class);
        $this->actingAs($manager);
        $role = Role::firstOrCreate([
            'name' => 'UsersManager',
            'guard_name' => 'web'
        ]);
        Config::set('auth.providers.users.model', User::class);
        $manager->assignRole($role);

        $response = $this->json('GET','/ldap_users');
        $response->assertSuccessful();

        // TODO
//        $user2 = create(User::class);
//        $user3 = create(User::class);
//
//        $response = $this->json('GET','/api/v1/users');
//
//        $response->assertSuccessful();
//        $this->assertCount(3,json_decode($response->getContent()));
//
//        $response->assertJsonStructure([[
//            'id',
//            'name',
//            'email',
//            'created_at',
//            'updated_at',
//            'formatted_created_at',
//            'formatted_updated_at',
//            'admin',
//        ]]);
//
//        foreach ( [$manager, $user2, $user3] as $user) {
//            $response->assertJsonFragment([
//                'id' => $user->id,
//                'name' => $user->name,
//                'email' => $user->email
//            ]);
//        }
//        $this->assertCount(3,json_decode($response->getContent()));
    }

    /** @test */
    public function regular_user_cannot_see_ldap_users()
    {
        $user = create(User::class);
        $this->actingAs($user);

        $response = $this->json('GET','/ldap_users');

        $response->assertStatus(403);

    }

}
