<?php

namespace Tests\Feature\Tenants\Api;

use App\Models\Incident;
use App\Models\User;
use App\Models\Reply;
use Config;
use Spatie\Permission\Models\Role;
use Tests\BaseTenantTest;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Contracts\Console\Kernel;

/**
 * Class IncidentRepliesControllerTest.
 *
 * @package Tests\Feature\Tenants
 */
class IncidentRepliesControllerTest extends BaseTenantTest
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

    protected function createIncident()
    {
        return Incident::create([
            'subject' => 'No funciona res a la Sala Mestral',
            'description' => 'Bla bla bla',
        ]);
    }

    /**
     * @param $incident
     */
    protected function addRepliesToIncident($incident)
    {
        $user = factory(User::class)->create();
        $reply1 = Reply::create([
            'body' => 'Si us plau podeu detallar una mica més el problema?',
            'user_id' => $user->id
        ]);
        $user2 = factory(User::class)->create();

        $reply2 = Reply::create([
            'body' => 'En realitat només falla la llum',
            'user_id' => $user2->id
        ]);
        $reply3 = Reply::create([
            'body' => 'Tanquem doncs la incidència, ja ha tornat la llum',
            'user_id' => $user->id
        ]);
        $incident->addReply($reply1);
        $incident->addReply($reply2);
        $incident->addReply($reply3);
    }

    /**
     * @return mixed
     */
    protected function prepareIncidentWithReplies()
    {
        $incident = $this->createIncident();
        $incidentUser = factory(User::class)->create();
        $incident->assignUser($incidentUser);
        $this->addRepliesToIncident($incident);
        return $incident;
    }

    protected function createUserWithRoleIncidents()
    {
        $user = factory(User::class)->create([
            'name' => 'Pepe Pardo Jeans',
            'email' => 'pepepardojeans@gmail.com'
        ]);
        $role = Role::firstOrCreate(['name' => 'Incidents']);
        Config::set('auth.providers.users.model', User::class);
        $user->assignRole($role);
        return $user;
    }

    /**
     * @test
     */
    public function an_incident_can_have_replies()
    {
        $incident =  $this->prepareIncidentWithReplies();

        $user = $this->createUserWithRoleIncidents();
        $this->actingAs($user,'api');

        $response = $this->json('GET','/api/v1/incidents/' . $incident->id . '/replies');
        $response->assertSuccessful();
        $result = json_decode($response->getContent());
        $this->assertEquals('Si us plau podeu detallar una mica més el problema?', $result[0]->body);
        $this->assertEquals( 'En realitat només falla la llum', $result[1]->body);
        $this->assertEquals('Tanquem doncs la incidència, ja ha tornat la llum', $result[2]->body);
    }

    /**
     * @test
     */
    public function regular_user_cannot_access_to_incident_replies()
    {
        $incident =  $this->prepareIncidentWithReplies();
        $user = factory(User::class)->create();
        $this->actingAs($user,'api');

        $response = $this->json('GET','/api/v1/incidents/' . $incident->id . '/replies');
        $response->assertStatus(403);
    }

    /**
     * @test
     */
    public function regular_user_cannot_access_to_incident_replies_if_incident_does_not_exists()
    {
        $response = $this->json('GET','/api/v1/incidents/1/replies');
        $response->assertStatus(404);
    }

    /**
     * @test
     */
    public function logged_user_can_add_a_reply_to_an_incident()
    {
        $incident = $this->createIncident();
        $user = $this->createUserWithRoleIncidents();
        $this->actingAs($user,'api');
        $response = $this->json('POST','/api/v1/incidents/' . $incident->id . '/replies',[
            'body' => 'Ja us hem resolt la incidència.'
        ]);
        $response->assertSuccessful();
        $result = json_decode($response->getContent());
        $this->assertEquals('Ja us hem resolt la incidència.', $result->body);
        $this->assertEquals( $user->id, $result->user_id);
        $this->assertEquals($user->name, $result->user_name);
        $this->assertEquals($user->email, $result->user_email);
    }
}
