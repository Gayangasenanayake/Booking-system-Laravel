<?php

namespace Modules\Activity\Tests\Unit;

use Modules\Staff\Database\factories\StaffFactory;
use Tests\Feature\AuthenticationTest;
use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Modules\Activity\Database\factories\ActivityFactory;
use Modules\Activity\Database\factories\ActivityPriceTierFactory;
use Modules\Activity\Database\factories\ActivityScheduleGroupFactory;

class ActivityScheduleGroupTest extends TestCase
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
    public function testCreateActivityScheduleGroup()
    {
        $connectionConfig = \config('database.connections.tenant');
        $connectionConfig['database'] = 'bb_'.AuthenticationTest::$tenantIdentifier;
        \config(['database.connections.tenant'=>$connectionConfig]);

        $activity = ActivityFactory::new()->connection('tenant')->create();
        $price_tier = ActivityPriceTierFactory::new()->connection('tenant')->create(['activity_id'=>$activity->id]);
        $staff_members = StaffFactory::new()->times(2)->connection('tenant')->create();
        $schedule = ActivityScheduleGroupFactory::new()->make()->toArray();
        $schedule['price_tier_id'] = $price_tier->id;
        $schedule['day'] = "['Monday','Tuesday','Friday']";
        $schedule['assigned_staff'] = $staff_members->pluck('id')->toArray(); //get ids and convert to array.
        $headers = self::$headers;
        $response = $this->postJson('http://'.AuthenticationTest::$tenantIdentifier.'.'.env('BASE_DOMAIN').'/api/activity/'.$activity->id.'/schedule_group',$schedule,$headers);
        $response->assertStatus(201);
    }

    public function testCreateActivityScheduleGroupWithNullValues()
    {
        $connectionConfig = \config('database.connections.tenant');
        $connectionConfig['database'] = 'bb_'.AuthenticationTest::$tenantIdentifier;
        \config(['database.connections.tenant'=>$connectionConfig]);

        $activity = ActivityFactory::new()->connection('tenant')->create();
        $schedule = [];
        $headers = self::$headers;
        $response = $this->postJson('http://'.AuthenticationTest::$tenantIdentifier.'.'.env('BASE_DOMAIN').'/api/activity/'.$activity->id.'/schedule_group',$schedule,$headers);
        $response->assertStatus(422);
    }

    public function testCreateActivityScheduleGroupWithInvalidValues()
    {
        $connectionConfig = \config('database.connections.tenant');
        $connectionConfig['database'] = 'bb_'.AuthenticationTest::$tenantIdentifier;
        \config(['database.connections.tenant'=>$connectionConfig]);

        $activity = ActivityFactory::new()->connection('tenant')->create();
        $schedule = [
            'from_date'=> 5.6,
            'to_date'=> 6.8,
            'day'=>'monday',
            'start_time'=> 2.30,
            'end_time'=> 8.30,
            'allocated_slots'=> "twenty",
            'min_number_of_places'=> "one",
            'max_number_of_places'=> "ten"
        ];
        $headers = self::$headers;
        $response = $this->postJson('http://'.AuthenticationTest::$tenantIdentifier.'.'.env('BASE_DOMAIN').'/api/activity/'.$activity->id.'/schedule_group',$schedule,$headers);
        $response->assertStatus(422);
    }

    public function testViewActivityScheduleGroup()
    {
        $connectionConfig = \config('database.connections.tenant');
        $connectionConfig['database'] = 'bb_'.AuthenticationTest::$tenantIdentifier;
        \config(['database.connections.tenant'=>$connectionConfig]);

        $activity = ActivityFactory::new()->connection('tenant')->create();
        $price_tier = ActivityPriceTierFactory::new()->connection('tenant')->create(['activity_id'=>$activity->id]);
//        $staff_members = StaffFactory::new()->times(2)->connection('tenant')->create();
//        $staff = $staff_members->pluck('id')->toArray();
        ActivityScheduleGroupFactory::new()->connection('tenant')->create([
            'activity_id'=>$activity->id,
            'price_tier_id'=>$price_tier->id,
//            'assigned_staff'=>$staff
        ]);
        $headers = self::$headers;
        $response = $this->getJson('http://'.AuthenticationTest::$tenantIdentifier.'.'.env('BASE_DOMAIN').'/api/activity/'.$activity->id.'/schedule_group',$headers);
        $response->assertStatus(200);
    }

    public function testUpdateActivityScheduleGroup()
    {
        $connectionConfig = \config('database.connections.tenant');
        $connectionConfig['database'] = 'bb_'.AuthenticationTest::$tenantIdentifier;
        \config(['database.connections.tenant'=>$connectionConfig]);

        $activity = ActivityFactory::new()->connection('tenant')->create();
        $price_tier = ActivityPriceTierFactory::new()->connection('tenant')->create(['activity_id'=>$activity->id]);
        $old_schedule = ActivityScheduleGroupFactory::new()->connection('tenant')->create([
            'activity_id'=>$activity->id,
            'price_tier_id'=>$price_tier->id,
        ]);
        $schedule = ActivityScheduleGroupFactory::new()->make()->toArray();
        $schedule['day'] = ["Monday","Tuesday","Friday"];
        $schedule['price_tier_id'] = $price_tier->id;
//        $schedule['assigned_staff'] = [1,2];
        $headers = self::$headers;
        $response = $this->putJson('http://'.AuthenticationTest::$tenantIdentifier.'.'.env('BASE_DOMAIN').'/api/activity/'.$activity->id.'/schedule_group/'.$old_schedule->id,$schedule,$headers);
        $response->assertStatus(200);
    }

    public function testUpdateActivityScheduleGroupWithNullValues()
    {
        $connectionConfig = \config('database.connections.tenant');
        $connectionConfig['database'] = 'bb_'.AuthenticationTest::$tenantIdentifier;
        \config(['database.connections.tenant'=>$connectionConfig]);

        $activity = ActivityFactory::new()->connection('tenant')->create();
        $price_tier = ActivityPriceTierFactory::new()->connection('tenant')->create(['activity_id'=>$activity->id]);
        $old_schedule = ActivityScheduleGroupFactory::new()->connection('tenant')->create([
            'activity_id'=>$activity->id,
            'price_tier_id'=>$price_tier->id,
//            'assigned_staff'=>[1,2]
        ]);
        $schedule = [
            'from_date'=> 5.6,
            'to_date'=> 6.8,
            'day'=>'monday',
            'start_time'=> 2.30,
            'end_time'=> 8.30,
            'allocated_slots'=> "twenty",
            'min_number_of_places'=> "one",
            'max_number_of_places'=> "ten"
        ];
        $headers = self::$headers;
        $response = $this->putJson('http://'.AuthenticationTest::$tenantIdentifier.'.'.env('BASE_DOMAIN').'/api/activity/'.$activity->id.'/schedule_group/'.$old_schedule->id,$schedule,$headers);
        $response->assertStatus(422);
    }

    public function testSaveActivitySchedule()
    {
        $connectionConfig = \config('database.connections.tenant');
        $connectionConfig['database'] = 'bb_'.AuthenticationTest::$tenantIdentifier;
        \config(['database.connections.tenant'=>$connectionConfig]);

        $activity = ActivityFactory::new()->connection('tenant')->create();
        $schedule = ActivityScheduleGroupFactory::new()->make()->toArray();
        $schedule['day'] = "['Monday','Tuesday','Friday']";
        $headers = self::$headers;
        $response = $this->postJson('http://'.AuthenticationTest::$tenantIdentifier.'.'.env('BASE_DOMAIN').'/api/activity/'.$activity->id.'/schedule_group/draft',$schedule,$headers);
        $response->assertStatus(201);
    }

    public function testSaveActivityScheduleWithInvalidValues()
    {
        $connectionConfig = \config('database.connections.tenant');
        $connectionConfig['database'] = 'bb_'.AuthenticationTest::$tenantIdentifier;
        \config(['database.connections.tenant'=>$connectionConfig]);

        $activity = ActivityFactory::new()->connection('tenant')->create();
        $schedule = [
            'from_date'=> 5.6,
            'to_date'=> 6.8,
            'day'=>'monday',
            'start_time'=> 2.30,
            'end_time'=> 8.30,
        ];
        $headers = self::$headers;
        $response = $this->postJson('http://'.AuthenticationTest::$tenantIdentifier.'.'.env('BASE_DOMAIN').'/api/activity/'.$activity->id.'/schedule_group/draft',$schedule,$headers);
        $response->assertStatus(422);
    }

    public function testUpdateAndSaveActivitySchedule()
    {
        $connectionConfig = \config('database.connections.tenant');
        $connectionConfig['database'] = 'bb_'.AuthenticationTest::$tenantIdentifier;
        \config(['database.connections.tenant'=>$connectionConfig]);

        $activity = ActivityFactory::new()->connection('tenant')->create();
        $old_schedule = ActivityScheduleGroupFactory::new()->connection('tenant')->create([
            'activity_id'=>$activity->id,
        ]);
        $schedule = ActivityScheduleGroupFactory::new()->make()->toArray();
        $schedule['day'] = ["Monday","Tuesday","Friday"];
        $headers = self::$headers;
        $response = $this->putJson('http://'.AuthenticationTest::$tenantIdentifier.'.'.env('BASE_DOMAIN').'/api/activity/'.$activity->id.'/schedule_group/draft_update/'.$old_schedule->id,$schedule,$headers);
        $response->assertStatus(200);
    }

    public function testUpdateAndSaveActivityScheduleWithInvalidValues()
    {
        $connectionConfig = \config('database.connections.tenant');
        $connectionConfig['database'] = 'bb_'.AuthenticationTest::$tenantIdentifier;
        \config(['database.connections.tenant'=>$connectionConfig]);

        $activity = ActivityFactory::new()->connection('tenant')->create();
        $old_schedule = ActivityScheduleGroupFactory::new()->connection('tenant')->create([
            'activity_id'=>$activity->id,
        ]);
        $schedule = [
            'from_date'=> 5.6,
            'to_date'=> 6.8,
            'day'=>'monday',
            'start_time'=> 2.30,
            'end_time'=> 8.30,
        ];
        $headers = self::$headers;
        $response = $this->putJson('http://'.AuthenticationTest::$tenantIdentifier.'.'.env('BASE_DOMAIN').'/api/activity/'.$activity->id.'/schedule_group/draft_update/'.$old_schedule,$schedule,$headers);
        $response->assertStatus(422);
    }

    public function testRescheduleActivityScheduleGroup()
    {
        $connectionConfig = \config('database.connections.tenant');
        $connectionConfig['database'] = 'bb_'.AuthenticationTest::$tenantIdentifier;
        \config(['database.connections.tenant'=>$connectionConfig]);

        $from_date = $this->faker->dateTimeBetween(now()->addDays(7),now()->addDays(14));
        $to_date = (clone $from_date)->modify('+6 months');
        $start_time = $this->faker->dateTimeBetween('now','+3 hours');
        $end_time = (clone $start_time)->modify('+3 hours');

        $activity = ActivityFactory::new()->connection('tenant')->create();
        $price_tier = ActivityPriceTierFactory::new()->connection('tenant')->create(['activity_id'=>$activity->id]);
        $old_schedule = ActivityScheduleGroupFactory::new()->connection('tenant')->create([
            'activity_id'=>$activity->id,
            'price_tier_id'=>$price_tier->id,
//            'assigned_staff'=>[1,2]
        ]);
        $new_data = [
            'from_date'=> $from_date->format('y-m-d'),
            'to_date'=> $to_date->format('y-m-d'),
            'start_time'=> $start_time->format('h:i'),
            'end_time'=> $end_time->format('h:i'),
        ];
        $headers = self::$headers;
        $response = $this->postJson('http://'.AuthenticationTest::$tenantIdentifier.'.'.env('BASE_DOMAIN').'/api/activity/'.$activity->id.'/schedule_group/reschedule/'.$old_schedule->id,$new_data,$headers);
        $response->assertStatus(201);
    }

    public function testRescheduleActivityScheduleGroupWithNullValues()
    {
        $connectionConfig = \config('database.connections.tenant');
        $connectionConfig['database'] = 'bb_'.AuthenticationTest::$tenantIdentifier;
        \config(['database.connections.tenant'=>$connectionConfig]);

        $activity = ActivityFactory::new()->connection('tenant')->create();
        $price_tier = ActivityPriceTierFactory::new()->connection('tenant')->create(['activity_id'=>$activity->id]);
        $old_schedule = ActivityScheduleGroupFactory::new()->connection('tenant')->create([
            'activity_id'=>$activity->id,
            'price_tier_id'=>$price_tier->id,
//            'assigned_staff'=>[1,2]
        ]);
        $new_data = [];
        $headers = self::$headers;
        $response = $this->postJson('http://'.AuthenticationTest::$tenantIdentifier.'.'.env('BASE_DOMAIN').'/api/activity/'.$activity->id.'/schedule_group/reschedule/'.$old_schedule,$new_data,$headers);
        $response->assertStatus(422);
    }

    public function testRescheduleActivityScheduleGroupWithInvalidValues()
    {
        $connectionConfig = \config('database.connections.tenant');
        $connectionConfig['database'] = 'bb_'.AuthenticationTest::$tenantIdentifier;
        \config(['database.connections.tenant'=>$connectionConfig]);

        $activity = ActivityFactory::new()->connection('tenant')->create();
        $price_tier = ActivityPriceTierFactory::new()->connection('tenant')->create(['activity_id'=>$activity->id]);
        $old_schedule = ActivityScheduleGroupFactory::new()->connection('tenant')->create([
            'activity_id'=>$activity->id,
            'price_tier_id'=>$price_tier->id,
//            'assigned_staff'=>[1,2]
        ]);
        $new_data = [
            'from_date'=> 23.06,
            'to_date'=> 23.07,
            'start_time'=> 1.30,
            'end_time'=> 4.30,
        ];
        $headers = self::$headers;
        $response = $this->postJson('http://'.AuthenticationTest::$tenantIdentifier.'.'.env('BASE_DOMAIN').'/api/activity/'.$activity->id.'/schedule_group/reschedule/'.$old_schedule,$new_data,$headers);
        $response->assertStatus(422);
    }

    public function testDestroyActivityScheduleGroup()
    {
        $connectionConfig = \config('database.connections.tenant');
        $connectionConfig['database'] = 'bb_'.AuthenticationTest::$tenantIdentifier;
        \config(['database.connections.tenant'=>$connectionConfig]);

        $activity = ActivityFactory::new()->connection('tenant')->create();
        $price_tier = ActivityPriceTierFactory::new()->connection('tenant')->create(['activity_id'=>$activity->id]);
        $schedule = ActivityScheduleGroupFactory::new()->make()->toArray();
        $schedule['price_tier_id'] = $price_tier->id;
        $schedule['day'] = "['Monday','Tuesday','Friday']";
        $headers = self::$headers;
        $result = $this->postJson('http://'.AuthenticationTest::$tenantIdentifier.'.'.env('BASE_DOMAIN').'/api/activity/'.$activity->id.'/schedule_group',$schedule,$headers);
        $decode_response = json_decode($result->getContent());
        $id = $decode_response->data->id;
        $response = $this->deleteJson('http://'.AuthenticationTest::$tenantIdentifier.'.'.env('BASE_DOMAIN').'/api/activity/'.$activity->id.'/schedule_group/'.$id,$headers);
        $response->assertStatus(200);
    }
}
