<?php

namespace Modules\Schedule\Tests\Unit;

use Tests\Feature\AuthenticationTest;
use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Activity\Database\factories\ActivityFactory;
use Modules\Activity\Database\factories\ActivityPriceTierFactory;
use Modules\Schedule\Database\factories\ScheduleFactory;

class ScheduleTesting extends TestCase
{
    use WithFaker;
    /**
     * A basic unit test example.
     *
     * @return void
     */
    public function testCreateSchedule()
    {
        $connectionConfig = \config('database.connections.tenant');
        $connectionConfig['database'] = 'bb_'.AuthenticationTest::$tenantIdentifier;
        \config(['database.connections.tenant'=>$connectionConfig]);

        $activity = ActivityFactory::new()->connection('tenant')->create();
        $price_tier = ActivityPriceTierFactory::new()->connection('tenant')->create(['activity_id'=>$activity->id]);
        $schedule = ScheduleFactory::new()->make()->toArray();
        $schedule['price_tier_id'] = $price_tier->id;
        $schedule['activity_id'] = $activity->id;
        $token = AuthenticationTest::$token;
        $headers = ['Authorization' => 'Bearer ' . $token];
        $response = $this->postJson('http://'.AuthenticationTest::$tenantIdentifier.'.'.env('BASE_DOMAIN').'/api/schedule',$schedule,$headers);
        $response->assertStatus(201);
    }

    public function testCreateScheduleWithNullValues()
    {
        $connectionConfig = \config('database.connections.tenant');
        $connectionConfig['database'] = 'bb_'.AuthenticationTest::$tenantIdentifier;
        \config(['database.connections.tenant'=>$connectionConfig]);

        $schedule = [];
        $token = AuthenticationTest::$token;
        $headers = ['Authorization' => 'Bearer ' . $token];
        $response = $this->postJson('http://'.AuthenticationTest::$tenantIdentifier.'.'.env('BASE_DOMAIN').'/api/schedule',$schedule,$headers);
        $response->assertStatus(422);
    }

