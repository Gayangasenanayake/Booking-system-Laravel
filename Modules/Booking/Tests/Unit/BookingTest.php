<?php

namespace Modules\Booking\Tests\Unit;

use Modules\Booking\Database\factories\BookingFactory;
use Tests\Feature\AuthenticationTest;
use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;

class BookingTest extends TestCase
{
    use WithFaker;
    /**
     * A basic unit test example.
     *
     * @return void
     */
    public function testStoreBooking()
    {
        $connectionConfig = \config('database.connections.tenant');
        $connectionConfig['database'] = 'bb_'.AuthenticationTest::$tenantIdentifier;
        \config(['database.connections.tenant'=>$connectionConfig]);

        $booking = BookingFactory::new()->make()->toArray();
        $token = AuthenticationTest::$token;
        $headers = ['Authorization' => 'Bearer ' . $token];
        $response = $this->postJson('http://'.AuthenticationTest::$tenantIdentifier.'.'.env('BASE_DOMAIN').'/api/booking',$booking,$headers);
        if ($response->getStatusCode() !== 200) {
            dump($response->getStatusCode());
            dump($response->getContent());
        }
        $response->assertStatus(200);
    }

    public function testTryToCreateBookingWithNullValues()
    {
        $connectionConfig = \config('database.connections.tenant');
        $connectionConfig['database'] = 'bb_'.AuthenticationTest::$tenantIdentifier;
        \config(['database.connections.tenant'=>$connectionConfig]);

        $booking = [];
        $token = AuthenticationTest::$token;
        $headers = ['Authorization' => 'Bearer ' . $token];
        $response = $this->postJson('http://'.AuthenticationTest::$tenantIdentifier.'.'.env('BASE_DOMAIN').'/api/booking',$booking,$headers);
        $response->assertStatus(422);
    }

    public function testTryToCreateBookingWithInvalidValues()
    {
        $connectionConfig = \config('database.connections.tenant');
        $connectionConfig['database'] = 'bb_'.AuthenticationTest::$tenantIdentifier;
        \config(['database.connections.tenant'=>$connectionConfig]);

        $booking = [
            "booking_items"=>"schedule,22,2",
            "booking_participants"=>"Lakshan,Lakshan@gmail.com"
        ];
        $token = AuthenticationTest::$token;
        $headers = ['Authorization' => 'Bearer ' . $token];
        $response = $this->postJson('http://'.AuthenticationTest::$tenantIdentifier.'.'.env('BASE_DOMAIN').'/api/booking',$booking,$headers);
        $response->assertStatus(422);
        $response->assertJson([
            "errors"=>[
                "booking_items"=>["The booking items field must be an array."],
                "booking_participants"=>["The booking participants field must be an array."],
            ]
        ]);
    }

    public function testViewBooking()
    {
        $connectionConfig = \config('database.connections.tenant');
        $connectionConfig['database'] = 'bb_'.AuthenticationTest::$tenantIdentifier;
        \config(['database.connections.tenant'=>$connectionConfig]);

        $booking = BookingFactory::new()->make()->toArray();
        $token = AuthenticationTest::$token;
        $headers = ['Authorization' => 'Bearer ' . $token];
        $this->postJson('http://'.AuthenticationTest::$tenantIdentifier.'.'.env('BASE_DOMAIN').'/api/booking',$booking,$headers);
        $response = $this->getJson('http://'.AuthenticationTest::$tenantIdentifier.'.'.env('BASE_DOMAIN').'/api/booking',$headers);
        $response->assertStatus(200);
    }

    public function testViewBookingById()
    {
        $connectionConfig = \config('database.connections.tenant');
        $connectionConfig['database'] = 'bb_'.AuthenticationTest::$tenantIdentifier;
        \config(['database.connections.tenant'=>$connectionConfig]);

        $booking = BookingFactory::new()->make()->toArray();
        $token = AuthenticationTest::$token;
        $headers = ['Authorization' => 'Bearer ' . $token];
        $result = $this->postJson('http://'.AuthenticationTest::$tenantIdentifier.'.'.env('BASE_DOMAIN').'/api/booking',$booking,$headers);
        $response = $this->getJson('http://'.AuthenticationTest::$tenantIdentifier.'.'.env('BASE_DOMAIN').'/api/booking/'.$result->json('data.id'),$headers);
        $response->assertStatus(200);
    }

    public function testDeleteBooking()
    {
        $connectionConfig = \config('database.connections.tenant');
        $connectionConfig['database'] = 'bb_'.AuthenticationTest::$tenantIdentifier;
        \config(['database.connections.tenant'=>$connectionConfig]);

        $booking = BookingFactory::new()->make()->toArray();
        $token = AuthenticationTest::$token;
        $headers = ['Authorization' => 'Bearer ' . $token];
        $result = $this->postJson('http://'.AuthenticationTest::$tenantIdentifier.'.'.env('BASE_DOMAIN').'/api/booking',$booking,$headers);
        $decode_response = json_decode($result->getContent());
        $booking_id = $decode_response->id;
        $response = $this->deleteJson('http://'.AuthenticationTest::$tenantIdentifier.'.'.env('BASE_DOMAIN').'/api/booking/'.$booking_id,$headers);
        $response->assertStatus(200);
    }

    public function testDeleteBookingWithInvalidId()
    {
        $connectionConfig = \config('database.connections.tenant');
        $connectionConfig['database'] = 'bb_'.AuthenticationTest::$tenantIdentifier;
        \config(['database.connections.tenant'=>$connectionConfig]);

        $booking = BookingFactory::new()->make()->toArray();
        $token = AuthenticationTest::$token;
        $headers = ['Authorization' => 'Bearer ' . $token];
        $result = $this->postJson('http://'.AuthenticationTest::$tenantIdentifier.'.'.env('BASE_DOMAIN').'/api/booking',$booking,$headers);
        $decode_response = json_decode($result->getContent());
        $booking_id = $decode_response->id + 1;
        $response = $this->deleteJson('http://'.AuthenticationTest::$tenantIdentifier.'.'.env('BASE_DOMAIN').'/api/booking/'.$booking_id,$headers);
        $response->assertStatus(500);
    }
}
