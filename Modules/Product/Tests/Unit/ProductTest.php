<?php

namespace Modules\Product\Tests\Unit;

use Modules\Product\Database\factories\ProductFactory;
use Tests\Feature\AuthenticationTest;
use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;

class ProductTest extends TestCase
{
    use WithFaker;
    /**
     * A basic unit test example.
     *
     * @return void
     */

    public function testStoreProduct()
    {
        $connectionConfig = \config('database.connections.tenant');
        $connectionConfig['database'] = 'bb_'.AuthenticationTest::$tenantIdentifier;
        \config(['database.connections.tenant'=>$connectionConfig]);

        $product = ProductFactory::new()->make()->toArray();
        $product['tags'] = ["sport","game","swim"];
        $token = AuthenticationTest::$token;
        $headers = ['Authorization' => 'Bearer ' . $token];
        $response = $this->postJson('http://'.AuthenticationTest::$tenantIdentifier.'.'.env('BASE_DOMAIN').'/api/product', $product,$headers);
        $response->assertStatus(200);
    }

    public function testStoreProductWithoutRequiredData()
    {
        $connectionConfig = \config('database.connections.tenant');
        $connectionConfig['database'] = 'bb_'.AuthenticationTest::$tenantIdentifier;
        \config(['database.connections.tenant'=>$connectionConfig]);

        $product = [];
        $token = AuthenticationTest::$token;
        $headers = ['Authorization' => 'Bearer ' . $token];
        $response = $this->postJson('http://'.AuthenticationTest::$tenantIdentifier.'.'.env('BASE_DOMAIN').'/api/product', $product,$headers);
        $response->assertStatus(422);
    }

    public function testStoreProductWithInvalidData()
    {
        $connectionConfig = \config('database.connections.tenant');
        $connectionConfig['database'] = 'bb_'.AuthenticationTest::$tenantIdentifier;
        \config(['database.connections.tenant'=>$connectionConfig]);

        $product = [
            'title' => $this->faker->numberBetween(199,200),
            'sku' => $this->faker->numberBetween(199,200),
            'brief_description' => $this->faker->numberBetween(199,200),
            'long_description'=> $this->faker->numberBetween(199,200),
        ];
        $token = AuthenticationTest::$token;
        $headers = ['Authorization' => 'Bearer ' . $token];
        $response = $this->postJson('http://'.AuthenticationTest::$tenantIdentifier.'.'.env('BASE_DOMAIN').'/api/product', $product,$headers);
        $response->assertStatus(422);
//        $response->assertJson([
//            "errors" => [
//                "title" => ["The title field must be a string."],
//                "sku" => ["The sku field must be a string."],
//                "brief_description" => ["The brief_description field must be a string."],
//                "long_description" => ["The long_description field must be a string."]
//            ]
//        ]);
    }

    public function testUpdateProduct()
    {
        $connectionConfig = \config('database.connections.tenant');
        $connectionConfig['database'] = 'bb_'.AuthenticationTest::$tenantIdentifier;
        \config(['database.connections.tenant'=>$connectionConfig]);

        $product = ProductFactory::new()->connection('tenant')->create();
        $tags = ["sport","game","swim"];
        foreach ($tags as $value) {
            $product->tags()->create([
                'name' => $value,
                'taggable_id' => $product->id,
                'taggable_type' => 'App/Product'
            ]);
        }
        $new_data = ProductFactory::new()->make()->toArray();
        $new_data['tags'] = ["swim","gaming","fun"];
        $token = AuthenticationTest::$token;
        $headers = ['Authorization' => 'Bearer ' . $token];
        $response = $this->putJson('http://'.AuthenticationTest::$tenantIdentifier.'.'.env('BASE_DOMAIN').'/api/product/'.$product->id, $new_data,$headers);
        $response->assertStatus(200);
    }

    public function testUpdateProductWithInvalidId()
    {
        $connectionConfig = \config('database.connections.tenant');
        $connectionConfig['database'] = 'bb_'.AuthenticationTest::$tenantIdentifier;
        \config(['database.connections.tenant'=>$connectionConfig]);

        $product = ProductFactory::new()->connection('tenant')->create();
        $new_data = ProductFactory::new()->make()->toArray();
        $token = AuthenticationTest::$token;
        $headers = ['Authorization' => 'Bearer ' . $token];
        $response = $this->putJson('http://'.AuthenticationTest::$tenantIdentifier.'.'.env('BASE_DOMAIN').'/api/product/'.$product->id + 1, $new_data,$headers);
        $response->assertStatus(500);
        $response->assertJson([
            'message' => "No query results for model [Modules\\Product\\Entities\\Product] ".$product->id + 1
        ]);
    }

    public function testDestroyProduct()
    {
        $connectionConfig = \config('database.connections.tenant');
        $connectionConfig['database'] = 'bb_'.AuthenticationTest::$tenantIdentifier;
        \config(['database.connections.tenant'=>$connectionConfig]);

        $product = ProductFactory::new()->make()->toArray();
        $product['tags'] = ["sport","game","swim"];
        $token = AuthenticationTest::$token;
        $headers = ['Authorization' => 'Bearer ' . $token];
        $result = $this->postJson('http://'.AuthenticationTest::$tenantIdentifier.'.'.env('BASE_DOMAIN').'/api/product', $product,$headers);
        $decode_response = json_decode($result->getContent());
        $product_id = $decode_response->id;
        $response = $this->deleteJson('http://'.AuthenticationTest::$tenantIdentifier.'.'.env('BASE_DOMAIN').'/api/product/'.$product_id,$headers);
        $response->assertStatus(200);
    }
    public function testShowProduct()
    {
        $connectionConfig = \config('database.connections.tenant');
        $connectionConfig['database'] = 'bb_'.AuthenticationTest::$tenantIdentifier;
        \config(['database.connections.tenant'=>$connectionConfig]);

        $product = ProductFactory::new()->connection('tenant')->create();
        $tags = ["sport","game","swim"];
        foreach ($tags as $value) {
            $product->tags()->create([
                'name' => $value,
                'taggable_id' => $product->id,
                'taggable_type' => 'App/Product'
            ]);
        }
        $token = AuthenticationTest::$token;
        $headers = ['Authorization' => 'Bearer ' . $token];
        $response = $this->getJson('http://'.AuthenticationTest::$tenantIdentifier.'.'.env('BASE_DOMAIN').'/api/product/',$headers);
        $response->assertStatus(200);
    }
}
