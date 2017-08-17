<?php

/**
 * 购买商品逻辑层
 * User: 高翔
 * Date: 2017/7/18
 * Time: 17:54
 */
class BuyGoodsLogic extends \Phalcon\Mvc\Controller
{
    /*
     * 购买商品分发
     * @param product 商品分类
     * */
    public function buyGoods($data)
    {
        $trade_status  = getArrVal($data, 'trade_status', '');
        if (!in_array($trade_status, ['TRADE_FINISHED', 'TRADE_SUCCESS'])) {
            return true;
        }

        $product = getArrVal($data, 'product', '');

        switch ($product) {
            case 'aixiao':
                if ($this->rechargeFinance($data)) { // 更新充值成功财务
                    return $this->buyApps($data['out_trade_no']); // 购买爱校相关应用
                } else {
                    return false; // 跟新失败
                }
                break;
            default:
                break;
        }
    }

    /*
     * 财务充值成功更新
     *
     * @param alipay notify
     *
     * @return bool
     * */
    public function rechargeFinance($data)
    {
        $finance_model = new MerchantAccountRecordDetailOrderModel();
        $sqlJoin       = new SqlJoin();
        $data_time     = date('Y-m-d H:i:s');

        //获取支付宝返回信息
        $serial_number = getArrVal($data, 'out_trade_no', '');
        $trade_no      = getArrVal($data, 'trade_no', '');
        $ali_trade_no  = getArrVal($data, 'trade_no', '');
        $money         = getArrVal($data, 'total_fee', 0.00);


        //验证充值记录是否存在
        $mardr_data = $sqlJoin->query(
            $finance_model,
            "SELECT id,status,money,account_record_id FROM merchant_account_record_detail_recharges WHERE serial_number='{$serial_number}'",
            'find'
        );
        if (empty($mardr_data)) {
            DLOG("serial_number='{$serial_number}'商家支付宝充值充值记录不存在", 'WARN', 'notify.log');
            return false;
        }
        // TODO: 调试注掉
        if ($mardr_data['money'] != $money) {
            DLOG("mardr_data=".json_encode($mardr_data)."商家充值金额与支付宝返回金额不一致", 'WARN', 'notify.log');
            return false;
        }
        if ($mardr_data['status'] != '2') {
            if ($mardr_data['status'] == '3') {
                DLOG("交易已经成功，不能再次处理！", 'INFO', 'notify.log');
            } else {
                DLOG("商家充值记录状态有问题", 'WARN', 'notify.log');
            }
            return false;
        }

        //获取进出账记录
        $mar_data = $sqlJoin->query(
            $finance_model,
            "SELECT account_id FROM merchant_account_records WHERE id='{$mardr_data['account_record_id']}'",
            'find'
        );
        if (empty($mar_data)) {
            DLOG("商家进出账记录不存在", 'WARN', 'notify.log');
            return false;
        }

        //获取商家务记录详情充值（进账）-在线充值（支付宝、网银）详情表记录
        $mardro_data = $sqlJoin->query(
            $finance_model,
            "SELECT id FROM `merchant_account_record_detail_recharge_onlines` WHERE recharge_id='{$mardr_data['id']}'",
            'find'
        );
        if (empty($mardro_data)) {
            DLOG("商家线上充值记录不存在", 'WARN', 'notify.log');
            return false;
        }

        //获取商家账户信息
        $merchant_account_data = $sqlJoin->query(
            $finance_model,
            "SELECT id,balances FROM merchant_accounts WHERE id='{$mar_data['account_id']}'",
            'find'
        );
        if (empty($merchant_account_data)) {
            DLOG("商家账户不存在", 'WARN', 'notify.log');
            return false;
        }


        //开启事务
        $this->finance_master->begin();

        //更新商家财务账户信息
        $acc_sql = $sqlJoin->table('merchant_accounts')
            ->where(['id' => $merchant_account_data['id']])
            ->sql(
                'update',
                [
                    'balances' => $merchant_account_data['balances'] + $money,
                    'modified' => $data_time
                ]
            );
        if (!$sqlJoin->update($finance_model, $acc_sql)) {
            DLOG("更新商家账户记录失败".$acc_sql, 'ERROR', 'notify.log');
            return false;
        }

        //更新商家财务账户记录详情充值（进账）表信息
        $mardr_sql = $sqlJoin->table('merchant_account_record_detail_recharges')
            ->where(['id' => $mardr_data['id']])
            ->sql(
                'update',
                [
                    'status' => '3',
                    'recharge_succ_time' => getArrVal($data, 'gmt_payment', $data_time)
                ]
            );
        if (!$sqlJoin->update($finance_model, $mardr_sql)) {
            DLOG("更新商家账户记录详情信息失败".$mardr_sql, 'ERROR', 'notify.log');
            return false;
        }


        //更新商家务记录详情充值（进账）-在线充值（支付宝、网银）详情表信息
        $mardro_sql = $sqlJoin->table('merchant_account_record_detail_recharge_onlines')
            ->where(['id' => $mardro_data['id']])
            ->sql(
                'update',
                [
                    'trade_code'   => $serial_number,
                    'ali_trade_no' => $ali_trade_no,
                    'notify_type'  => '2',
                    'type'         =>'2' // 充值方式(1:网银 2:支付宝 3:京东支付)
                ]
            );
        if (!$sqlJoin->update($finance_model, $mardro_sql)) {
            DLOG("更新商家账户记录详情信息失败".$mardr_sql, 'ERROR', 'notify.log');
            return false;
        }

        DLOG("商家支付宝充值信息更新成功", 'INFO', 'notify.log');

        $this->finance_master->commit(); // 事务提交

        return true;
    }