    public function testCreateScheduleWithInvalidValues()
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
        $token = AuthenticationTest::$token;
        $headers = ['Authorization' => 'Bearer ' . $token];
        $response = $this->postJson('http://'.AuthenticationTest::$tenantIdentifier.'.'.env('BASE_DOMAIN').'/api/schedule',$schedule,$headers);
        $response->assertStatus(422);
    }

    public function testViewSchedule()
    {
        $connectionConfig = \config('database.connections.tenant');
        $connectionConfig['database'] = 'bb_'.AuthenticationTest::$tenantIdentifier;
        \config(['database.connections.tenant'=>$connectionConfig]);

        $activity = ActivityFactory::new()->connection('tenant')->create();
        $price_tier = ActivityPriceTierFactory::new()->connection('tenant')->create(['activity_id'=>$activity->id]);
        ScheduleFactory::new()->connection('tenant')->create(['activity_id'=>$activity->id,'price_tier_id'=>$price_tier->id]);
        $token = AuthenticationTest::$token;
        $headers = ['Authorization' => 'Bearer ' . $token];
        $response = $this->getJson('http://'.AuthenticationTest::$tenantIdentifier.'.'.env('BASE_DOMAIN').'/api/schedule',$headers);
        $response->assertStatus(200);
    }

    public function testUpdateSchedule()
    {
        $connectionConfig = \config('database.connections.tenant');
        $connectionConfig['database'] = 'bb_'.AuthenticationTest::$tenantIdentifier;
        \config(['database.connections.tenant'=>$connectionConfig]);

        $activity = ActivityFactory::new()->connection('tenant')->create();
        $price_tier = ActivityPriceTierFactory::new()->connection('tenant')->create(['activity_id'=>$activity->id]);
        $schedule = ScheduleFactory::new()->connection('tenant')->create(['activity_id'=>$activity->id,'price_tier_id'=>$price_tier->id]);
        $new_schedule = ScheduleFactory::new()->make()->toArray();
        $new_schedule['price_tier_id'] = $price_tier->id;
        $new_schedule['activity_id'] = $activity->id;
        $token = AuthenticationTest::$token;
        $headers = ['Authorization' => 'Bearer ' . $token];
        $response = $this->putJson('http://'.AuthenticationTest::$tenantIdentifier.'.'.env('BASE_DOMAIN').'/api/schedule/'.$schedule->id,$new_schedule,$headers);
        $response->assertStatus(200);
    }

    public function testUpdateScheduleWithNullValues()
    {
        $connectionConfig = \config('database.connections.tenant');
        $connectionConfig['database'] = 'bb_'.AuthenticationTest::$tenantIdentifier;
        \config(['database.connections.tenant'=>$connectionConfig]);

        $activity = ActivityFactory::new()->connection('tenant')->create();
        $price_tier = ActivityPriceTierFactory::new()->connection('tenant')->create(['activity_id'=>$activity->id]);
        $schedule = ScheduleFactory::new()->connection('tenant')->create(['activity_id'=>$activity->id,'price_tier_id'=>$price_tier->id]);
        $new_schedule = [];
        $token = AuthenticationTest::$token;
        $headers = ['Authorization' => 'Bearer ' . $token];
        $response = $this->putJson('http://'.AuthenticationTest::$tenantIdentifier.'.'.env('BASE_DOMAIN').'/api/schedule/'.$schedule->id,$new_schedule,$headers);
        $response->assertStatus(422);
    }

    public function testUpdateScheduleWithInvalidValues()
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
        $token = AuthenticationTest::$token;
        $headers = ['Authorization' => 'Bearer ' . $token];
        $response = $this->putJson('http://'.AuthenticationTest::$tenantIdentifier.'.'.env('BASE_DOMAIN').'/api/schedule/'.$schedule->id,$new_schedule,$headers);
        $response->assertStatus(422);
    }

    public function testSaveActivitySchedule()
    {
        $connectionConfig = \config('database.connections.tenant');
        $connectionConfig['database'] = 'bb_'.AuthenticationTest::$tenantIdentifier;
        \config(['database.connections.tenant'=>$connectionConfig]);

        $schedule = ScheduleFactory::new()->make()->toArray();
        $token = AuthenticationTest::$token;
        $headers = ['Authorization' => 'Bearer ' . $token];
        $response = $this->postJson('http://'.AuthenticationTest::$tenantIdentifier.'.'.env('BASE_DOMAIN').'/api/schedule/draft',$schedule,$headers);
        $response->assertStatus(201);
    }

    public function testSaveScheduleWithInvalidValues()
    {
        $connectionConfig = \config('database.connections.tenant');
        $connectionConfig['database'] = 'bb_'.AuthenticationTest::$tenantIdentifier;
        \config(['database.connections.tenant'=>$connectionConfig]);

        $schedule = [
            'date'=> 2023,
            'start_time'=> 2.30,
            'end_time'=> 4.00,
            'allocated_slots'=> 'two'
        ];
        $token = AuthenticationTest::$token;
        $headers = ['Authorization' => 'Bearer ' . $token];
        $response = $this->postJson('http://'.AuthenticationTest::$tenantIdentifier.'.'.env('BASE_DOMAIN').'/api/schedule/draft',$schedule,$headers);
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
        $token = AuthenticationTest::$token;
        $headers = ['Authorization' => 'Bearer ' . $token];
        $response = $this->putJson('http://'.AuthenticationTest::$tenantIdentifier.'.'.env('BASE_DOMAIN').'/api/schedule/draft_update/'.$schedule->id,$new_schedule,$headers);
        $response->assertStatus(200);
    }

    public function testUpdateAndSaveScheduleWithInvalidValues()
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
        $token = AuthenticationTest::$token;
        $headers = ['Authorization' => 'Bearer ' . $token];
        $response = $this->putJson('http://'.AuthenticationTest::$tenantIdentifier.'.'.env('BASE_DOMAIN').'/api/schedule/draft_update/'.$schedule->id,$new_schedule,$headers);
        $response->assertStatus(422);
    }

    public function testRescheduleSchedule()
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
        $token = AuthenticationTest::$token;
        $headers = ['Authorization' => 'Bearer ' . $token];
        $response = $this->postJson('http://'.AuthenticationTest::$tenantIdentifier.'.'.env('BASE_DOMAIN').'/api/schedule/reschedule/'.$schedule->id,$new_data,$headers);
        $response->assertStatus(201);
    }

    public function testRescheduleScheduleWithNullValues()
    {
        $connectionConfig = \config('database.connections.tenant');
        $connectionConfig['database'] = 'bb_'.AuthenticationTest::$tenantIdentifier;
        \config(['database.connections.tenant'=>$connectionConfig]);

        $activity = ActivityFactory::new()->connection('tenant')->create();
        $price_tier = ActivityPriceTierFactory::new()->connection('tenant')->create(['activity_id'=>$activity->id]);
        $schedule = ScheduleFactory::new()->connection('tenant')->create(['activity_id'=>$activity->id,'price_tier_id'=>$price_tier->id]);
        $new_data = [];
        $token = AuthenticationTest::$token;
        $headers = ['Authorization' => 'Bearer ' . $token];
        $response = $this->putJson('http://'.AuthenticationTest::$tenantIdentifier.'.'.env('BASE_DOMAIN').'/api/schedule/reschedule'.$schedule->id,$new_data);
        $response->assertStatus(422);
    }

    public function testRescheduleScheduleWithInvalidValues()
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
        $token = AuthenticationTest::$token;
        $headers = ['Authorization' => 'Bearer ' . $token];
        $response = $this->putJson('http://'.AuthenticationTest::$tenantIdentifier.'.'.env('BASE_DOMAIN').'/api/schedule/reschedule'.$schedule->id,$new_data,$headers);
        $response->assertStatus(422);
    }

    public function testDestroySchedule()
    {
        $connectionConfig = \config('database.connections.tenant');
        $connectionConfig['database'] = 'bb_'.AuthenticationTest::$tenantIdentifier;
        \config(['database.connections.tenant'=>$connectionConfig]);

        $activity = ActivityFactory::new()->connection('tenant')->create();
        $price_tier = ActivityPriceTierFactory::new()->connection('tenant')->create(['activity_id'=>$activity->id]);
        $schedule = ScheduleFactory::new()->connection('tenant')->create(['activity_id'=>$activity->id,'price_tier_id'=>$price_tier->id]);
        $token = AuthenticationTest::$token;
        $headers = ['Authorization' => 'Bearer ' . $token];
        $response = $this->deleteJson('http://'.AuthenticationTest::$tenantIdentifier.'.'.env('BASE_DOMAIN').'/api/schedule/'.$schedule->id,$headers);
        $response->assertStatus(200);
    }
}
