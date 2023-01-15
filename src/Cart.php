<?php

namespace Jtar\Cart;

use Hyperf\Database\Model\Model;

class Cart
{
    /**
     * 购物车前缀
     *
     * @var string
     */
    public string $prefix = 'cart';


    /**
     * 商品资料
     */
    protected array $goods;

    protected int $user_id;
    
    protected $delSid = [];

    /**
     * @param array $goods
     */
    public function __construct($user_id, array $goods = [])
    {
        $this->user_id = $user_id;

        $this->setPrefix();

        if ( empty($goods) ){
            $this->setGood();
        }else{
            $this->goods = $goods;
        }
    }

    public function update(array $data)
    {
//        [
//            'sid'=>'商品唯一编号',// 唯一 sid，添加购物车时自动生成
//            'num'=>88
//        ];

        //  删除商品
        if ($data['num'] == 0){
            unset($this->goods[$data['sid']]);
            
            $this->delSid[] = $data['sid'];
        }else{
            $goods = $this->goods[$data['sid']];

            //  减少
            if ($data['num'] < 0 && $goods['num'] + $data['num'] < 0){
                unset($this->goods[$data['sid']]);

                $this->delSid[] = $data['sid'];
            }else{
                $this->goods[$data['sid']]['num'] = $data['num'];
            }
        }

        return $this->store();
    }


    /**
     * 添加购物车
     *
     * @access  public
     *
     * @param array $data
     *      $data为数组包含以下几个值
     *      $Data= [
     *      "id"=>1,                        //商品ID
     *      "name"=>"后盾网2周年西服",         //商品名称
     *      "num"=>2,                       //商品数量
     *      "price"=>188.88,                //商品价格
     *      "options"=>array(               //其他参数，如价格、颜色可以是数组或字符串|可以不添加
         *      "color"=>"red",
         *      "size"=>"L"
     *      ]
     *
     * @throws \Exception
     * @return void
     */
    public function add(array $data)
    {
        //添加商品支持多商品添加
        $options = isset($data['options']) ? $data['options'] : '';
        //生成维一ID用于处理相同商品有不同属性时
        $sid     = md5($data['id'].serialize($options));

        if (empty($this->goods)){

            if ($data['num'] <= 0){
                throw new \Exception('商品数量不能小于0');
            }

            $data['sid']                = $sid;
            $this->goods[$sid]          = $data;
            $this->goods[$sid]['total'] = $data['num'] * $data['price'];
            $this->goods[$sid]['type']  = 'add';


        }else{
            if (isset($this->goods[$sid])) {
                //如果数量为0删除商品
                if ($data['num'] == 0) {
                    unset($this->goods[$sid]);
                    $this->delSid[] = $sid;
                } else {

                    //已经存在相同商品时增加商品数量
                    $this->goods[$sid]['num']   = $this->goods[$sid]['num']
                        + $data['num'];
                    $this->goods[$sid]['total'] = $this->goods[$sid]['num']
                        * $this->goods[$sid]['price'];

                    $this->goods[$sid]['sid'] = $sid;
                }
            } else {
                if ($data['num'] != 0) {
                    $data['sid']                = $sid;
                    $this->goods[$sid]          = $data;
                    $this->goods[$sid]['total'] = $data['num'] * $data['price'];
                    $this->goods[$sid]['type']  = 'add';

                }
            }
        }
        return $this->store();
    }


    public function insDatabases($v)
    {
        /**
         * @var Model $model
         */
        $model = new (config('cart.model'));

        $options = [];
        if (isset($v['options'])) $options = $v['options'];

        //  添加
        $ins = [
            'user_id'   =>  $this->user_id,
            'product_id'    =>  $v['id'],
            'name'      =>  $v['name'] ?? '',
            'price'      =>  $v['price'] ?? '',
            'merchant_id'      =>  $v['merchant_id'] ?? 0,
            'options'      =>  jtarArrToJson($options),
            'images'      =>  $v['images'] ?? '',
            'sku_id'      =>  $v['sku_id'] ?? 0,
            'cart_id'      =>  $v['cart_id'] ?? 0,
            'card_type'      =>  $v['card_type'] ?? 0,
            'total'         =>  $v['total'] ?? 0,
            'sid'         =>  $v['sid'] ?? 0,
            'disabled'         =>  $v['disabled'] ?? 0,
            'disabled_reason'         =>  $v['disabled_reason'] ?? 0,
        ];

        $create = $model::query()->create($ins);

        return $create->id;
    }