    /*
     * 购买爱校相关应用
     *
     * @param string $trade_no 订单号
     *
     * @return bool
     * */
    public function buyApps($trade_no)
    {
        $finance_model        = new MerchantAccountRecordDetailOrderModel();
        $business_model       = new PlatformCouponReleaseModel();
        $member_model         = new MemberModel();
        $sqlJoin              = new SqlJoin();
        $merchant_order_logic = new MerchantOrderLogic();
        $date_time            = date('Y-m-d H:i:s');
        $account_record_id    = getUuid(); //出账记录ID
        $platform_roles_id    = getUuid(); // 角色ID

        //获取支付宝返回信息
        $serial_number = $trade_no;

        // 查询订单信息
        $field = "
            a.id,
            a.member_id,
            a.order_id,
            a.use_num,
            a.merchant_id,
            b.price,
            b.discount_amount,
            b.real_amount,
            b.coupon_id,
            b.status,
            b.order_type,
            a.pay_type,
            a.platform_id,
            a.platform_version_id
        ";
        $where = [
            'b.trade_no' => $serial_number
        ];
        $sql = $sqlJoin->table('merchant_account_record_detail_orders as b')
            ->join('left join merchant_account_record_detail_order_apps as a ON a.order_id=b.id')
            ->field($field)
            ->where($where)
            ->sql();
        $order_detail = $sqlJoin->query($finance_model, $sql, 'find');

        $member_id   = getArrVal($order_detail, 'member_id', '');
        $order_id    = getArrVal($order_detail, 'order_id', '');
        $coupon_id   = getArrVal($order_detail, 'coupon_id', '');
        $merchant_id = getArrVal($order_detail, 'merchant_id', '');
        $balances    = $order_detail['price'] - $order_detail['discount_amount']; // 购买应用需要支付金额

        // 查询员工信息
        $employee_where = "
            a.member_id='{$member_id}' AND
            a.merchant_id='{$merchant_id}' AND
            a.is_usable = '1' AND
            a.is_delete = '2'
        ";
        $employee_sql = $sqlJoin->table('mc_employees as a')
            ->join('left join mc_members as b on a.member_id=b.id')
            ->field('a.id as employee_id')
            ->where($employee_where)
            ->sql();
        $employee_data = $sqlJoin->query($member_model, $employee_sql, 'find');
        $employee_id = $employee_data['employee_id'];

        // 验证商家账户是否存在
        $merchant_account = $merchant_order_logic->getMerchantFinancialAccount(['merchant_id' => $merchant_id]);
        if (empty($merchant_account)) {
            DLOG("merchant_id='{$merchant_id}'验证商家账户是否不存在", 'ERROR', 'notify.log');
            return false;
        }

        //扣除余额
        if ($balances > 0) {
            $sql  = "UPDATE `finance_center`.`merchant_accounts` SET `balances`=`balances`-{$balances}, ";
            $sql .= "`modifier_id`='{$employee_id}',`modified` = '{$date_time}' ";
            $sql .= "WHERE `id` = '{$merchant_account['id']}' ";
            $sql_queue[] = array('db_name' => 'finance_center', 'sql' => $sql);
        }

        // 添加商家出账记录
        $sql  = " INSERT INTO `finance_center`.`merchant_account_records` SET ";
        $sql .= " `id` = '{$account_record_id}', ";
        $sql .= " `account_id` = '{$merchant_account['id']}', ";
        $sql .= " `record_type` = '2', ";
        $sql .= " `amounts` = -{$balances}, ";
        $sql .= " `created` = '{$date_time}' ";
        $sql_queue[] = array('db_name' => 'finance_center', 'sql' => $sql);

        // 更新优惠券信息
        if ($coupon_id != '') {
            $sql  = "UPDATE `business_center`.`bc_platform_coupons` ";
            $sql .= "SET `status` = 3,`use_time` = '{$date_time}',`use_id`='{$employee_id}',`order_id`='{$order_id}'";
            $sql .= " WHERE `id`='{$coupon_id}' ";
            $sql_queue[] = ['db_name' => 'business_center', 'sql' => $sql];
        }

        // 修改订单
        $sql = " UPDATE `finance_center`.`merchant_account_record_detail_orders` SET ";
        $sql.= " `order_type` = '7', `account_record_id` = '{$account_record_id}',`status` = '1', ";
        $sql.= " `real_amount` = '{$balances}' ";
        $sql.= " WHERE `id`='{$order_id}' ";
        $sql_queue[] = array('db_name' => 'finance_center', 'sql' => $sql);

        $sql = " UPDATE `finance_center`.`merchant_account_record_detail_order_apps` SET ";
        $sql.= " `modified` = '{$date_time}',modifier_id = '{$employee_id}' ";
        $sql.= " WHERE `id` = '{$order_detail['id']}' ";
        $sql_queue[] = array('db_name' => 'finance_center', 'sql' => $sql);

        //首缴 添加管理员 员工角色关系等、续费不用改变，升级更新管理员角色
        if ($order_detail['pay_type'] == 1) {
            $sql  = "INSERT INTO `member_center`.`mc_platform_roles` SET ";
            $sql .= " `id`='{$platform_roles_id}', ";
            $sql .= " `merchant_id`='{$merchant_id}', ";
            $sql .= " `platform_id`='{$order_detail['platform_id']}', ";
            $sql .= " `name`='超级管理员', ";
            $sql .= " `level`='{$merchant_id}-{$order_detail['platform_id']}', ";
            $sql .= " `describe`='教务宝-超级管理员', ";
            $sql .= " `permission`='{$order_detail['platform_version_id']}', ";
            $sql .= " `creator_id`='{$employee_id}', ";
            $sql .= " `created` = '{$date_time}', ";
            $sql .= " `modifier_id` = '{$employee_id}', ";
            $sql .= " `modified` = '{$date_time}'";
            $sql_queue[] = array('db_name' => 'member_center', 'sql' => $sql);

            $sql = "INSERT INTO `member_center`.`mc_employee_role` SET ";
            $sql .= " `employee_id` = '{$employee_id}', ";
            $sql .= " `role_id` = '{$platform_roles_id}', ";
            $sql .= " `creator_id` = '{$employee_id}', ";
            $sql .= " `created` = '{$date_time}', ";
            $sql .= " `modifier_id` = '{$employee_id}', ";
            $sql .= " `modified` = '{$date_time}'";
            $sql_queue[] = array('db_name' => 'member_center', 'sql' => $sql);

        } else {
            $sql  = " UPDATE `member_center`.`mc_employee_role` AS mer ";
            $sql .= " LEFT JOIN `member_center`.`mc_platform_roles` AS mpr ON mer.`role_id`=mpr.`id` ";
            $sql .= " SET mpr.`permission`='{$order_detail['platform_version_id']}', mpr.`modified`='{$date_time}' ";
            $sql .= " WHERE mer.`employee_id`='{$employee_id}' AND mpr.`is_delete`='2' AND mpr.`is_usable`='1' ";
            $sql .= " AND mpr.`platform_id`='{$order_detail['platform_id']}' ";
            $sql .= " AND mpr.`merchant_id`='{$merchant_id}' ";
            $sql_queue[] = array('db_name' => 'member_center', 'sql' => $sql);
        }



        // 更新试用申请数据
        $version_ids = C('TRIAL_JWB.platform_version_ids');
        if (in_array($order_detail['platform_version_id'], $version_ids)) {
            // 查询商家所有员工
            $employee_result = $sqlJoin->query(
                $member_model,
                "SELECT member_id FROM `mc_employees` WHERE merchant_id='{$merchant_id}' AND is_delete='2'"
            );
            $member_ids = arrayToStr($employee_result, 'member_id');

            $trial_sql = $sqlJoin->table('bc_platform_trials')
                ->field('deadline,is_buy,buy_time,buy_merchants_id')
                ->where("member_id IN({$member_ids}) AND is_buy = 2")
                ->sql();
            $trial_result = $sqlJoin->query($business_model, $trial_sql);

            if (!empty($trial_result)) {
                //更新试用申请
                $sql  = "UPDATE `business_center`.`bc_platform_trials` ";
                $sql .= "SET `is_buy` = 1, `buy_time` = '{$employee_id}', `buy_merchants_id` = '{$merchant_id}' ";
                $sql .= "WHERE is_buy = 2 AND `member_id` IN ({$member_ids})";
                $sql_queue[] = array('db_name' => 'business_center', 'sql' => $sql);
            }
        }

        foreach ($sql_queue as $value) {
            addQueue($value['db_name'], $value['sql']);
        }

        DLOG("购买爱校相关应用成功", 'INFO', 'notify.log');

        return true;
    }
}
