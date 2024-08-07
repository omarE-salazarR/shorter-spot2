<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Url;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Http\Request;

class ShorterControllerIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected $token;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
        // Crear un usuario para las pruebas
        
        $this->artisan('db:seed'); // Asegúrate de que tu seeder crea un usuario para las pruebas

        // Obtener token de autenticación
        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'password123'
        ]);
        $this->token = $response->json('token');
    }

    /** @test */
    public function it_should_return_short_url_when_url_exists()
    {
        $url = Url::create([
            'original_url' => 'http://example.com',
            'short_url' => 'http://short.url/abcd1234',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
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
            'Authorization' => 'Bearer ' . $this->token,
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
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/obtainUrl', []);

        $response->assertStatus(422)
                 ->assertJsonStructure(['errors']);
    }

    /** @test */
    public function it_should_redirect_to_original_url()
    {
        $url = Url::create([
            'original_url' => 'http://redirectexample.com',
            'short_url' => 'http://short.url/redirect1234',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/verify', [
            'url' => 'http://short.url/redirect1234'
        ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'url' => 'http://redirectexample.com',
                 ]);
    }
}
