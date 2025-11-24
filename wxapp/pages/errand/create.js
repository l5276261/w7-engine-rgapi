// pages/errand/create.js
const util = require('../../utils/util.js');

Page({
    data: {
        content: '',
        fee: '',
        pickup_address: '',
        delivery_address: '',
        phone: ''
    },

    onContentInput(e) { this.setData({ content: e.detail.value }) },
    onFeeInput(e) { this.setData({ fee: e.detail.value }) },
    onPickupInput(e) { this.setData({ pickup_address: e.detail.value }) },
    onDeliveryInput(e) { this.setData({ delivery_address: e.detail.value }) },
    onPhoneInput(e) { this.setData({ phone: e.detail.value }) },

    submitTask: function () {
        if (!this.data.content) {
            wx.showToast({ title: '请输入任务描述', icon: 'none' });
            return;
        }
        if (!this.data.fee) {
            wx.showToast({ title: '请输入跑腿费', icon: 'none' });
            return;
        }

        const data = {
            content: this.data.content,
            fee: this.data.fee,
            pickup_address: this.data.pickup_address,
            delivery_address: this.data.delivery_address,
            phone: this.data.phone
        };

        util.request('MobileErrandCreate', data, 'POST').then(res => {
            wx.showToast({ title: '发布成功' });
            setTimeout(() => {
                wx.navigateBack();
            }, 1500);
        });
    }
})
