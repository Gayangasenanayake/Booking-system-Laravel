<?php

namespace Modules\Product\Tests\Unit;

use Modules\Product\Database\factories\ProductFactory;
use Modules\Product\Database\factories\ProductPricingInfoFactory;
use Tests\Feature\AuthenticationTest;
use Tests\TestCase;

class ProductPriceInfoTest extends TestCase
{
    /**
     * A basic unit test example.
     *
     * @return void
     */

    public function testStoreProductPriceInto()
    {
        $connectionConfig = \config('database.connections.tenant');
        $connectionConfig['database'] = 'bb_'.AuthenticationTest::$tenantIdentifier;
        \config(['database.connections.tenant'=>$connectionConfig]);

        $product = ProductFactory::new()->connection('tenant')->create();
        $price = [
            'base_price'=>150,
            'advertised_price'=>200
        ];
        $token = AuthenticationTest::$token;
        $headers = ['Authorization' => 'Bearer ' . $token];
        $response = $this->postJson('http://'.AuthenticationTest::$tenantIdentifier.'.'.env('BASE_DOMAIN').'/api/product/'.$product->id.'/pricing_info',$price,$headers);
        $response->assertStatus(201);
    }

    public function testStoreProductPriceIntoWithNull()
    {
        $connectionConfig = \config('database.connections.tenant');
        $connectionConfig['database'] = 'bb_'.AuthenticationTest::$tenantIdentifier;
        \config(['database.connections.tenant'=>$connectionConfig]);

        $product = ProductFactory::new()->connection('tenant')->create();
        $price = [ ];
        $token = AuthenticationTest::$token;
        $headers = ['Authorization' => 'Bearer ' . $token];
        $response = $this->postJson('http://'.AuthenticationTest::$tenantIdentifier.'.'.env('BASE_DOMAIN').'/api/product/'.$product->id.'/pricing_info',$price,$headers);
        $response->assertStatus(422);
        $response->assertJson([
            "errors"=>[
                "base_price"=>["The base price field is required."],
                "advertised_price"=>["The advertised price field is required."]
            ]
        ]);
    }
    public function testStoreProductPriceIntoWithInvalidData()
    {
        $connectionConfig = \config('database.connections.tenant');
        $connectionConfig['database'] = 'bb_'.AuthenticationTest::$tenantIdentifier;
        \config(['database.connections.tenant'=>$connectionConfig]);

        $product = ProductFactory::new()->connection('tenant')->create();
        $price = [
            'base_price'=>"test",
            'advertised_price'=>"test"
        ];
        $token = AuthenticationTest::$token;
        $headers = ['Authorization' => 'Bearer ' . $token];
        $response = $this->postJson('http://'.AuthenticationTest::$tenantIdentifier.'.'.env('BASE_DOMAIN').'/api/product/'.$product->id.'/pricing_info',$price,$headers);
        $response->assertStatus(422);
        $response->assertJson([
            "errors"=>[
                "base_price"=>["The base price field must be a number."],
                "advertised_price"=>["The advertised price field must be a number."]
            ]
        ]);
    }

    public function testTryToStorePriceInfoAgainForSameProduct()
    {
        $connectionConfig = \config('database.connections.tenant');
        $connectionConfig['database'] = 'bb_'.AuthenticationTest::$tenantIdentifier;
        \config(['database.connections.tenant'=>$connectionConfig]);

        $product = ProductFactory::new()->connection('tenant')->create();
        ProductPricingInfoFactory::new()->connection('tenant')->create(['product_id'=>$product->id]);
        $price = [
            'base_price'=>500,
            'advertised_price'=>1500
        ];
        $token = AuthenticationTest::$token;
        $headers = ['Authorization' => 'Bearer ' . $token];
        $response = $this->postJson('http://'.AuthenticationTest::$tenantIdentifier.'.'.env('BASE_DOMAIN').'/api/product/'.$product->id.'/pricing_info',$price,$headers);
        $response->assertStatus(422);
        $response->assertJson([
//            "message"=> "Product already has a price info!"
        ]);
    }

public function testUpdateProductPriceInfo()
{
    $connectionConfig = \config('database.connections.tenant');
    $connectionConfig['database'] = 'bb_'.AuthenticationTest::$tenantIdentifier;
    \config(['database.connections.tenant'=>$connectionConfig]);

    $product = ProductFactory::new()->connection('tenant')->create();
    $price_info = ProductPricingInfoFactory::new()->connection('tenant')->create(['product_id'=>$product->id]);
    $new_price = [
        'base_price'=>1000,
        'advertised_price'=>2000
    ];
    $token = AuthenticationTest::$token;
    $headers = ['Authorization' => 'Bearer ' . $token];
    $response = $this->putJson('http://'.AuthenticationTest::$tenantIdentifier.'.'.env('BASE_DOMAIN').'/api/product/'.$product->id.'/pricing_info/'.$price_info->id,$new_price,$headers);
    $response->assertStatus(200);
}

