<?php

namespace Modules\Staff\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Course\Database\factories\CourseFactory;
use Modules\Staff\Database\factories\StaffFactory;
use Tests\Feature\AuthenticationTest;
use Tests\TestCase;

class StaffTest extends TestCase
{
    /**
     * A basic unit test example.
     *
     * @return void
     */
    public function testDisplayStaff()
    {
        $connectionConfig = \config('database.connections.tenant');
        $connectionConfig['database'] = 'bb_'.AuthenticationTest::$tenantIdentifier;
        \config(['database.connections.tenant'=>$connectionConfig]);

        $token = AuthenticationTest::$token;
        $headers = ['Authorization' => 'Bearer ' . $token];
        $response = $this->getJson('http://'.AuthenticationTest::$tenantIdentifier.'.'.env('BASE_DOMAIN').'/api/staff',$headers);
        $response->assertStatus(200);
    }

    public function testStoreStaff()
    {
        $connectionConfig = \config('database.connections.tenant');
        $connectionConfig['database'] = 'bb_'.AuthenticationTest::$tenantIdentifier;
        \config(['database.connections.tenant'=>$connectionConfig]);

        $staff = StaffFactory::new()->make()->toArray();
        $token = AuthenticationTest::$token;
        $headers = ['Authorization' => 'Bearer ' . $token];
        $response = $this->postJson('http://'.AuthenticationTest::$tenantIdentifier.'.'.env('BASE_DOMAIN').'/api/staff',$staff,$headers);
        $response->assertStatus(201);
    }

    public function testStoreStaffWithNullValues()
    {
        $connectionConfig = \config('database.connections.tenant');
        $connectionConfig['database'] = 'bb_'.AuthenticationTest::$tenantIdentifier;
        \config(['database.connections.tenant'=>$connectionConfig]);

        $staff = [];
        $token = AuthenticationTest::$token;
        $headers = ['Authorization' => 'Bearer ' . $token];
        $response = $this->postJson('http://'.AuthenticationTest::$tenantIdentifier.'.'.env('BASE_DOMAIN').'/api/staff',$staff,$headers);
        $response->assertStatus(422);
    }

    public function testCreateStaffWithInvalidValues()
    {
        $connectionConfig = \config('database.connections.tenant');
        $connectionConfig['database'] = 'bb_'.AuthenticationTest::$tenantIdentifier;
        \config(['database.connections.tenant'=>$connectionConfig]);

        $staff = [
            'name'=> 650,
            'title'=> 2,
            'experience'=> 500,
            'profile_data'=> 650,
            'status'=> 1,
            'email'=> "original price",
        ];
        $token = AuthenticationTest::$token;
        $headers = ['Authorization' => 'Bearer ' . $token];
        $response = $this->postJson('http://'.AuthenticationTest::$tenantIdentifier.'.'.env('BASE_DOMAIN').'/api/staff',$staff,$headers);
        $response->assertStatus(422);
    }

    public function testUpdateStaff()
    {
        $connectionConfig = \config('database.connections.tenant');
        $connectionConfig['database'] = 'bb_'.AuthenticationTest::$tenantIdentifier;
        \config(['database.connections.tenant'=>$connectionConfig]);

        $staff = StaffFactory::new()->connection('tenant')->create();
        $new_staff = StaffFactory::new()->make()->toArray();
        $token = AuthenticationTest::$token;
        $headers = ['Authorization' => 'Bearer ' . $token];
        $response = $this->putJson('http://'.AuthenticationTest::$tenantIdentifier.'.'.env('BASE_DOMAIN').'/api/staff/'.$staff->id,$new_staff,$headers);
        $response->assertStatus(200);
    }

    public function testUpdateStaffWithNullValues()
    {
        $connectionConfig = \config('database.connections.tenant');
        $connectionConfig['database'] = 'bb_'.AuthenticationTest::$tenantIdentifier;
        \config(['database.connections.tenant'=>$connectionConfig]);

        $staff = StaffFactory::new()->connection('tenant')->create();
        $new_staff = [];
        $token = AuthenticationTest::$token;
        $headers = ['Authorization' => 'Bearer ' . $token];
        $response = $this->putJson('http://'.AuthenticationTest::$tenantIdentifier.'.'.env('BASE_DOMAIN').'/api/staff/'.$staff->id,$new_staff,$headers);
        $response->assertStatus(422);
    }

    public function testRemoveStaff()
    {
        $connectionConfig = \config('database.connections.tenant');
        $connectionConfig['database'] = 'bb_'.AuthenticationTest::$tenantIdentifier;
        \config(['database.connections.tenant'=>$connectionConfig]);

        $staff = StaffFactory::new()->make()->toArray();
        $token = AuthenticationTest::$token;
        $headers = ['Authorization' => 'Bearer ' . $token];
        $result = $this->postJson('http://'.AuthenticationTest::$tenantIdentifier.'.'.env('BASE_DOMAIN').'/api/staff',$staff,$headers);
        $decode_response = json_decode($result->getContent());
        $staff_id = $decode_response->data->id;
        $response = $this->deleteJson('http://'.AuthenticationTest::$tenantIdentifier.'.'.env('BASE_DOMAIN').'/api/staff/'.$staff_id,$headers);
        $response->assertStatus(200);
    }
}
