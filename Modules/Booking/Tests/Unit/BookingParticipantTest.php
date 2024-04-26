<?php

namespace Modules\Booking\Tests\Unit;

use Modules\Booking\Database\factories\BookingFactory;
use Modules\Booking\Database\factories\BookingParticipantFactory;
use Tests\Feature\AuthenticationTest;
use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class BookingParticipantTest extends TestCase
{
    /**
     * A basic unit test example.
     *
     * @return void
     */
    public function testStoreBookingParticipant()
    {
        $connectionConfig = \config('database.connections.tenant');
        $connectionConfig['database'] = 'bb_'.AuthenticationTest::$tenantIdentifier;
        \config(['database.connections.tenant'=>$connectionConfig]);

        $booking = BookingFactory::new()->make()->toArray();
        $token = AuthenticationTest::$token;
        $headers = ['Authorization' => 'Bearer ' . $token];
        $new_booking = $this->postJson('http://'.AuthenticationTest::$tenantIdentifier.'.'.env('BASE_DOMAIN').'/api/booking',$booking,$headers);
        $decode_response = json_decode($new_booking->getContent());
        $booking_id = $decode_response->id;
        $participant = BookingParticipantFactory::new()->make()->toArray();
        $response = $this->postJson('http://'.AuthenticationTest::$tenantIdentifier.'.'.env('BASE_DOMAIN').'/api/booking/'.$booking_id.'/participant',$participant,$headers);
        $response->assertStatus(201);
    }

    public function testStoreBookingParticipantWithNullValues()
    {
        $connectionConfig = \config('database.connections.tenant');
        $connectionConfig['database'] = 'bb_'.AuthenticationTest::$tenantIdentifier;
        \config(['database.connections.tenant'=>$connectionConfig]);

        $booking = BookingFactory::new()->make()->toArray();
        $token = AuthenticationTest::$token;
        $headers = ['Authorization' => 'Bearer ' . $token];
        $new_booking = $this->postJson('http://'.AuthenticationTest::$tenantIdentifier.'.'.env('BASE_DOMAIN').'/api/booking',$booking,$headers);
        $decode_response = json_decode($new_booking->getContent());
        $booking_id = $decode_response->id;
        $participant = [];
        $response = $this->postJson('http://'.AuthenticationTest::$tenantIdentifier.'.'.env('BASE_DOMAIN').'/api/booking/'.$booking_id.'/participant',$participant,$headers);
        $response->assertStatus(422);
    }

    public function testStoreBookingParticipantWithInvalidValues()
    {
        $connectionConfig = \config('database.connections.tenant');
        $connectionConfig['database'] = 'bb_'.AuthenticationTest::$tenantIdentifier;
        \config(['database.connections.tenant'=>$connectionConfig]);

        $booking = BookingFactory::new()->make()->toArray();
        $token = AuthenticationTest::$token;
        $headers = ['Authorization' => 'Bearer ' . $token];
        $new_booking = $this->postJson('http://'.AuthenticationTest::$tenantIdentifier.'.'.env('BASE_DOMAIN').'/api/booking',$booking,$headers);
        $decode_response = json_decode($new_booking->getContent());
        $booking_id = $decode_response->id;
        $participant = [
            "name"=>258,
            "email"=>"lakshan",
            "age"=>"twenty eight",
            "dietary_requirements"=>1
        ];
        $response = $this->postJson('http://'.AuthenticationTest::$tenantIdentifier.'.'.env('BASE_DOMAIN').'/api/booking/'.$booking_id.'/participant',$participant,$headers);
        $response->assertStatus(422);
    }

    public function testViewBookingParticipant()
    {
        $connectionConfig = \config('database.connections.tenant');
        $connectionConfig['database'] = 'bb_'.AuthenticationTest::$tenantIdentifier;
        \config(['database.connections.tenant'=>$connectionConfig]);

        $booking = BookingFactory::new()->make()->toArray();
        $token = AuthenticationTest::$token;
        $headers = ['Authorization' => 'Bearer ' . $token];
        $new_booking = $this->postJson('http://'.AuthenticationTest::$tenantIdentifier.'.'.env('BASE_DOMAIN').'/api/booking',$booking,$headers);
        $decode_response = json_decode($new_booking->getContent());
        $booking_id = $decode_response->id;
        $participant = BookingParticipantFactory::new()->connection('tenant')->create(['booking_id'=>$booking_id]);
        $response = $this->getJson('http://'.AuthenticationTest::$tenantIdentifier.'.'.env('BASE_DOMAIN').'/api/booking/'.$booking_id.'/participant',$headers);
        $response->assertStatus(200);
    }

    public function testUpdateBookingParticipant()
    {
        $connectionConfig = \config('database.connections.tenant');
        $connectionConfig['database'] = 'bb_'.AuthenticationTest::$tenantIdentifier;
        \config(['database.connections.tenant'=>$connectionConfig]);

        $booking = BookingFactory::new()->make()->toArray();
        $token = AuthenticationTest::$token;
        $headers = ['Authorization' => 'Bearer ' . $token];
        $new_booking = $this->postJson('http://'.AuthenticationTest::$tenantIdentifier.'.'.env('BASE_DOMAIN').'/api/booking',$booking,$headers);
        $decode_response = json_decode($new_booking->getContent());
        $booking_id = $decode_response->id;
        $participant = BookingParticipantFactory::new()->connection('tenant')->create(['booking_id'=>$booking_id]);
        $new_participant = BookingParticipantFactory::new()->make()->toArray();
        $response = $this->putJson('http://'.AuthenticationTest::$tenantIdentifier.'.'.env('BASE_DOMAIN').'/api/booking/'.$booking_id.'/participant/'.$participant->id,$new_participant,$headers);
        $response->assertStatus(200);
    }

    public function testUpdateBookingParticipantWithNullValues()
    {
        $connectionConfig = \config('database.connections.tenant');
        $connectionConfig['database'] = 'bb_'.AuthenticationTest::$tenantIdentifier;
        \config(['database.connections.tenant'=>$connectionConfig]);

        $booking = BookingFactory::new()->make()->toArray();
        $token = AuthenticationTest::$token;
        $headers = ['Authorization' => 'Bearer ' . $token];
        $new_booking = $this->postJson('http://'.AuthenticationTest::$tenantIdentifier.'.'.env('BASE_DOMAIN').'/api/booking',$booking,$headers);
        $decode_response = json_decode($new_booking->getContent());
        $booking_id = $decode_response->id;
        $participant = BookingParticipantFactory::new()->connection('tenant')->create(['booking_id'=>$booking_id]);
        $new_participant = [];
        $response = $this->putJson('http://'.AuthenticationTest::$tenantIdentifier.'.'.env('BASE_DOMAIN').'/api/booking/'.$booking_id.'/participant/'.$participant->id,$new_participant,$headers);
        $response->assertStatus(422);
    }

    public function testUpdateBookingParticipantWithInvalidValues()
    {
        $connectionConfig = \config('database.connections.tenant');
        $connectionConfig['database'] = 'bb_'.AuthenticationTest::$tenantIdentifier;
        \config(['database.connections.tenant'=>$connectionConfig]);

        $booking = BookingFactory::new()->make()->toArray();
        $token = AuthenticationTest::$token;
        $headers = ['Authorization' => 'Bearer ' . $token];
        $new_booking = $this->postJson('http://'.AuthenticationTest::$tenantIdentifier.'.'.env('BASE_DOMAIN').'/api/booking',$booking,$headers);
        $decode_response = json_decode($new_booking->getContent());
        $booking_id = $decode_response->id;
        $participant = BookingParticipantFactory::new()->connection('tenant')->create(['booking_id'=>$booking_id]);
        $new_participant = [
            "name"=>258,
            "email"=>"lakshan",
            "age"=>"twenty eight",
            "dietary_requirements"=>1
        ];
        $response = $this->putJson('http://'.AuthenticationTest::$tenantIdentifier.'.'.env('BASE_DOMAIN').'/api/booking/'.$booking_id.'/participant/'.$participant->id,$new_participant,$headers);
        $response->assertStatus(422);
    }

    public function testDestroyBookingParticipant()
    {
        $connectionConfig = \config('database.connections.tenant');
        $connectionConfig['database'] = 'bb_'.AuthenticationTest::$tenantIdentifier;
        \config(['database.connections.tenant'=>$connectionConfig]);

        $booking = BookingFactory::new()->make()->toArray();
        $token = AuthenticationTest::$token;
        $headers = ['Authorization' => 'Bearer ' . $token];
        $new_booking = $this->postJson('http://'.AuthenticationTest::$tenantIdentifier.'.'.env('BASE_DOMAIN').'/api/booking',$booking,$headers);
        $decode_response = json_decode($new_booking->getContent());
        $booking_id = $decode_response->id;
        $participant = BookingParticipantFactory::new()->connection('tenant')->create(['booking_id'=>$booking_id]);
        $response = $this->deleteJson('http://'.AuthenticationTest::$tenantIdentifier.'.'.env('BASE_DOMAIN').'/api/booking/'.$booking_id.'/participant/'.$participant->id,$headers);
        $response->assertStatus(200);
    }

    public function testDestroyBookingParticipantInvalidId()
    {
        $connectionConfig = \config('database.connections.tenant');
        $connectionConfig['database'] = 'bb_'.AuthenticationTest::$tenantIdentifier;
        \config(['database.connections.tenant'=>$connectionConfig]);

        $booking = BookingFactory::new()->make()->toArray();
        $token = AuthenticationTest::$token;
        $headers = ['Authorization' => 'Bearer ' . $token];
        $new_booking = $this->postJson('http://'.AuthenticationTest::$tenantIdentifier.'.'.env('BASE_DOMAIN').'/api/booking',$booking,$headers);
        $decode_response = json_decode($new_booking->getContent());
        $booking_id = $decode_response->id;
        $participant = BookingParticipantFactory::new()->connection('tenant')->create(['booking_id'=>$booking_id]);
        $response = $this->deleteJson('http://'.AuthenticationTest::$tenantIdentifier.'.'.env('BASE_DOMAIN').'/api/booking/'.$booking_id.'/participant/'.$participant->id+1,$headers);
        $response->assertStatus(404);
    }
}
