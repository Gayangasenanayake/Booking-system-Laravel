<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Modules\Activity\Database\factories\ActivityFactory;
use Modules\Activity\Database\factories\PrerequisiteFactory;
use Tests\TestCase;

class ActivityPrerequisiteTesting extends TestCase
{
    protected static $headers;

    public function setUp(): void
    {
        $token = AuthenticationTest::$token;
        self::$headers = ['Authorization' => 'Bearer ' . $token];
        parent::setUp();
    }
    /**
     * A basic feature test example.
     */
    public function testViewPrerequisite(): void
    {
        $connectionConfig = \config('database.connections.tenant');
        $connectionConfig['database'] = 'bb_'.AuthenticationTest::$tenantIdentifier;
        \config(['database.connections.tenant'=>$connectionConfig]);

        $activity = ActivityFactory::new()->connection('tenant')->create();
        PrerequisiteFactory::new()->connection('tenant')->create(['activity_id'=>$activity->id]);
        $response = $this->getJson('http://'.AuthenticationTest::$tenantIdentifier.'.'.env('BASE_DOMAIN').'/api/activity/'.$activity->id.'/prerequisite');
        $response->assertStatus(200);
    }

    public function testCreatePrerequisite(): void
    {
        $connectionConfig = \config('database.connections.tenant');
        $connectionConfig['database'] = 'bb_'.AuthenticationTest::$tenantIdentifier;
        \config(['database.connections.tenant'=>$connectionConfig]);

        $activity = ActivityFactory::new()->connection('tenant')->create();
        $Prerequisite = PrerequisiteFactory::new()->make()->toArray();
        $response = $this->postJson('http://'.AuthenticationTest::$tenantIdentifier.'.'.env('BASE_DOMAIN').'/api/activity/'.$activity->id.'/prerequisite',$Prerequisite);
        $response->assertStatus(201);
    }

    public function testCreatePrerequisiteWithNullValues(): void
    {
        $connectionConfig = \config('database.connections.tenant');
        $connectionConfig['database'] = 'bb_'.AuthenticationTest::$tenantIdentifier;
        \config(['database.connections.tenant'=>$connectionConfig]);

        $activity = ActivityFactory::new()->connection('tenant')->create();
        $Prerequisite = [];
        $response = $this->postJson('http://'.AuthenticationTest::$tenantIdentifier.'.'.env('BASE_DOMAIN').'/api/activity/'.$activity->id.'/prerequisite',$Prerequisite);
        $response->assertStatus(422);
        $response->assertJson([
            "errors"=>[
                "field_type"=>["The field type field is required."],
                "title"=>["The title field is required."],
                "description"=>["The description field is required."],
            ]
        ]);
    }

    public function testCreatePrerequisiteWithInvalidValues(): void
    {
        $connectionConfig = \config('database.connections.tenant');
        $connectionConfig['database'] = 'bb_'.AuthenticationTest::$tenantIdentifier;
        \config(['database.connections.tenant'=>$connectionConfig]);

        $activity = ActivityFactory::new()->connection('tenant')->create();
        $Prerequisite = [
            "field_type"=>500,
            "title"=>600,
            "description"=>700
        ];
        $response = $this->postJson('http://'.AuthenticationTest::$tenantIdentifier.'.'.env('BASE_DOMAIN').'/api/activity/'.$activity->id.'/prerequisite',$Prerequisite);
        $response->assertStatus(422);
        $response->assertJson([
            "errors"=>[
                "field_type"=>["The field type field must be a string."],
                "title"=>["The title field must be a string."],
            ]
        ]);
    }

    public function testUpdatePrerequisite(): void
    {
        $connectionConfig = \config('database.connections.tenant');
        $connectionConfig['database'] = 'bb_'.AuthenticationTest::$tenantIdentifier;
        \config(['database.connections.tenant'=>$connectionConfig]);

        $activity = ActivityFactory::new()->connection('tenant')->create();
        $Prerequisite = PrerequisiteFactory::new()->connection('tenant')->create(['activity_id'=>$activity->id]);
        $new_Prerequisite = PrerequisiteFactory::new()->make()->toArray();
        $response = $this->putJson('http://'.AuthenticationTest::$tenantIdentifier.'.'.env('BASE_DOMAIN').'/api/activity/'.$activity->id.'/prerequisite/'.$Prerequisite->id,$new_Prerequisite);
        $response->assertStatus(200);
    }