    /**
     * 设置商品
     * @param int $mode
     */
    public function setGood(int $mode = 1): void
    {
        $this->goods = $this->getGood($mode);
    }

    public function getGood($mode = 1)
    {
        $goods = [];

        if ($mode == 0){
            /**
             * @var Model $model
             */
            $model = new (config('cart.model'));

            $get = $model::query()->where('user_id',$this->user_id)->get();

            if ($get->count())
            {
                $goods = $get->toArray();

                $arr = [];
                foreach ($goods as $k => $v){
                    $arr[$v['sid']] = $v;
                }

                return $arr;
            }
        }

        if ($mode == 1){
            $goodCache = jtarGetRedis()->get($this->getPrefix());

            if ($goodCache) $goods = jtarJsonToArr($goodCache);

            if (isset($goods['goods'])){
                $goods = $goods['goods'];

                $arr = [];
                foreach ($goods as $k => $v){
                    $arr[$v['sid']] = $v;
                }

                return $arr;
            }
        }

        return $goods;
    }

    public function setPrefix($prefix = ''): void
    {
        if ($prefix != '') {
            $this->prefix = $prefix;
        }else{
            $this->prefix = $this->prefix  .  '_'   . env('APP_NAME') . '_' . env('APP_ENV') . '_' . $this->user_id;
        }
    }

    public function getPrefix(): string
    {
        return $this->prefix;
    }


    public function calculate(){
        return [
            'goods'       => $this->goods,
            'total_rows'  => $this->getTotalNums(),
            'total_price' => $this->getTotalPrice(),
        ];
    }

    /**
     * 获得购物车中的所有数据
     * 包括商品数据、总数量、总价格
     *
     * @param int $mode
     * @return mixed
     */
    public function getAllData(): mixed
    {
        $data = $this->calculate();

        $data['goods'] = array_values($data['goods']);

        return $data;
    }


    public function setCache(): void
    {
        $data = $this->calculate();

        $goods = $data['goods'];

        foreach ($goods as $k => $v){
            $temp = $v;

            if (isset($temp['type'])) unset($temp['type']);
            $goods[$k] = $temp;
        }
        
        $data['goods'] = $goods;

        jtarGetRedis()->set($this->getPrefix(), jtarArrToJson($data);
    }

    /**
     * 保存
     *
     * @return mixed
     */
    private function store()
    {
        /**
         * @var Model $model
         */
        $model = new (config('cart.model'));

        if (!empty($this->delSid)){
            $model::query()->where('user_id',$this->user_id)
                ->whereIn('sid', $this->delSid)->delete();
        }

        foreach ($this->goods as $k => $v){
            //  说明是更新
            if (isset($v['sid']) && isset($v['type']) && $v['type'] = 'add'){
                $id = $this->insDatabases($v);
                $this->goods[$v['sid']]['id'] = $id;
            }else{
                if (isset($v['sid'])){
                    $find = $model::query()->where('user_id',$this->user_id)
                        ->where('product_id',$v['id'])
                        ->where('sid',$v['sid'])
                        ->first();

                    if ($find){
                        $find->update([
                            'num'       =>  $v['num'],
                            'price'     =>  $v['price'],
                            'total'     =>  $v['num'] * $v['price']
                        ]);
                    }
                }

            }
        }

        $this->setCache();

        return $this->calculate();
    }


    /**
     * 统计购物车中商品数量
     */
    public function getTotalNums() :int
    {
        $rows = 0;
        foreach ($this->goods as $k => $v) {
            $rows += $v['num'];
        }

        return $rows;
    }

    /**
     * 获得商品汇总价格
     */
    public function getTotalPrice(): float|int
    {
        $total = 0;
        foreach ($this->goods as $k => $v) {
            $total += $v['price'] * $v['num'];
        }

        return $total;
    }

    /**
     * 删除购物车
     *
     * @param string $sid 商品SID编号
     *
     * @throws \Exception
     */
    public function del($sid)
    {
        $this->delSid[] = $sid;

        unset($this->goods[$sid]);

        return $this->store();
    }

    /**
     * 删除所有商品
     */
    public function flush()
    {
        $this->delSid = array_keys($this->goods);
        $this->goods = [];

        return $this->store();
    }
}