<?php

namespace Modules\Activity\Tests\Unit;

use Modules\Activity\Database\factories\ActivityFactory;
use Modules\Activity\Database\factories\ActivityPriceTierFactory;
use Modules\Schedule\Database\factories\ScheduleFactory;
use Tests\Feature\AuthenticationTest;
use Tests\TestCase;

class ActivityPriceTierTest extends TestCase
{
    protected static $headers;

    public function setUp(): void
    {
        $token = AuthenticationTest::$token;
        self::$headers = ['Authorization' => 'Bearer ' . $token];
        parent::setUp();
    }

    /**
     * A basic unit test example.
     *
     * @return void
     */
    public function testStoreActivityPriceTier()
    {
        $connectionConfig = \config('database.connections.tenant');
        $connectionConfig['database'] = 'bb_'.AuthenticationTest::$tenantIdentifier;
        \config(['database.connections.tenant'=>$connectionConfig]);

        $activity = ActivityFactory::new()->connection('tenant')->create();
        $price_tier = ActivityPriceTierFactory::new()->make()->toArray();
        $price_tier['activity_id'] = $activity->id;
        $headers = self::$headers;
        $response = $this->postJson('http://'.AuthenticationTest::$tenantIdentifier.'.'.env('APP_DOMAIN').'/api/activity/'.$activity->id.'/price_tier',$price_tier,$headers);
        $response->assertStatus(201);
    }

    public function testTryToCreatePriceTierWithNullValues()
    {
        $connectionConfig = \config('database.connections.tenant');
        $connectionConfig['database'] = 'bb_'.AuthenticationTest::$tenantIdentifier;
        \config(['database.connections.tenant'=>$connectionConfig]);

        $activity = ActivityFactory::new()->connection('tenant')->create();
        $price_tier = [];
        $headers = self::$headers;
        $response = $this->postJson('http://'.AuthenticationTest::$tenantIdentifier.'.'.env('APP_DOMAIN').'/api/activity/'.$activity->id.'/price_tier',$price_tier,$headers);
        $response->assertStatus(422);
        $response->assertJson([
            "errors"=>[
                "name"=>["The name field is required."],
                "price"=>["The price field is required."],
                "advertised_price"=>["The advertised price field is required."]
            ]
        ]);
    }

    public function testTryToCreatePriceTierWithInvalidData()
    {
        $connectionConfig = \config('database.connections.tenant');
        $connectionConfig['database'] = 'bb_'.AuthenticationTest::$tenantIdentifier;
        \config(['database.connections.tenant'=>$connectionConfig]);

        $activity = ActivityFactory::new()->connection('tenant')->create();
        $price_tier = [
            "name" => 255,
            "price" => "test",
            "advertised_price" => "test"
        ];
        $headers = self::$headers;
        $response = $this->postJson('http://'.AuthenticationTest::$tenantIdentifier.'.'.env('APP_DOMAIN').'/api/activity/'.$activity->id.'/price_tier',$price_tier,$headers);
        $response->assertStatus(422);
        $response->assertJson([
            "errors"=>[
                "name"=>["The name field must be a string."],
                "price"=>["The price field must be a number."],
                "advertised_price"=>["The advertised price field must be a number."]
            ]
        ]);
    }

    public function testUpdateActivityPriceTier()
    {
        $connectionConfig = \config('database.connections.tenant');
        $connectionConfig['database'] = 'bb_'.AuthenticationTest::$tenantIdentifier;
        \config(['database.connections.tenant'=>$connectionConfig]);

        $activity = ActivityFactory::new()->connection('tenant')->create();
        $price_tier = ActivityPriceTierFactory::new()->connection('tenant')->create(['activity_id'=>$activity->id]);
        $new_price_tier = ActivityPriceTierFactory::new()->make()->toArray();
        $headers = self::$headers;
        $response = $this->putJson('http://'.AuthenticationTest::$tenantIdentifier.'.'.env('APP_DOMAIN').'/api/activity/'.$activity->id.'/price_tier/'.$price_tier->id,$new_price_tier,$headers);
        $response->assertStatus(200);
    }

