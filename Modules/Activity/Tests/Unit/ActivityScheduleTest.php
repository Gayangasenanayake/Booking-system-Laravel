<?php

namespace Modules\Activity\Tests\Unit;

use Tests\Feature\AuthenticationTest;
use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Activity\Database\factories\ActivityFactory;
use Modules\Activity\Database\factories\ActivityPriceTierFactory;
use Modules\Schedule\Database\factories\ScheduleFactory;

class ActivityScheduleTest extends TestCase
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
    public function testCreateActivitySchedule()
    {
        $connectionConfig = \config('database.connections.tenant');
        $connectionConfig['database'] = 'bb_'.AuthenticationTest::$tenantIdentifier;
        \config(['database.connections.tenant'=>$connectionConfig]);

        $activity = ActivityFactory::new()->connection('tenant')->create();
        $price_tier = ActivityPriceTierFactory::new()->connection('tenant')->create(['activity_id'=>$activity->id]);
        $schedule = ScheduleFactory::new()->make()->toArray();
        $schedule['price_tier_id'] = $price_tier->id;
        $headers = self::$headers;
        $response = $this->postJson('http://'.AuthenticationTest::$tenantIdentifier.'.'.env('BASE_DOMAIN').'/api/activity/'.$activity->id.'/schedule',$schedule,$headers);
        $response->assertStatus(201);
    }

    public function testCreateActivityScheduleWithNullValues()
    {
        $connectionConfig = \config('database.connections.tenant');
        $connectionConfig['database'] = 'bb_'.AuthenticationTest::$tenantIdentifier;
        \config(['database.connections.tenant'=>$connectionConfig]);

        $activity = ActivityFactory::new()->connection('tenant')->create();
        $schedule = [];
        $headers = self::$headers;
        $response = $this->postJson('http://'.AuthenticationTest::$tenantIdentifier.'.'.env('BASE_DOMAIN').'/api/activity/'.$activity->id.'/schedule',$schedule,$headers);
        $response->assertStatus(422);
    }

    public function testCreateActivityScheduleWithInvalidValues()
    {
        $connectionConfig = \config('database.connections.tenant');
        $connectionConfig['database'] = 'bb_'.AuthenticationTest::$tenantIdentifier;
        \config(['database.connections.tenant'=>$connectionConfig]);

        $activity = ActivityFactory::new()->connection('tenant')->create();
        $price_tier = ActivityPriceTierFactory::new()->connection('tenant')->create(['activity_id'=>$activity->id]);
        $schedule = [
            'date'=> 2023,
            'start_time'=> 2.30,
            'end_time'=> 4.00,
            'allocated_slots'=> 'two',
            'min_number_of_places'=> 'one',
            'max_number_of_places'=> 'five',
            'price_tier_id'=> $price_tier->id,
        ];
        $headers = self::$headers;
        $response = $this->postJson('http://'.AuthenticationTest::$tenantIdentifier.'.'.env('BASE_DOMAIN').'/api/activity/'.$activity->id.'/schedule',$schedule,$headers);
        $response->assertStatus(422);
    }

    public function testViewActivitySchedule()
    {
        $connectionConfig = \config('database.connections.tenant');
        $connectionConfig['database'] = 'bb_'.AuthenticationTest::$tenantIdentifier;
        \config(['database.connections.tenant'=>$connectionConfig]);

        $activity = ActivityFactory::new()->connection('tenant')->create();
        $price_tier = ActivityPriceTierFactory::new()->connection('tenant')->create(['activity_id'=>$activity->id]);
        ScheduleFactory::new()->connection('tenant')->create(['activity_id'=>$activity->id,'price_tier_id'=>$price_tier->id]);
        $headers = self::$headers;
        $response = $this->getJson('http://'.AuthenticationTest::$tenantIdentifier.'.'.env('BASE_DOMAIN').'/api/activity/'.$activity->id.'/schedule',$headers);
        $response->assertStatus(200);
    }

    public function testUpdateActivitySchedule()
    {
        $connectionConfig = \config('database.connections.tenant');
        $connectionConfig['database'] = 'bb_'.AuthenticationTest::$tenantIdentifier;
        \config(['database.connections.tenant'=>$connectionConfig]);

        $activity = ActivityFactory::new()->connection('tenant')->create();
        $price_tier = ActivityPriceTierFactory::new()->connection('tenant')->create(['activity_id'=>$activity->id]);
        $schedule = ScheduleFactory::new()->connection('tenant')->create(['activity_id'=>$activity->id,'price_tier_id'=>$price_tier->id]);
        $new_schedule = ScheduleFactory::new()->make()->toArray();
        $new_schedule['price_tier_id'] = $price_tier->id;
        $headers = self::$headers;
        $response = $this->putJson('http://'.AuthenticationTest::$tenantIdentifier.'.'.env('BASE_DOMAIN').'/api/activity/'.$activity->id.'/schedule/'.$schedule->id,$new_schedule,$headers);
        $response->assertStatus(200);
    }

    public function testUpdateActivityScheduleWithNullValues()
    {
        $connectionConfig = \config('database.connections.tenant');
        $connectionConfig['database'] = 'bb_'.AuthenticationTest::$tenantIdentifier;
        \config(['database.connections.tenant'=>$connectionConfig]);

        $activity = ActivityFactory::new()->connection('tenant')->create();
        $price_tier = ActivityPriceTierFactory::new()->connection('tenant')->create(['activity_id'=>$activity->id]);
        $schedule = ScheduleFactory::new()->connection('tenant')->create(['activity_id'=>$activity->id,'price_tier_id'=>$price_tier->id]);
        $new_schedule = [];
        $headers = self::$headers;
        $response = $this->putJson('http://'.AuthenticationTest::$tenantIdentifier.'.'.env('BASE_DOMAIN').'/api/activity/'.$activity->id.'/schedule/'.$schedule->id,$new_schedule,$headers);
        $response->assertStatus(422);
    }

    public function testUpdateActivityScheduleWithInvalidValues()
    {
        $connectionConfig = \config('database.connections.tenant');
        $connectionConfig['database'] = 'bb_'.AuthenticationTest::$tenantIdentifier;
        \config(['database.connections.tenant'=>$connectionConfig]);

        $activity = ActivityFactory::new()->connection('tenant')->create();
        $price_tier = ActivityPriceTierFactory::new()->connection('tenant')->create(['activity_id'=>$activity->id]);
        $schedule = ScheduleFactory::new()->connection('tenant')->create(['activity_id'=>$activity->id,'price_tier_id'=>$price_tier->id]);
        $new_schedule = [
            'date'=> 2023,
            'start_time'=> 2.30,
            'end_time'=> 4.00,
            'allocated_slots'=> 'two',
            'min_number_of_places'=> 'one',
            'max_number_of_places'=> 'five',
            'price_tier_id'=> $price_tier->id,
        ];
        $headers = self::$headers;
        $response = $this->putJson('http://'.AuthenticationTest::$tenantIdentifier.'.'.env('BASE_DOMAIN').'/api/activity/'.$activity->id.'/schedule/'.$schedule->id,$new_schedule,$headers);
        $response->assertStatus(422);
    }

    public function testSaveActivitySchedule()
    {
        $connectionConfig = \config('database.connections.tenant');
        $connectionConfig['database'] = 'bb_'.AuthenticationTest::$tenantIdentifier;
        \config(['database.connections.tenant'=>$connectionConfig]);

        $activity = ActivityFactory::new()->connection('tenant')->create();
        $schedule = ScheduleFactory::new()->make()->toArray();
        $headers = self::$headers;
        $response = $this->postJson('http://'.AuthenticationTest::$tenantIdentifier.'.'.env('BASE_DOMAIN').'/api/activity/'.$activity->id.'/schedule/draft',$schedule,$headers);
        $response->assertStatus(201);
    }

    public function testSaveActivityScheduleWithInvalidValues()
    {
        $connectionConfig = \config('database.connections.tenant');
        $connectionConfig['database'] = 'bb_'.AuthenticationTest::$tenantIdentifier;
        \config(['database.connections.tenant'=>$connectionConfig]);

        $activity = ActivityFactory::new()->connection('tenant')->create();
        $schedule = [
            'date'=> 2023,
            'start_time'=> 2.30,
            'end_time'=> 4.00,
            'allocated_slots'=> 'two'
        ];
        $headers = self::$headers;
        $response = $this->postJson('http://'.AuthenticationTest::$tenantIdentifier.'.'.env('BASE_DOMAIN').'/api/activity/'.$activity->id.'/schedule/draft',$schedule,$headers);
        $response->assertStatus(422);
    }

    public function testUpdateAndSaveActivitySchedule()
    {
        $connectionConfig = \config('database.connections.tenant');
        $connectionConfig['database'] = 'bb_'.AuthenticationTest::$tenantIdentifier;
        \config(['database.connections.tenant'=>$connectionConfig]);

        $activity = ActivityFactory::new()->connection('tenant')->create();
        $schedule = ScheduleFactory::new()->connection('tenant')->create(['activity_id'=>$activity->id]);
        $new_schedule = ScheduleFactory::new()->make()->toArray();
        $headers = self::$headers;
        $response = $this->putJson('http://'.AuthenticationTest::$tenantIdentifier.'.'.env('BASE_DOMAIN').'/api/activity/'.$activity->id.'/schedule/draft_update/'.$schedule->id,$new_schedule,$headers);
        $response->assertStatus(200);
    }

    public function testUpdateAndSaveActivityScheduleWithInvalidValues()
    {
        $connectionConfig = \config('database.connections.tenant');
        $connectionConfig['database'] = 'bb_'.AuthenticationTest::$tenantIdentifier;
        \config(['database.connections.tenant'=>$connectionConfig]);

        $activity = ActivityFactory::new()->connection('tenant')->create();
        $schedule = ScheduleFactory::new()->connection('tenant')->create(['activity_id'=>$activity->id]);
        $new_schedule = [
            'date'=> 2023,
            'start_time'=> 2.30,
            'end_time'=> 4.00,
            'allocated_slots'=> 'two'
        ];
        $headers = self::$headers;
        $response = $this->putJson('http://'.AuthenticationTest::$tenantIdentifier.'.'.env('BASE_DOMAIN').'/api/activity/'.$activity->id.'/schedule/draft_update/'.$schedule->id,$new_schedule,$headers);
        $response->assertStatus(422);
    }

    public function testRescheduleActivitySchedule()
    {
        $connectionConfig = \config('database.connections.tenant');
        $connectionConfig['database'] = 'bb_'.AuthenticationTest::$tenantIdentifier;
        \config(['database.connections.tenant'=>$connectionConfig]);

        $date = $this->faker->dateTimeBetween(now()->addDays(7),now()->addDays(14));
        $start_time = $this->faker->dateTimeBetween('now','+3 hours');
        $end_time = (clone $start_time)->modify('+3 hours');

        $activity = ActivityFactory::new()->connection('tenant')->create();
        $price_tier = ActivityPriceTierFactory::new()->connection('tenant')->create(['activity_id'=>$activity->id]);
        $schedule = ScheduleFactory::new()->connection('tenant')->create(['activity_id'=>$activity->id,'price_tier_id'=>$price_tier->id]);
        $new_data = [
            'date'=> $date->format('y-m-d'),
            'start_time'=> $start_time->format('h:i'),
            'end_time'=> $end_time->format('h:i')
        ];
        $headers = self::$headers;
        $response = $this->postJson('http://'.AuthenticationTest::$tenantIdentifier.'.'.env('BASE_DOMAIN').'/api/activity/'.$activity->id.'/schedule/reschedule/'.$schedule->id,$new_data,$headers);
        $response->assertStatus(201);
    }

    public function testRescheduleActivityScheduleWithNullValues()
    {
        $connectionConfig = \config('database.connections.tenant');
        $connectionConfig['database'] = 'bb_'.AuthenticationTest::$tenantIdentifier;
        \config(['database.connections.tenant'=>$connectionConfig]);

        $activity = ActivityFactory::new()->connection('tenant')->create();
        $price_tier = ActivityPriceTierFactory::new()->connection('tenant')->create(['activity_id'=>$activity->id]);
        $schedule = ScheduleFactory::new()->connection('tenant')->create(['activity_id'=>$activity->id,'price_tier_id'=>$price_tier->id]);
        $new_data = [];
        $headers = self::$headers;
        $response = $this->putJson('http://'.AuthenticationTest::$tenantIdentifier.'.'.env('BASE_DOMAIN').'/api/activity/'.$activity->id.'/schedule/reschedule'.$schedule->id,$new_data,$headers);
        $response->assertStatus(422);
    }

    public function testRescheduleActivityScheduleWithInvalidValues()
    {
        $connectionConfig = \config('database.connections.tenant');
        $connectionConfig['database'] = 'bb_'.AuthenticationTest::$tenantIdentifier;
        \config(['database.connections.tenant'=>$connectionConfig]);

        $activity = ActivityFactory::new()->connection('tenant')->create();
        $price_tier = ActivityPriceTierFactory::new()->connection('tenant')->create(['activity_id'=>$activity->id]);
        $schedule = ScheduleFactory::new()->connection('tenant')->create(['activity_id'=>$activity->id,'price_tier_id'=>$price_tier->id]);
        $new_data = [
            'date'=> 2023,
            'start_time'=> 2.30,
            'end_time'=> 4.30
        ];
        $headers = self::$headers;
        $response = $this->putJson('http://'.AuthenticationTest::$tenantIdentifier.'.'.env('BASE_DOMAIN').'/api/activity/'.$activity->id.'/schedule/reschedule'.$schedule->id,$new_data,$headers);
        $response->assertStatus(422);
    }

    public function testDestroyActivitySchedule()
    {
        $connectionConfig = \config('database.connections.tenant');
        $connectionConfig['database'] = 'bb_'.AuthenticationTest::$tenantIdentifier;
        \config(['database.connections.tenant'=>$connectionConfig]);

        $activity = ActivityFactory::new()->connection('tenant')->create();
        $price_tier = ActivityPriceTierFactory::new()->connection('tenant')->create(['activity_id'=>$activity->id]);
        $schedule = ScheduleFactory::new()->make()->toArray();
        $schedule['price_tier_id'] = $price_tier->id;
        $headers = self::$headers;
        $result = $this->postJson('http://'.AuthenticationTest::$tenantIdentifier.'.'.env('BASE_DOMAIN').'/api/activity/'.$activity->id.'/schedule',$schedule,$headers);
        $decode_response = json_decode($result->getContent());
        $schedule_id = $decode_response->data->id;
        $response = $this->deleteJson('http://'.AuthenticationTest::$tenantIdentifier.'.'.env('BASE_DOMAIN').'/api/activity/'.$activity->id.'/schedule/'.$schedule_id,$headers);
        $response->assertStatus(200);
    }
}
