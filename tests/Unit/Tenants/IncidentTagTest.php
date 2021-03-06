<?php

namespace Tests\Unit\Tenants;

use App\Console\Kernel;
use App\Models\IncidentTag;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Class IncidentTagTest.
 *
 * @package Tests\Unit\Tenants
 */
class IncidentTagTest extends TestCase
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
    public function map()
    {
        $tag= IncidentTag::create([
            'value' => 'wontfix',
            'description' => 'No és vol o no es pot resoldre',
            'color' => '#456578',
            'icon' => 'label'
        ]);

        $mappedTag = $tag->map();
        $this->assertEquals(1,$mappedTag['id']);
        $this->assertEquals('wontfix',$mappedTag['value']);
        $this->assertEquals('No és vol o no es pot resoldre',$mappedTag['description']);
        $this->assertEquals('#456578',$mappedTag['color']);
        $this->assertEquals('label',$mappedTag['icon']);
        $this->assertNotNull($mappedTag['created_at']);
        $this->assertNotNull($mappedTag['updated_at']);
        $this->assertNotNull($mappedTag['created_at_timestamp']);
        $this->assertNotNull($mappedTag['updated_at_timestamp']);
        $this->assertNotNull($mappedTag['formatted_created_at']);
        $this->assertNotNull($mappedTag['formatted_updated_at']);
        $this->assertNotNull($mappedTag['formatted_created_at_diff']);
        $this->assertNotNull($mappedTag['formatted_updated_at_diff']);
        $this->assertEquals('incident_tags',$mappedTag['api_uri']);

    }
}
