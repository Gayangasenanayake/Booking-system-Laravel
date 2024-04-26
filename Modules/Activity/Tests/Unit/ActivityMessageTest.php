<?php

namespace Modules\Activity\Tests\Unit;

use Modules\Activity\Database\factories\ActivityFactory;
use Modules\Activity\Database\factories\ActivityMessageFactory;
use Tests\Feature\AuthenticationTest;
use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ActivityMessageTest extends TestCase
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
    public function testViewActivityMessage()
    {
        $connectionConfig = \config('database.connections.tenant');
        $connectionConfig['database'] = 'bb_'.AuthenticationTest::$tenantIdentifier;
        \config(['database.connections.tenant'=>$connectionConfig]);

        $activity = ActivityFactory::new()->connection('tenant')->create();
        $message = ActivityMessageFactory::new()->connection('tenant')->create(['activity_id'=>$activity->id]);
        $headers = self::$headers;
        $response = $this->getJson('http://'.AuthenticationTest::$tenantIdentifier.'.'.env('BASE_DOMAIN').'/api/activity/'.$activity->id.'/message',$headers);
        $response->assertStatus(200);
    }

    public function testStoreActivityMessage()
    {
        $connectionConfig = \config('database.connections.tenant');
        $connectionConfig['database'] = 'bb_'.AuthenticationTest::$tenantIdentifier;
        \config(['database.connections.tenant'=>$connectionConfig]);

        $activity = ActivityFactory::new()->connection('tenant')->create();
        $message = ActivityMessageFactory::new()->make()->toArray();
        $headers = self::$headers;
        $response = $this->postJson('http://'.AuthenticationTest::$tenantIdentifier.'.'.env('BASE_DOMAIN').'/api/activity/'.$activity->id.'/message',$message,$headers);
        $response->assertStatus(201);
    }

    public function testStoreActivityMessageWithNull()
    {
        $connectionConfig = \config('database.connections.tenant');
        $connectionConfig['database'] = 'bb_'.AuthenticationTest::$tenantIdentifier;
        \config(['database.connections.tenant'=>$connectionConfig]);

        $activity = ActivityFactory::new()->connection('tenant')->create();
        $message = [ ];
        $headers = self::$headers;
        $response = $this->postJson('http://'.AuthenticationTest::$tenantIdentifier.'.'.env('BASE_DOMAIN').'/api/activity/'.$activity->id.'/message',$message,$headers);
        $response->assertStatus(422);
        $response->assertJson([
            "errors"=>[
                "subject"=>["The subject field is required."],
                "body"=>["The body field is required."],
                "from"=>["The from field is required."],
                "to"=>["The to field is required."],
                "days"=>["The days field is required."],
                "after_or_before"=>["The after or before field is required."],
            ]
        ]);
    }

    public function testStoreActivityMessageWithInvalidValues()
    {
        $connectionConfig = \config('database.connections.tenant');
        $connectionConfig['database'] = 'bb_'.AuthenticationTest::$tenantIdentifier;
        \config(['database.connections.tenant'=>$connectionConfig]);

        $activity = ActivityFactory::new()->connection('tenant')->create();
        $message = [
            'subject'=>450,
            'body'=>456,588,
            'from'=>"shan",
            'to'=>"to",
            'days'=>"two",
            'after_or_before'=>456
        ];
        $headers = self::$headers;
        $response = $this->postJson('http://'.AuthenticationTest::$tenantIdentifier.'.'.env('BASE_DOMAIN').'/api/activity/'.$activity->id.'/message',$message,$headers);
        $response->assertStatus(422);
        $response->assertJson([
            "errors"=>[
                "subject"=>["The subject field must be a string."],
                "body"=>["The body field must be a string."],
                "from"=>["The from field must be a valid email address."],
                "to"=>["The to field must be a valid email address."],
                "days"=>["The days field must be an integer."],
                "after_or_before"=>["The after or before field must be a string."],
            ]
        ]);
    }

    public function testUpdateActivityMessage()
    {
        $connectionConfig = \config('database.connections.tenant');
        $connectionConfig['database'] = 'bb_'.AuthenticationTest::$tenantIdentifier;
        \config(['database.connections.tenant'=>$connectionConfig]);

        $activity = ActivityFactory::new()->connection('tenant')->create();
        $message = ActivityMessageFactory::new()->connection('tenant')->create(['activity_id'=>$activity->id]);
        $new_message = ActivityMessageFactory::new()->make()->toArray();
        $headers = self::$headers;
        $response = $this->putJson('http://'.AuthenticationTest::$tenantIdentifier.'.'.env('BASE_DOMAIN').'/api/activity/'.$activity->id.'/message/'.$message->id,$new_message,$headers);
        $response->assertStatus(200);
    }

    public function testTryToUpdateActivityMessageWithNullValues()
    {
        $connectionConfig = \config('database.connections.tenant');
        $connectionConfig['database'] = 'bb_'.AuthenticationTest::$tenantIdentifier;
        \config(['database.connections.tenant'=>$connectionConfig]);

        $activity = ActivityFactory::new()->connection('tenant')->create();
        $message = ActivityMessageFactory::new()->connection('tenant')->create(['activity_id'=>$activity->id]);
        $new_message = [];
        $headers = self::$headers;
        $response = $this->putJson('http://'.AuthenticationTest::$tenantIdentifier.'.'.env('BASE_DOMAIN').'/api/activity/'.$activity->id.'/message/'.$message->id,$new_message,$headers);
        $response->assertStatus(422);
        $response->assertJson([
            "errors"=>[
                "subject"=>["The subject field is required."],
                "body"=>["The body field is required."],
                "from"=>["The from field is required."],
                "to"=>["The to field is required."],
                "days"=>["The days field is required."],
                "after_or_before"=>["The after or before field is required."],
            ]
        ]);
    }

    public function testTryToUpdateActivityMessageWithInvalidValues()
    {
        $connectionConfig = \config('database.connections.tenant');
        $connectionConfig['database'] = 'bb_'.AuthenticationTest::$tenantIdentifier;
        \config(['database.connections.tenant'=>$connectionConfig]);

        $activity = ActivityFactory::new()->connection('tenant')->create();
        $message = ActivityMessageFactory::new()->connection('tenant')->create(['activity_id'=>$activity->id]);
        $new_message = [
            'subject'=>450,
            'body'=>456,588,
            'from'=>"shan",
            'to'=>"to",
            'days'=>"two",
            'after_or_before'=>456
        ];
        $headers = self::$headers;
        $response = $this->putJson('http://'.AuthenticationTest::$tenantIdentifier.'.'.env('BASE_DOMAIN').'/api/activity/'.$activity->id.'/message/'.$message->id,$new_message,$headers);
        $response->assertStatus(422);
        $response->assertJson([
            "errors"=>[
                "subject"=>["The subject field must be a string."],
                "body"=>["The body field must be a string."],
                "from"=>["The from field must be a valid email address."],
                "to"=>["The to field must be a valid email address."],
                "days"=>["The days field must be an integer."],
                "after_or_before"=>["The after or before field must be a string."],
            ]
        ]);
    }

    public function testTryToUpdateActivityMessageUsingInvalidId()
    {
        $connectionConfig = \config('database.connections.tenant');
        $connectionConfig['database'] = 'bb_'.AuthenticationTest::$tenantIdentifier;
        \config(['database.connections.tenant'=>$connectionConfig]);

        $activity = ActivityFactory::new()->connection('tenant')->create();
        $message = ActivityMessageFactory::new()->connection('tenant')->create(['activity_id'=>$activity->id]);
        $new_message = ActivityMessageFactory::new()->make()->toArray();
        $headers = self::$headers;
        $response = $this->putJson('http://'.AuthenticationTest::$tenantIdentifier.'.'.env('BASE_DOMAIN').'/api/activity/'.$activity->id.'/message/'.$message->id+1,$new_message,$headers);
        $response->assertStatus(500);
        $response->assertJson([
            "message"=>"No query results for model [Modules\\Activity\\Entities\\Message] ".$message->id+1
        ]);
    }
}
