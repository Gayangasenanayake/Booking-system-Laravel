<?php

namespace Modules\Course\Tests\Unit;

use Tests\Feature\AuthenticationTest;
use Tests\TestCase;
use Modules\Course\Database\factories\CourseFactory;

class CourseTest extends TestCase
{
    protected static $headers;
    /**
     * A basic unit test example.
     *
     * @return void
     */
    public function setUp(): void
    {
        $token = AuthenticationTest::$token;
        self::$headers = ['Authorization' => 'Bearer ' . $token];
        parent::setUp();
    }

    public function testDisplayCourse()
    {
        $connectionConfig = \config('database.connections.tenant');
        $connectionConfig['database'] = 'bb_'.AuthenticationTest::$tenantIdentifier;
        \config(['database.connections.tenant'=>$connectionConfig]);

        $headers = self::$headers;
        $course = CourseFactory::new()->connection('tenant')->create();
        $response = $this->getJson('http://'.AuthenticationTest::$tenantIdentifier.'.'.env('BASE_DOMAIN').'/api/course',$headers);
        $response->assertStatus(200);
    }

    public function testCreateCourse()
    {
        $connectionConfig = \config('database.connections.tenant');
        $connectionConfig['database'] = 'bb_'.AuthenticationTest::$tenantIdentifier;
        \config(['database.connections.tenant'=>$connectionConfig]);

        $headers = self::$headers;
        $course = CourseFactory::new()->make()->toArray();
        $response = $this->postJson('http://'.AuthenticationTest::$tenantIdentifier.'.'.env('BASE_DOMAIN').'/api/course',$course,$headers);
        $response->assertStatus(201);
    }

    public function testCreateCourseWithNullValues()
    {
        $connectionConfig = \config('database.connections.tenant');
        $connectionConfig['database'] = 'bb_'.AuthenticationTest::$tenantIdentifier;
        \config(['database.connections.tenant'=>$connectionConfig]);

        $headers = self::$headers;
        $course = [];
        $response = $this->postJson('http://'.AuthenticationTest::$tenantIdentifier.'.'.env('BASE_DOMAIN').'/api/course',$course,$headers);
        $response->assertStatus(422);
    }

    public function testCreateCourseWithInvalidValues()
    {
        $connectionConfig = \config('database.connections.tenant');
        $connectionConfig['database'] = 'bb_'.AuthenticationTest::$tenantIdentifier;
        \config(['database.connections.tenant'=>$connectionConfig]);

        $headers = self::$headers;
        $course = [
            'name'=> 650,
            'price'=> "price",
            'original_price'=> "original price",
        ];
        $response = $this->postJson('http://'.AuthenticationTest::$tenantIdentifier.'.'.env('BASE_DOMAIN').'/api/course',$course,$headers);
        $response->assertStatus(422);
    }

    public function testUpdateCourse()
    {
        $connectionConfig = \config('database.connections.tenant');
        $connectionConfig['database'] = 'bb_'.AuthenticationTest::$tenantIdentifier;
        \config(['database.connections.tenant'=>$connectionConfig]);

        $headers = self::$headers;
        $course = CourseFactory::new()->connection('tenant')->create();
        $new_course = CourseFactory::new()->make()->toArray();
        $response = $this->putJson('http://'.AuthenticationTest::$tenantIdentifier.'.'.env('BASE_DOMAIN').'/api/course/'.$course->id,$new_course,$headers);
        $response->assertStatus(200);
    }

    public function testUpdateCourseWithNullValues()
    {
        $connectionConfig = \config('database.connections.tenant');
        $connectionConfig['database'] = 'bb_'.AuthenticationTest::$tenantIdentifier;
        \config(['database.connections.tenant'=>$connectionConfig]);

        $headers = self::$headers;
        $course = CourseFactory::new()->connection('tenant')->create();
        $new_course = [];
        $response = $this->putJson('http://'.AuthenticationTest::$tenantIdentifier.'.'.env('BASE_DOMAIN').'/api/course/'.$course->id,$new_course,$headers);
        $response->assertStatus(422);
    }

    public function testRemoveCourse()
    {
        $connectionConfig = \config('database.connections.tenant');
        $connectionConfig['database'] = 'bb_'.AuthenticationTest::$tenantIdentifier;
        \config(['database.connections.tenant'=>$connectionConfig]);

        $headers = self::$headers;
        $course = CourseFactory::new()->make()->toArray();
        $result = $this->postJson('http://'.AuthenticationTest::$tenantIdentifier.'.'.env('BASE_DOMAIN').'/api/course',$course,$headers);
        $decode_response = json_decode($result->getContent());
        $course_id = $decode_response->data->id;
        $response = $this->deleteJson('http://'.AuthenticationTest::$tenantIdentifier.'.'.env('BASE_DOMAIN').'/api/course/'.$course_id, $headers);
        $response->assertStatus(200);
    }
}
