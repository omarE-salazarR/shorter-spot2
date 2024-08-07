<?php
namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Url;
use App\Models\User;
use Illuminate\Support\Str;

class ShorterControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $token;

    protected function setUp(): void
    {
        
        parent::setUp();
        $this->artisan('migrate');
        // Crea un usuario y un token para pruebas
        $this->user = User::factory()->create();
        $this->token = $this->user->createToken('TestToken')->plainTextToken;
    }

    /** @test */
    public function it_should_return_short_url_when_url_exists()
    {
        $url = Url::create([
            'original_url' => 'http://example.com',
            'short_url' => 'http://short.url/abcd1234',
        ]);

        $response = $this->withHeaders([
            'Authorization' => "Bearer $this->token"
        ])->postJson('/api/obtainUrl', [
            'url' => 'http://example.com',
            'domain' => 'http://short.url'
        ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'short_url' => 'http://short.url/abcd1234',
                 ]);
    }

    /** @test */
    public function it_should_create_new_short_url_when_url_does_not_exist()
    {
        $domain = 'http://short.url';
        $url = 'http://newexample.com';

        $response = $this->withHeaders([
            'Authorization' => "Bearer $this->token"
        ])->postJson('/api/obtainUrl', [
            'url' => $url,
            'domain' => $domain
        ]);

        $response->assertStatus(200)
                 ->assertJsonStructure(['short_url']);

        $this->assertDatabaseHas('urls', [
            'original_url' => $url,
            'short_url' => $domain . '/' . Str::substr($response->json('short_url'), strrpos($response->json('short_url'), '/') + 1),
        ]);
    }

    /** @test */
    public function it_should_return_validation_errors()
    {
        $response = $this->withHeaders([
            'Authorization' => "Bearer $this->token"
        ])->postJson('/api/obtainUrl', []);

        $response->assertStatus(422)
                 ->assertJsonStructure(['errors']);
    }
}
