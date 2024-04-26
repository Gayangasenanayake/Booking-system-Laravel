<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
//    use RefreshDatabase;
    use WithFaker;

    public static $token;
    public static $tenantIdentifier;
    /**
     * @test
     *
     * @return void
     */
    public function testTenantRegister(): void
    {
        self::$tenantIdentifier = $this->faker->firstName;
        $response = $this->postJson('/api/register', [
            'tenant_name' => self::$tenantIdentifier,
            'name' => self::$tenantIdentifier,
            'email' => self::$tenantIdentifier.'@gmail.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);
//        $decode_response = json_decode($response->getContent());
//        $_ENV['BEARER_TOKEN'] = $decode_response->token;
        $response->assertStatus(200);
    }

    /**
     * @test
     *
     * @return void
     */
    public function testLogin(): void
    {
        $response = $this->postJson('http://'.self::$tenantIdentifier.'.'.env('BASE_DOMAIN').'/api/login', [
            'email' => self::$tenantIdentifier.'@gmail.com',
            'password' => 'password',
        ]);
        $decode_response = json_decode($response->getContent());
//        $_ENV['BEARER_TOKEN'] = $decode_response->token;
        self::$token = $decode_response->token;
        $response->assertStatus(200);
    }
}
