// pages/order/create.js
const util = require('../../utils/util.js');

Page({
    data: {
        items: [],
        merchantId: 0,
        deliveryFee: 3.00, // 默认配送费，实际应从商家信息获取
        totalPrice: 0,
        finalPrice: 0,
        address: null,
        remark: ''
    },

    onLoad: function (options) {
        const items = wx.getStorageSync('cartItems') || [];
        const merchantId = wx.getStorageSync('merchantId');

        let total = 0;
        items.forEach(item => {
            total += item.price * item.quantity;
        });

        this.setData({
            items: items,
            merchantId: merchantId,
            totalPrice: total.toFixed(2),
            finalPrice: (total + this.data.deliveryFee).toFixed(2)
        });
    },

    chooseAddress: function () {
        wx.chooseAddress({
            success: (res) => {
                this.setData({
                    address: {
                        userName: res.userName,
                        telNumber: res.telNumber,
                        addressDetail: res.provinceName + res.cityName + res.countyName + res.detailInfo
                    }
                });
            }
        });
    },

    onRemarkInput: function (e) {
        this.setData({ remark: e.detail.value });
    },

    submitOrder: function () {
        if (!this.data.address) {
            wx.showToast({ title: '请选择收货地址', icon: 'none' });
            return;
        }

        const data = {
            merchant_id: this.data.merchantId,
            items: JSON.stringify(this.data.items), // 转换为JSON字符串
            delivery_address: this.data.address.addressDetail,
            delivery_name: this.data.address.userName,
            delivery_phone: this.data.address.telNumber,
            remark: this.data.remark
        };

        util.request('MobileOrderCreate', data, 'POST').then(res => {
            wx.showToast({ title: '下单成功' });
            // 模拟支付成功，跳转到订单列表
            setTimeout(() => {
                wx.switchTab({
                    url: '/pages/order/list' // 假设订单列表在tabbar，如果不是则用navigateTo
                });
            }, 1500);
        });
    }
})
