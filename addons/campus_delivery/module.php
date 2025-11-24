<?php
/**
 * 校园外卖平台模块定义
 */
defined('IN_IA') or exit('Access Denied');

class Campus_deliveryModule extends WeModule {
    
    /**
     * 模块安装后显示的配置表单
     */
    public function settingsDisplay($settings) {
        global $_W, $_GPC;
        
        if (checksubmit('submit')) {
            $settings = array(
                'delivery_fee' => floatval($_GPC['delivery_fee']),
                'min_order_amount' => floatval($_GPC['min_order_amount']),
                'platform_profit_rate' => floatval($_GPC['platform_profit_rate']),
                'rider_profit_rate' => floatval($_GPC['rider_profit_rate']),
                'merchant_profit_rate' => floatval($_GPC['merchant_profit_rate']),
                'errand_platform_rate' => floatval($_GPC['errand_platform_rate']),
            );
            $this->saveSettings($settings);
            message('配置保存成功', referer(), 'success');
        }
        
        include $this->template('settings');
    }
    
    /**
     * 模块卸载前的操作
     */
    public function modulesUninstall() {
        return true;
    }
}
