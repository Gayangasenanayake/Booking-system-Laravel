<?php

namespace Modules\Activity\Tests\Unit;

use Modules\Activity\Database\factories\ActivityFactory;
use Modules\Activity\Database\factories\ActivityPriceInfoFactory;
use Tests\Feature\AuthenticationTest;
use Tests\TestCase;

class ActivityPriceInfoTest extends TestCase
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
    public function testStoreActivityPriceInto()
    {
        $connectionConfig = \config('database.connections.tenant');
        $connectionConfig['database'] = 'bb_'.AuthenticationTest::$tenantIdentifier;
        \config(['database.connections.tenant'=>$connectionConfig]);

        $activity = ActivityFactory::new()->connection('tenant')->create();
        $price = [
            'base_price'=>150,
            'advertised_price'=>200
        ];
        $headers = self::$headers;
        $response = $this->postJson('http://'.AuthenticationTest::$tenantIdentifier.'.'.env('BASE_DOMAIN').'/api/activity/'.$activity->id.'/pricing_info',$price,$headers);
        $response->assertStatus(200);
    }

    public function testStoreActivityPriceIntoWithNull()
    {
        $connectionConfig = \config('database.connections.tenant');
        $connectionConfig['database'] = 'bb_'.AuthenticationTest::$tenantIdentifier;
        \config(['database.connections.tenant'=>$connectionConfig]);

        $activity = ActivityFactory::new()->connection('tenant')->create();
        $price = [ ];
        $headers = self::$headers;
        $response = $this->postJson('http://'.AuthenticationTest::$tenantIdentifier.'.'.env('BASE_DOMAIN').'/api/activity/'.$activity->id.'/pricing_info',$price,$headers);
        $response->assertStatus(422);
        $response->assertJson([
           "errors"=>[
               "base_price"=>["The base price field is required."],
               "advertised_price"=>["The advertised price field is required."]
           ]
        ]);
    }

    public function testStoreActivityPriceIntoWithInvalidData()
    {
        $connectionConfig = \config('database.connections.tenant');
        $connectionConfig['database'] = 'bb_'.AuthenticationTest::$tenantIdentifier;
        \config(['database.connections.tenant'=>$connectionConfig]);

        $activity = ActivityFactory::new()->connection('tenant')->create();
        $price = [
            'base_price'=>"test",
            'advertised_price'=>"test"
        ];
        $headers = self::$headers;
        $response = $this->postJson('http://'.AuthenticationTest::$tenantIdentifier.'.'.env('BASE_DOMAIN').'/api/activity/'.$activity->id.'/pricing_info',$price,$headers);
        $response->assertStatus(422);
        $response->assertJson([
            "errors"=>[
                "base_price"=>["The base price field must be a number."],
                "advertised_price"=>["The advertised price field must be a number."]
            ]
        ]);
    }

    public function testTryToStorePriceInfoAgainForSameActivity()
    {
        $connectionConfig = \config('database.connections.tenant');
        $connectionConfig['database'] = 'bb_'.AuthenticationTest::$tenantIdentifier;
        \config(['database.connections.tenant'=>$connectionConfig]);

        $activity = ActivityFactory::new()->connection('tenant')->create();
        ActivityPriceInfoFactory::new()->connection('tenant')->create(['activity_id'=>$activity->id]);
        $price = [
            'base_price'=>500,
            'advertised_price'=>1500
        ];
        $headers = self::$headers;
        $response = $this->postJson('http://'.AuthenticationTest::$tenantIdentifier.'.'.env('BASE_DOMAIN').'/api/activity/'.$activity->id.'/pricing_info',$price,$headers);
        $response->assertStatus(422);
        $response->assertJson([
            "message"=> "Activity already has a price info!"
        ]);
    }

    public function testUpdateActivityPriceInfo()
    {
        $connectionConfig = \config('database.connections.tenant');
        $connectionConfig['database'] = 'bb_'.AuthenticationTest::$tenantIdentifier;
        \config(['database.connections.tenant'=>$connectionConfig]);

        $activity = ActivityFactory::new()->connection('tenant')->create();
        $price_info = ActivityPriceInfoFactory::new()->connection('tenant')->create(['activity_id'=>$activity->id]);
        $new_price = [
            'base_price'=>1000,
            'advertised_price'=>1500
        ];
        $headers = self::$headers;
        $response = $this->putJson('http://'.AuthenticationTest::$tenantIdentifier.'.'.env('BASE_DOMAIN').'/api/activity/'.$activity->id.'/pricing_info/'.$price_info->id,$new_price,$headers);
        $response->assertStatus(200);
    }

    public function testUpdateActivityPriceInfoWithInvalidValues()
    {
        $connectionConfig = \config('database.connections.tenant');
        $connectionConfig['database'] = 'bb_'.AuthenticationTest::$tenantIdentifier;
        \config(['database.connections.tenant'=>$connectionConfig]);

        $activity = ActivityFactory::new()->connection('tenant')->create();
        $price_info = ActivityPriceInfoFactory::new()->connection('tenant')->create(['activity_id'=>$activity->id]);
        $new_price = [
            'base_price'=>"true",
            'advertised_price'=>"update"
        ];
        $headers = self::$headers;
        $response = $this->putJson('http://'.AuthenticationTest::$tenantIdentifier.'.'.env('BASE_DOMAIN').'/api/activity/'.$activity->id.'/pricing_info/'.$price_info->id,$new_price,$headers);
        $response->assertStatus(422);
        $response->assertJson([
            "errors"=>[
                "base_price"=>["The base price field must be a number."],
                "advertised_price"=>["The advertised price field must be a number."]
            ]
        ]);
    }

    public function testUpdateActivityPriceInfoWithNullValues()
    {
        $connectionConfig = \config('database.connections.tenant');
        $connectionConfig['database'] = 'bb_'.AuthenticationTest::$tenantIdentifier;
        \config(['database.connections.tenant'=>$connectionConfig]);

        $activity = ActivityFactory::new()->connection('tenant')->create();
        $price_info = ActivityPriceInfoFactory::new()->connection('tenant')->create(['activity_id'=>$activity->id]);
        $new_price = [];
        $headers = self::$headers;
        $response = $this->putJson('http://'.AuthenticationTest::$tenantIdentifier.'.'.env('BASE_DOMAIN').'/api/activity/'.$activity->id.'/pricing_info/'.$price_info->id,$new_price,$headers);
        $response->assertStatus(422);
        $response->assertJson([
            "errors"=>[
                "base_price"=>["The base price field is required."],
                "advertised_price"=>["The advertised price field is required."]
            ]
        ]);
    }

    public function testUpdateActivityPriceInfoWithInvalidId()
    {
        $connectionConfig = \config('database.connections.tenant');
        $connectionConfig['database'] = 'bb_'.AuthenticationTest::$tenantIdentifier;
        \config(['database.connections.tenant'=>$connectionConfig]);

        $activity = ActivityFactory::new()->connection('tenant')->create();
        $price_info = ActivityPriceInfoFactory::new()->connection('tenant')->create(['activity_id'=>$activity->id]);
        $new_price = [
            'base_price'=>1000,
            'advertised_price'=>1500
        ];
        $headers = self::$headers;
        $response = $this->putJson('http://'.AuthenticationTest::$tenantIdentifier.'.'.env('BASE_DOMAIN').'/api/activity/'.$activity->id.'/pricing_info/'.$price_info->id+1,$new_price,$headers);
        $response->assertStatus(500);
        $response->assertJson([
            "message"=>"No query results for model [Modules\\Activity\\Entities\\ActivityPricingInfo] ".$price_info->id+1
        ]);
    }
}
