<?php

namespace Tests\Unit\Tenants;

use App\GoogleGSuite\GoogleDirectory;
use App\Models\User;
use Config;
use Google_Service_Exception;
use Illuminate\Contracts\Console\Kernel;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Class GoogleDirectoryTest.
 *
 * @package Tests\Unit
 */
class GoogleDirectoryTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Set up.
     */
    protected function setUp()
    {
        parent::setUp();
        Config::set('auth.providers.users.model',User::class);
    }

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

    /**
     * @test
     * @group google
     * @group slow
     */
    public function can_get_groups()
    {
        config_google_api();
        tune_google_client();
        $groups = (new GoogleDirectory())->groups();
        $this->assertNotNull($groups);
        $this->assertTrue(is_array($groups));
        $this->assertTrue(google_group_check($groups[0]));
    }

    /**
     * @test
     * @group google
     * @group slow
     */
    public function can_get_group()
    {
        config_google_api();
        tune_google_client();
        $group = (new GoogleDirectory())->group('claustre@iesebre.com');
        $this->assertNotNull($group);
        $this->assertTrue(google_group_check($group));
    }

    /**
     * @test
     * @group google
     */
    public function exception_getting_unexisting_group()
    {
        try {
            $group = (new GoogleDirectory())->group('324wqeqq232qwq@iesebre.com');
        } catch (Google_Service_Exception $e) {
            $this->assertTrue(true);
            return;
        }
        $this->fail("Getting and unexisting group did not throw a Google_Service_Exception.");
    }

    /**
     * @test
     * @group slow
     * @group google
     */
    public function can_create_group()
    {
        config_google_api();
        tune_google_client();
        google_group_remove('provaesborrar999@iesebre.com');
        sleep(10);
        (new GoogleDirectory())->group([
            'name' => 'Prova',
            'email' => 'provaesborrar999@iesebre.com',
            'description' => 'Descripció de prova',
        ]);
        sleep(3);
        $this->assertTrue(google_group_exists('provaesborrar999@iesebre.com'));
        google_group_remove('provaesborrar999@iesebre.com');


        google_group_remove('provaesborrar888@iesebre.com');
        sleep(5);

        (new GoogleDirectory())->group([
            'name' => 'Prova',
            'email' => 'provaesborrar888@iesebre.com'
        ]);
        sleep(5);

        $this->assertTrue(google_group_exists('provaesborrar888@iesebre.com'));
        google_group_remove('provaesborrar888@iesebre.com');

    }

    /**
     * Remove group.
     *
     * @test
     * @group slow
     * @group google
     */
    public function can_remove_group()
    {
        config_google_api();
        tune_google_client();
        google_group_create('provaesborrar777@iesebre.com');
        sleep(5);
        $group = (new GoogleDirectory())->removeGroup('provaesborrar777@iesebre.com');
        $this->assertFalse(google_group_exists('provaesborrar777@iesebre.com'));
    }

    /**
     * @test
     * @group slow
     * @group google
     */
    public function can_get_members_of_a_group()
    {
        config_google_api();
        tune_google_client();
        $members = (new GoogleDirectory())->groupMembers('claustre@iesebre.com');
        $this->assertTrue(google_groups_check_members($members));
    }

    /**
     * @test
     * @group slow
     * @group google
     */
    public function can_get_users()
    {
        config_google_api();
        tune_google_client();
        $users = (new GoogleDirectory())->users();
        $this->assertTrue(google_groups_check_users($users));
    }

}
