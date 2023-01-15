<?php
namespace Jtar\Cart\Command;
use Hyperf\Command\Annotation\Command;
use Hyperf\Command\Command as HyperfCommand;
use Hyperf\DbConnection\Db;
use Symfony\Component\Console\Input\InputArgument;

#[Command]
class CreateCartTableCommand extends HyperfCommand
{
    protected ?string $name = 'create-cart';

    public function configure()
    {
        parent::configure();
        $this->setHelp('创建购物车表');

        $this->addArgument('table', InputArgument::OPTIONAL, '表名字', 'cart');
    }

    public function handle()
    {
        $table_name = $this->input->getArgument('table');

        $sql = "CREATE TABLE `{$table_name}`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL COMMENT '用户ID',
  `product_id` int(11) NULL DEFAULT 0 COMMENT '商品ID',
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '商品名字',
  `num` int(11) NULL DEFAULT 0 COMMENT '数量',
  `price` decimal(10, 2) NULL DEFAULT 0.00 COMMENT '价格',
  `merchant_id` int(11) NULL DEFAULT 0 COMMENT '商户ID',
  `created_at` timestamp(0) NULL DEFAULT NULL,
  `updated_at` timestamp(0) NULL DEFAULT NULL,
  `options` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '商品规格渲染',
  `images` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '商品图片',
  `sku_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '0' COMMENT '商品sku唯一字符串',
  `cart_id` int(11) NULL DEFAULT 0 COMMENT '购物车ID,用于多购物车',
  `card_type` int(11) NULL DEFAULT NULL COMMENT '购物车类型',
  `total` decimal(10, 2) NULL DEFAULT 0.00,
  `sid` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '每个商品唯一标识',
  `disabled` int(1) NULL DEFAULT 0 COMMENT '是否失效，1是',
  `disabled_reason` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '失效原因',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Dynamic;
";

        Db::select($sql);

        $this->info("{$table_name}表创建成功");
    }

}