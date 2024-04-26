<?php

namespace Modules\Activity\Tests\Unit;

use Illuminate\Foundation\Testing\WithFaker;
use Modules\Activity\Database\factories\ActivityFactory;
use Modules\Course\Database\factories\CourseFactory;
use Modules\Schedule\Database\factories\ScheduleFactory;
use Tests\Feature\AuthenticationTest;
use Tests\TestCase;

class ActivityTest extends TestCase
{
    use WithFaker;
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
    public function testStoreActivity()
    {
        $connectionConfig = \config('database.connections.tenant');
        $connectionConfig['database'] = 'bb_'.AuthenticationTest::$tenantIdentifier;
        \config(['database.connections.tenant'=>$connectionConfig]);

        $activity = ActivityFactory::new()->make()->toArray();
        $activity['tags'] = ["sport","game","swim"];
        $headers = self::$headers;
        $response = $this->postJson('http://'.AuthenticationTest::$tenantIdentifier.'.'.env('BASE_DOMAIN').'/api/activity', $activity, $headers);
        $response->assertStatus(200);
    }

    public function testStoreActivityWithoutRequiredData()
    {
        $connectionConfig = \config('database.connections.tenant');
        $connectionConfig['database'] = 'bb_'.AuthenticationTest::$tenantIdentifier;
        \config(['database.connections.tenant'=>$connectionConfig]);

        $activity = [];
        $headers = self::$headers;
        $response = $this->postJson('http://'.AuthenticationTest::$tenantIdentifier.'.'.env('BASE_DOMAIN').'/api/activity', $activity,$headers);
        $response->assertStatus(422);
        $response->assertJson(["message" => "The title field is required. (and 3 more errors)"]);
    }

    public function testStoreActivityWithInvalidData()
    {
        $connectionConfig = \config('database.connections.tenant');
        $connectionConfig['database'] = 'bb_'.AuthenticationTest::$tenantIdentifier;
        \config(['database.connections.tenant'=>$connectionConfig]);

        $activity = [
            'title' => $this->faker->numberBetween(199,200),
            'activity_code' => $this->faker->numberBetween(199,200),
            'qty_label' => $this->faker->numberBetween(199,200),
            'short_description'=> $this->faker->numberBetween(199,200),
        ];
        $headers = self::$headers;
        $response = $this->postJson('http://'.AuthenticationTest::$tenantIdentifier.'.'.env('BASE_DOMAIN').'/api/activity', $activity,$headers);
        $response->assertStatus(422);
        $response->assertJson([
            "errors" => [
                "title" => ["The title field must be a string."],
                "activity_code" => ["The activity code field must be a string."],
                "qty_label" => ["The qty label field must be a string."],
                "short_description" => ["The short description field must be a string."]
            ]
        ]);
    }

    public function testUpdateActivity()
    {
        $connectionConfig = \config('database.connections.tenant');
        $connectionConfig['database'] = 'bb_'.AuthenticationTest::$tenantIdentifier;
        \config(['database.connections.tenant'=>$connectionConfig]);

        $activity = ActivityFactory::new()->connection('tenant')->create();
        $tags = ["sport","game","swim"];
        foreach ($tags as $value) {
            $activity->tags()->create([
                'name' => $value,
                'taggable_id' => $activity->id,
                'taggable_type' => 'App/Activity'
            ]);
        }
        $new_data = ActivityFactory::new()->make()->toArray();
        $new_data['tags'] = ["swim","gaming","fun"];
        $headers = self::$headers;
        $response = $this->putJson('http://'.AuthenticationTest::$tenantIdentifier.'.'.env('BASE_DOMAIN').'/api/activity/'.$activity->id, $new_data,$headers);
        $response->assertStatus(200);
    }

    public function testUpdateActivityWithInvalidId()
    {
        $connectionConfig = \config('database.connections.tenant');
        $connectionConfig['database'] = 'bb_'.AuthenticationTest::$tenantIdentifier;
        \config(['database.connections.tenant'=>$connectionConfig]);

        $activity = ActivityFactory::new()->connection('tenant')->create();
        $new_data = ActivityFactory::new()->make()->toArray();
        $headers = self::$headers;
        $response = $this->putJson('http://'.AuthenticationTest::$tenantIdentifier.'.'.env('BASE_DOMAIN').'/api/activity/'.$activity->id + 1, $new_data,$headers);
        $response->assertStatus(500);
        $response->assertJson([
            'message' => "No query results for model [Modules\\Activity\\Entities\\Activity] ".$activity->id + 1
        ]);
    }

    public function testDestroyActivity()
    {
        $connectionConfig = \config('database.connections.tenant');
        $connectionConfig['database'] = 'bb_'.AuthenticationTest::$tenantIdentifier;
        \config(['database.connections.tenant'=>$connectionConfig]);

        $activity = ActivityFactory::new()->make()->toArray();
        $activity['tags'] = ["sport","game","swim"];
        $headers = self::$headers;
        $result = $this->postJson('http://'.AuthenticationTest::$tenantIdentifier.'.'.env('BASE_DOMAIN').'/api/activity', $activity, $headers);
        $decode_response = json_decode($result->getContent());
        $activity_id = $decode_response->data->id;
        $response = $this->deleteJson('http://'.AuthenticationTest::$tenantIdentifier.'.'.env('BASE_DOMAIN').'/api/activity/'.$activity_id,$headers);
        $response->assertStatus(200);
    }

    public function testDestroyActivityThatAlreadyHaveRelations()
    {
        $connectionConfig = \config('database.connections.tenant');
        $connectionConfig['database'] = 'bb_'.AuthenticationTest::$tenantIdentifier;
        \config(['database.connections.tenant'=>$connectionConfig]);

        $activity = ActivityFactory::new()->make()->toArray();
        $activity['tags'] = ["sport","game","swim"];
        $headers = self::$headers;
        $result = $this->postJson('http://'.AuthenticationTest::$tenantIdentifier.'.'.env('BASE_DOMAIN').'/api/activity', $activity, $headers);
        $decode_response = json_decode($result->getContent());
        $activity_id = $decode_response->data->id;
        CourseFactory::new()->connection('tenant')->create(['activity_id'=>$activity_id]);
        $response = $this->deleteJson('http://'.AuthenticationTest::$tenantIdentifier.'.'.env('BASE_DOMAIN').'/api/activity/'.$activity_id,$headers);
        $response->assertStatus(422);
        $response->assertJson([
            'message' => "Activity already assigned to courses!"
        ]);
    }

    public function testShowActivity()
    {
        $connectionConfig = \config('database.connections.tenant');
        $connectionConfig['database'] = 'bb_'.AuthenticationTest::$tenantIdentifier;
        \config(['database.connections.tenant'=>$connectionConfig]);

        $activity = ActivityFactory::new()->connection('tenant')->create();
        $tags = ["sport","game","swim"];
        foreach ($tags as $value) {
            $activity->tags()->create([
                'name' => $value,
                'taggable_id' => $activity->id,
                'taggable_type' => 'App/Activity'
            ]);
        }
        $headers = self::$headers;
        $response = $this->getJson('http://'.AuthenticationTest::$tenantIdentifier.'.'.env('BASE_DOMAIN').'/api/activity/'.$activity->id,$headers);
        $response->assertStatus(200);
    }
}