    public function testUpdatePrerequisiteWithNullValues(): void
    {
        $connectionConfig = \config('database.connections.tenant');
        $connectionConfig['database'] = 'bb_'.AuthenticationTest::$tenantIdentifier;
        \config(['database.connections.tenant'=>$connectionConfig]);

        $activity = ActivityFactory::new()->connection('tenant')->create();
        $Prerequisite = PrerequisiteFactory::new()->connection('tenant')->create(['activity_id'=>$activity->id]);
        $new_Prerequisite = [];
        $response = $this->putJson('http://'.AuthenticationTest::$tenantIdentifier.'.'.env('BASE_DOMAIN').'/api/activity/'.$activity->id.'/prerequisite/'.$Prerequisite->id,$new_Prerequisite);
        $response->assertStatus(422);
        $response->assertJson([
            "errors"=>[
                "field_type"=>["The field type field is required."],
                "title"=>["The title field is required."],
                "description"=>["The description field is required."],
            ]
        ]);
    }

    public function testUpdatePrerequisiteWithInvalidValues(): void
    {
        $connectionConfig = \config('database.connections.tenant');
        $connectionConfig['database'] = 'bb_'.AuthenticationTest::$tenantIdentifier;
        \config(['database.connections.tenant'=>$connectionConfig]);

        $activity = ActivityFactory::new()->connection('tenant')->create();
        $Prerequisite = PrerequisiteFactory::new()->connection('tenant')->create(['activity_id'=>$activity->id]);
        $new_Prerequisite = [
            "field_type"=>500,
            "title"=>600,
            "description"=>700
        ];
        $response = $this->putJson('http://'.AuthenticationTest::$tenantIdentifier.'.'.env('BASE_DOMAIN').'/api/activity/'.$activity->id.'/prerequisite/'.$Prerequisite->id,$new_Prerequisite);
        $response->assertStatus(422);
        $response->assertJson([
            "errors"=>[
                "field_type"=>["The field type field must be a string."],
                "title"=>["The title field must be a string."],
            ]
        ]);
    }

    public function testRemovePrerequisite(): void
    {
        $connectionConfig = \config('database.connections.tenant');
        $connectionConfig['database'] = 'bb_'.AuthenticationTest::$tenantIdentifier;
        \config(['database.connections.tenant'=>$connectionConfig]);

        $activity = ActivityFactory::new()->connection('tenant')->create();
        $Prerequisite = PrerequisiteFactory::new()->connection('tenant')->create(['activity_id'=>$activity->id]);
        $response = $this->deleteJson('http://'.AuthenticationTest::$tenantIdentifier.'.'.env('BASE_DOMAIN').'/api/activity/'.$activity->id.'/prerequisite/'.$Prerequisite->id);
        $response->assertStatus(200);
    }

    public function testTryToRemovePrerequisiteWithInvalidId(): void
    {
        $connectionConfig = \config('database.connections.tenant');
        $connectionConfig['database'] = 'bb_'.AuthenticationTest::$tenantIdentifier;
        \config(['database.connections.tenant'=>$connectionConfig]);

        $activity = ActivityFactory::new()->connection('tenant')->create();
        $Prerequisite = PrerequisiteFactory::new()->connection('tenant')->create(['activity_id'=>$activity->id]);
        $response = $this->deleteJson('http://'.AuthenticationTest::$tenantIdentifier.'.'.env('BASE_DOMAIN').'/api/activity/'.$activity->id.'/prerequisite/'.$Prerequisite->id+1);
        $response->assertStatus(500);
        $response->assertJson([
            "message"=>"No query results for model [Modules\\Activity\\Entities\\Prerequisities] ".$Prerequisite->id+1
        ]);
    }
}
