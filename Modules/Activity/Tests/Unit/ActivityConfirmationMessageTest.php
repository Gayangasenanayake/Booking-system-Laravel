<?php

namespace Modules\Activity\Tests\Unit;

use Database\Factories\TenantFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Modules\Activity\Database\factories\ActivityConfrimationMessageFactory;
use Modules\Activity\Database\factories\ActivityFactory;
use Tests\Feature\AuthenticationTest;
use Tests\TestCase;

class ActivityConfirmationMessageTest extends TestCase
{
    protected static $headers;

    public function setUp(): void
    {
        $token = AuthenticationTest::$token;
        self::$headers = ['Authorization' => 'Bearer '.$token];
        parent::setUp();
    }
    /**
     * A basic unit test example.
     *
     * @return void
     */
    public function testStoreConfirmationMessage()
    {
        $connectionConfig = \config('database.connections.tenant');
        $connectionConfig['database'] = 'bb_'.AuthenticationTest::$tenantIdentifier;
        \config(['database.connections.tenant'=>$connectionConfig]);

        $headers = self::$headers;
        $activity = ActivityFactory::new()->connection('tenant')->create();
        $message = ActivityConfrimationMessageFactory::new()->make()->toArray();
        $response = $this->postJson('http://'.AuthenticationTest::$tenantIdentifier.'.'.env('BASE_DOMAIN').'/api/activity/'.$activity->id.'/confirm_message',$message,$headers);
        $response->assertStatus(200);
    }

    public function testTryToStoreConfirmationMessageWithNullValue()
    {
        $connectionConfig = \config('database.connections.tenant');
        $connectionConfig['database'] = 'bb_'.AuthenticationTest::$tenantIdentifier;
        \config(['database.connections.tenant'=>$connectionConfig]);

        $headers = self::$headers;
        $activity = ActivityFactory::new()->connection('tenant')->create();
        $message = [];
        $response = $this->postJson('http://'.AuthenticationTest::$tenantIdentifier.'.'.env('BASE_DOMAIN').'/api/activity/'.$activity->id.'/confirm_message',$message,$headers);
        $response->assertStatus(500);
        $response->assertJson([
            "message"=>"The message field is required."
        ]);
    }

    public function testTryToStoreConfirmationMessageWithInvalidValue()
    {
        $connectionConfig = \config('database.connections.tenant');
        $connectionConfig['database'] = 'bb_'.AuthenticationTest::$tenantIdentifier;
        \config(['database.connections.tenant'=>$connectionConfig]);

        $headers = self::$headers;
        $activity = ActivityFactory::new()->connection('tenant')->create();
        $message = [
            'message'=>5000
        ];
        $response = $this->postJson('http://'.AuthenticationTest::$tenantIdentifier.'.'.env('BASE_DOMAIN').'/api/activity/'.$activity->id.'/confirm_message',$message,$headers);
        $response->assertStatus(500);
        $response->assertJson([
            "message"=>"The message field must be a string."
        ]);
    }

    public function testUpdateConfirmationMessage()
    {
        $connectionConfig = \config('database.connections.tenant');
        $connectionConfig['database'] = 'bb_'.AuthenticationTest::$tenantIdentifier;
        \config(['database.connections.tenant'=>$connectionConfig]);

        $headers = self::$headers;
        $activity = ActivityFactory::new()->connection('tenant')->create();
        $message = ActivityConfrimationMessageFactory::new()->connection('tenant')->create(['activity_id'=>$activity->id]);
        $new_message = ActivityConfrimationMessageFactory::new()->make()->toArray();
        $response = $this->putJson('http://'.AuthenticationTest::$tenantIdentifier.'.'.env('BASE_DOMAIN').'/api/activity/'.$activity->id.'/confirm_message/'.$message->id,$new_message,$headers);
        $response->assertStatus(200);
    }

    public function testTryToUpdateConfirmationMessageWithNullValues()
    {
        $connectionConfig = \config('database.connections.tenant');
        $connectionConfig['database'] = 'bb_'.AuthenticationTest::$tenantIdentifier;
        \config(['database.connections.tenant'=>$connectionConfig]);

        $headers = self::$headers;
        $activity = ActivityFactory::new()->connection('tenant')->create();
        $message = ActivityConfrimationMessageFactory::new()->connection('tenant')->create(['activity_id'=>$activity->id]);
        $new_message = [];
        $response = $this->putJson('http://'.AuthenticationTest::$tenantIdentifier.'.'.env('BASE_DOMAIN').'/api/activity/'.$activity->id.'/confirm_message/'.$message->id,$new_message,$headers);
        $response->assertStatus(500);
        $response->assertJson([
            "message"=>"The message field is required."
        ]);
    }

    public function testTryToUpdateConfirmationMessageWithInvalidValues()
    {
        $connectionConfig = \config('database.connections.tenant');
        $connectionConfig['database'] = 'bb_'.AuthenticationTest::$tenantIdentifier;
        \config(['database.connections.tenant'=>$connectionConfig]);

        $headers = self::$headers;
        $activity = ActivityFactory::new()->connection('tenant')->create();
        $message = ActivityConfrimationMessageFactory::new()->connection('tenant')->create(['activity_id'=>$activity->id]);
        $new_message = [
            'message' => 500
        ];
        $response = $this->putJson('http://'.AuthenticationTest::$tenantIdentifier.'.'.env('BASE_DOMAIN').'/api/activity/'.$activity->id.'/confirm_message/'.$message->id,$new_message,$headers);
        $response->assertStatus(500);
        $response->assertJson([
            "message"=>"The message field must be a string."
        ]);
    }

}
