<?php

namespace Modules\Customer\Tests\Unit;

use Tests\Feature\AuthenticationTest;
use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Modules\Customer\Database\factories\CustomerFactory;

class CustomerTest extends TestCase
{
    use WithFaker;
    /**
     * A basic unit test example.
     *
     * @return void
     */
//    public function testCustomerRegister()
//    {
//        $connectionConfig = \config('database.connections.tenant');
//        $connectionConfig['database'] = 'bb_'.AuthenticationTest::$tenantIdentifier;
//        \config(['database.connections.tenant'=>$connectionConfig]);
//
//        $response = $this->postJson('http://'.AuthenticationTest::$tenantIdentifier.'.'.env('BASE_DOMAIN').'/api/register',[
//            'name' => 'madusanka',
//            'password' => 'password',
//            'password_confirmation' => 'password',
//            'email' => $this->faker->email(),
//            'street' => 'galle road',
//            'city' => 'galkissa',
//            'province' => 'mount lav',
//            'mobile' => '0778337399',
//            'age' => 25,
//            'dietary_request' => 1,
//        ]);
//        $response->assertStatus(201);
//    }

//    public function testCustomerRegisterWithNullValues()
//    {
//        $connectionConfig = \config('database.connections.tenant');
//        $connectionConfig['database'] = 'bb_'.AuthenticationTest::$tenantIdentifier;
//        \config(['database.connections.tenant'=>$connectionConfig]);
//
//        $customer = [];
//        $response = $this->postJson('http://'.AuthenticationTest::$tenantIdentifier.'.'.env('BASE_DOMAIN').'/api/register',$customer);
//        $response->assertStatus(422);
//    }

    public function testDisplayCustomer()
    {
        $connectionConfig = \config('database.connections.tenant');
        $connectionConfig['database'] = 'bb_'.AuthenticationTest::$tenantIdentifier;
        \config(['database.connections.tenant'=>$connectionConfig]);

        CustomerFactory::new()->connection('tenant')->create();
        $token = AuthenticationTest::$token;
        $headers = ['Authorization' => 'Bearer ' . $token];
        $response = $this->getJson('http://'.AuthenticationTest::$tenantIdentifier.'.'.env('BASE_DOMAIN').'/api/customer',$headers);
        $response->assertStatus(200);
    }
}