    public function testUpdateProductPriceInfoWithInvalidValues()
    {
        $connectionConfig = \config('database.connections.tenant');
        $connectionConfig['database'] = 'bb_'.AuthenticationTest::$tenantIdentifier;
        \config(['database.connections.tenant'=>$connectionConfig]);

        $product = ProductFactory::new()->connection('tenant')->create();
        $price_info = ProductPricingInfoFactory::new()->connection('tenant')->create(['product_id'=>$product->id]);
        $new_price = [
            'base_price'=>"true",
            'advertised_price'=>"update"
        ];
        $token = AuthenticationTest::$token;
        $headers = ['Authorization' => 'Bearer ' . $token];
        $response = $this->putJson('http://'.AuthenticationTest::$tenantIdentifier.'.'.env('BASE_DOMAIN').'/api/product/'.$product->id.'/pricing_info/'.$price_info->id,$new_price,$headers);
        $response->assertStatus(422);
        $response->assertJson([
            "errors"=>[
                "base_price"=>["The base price field must be a number."],
                "advertised_price"=>["The advertised price field must be a number."]
            ]
        ]);
    }

    public function testUpdateProductPriceInfoWithNullValues()
    {
        $connectionConfig = \config('database.connections.tenant');
        $connectionConfig['database'] = 'bb_'.AuthenticationTest::$tenantIdentifier;
        \config(['database.connections.tenant'=>$connectionConfig]);

        $product = ProductFactory::new()->connection('tenant')->create();
        $price_info = ProductPricingInfoFactory::new()->connection('tenant')->create(['product_id'=>$product->id]);
        $new_price = [];
        $token = AuthenticationTest::$token;
        $headers = ['Authorization' => 'Bearer ' . $token];
        $response = $this->putJson('http://'.AuthenticationTest::$tenantIdentifier.'.'.env('BASE_DOMAIN').'/api/product/'.$product->id.'/pricing_info/'.$price_info->id,$new_price,$headers);
        $response->assertStatus(422);
        $response->assertJson([
            "errors"=>[
                "base_price"=>["The base price field is required."],
                "advertised_price"=>["The advertised price field is required."]
            ]
        ]);
    }

    public function testUpdateProductPriceInfoWithInvalidId()
    {
        $connectionConfig = \config('database.connections.tenant');
        $connectionConfig['database'] = 'bb_'.AuthenticationTest::$tenantIdentifier;
        \config(['database.connections.tenant'=>$connectionConfig]);

        $product = ProductFactory::new()->connection('tenant')->create();
        $price_info = ProductPricingInfoFactory::new()->connection('tenant')->create(['product_id'=>$product->id]);
        $new_price = [
            'base_price'=>1000,
            'advertised_price'=>1500
        ];
        $token = AuthenticationTest::$token;
        $headers = ['Authorization' => 'Bearer ' . $token];
        $response = $this->putJson('http://'.AuthenticationTest::$tenantIdentifier.'.'.env('BASE_DOMAIN').'/api/product/'.$product->id.'/pricing_info/'.$price_info->id+1,$new_price,$headers);
        $response->assertStatus(500);
    }
}

