<?php

// 命令行创建表
//  php bin/hyperf.php create-cart table

// 发布配置文件
// cart.php

//  配置cart.php配置文件模型


// 使用

// 添加购物车
//$data = [
//    'id' => 2, // 商品 ID
//    'name' => '商品2有规格',// 商品名称
//    'num' => 0, // 商品数量
//    'price' => 2.1, // 商品价格
//    'images' => 'images',
//    'options' => [
//        'color' => 'red',
//        'size' => 'L'
//    ],
//];
//
//$cart = new Cart(1);
//$cart->add($data);


// 获取购物车数据
/**
 * {
"success": true,
"message": "购物车数据",
"code": 200,
"data": {
"goods": [
{
"id": 32,
"name": "商品2有规格",
"num": 0,
"price": 2.1,
"images": "images",
"options": {
"color": "red",
"size": "L"
},
"sid": "05749ed308058812f96429fa5a70b801",
"total": 0
}
],
"total_rows": 0,
"total_price": 0
}
}
 */
//$cart = new Cart(1);
//return $this->success('购物车数据', $cart->getAllData());