// pages/index/index.js
const util = require('../../utils/util.js');

Page({
    data: {
        merchantList: [],
        page: 1,
        hasMore: true,
        loading: false
    },

    onLoad: function (options) {
        this.loadMerchants();
    },

    onPullDownRefresh: function () {
        this.setData({
            page: 1,
            hasMore: true,
            merchantList: []
        });
        this.loadMerchants().then(() => {
            wx.stopPullDownRefresh();
        });
    },

    onReachBottom: function () {
        if (this.data.hasMore && !this.data.loading) {
            this.setData({
                page: this.data.page + 1
            });
            this.loadMerchants();
        }
    },

    loadMerchants: function () {
        this.setData({ loading: true });
        return util.request('MobileMerchantList', {
            page: this.data.page
        }).then(res => {
            const newList = this.data.merchantList.concat(res.list);
            this.setData({
                merchantList: newList,
                hasMore: res.list.length >= 10, // 假设每页10条
                loading: false
            });
        }).catch(err => {
            this.setData({ loading: false });
            console.error(err);
        });
    },

    goToMerchant: function (e) {
        const id = e.currentTarget.dataset.id;
        wx.navigateTo({
            url: `/pages/merchant/detail?id=${id}`
        });
    }
})
