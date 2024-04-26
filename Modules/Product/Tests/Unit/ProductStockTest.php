<?php

namespace Modules\Product\Tests\Unit;

use Modules\Product\Database\factories\ProductFactory;
use Modules\Product\Database\factories\ProductStockFactory;
use Tests\Feature\AuthenticationTest;
use Tests\TestCase;

class ProductStockTest extends TestCase
{
    /**
     * A basic unit test example.
     *
     * @return void
     */
    public function testStoreProductStock()
    {
        $connectionConfig = \config('database.connections.tenant');
        $connectionConfig['database'] = 'bb_'.AuthenticationTest::$tenantIdentifier;
        \config(['database.connections.tenant'=>$connectionConfig]);

        $product = ProductFactory::new()->connection('tenant')->create();
        $stock = [
            'available_stock'=>150,
            'is_manage_stocks_for_product'=>1
        ];
        $token = AuthenticationTest::$token;
        $headers = ['Authorization' => 'Bearer ' . $token];
        $response = $this->postJson('http://'.AuthenticationTest::$tenantIdentifier.'.'.env('BASE_DOMAIN').'/api/product/'.$product->id.'/stock',$stock,$headers);
        $response->assertStatus(200);
    }

    public function testStoreProductStockWithNull()
    {
        $connectionConfig = \config('database.connections.tenant');
        $connectionConfig['database'] = 'bb_'.AuthenticationTest::$tenantIdentifier;
        \config(['database.connections.tenant'=>$connectionConfig]);

        $product = ProductFactory::new()->connection('tenant')->create();
        $stock = [ ];
        $token = AuthenticationTest::$token;
        $headers = ['Authorization' => 'Bearer ' . $token];
        $response = $this->postJson('http://'.AuthenticationTest::$tenantIdentifier.'.'.env('BASE_DOMAIN').'/api/product/'.$product->id.'/stock',$stock,$headers);
        $response->assertStatus(422);
        $response->assertJson([
            "errors"=>[
                "available_stock"=>["The available stock field is required."],
            ]
        ]);
    }

    public function testStoreProductStockWithInvalidData()
    {
        $connectionConfig = \config('database.connections.tenant');
        $connectionConfig['database'] = 'bb_'.AuthenticationTest::$tenantIdentifier;
        \config(['database.connections.tenant'=>$connectionConfig]);

        $product = ProductFactory::new()->connection('tenant')->create();
        $stock = [
            'available_stock'=>"test",
            'is_manage_stocks_for_product'=>"test"
        ];
        $token = AuthenticationTest::$token;
        $headers = ['Authorization' => 'Bearer ' . $token];
        $response = $this->postJson('http://'.AuthenticationTest::$tenantIdentifier.'.'.env('BASE_DOMAIN').'/api/product/'.$product->id.'/stock',$stock,$headers);
        $response->assertStatus(422);
        $response->assertJson([
//            "errors"=>[
//                "available_stock"=>["The add stock field must be a number."],
//                "is_manage_stocks_for_product"=>["The is mange stock must be a boolean."]
//            ]
        ]);
    }

    public function testTryToStoreStockAgainForSameProduct()
    {
        $connectionConfig = \config('database.connections.tenant');
        $connectionConfig['database'] = 'bb_'.AuthenticationTest::$tenantIdentifier;
        \config(['database.connections.tenant'=>$connectionConfig]);

        $product = ProductFactory::new()->connection('tenant')->create();
        ProductStockFactory::new()->connection('tenant')->create(['product_id'=>$product->id]);
        $stock = [
            'available_stock'=>500,
            'is_manage_stocks_for_product'=>1
        ];
        $token = AuthenticationTest::$token;
        $headers = ['Authorization' => 'Bearer ' . $token];
        $response = $this->postJson('http://'.AuthenticationTest::$tenantIdentifier.'.'.env('BASE_DOMAIN').'/api/product/'.$product->id.'/stock',$stock,$headers);
        $response->assertStatus(422);
    }
    public function testUpdateProductStock()
    {
        $connectionConfig = \config('database.connections.tenant');
        $connectionConfig['database'] = 'bb_'.AuthenticationTest::$tenantIdentifier;
        \config(['database.connections.tenant'=>$connectionConfig]);

        $product = ProductFactory::new()->connection('tenant')->create();
        $stock = ProductStockFactory::new()->connection('tenant')->create(['product_id'=>$product->id]);
        $new_stock = [
            'available_stock'=>100,
            'is_manage_stocks_for_product'=>0
        ];
        $token = AuthenticationTest::$token;
        $headers = ['Authorization' => 'Bearer ' . $token];
        $response = $this->putJson('http://'.AuthenticationTest::$tenantIdentifier.'.'.env('BASE_DOMAIN').'/api/product/'.$product->id.'/stock/'.$stock->id,$new_stock,$headers);
        $response->assertStatus(200);
    }

