<?php

namespace Modules\Activity\Tests\Unit;

use Tests\Feature\AuthenticationTest;
use Tests\TestCase;
use Modules\Activity\Database\factories\ActivityFactory;
use Modules\Activity\Database\factories\ActivitySeoFactory;

class ActivitySeoTest extends TestCase
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
    public function testViewSeo()
    {
        $connectionConfig = \config('database.connections.tenant');
        $connectionConfig['database'] = 'bb_'.AuthenticationTest::$tenantIdentifier;
        \config(['database.connections.tenant'=>$connectionConfig]);

        $activity = ActivityFactory::new()->connection('tenant')->create();
        ActivitySeoFactory::new()->connection('tenant')->create(['activity_id'=>$activity->id]);
        $headers = self::$headers;
        $response = $this->getJson('http://'.AuthenticationTest::$tenantIdentifier.'.'.env('BASE_DOMAIN').'/api/activity/'.$activity->id.'/seo',$headers);
        $response->assertStatus(200);
    }

    public function testStoreSeo()
    {
        $connectionConfig = \config('database.connections.tenant');
        $connectionConfig['database'] = 'bb_'.AuthenticationTest::$tenantIdentifier;
        \config(['database.connections.tenant'=>$connectionConfig]);

        $activity = ActivityFactory::new()->connection('tenant')->create();
        $seo = ActivitySeoFactory::new()->make()->toArray();
        $headers = self::$headers;
        $response = $this->postJson('http://'.AuthenticationTest::$tenantIdentifier.'.'.env('BASE_DOMAIN').'/api/activity/'.$activity->id.'/seo',$seo,$headers);
        $response->assertStatus(201);
    }

    public function testStoreSeoWithNullValues()
    {
        $connectionConfig = \config('database.connections.tenant');
        $connectionConfig['database'] = 'bb_'.AuthenticationTest::$tenantIdentifier;
        \config(['database.connections.tenant'=>$connectionConfig]);

        $activity = ActivityFactory::new()->connection('tenant')->create();
        $seo = [];
        $headers = self::$headers;
        $response = $this->postJson('http://'.AuthenticationTest::$tenantIdentifier.'.'.env('BASE_DOMAIN').'/api/activity/'.$activity->id.'/seo',$seo,$headers);
        $response->assertStatus(422);
        $response->assertJson([
            "errors"=>[
                "meta_title"=>["The meta title field is required."],
                "meta_description"=>["The meta description field is required."],
            ]
        ]);
    }

    public function testStoreSeoWithInvalidValues()
    {
        $connectionConfig = \config('database.connections.tenant');
        $connectionConfig['database'] = 'bb_'.AuthenticationTest::$tenantIdentifier;
        \config(['database.connections.tenant'=>$connectionConfig]);

        $activity = ActivityFactory::new()->connection('tenant')->create();
        $seo = [
            'meta_title'=>580,
            'meta_description'=>456
        ];
        $headers = self::$headers;
        $response = $this->postJson('http://'.AuthenticationTest::$tenantIdentifier.'.'.env('BASE_DOMAIN').'/api/activity/'.$activity->id.'/seo',$seo,$headers);
        $response->assertStatus(422);
        $response->assertJson([
            "errors"=>[
                "meta_title"=>["The meta title field must be a string."],
                "meta_description"=>["The meta description field must be a string."],
            ]
        ]);
    }

    public function testStoreSeoForSameActivity()
    {
        $connectionConfig = \config('database.connections.tenant');
        $connectionConfig['database'] = 'bb_'.AuthenticationTest::$tenantIdentifier;
        \config(['database.connections.tenant'=>$connectionConfig]);

        $activity = ActivityFactory::new()->connection('tenant')->create();
        $seo = ActivitySeoFactory::new()->connection('tenant')->create(['activity_id'=>$activity->id]);
        $new_seo = ActivitySeoFactory::new()->make()->toArray();
        $headers = self::$headers;
        $response = $this->postJson('http://'.AuthenticationTest::$tenantIdentifier.'.'.env('BASE_DOMAIN').'/api/activity/'.$activity->id.'/seo',$new_seo,$headers);
        $response->assertStatus(422);
        $response->assertJson([
            "message"=>"Activity already has SEO data!"
        ]);
    }

    public function testUpdateSeo()
    {
        $connectionConfig = \config('database.connections.tenant');
        $connectionConfig['database'] = 'bb_'.AuthenticationTest::$tenantIdentifier;
        \config(['database.connections.tenant'=>$connectionConfig]);

        $activity = ActivityFactory::new()->connection('tenant')->create();
        $seo = ActivitySeoFactory::new()->connection('tenant')->create(['activity_id'=>$activity->id]);
        $new_seo = ActivitySeoFactory::new()->make()->toArray();
        $headers = self::$headers;
        $response = $this->putJson('http://'.AuthenticationTest::$tenantIdentifier.'.'.env('BASE_DOMAIN').'/api/activity/'.$activity->id.'/seo/'.$seo->id,$new_seo,$headers);
        $response->assertStatus(200);
    }

    public function testUpdateSeoWithNullValues()
    {
        $connectionConfig = \config('database.connections.tenant');
        $connectionConfig['database'] = 'bb_'.AuthenticationTest::$tenantIdentifier;
        \config(['database.connections.tenant'=>$connectionConfig]);

        $activity = ActivityFactory::new()->connection('tenant')->create();
        $seo = ActivitySeoFactory::new()->connection('tenant')->create(['activity_id'=>$activity->id]);
        $new_seo = [];
        $headers = self::$headers;
        $response = $this->putJson('http://'.AuthenticationTest::$tenantIdentifier.'.'.env('BASE_DOMAIN').'/api/activity/'.$activity->id.'/seo/'.$seo->id,$new_seo,$headers);
        $response->assertStatus(422);
        $response->assertJson([
            "errors"=>[
                "meta_title"=>["The meta title field is required."],
                "meta_description"=>["The meta description field is required."],
            ]
        ]);
    }

    public function testUpdateSeoWithInvalidValues()
    {
        $connectionConfig = \config('database.connections.tenant');
        $connectionConfig['database'] = 'bb_'.AuthenticationTest::$tenantIdentifier;
        \config(['database.connections.tenant'=>$connectionConfig]);

        $activity = ActivityFactory::new()->connection('tenant')->create();
        $seo = ActivitySeoFactory::new()->connection('tenant')->create(['activity_id'=>$activity->id]);
        $new_seo = [
            'meta_title'=>580,
            'meta_description'=>456
        ];
        $headers = self::$headers;
        $response = $this->putJson('http://'.AuthenticationTest::$tenantIdentifier.'.'.env('BASE_DOMAIN').'/api/activity/'.$activity->id.'/seo/'.$seo->id,$new_seo,$headers);
        $response->assertStatus(422);
        $response->assertJson([
            "errors"=>[
                "meta_title"=>["The meta title field must be a string."],
                "meta_description"=>["The meta description field must be a string."],
            ]
        ]);
    }

    public function testRemoveSeo()
    {
        $connectionConfig = \config('database.connections.tenant');
        $connectionConfig['database'] = 'bb_'.AuthenticationTest::$tenantIdentifier;
        \config(['database.connections.tenant'=>$connectionConfig]);

        $activity = ActivityFactory::new()->connection('tenant')->create();
        $seo = ActivitySeoFactory::new()->make()->toArray();
        $headers = self::$headers;
        $result = $this->postJson('http://'.AuthenticationTest::$tenantIdentifier.'.'.env('BASE_DOMAIN').'/api/activity/'.$activity->id.'/seo',$seo,$headers);
        $decode_response = json_decode($result->getContent());
        $seo_id = $decode_response->data->id;
        $response = $this->deleteJson('http://'.AuthenticationTest::$tenantIdentifier.'.'.env('BASE_DOMAIN').'/api/activity/'.$activity->id.'/seo/'.$seo_id,$headers);
        $response->assertStatus(200);
    }
}