    public function testTryToUpdateActivityPriceTierWithNullValues()
    {
        $connectionConfig = \config('database.connections.tenant');
        $connectionConfig['database'] = 'bb_'.AuthenticationTest::$tenantIdentifier;
        \config(['database.connections.tenant'=>$connectionConfig]);

        $activity = ActivityFactory::new()->connection('tenant')->create();
        $price_tier = ActivityPriceTierFactory::new()->connection('tenant')->create(['activity_id'=>$activity->id]);
        $new_price_tier = [];
        $headers = self::$headers;
        $response = $this->putJson('http://'.AuthenticationTest::$tenantIdentifier.'.'.env('APP_DOMAIN').'/api/activity/'.$activity->id.'/price_tier/'.$price_tier->id,$new_price_tier,$headers);
        $response->assertStatus(422);
        $response->assertJson([
            "errors"=>[
                "name"=>["The name field is required."],
                "price"=>["The price field is required."],
                "advertised_price"=>["The advertised price field is required."]
            ]
        ]);
    }

    public function testTryToUpdateActivityPriceTierWithInvalidValues()
    {
        $connectionConfig = \config('database.connections.tenant');
        $connectionConfig['database'] = 'bb_'.AuthenticationTest::$tenantIdentifier;
        \config(['database.connections.tenant'=>$connectionConfig]);

        $activity = ActivityFactory::new()->connection('tenant')->create();
        $price_tier = ActivityPriceTierFactory::new()->connection('tenant')->create(['activity_id'=>$activity->id]);
        $new_price_tier = [
            "name" => 255,
            "price" => "test",
            "advertised_price" => "test"
        ];
        $headers = self::$headers;
        $response = $this->putJson('http://'.AuthenticationTest::$tenantIdentifier.'.'.env('BASE_DOMAIN').'/api/activity/'.$activity->id.'/price_tier/'.$price_tier->id,$new_price_tier,$headers);
        $response->assertStatus(422);
        $response->assertJson([
            "errors"=>[
                "name"=>["The name field must be a string."],
                "price"=>["The price field must be a number."],
                "advertised_price"=>["The advertised price field must be a number."]
            ]
        ]);
    }

    public function testDestroyPriceTier(){
        $connectionConfig = \config('database.connections.tenant');
        $connectionConfig['database'] = 'bb_'.AuthenticationTest::$tenantIdentifier;
        \config(['database.connections.tenant'=>$connectionConfig]);

        $activity = ActivityFactory::new()->connection('tenant')->create();
        $price_tier = ActivityPriceTierFactory::new()->make()->toArray();
        $price_tier['activity_id'] = $activity->id;
        $headers = self::$headers;
        $result = $this->postJson('http://'.AuthenticationTest::$tenantIdentifier.'.'.env('APP_DOMAIN').'/api/activity/'.$activity->id.'/price_tier',$price_tier,$headers);
        $decode_response = json_decode($result->getContent());
        $price_tier_id = $decode_response->data->id;
        $response = $this->deleteJson('http://'.AuthenticationTest::$tenantIdentifier.'.'.env('BASE_DOMAIN').'/api/activity/'.$activity->id.'/price_tier/'.$price_tier_id, $headers);
        $response->assertStatus(200);
    }

    public function testTryToDestroyPriceTierWithRelation(){
        $connectionConfig = \config('database.connections.tenant');
        $connectionConfig['database'] = 'bb_'.AuthenticationTest::$tenantIdentifier;
        \config(['database.connections.tenant'=>$connectionConfig]);

        $activity = ActivityFactory::new()->connection('tenant')->create();
        $price_tier = ActivityPriceTierFactory::new()->make()->toArray();
        $price_tier['activity_id'] = $activity->id;
        $headers = self::$headers;
        $result = $this->postJson('http://'.AuthenticationTest::$tenantIdentifier.'.'.env('APP_DOMAIN').'/api/activity/'.$activity->id.'/price_tier',$price_tier,$headers);
        $decode_response = json_decode($result->getContent());
        $price_tier_id = $decode_response->data->id;
        $schedule = ScheduleFactory::new()->connection('tenant')->create([
            'activity_id'=>$activity->id,
            'price_tier_id'=>$price_tier_id
        ]);
        $response = $this->deleteJson('http://'.AuthenticationTest::$tenantIdentifier.'.'.env('BASE_DOMAIN').'/api/activity/'.$activity->id.'/price_tier/'.$price_tier_id,$headers);
        $response->assertStatus(422);
    }
}