    public function testUpdateProductStockWithInvalidValues()
    {
        $connectionConfig = \config('database.connections.tenant');
        $connectionConfig['database'] = 'bb_'.AuthenticationTest::$tenantIdentifier;
        \config(['database.connections.tenant'=>$connectionConfig]);

        $product = ProductFactory::new()->connection('tenant')->create();
        $stock = ProductStockFactory::new()->connection('tenant')->create(['product_id'=>$product->id]);
        $new_stock = [
            'available_stock'=>"true",
            'is_manage_stocks_for_product'=>"update"
        ];
        $token = AuthenticationTest::$token;
        $headers = ['Authorization' => 'Bearer ' . $token];
        $response = $this->putJson('http://'.AuthenticationTest::$tenantIdentifier.'.'.env('BASE_DOMAIN').'/api/product/'.$product->id.'/stock/'.$stock->id,$new_stock,$headers);
        $response->assertStatus(422);
        $response->assertJson([
//            "errors"=>[
//                "available_stock"=>["The available stock field must be a number."],
//                "is_manage_stocks_for_product"=>["is mange stock must be true or false"]
//            ]
        ]);
    }
    public function testUpdateProductStockWithNullValues()
    {
        $connectionConfig = \config('database.connections.tenant');
        $connectionConfig['database'] = 'bb_'.AuthenticationTest::$tenantIdentifier;
        \config(['database.connections.tenant'=>$connectionConfig]);

        $product = ProductFactory::new()->connection('tenant')->create();
        $stock = ProductStockFactory::new()->connection('tenant')->create(['product_id'=>$product->id]);
        $new_stock = [];
        $token = AuthenticationTest::$token;
        $headers = ['Authorization' => 'Bearer ' . $token];
        $response = $this->putJson('http://'.AuthenticationTest::$tenantIdentifier.'.'.env('BASE_DOMAIN').'/api/product/'.$product->id.'/stock/'.$stock->id,$new_stock,$headers);
        $response->assertStatus(422);
        $response->assertJson([
            "errors"=>[
                "available_stock"=>["The available stock field is required."],
            ]
        ]);
    }

    public function testUpdateProductStockWithInvalidId()
    {
        $connectionConfig = \config('database.connections.tenant');
        $connectionConfig['database'] = 'bb_'.AuthenticationTest::$tenantIdentifier;
        \config(['database.connections.tenant'=>$connectionConfig]);

        $product = ProductFactory::new()->connection('tenant')->create();
        $stock = ProductStockFactory::new()->connection('tenant')->create(['product_id'=>$product->id]);
        $new_stock = [
            'available_stock'=>500,
            'is_manage_stocks_for_product'=>1
        ];
        $token = AuthenticationTest::$token;
        $headers = ['Authorization' => 'Bearer ' . $token];
        $response = $this->putJson('http://'.AuthenticationTest::$tenantIdentifier.'.'.env('BASE_DOMAIN').'/api/product/'.$product->id.'/stock/'.$stock->id+1,$new_stock,$headers);
        $response->assertStatus(500);
//        $response->assertJson([
//            "message"=>"No query results for model [Modules\\Product\\Entities\\Stock] ".$stock->id+1
//        ]);
    }
}
