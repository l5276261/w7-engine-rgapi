// pages/user/index.js
const util = require('../../utils/util.js');

Page({
    data: {
        userInfo: {}
    },

    onLoad: function (options) {
        const userInfo = wx.getStorageSync('userInfo');
        if (userInfo) {
            this.setData({ userInfo });
        }
    },

    getUserProfile: function () {
        wx.getUserProfile({
            desc: '用于完善会员资料',
            success: (res) => {
                this.setData({
                    userInfo: res.userInfo
                });
                wx.setStorageSync('userInfo', res.userInfo);
                // 实际开发中还需要将用户信息同步到服务器
            }
        });
    },

    goToOrder: function () {
        wx.switchTab({
            url: '/pages/order/list' // 假设订单列表在tabbar，如果不在则用navigateTo
        });
        // 如果不在tabbar:
        // wx.navigateTo({ url: '/pages/order/list' });
    },

    goToErrand: function () {
        wx.navigateTo({
            url: '/pages/errand/index?type=my' // 假设跑腿页支持查看我的任务
        });
    },

    goToSchedule: function () {
        wx.navigateTo({
            url: '/pages/user/schedule'
        });
    },

    goToAddress: function () {
        wx.chooseAddress({
            success: (res) => {
                // 仅演示调用微信地址
            }
        });
    },

    clearCache: function () {
        wx.showModal({
            title: '提示',
            content: '确定要清除缓存吗？',
            success: (res) => {
                if (res.confirm) {
                    wx.clearStorageSync();
                    this.setData({ userInfo: {} });
                    wx.showToast({ title: '清除成功' });
                }
            }
        });
    }
})
