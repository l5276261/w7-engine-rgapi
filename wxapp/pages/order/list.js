// pages/order/list.js
const util = require('../../utils/util.js');

Page({
    data: {
        status: '',
        orderList: [],
        page: 1
    },

    onShow: function () {
        this.setData({ page: 1 });
        this.loadOrders();
    },

    switchTab: function (e) {
        const status = e.currentTarget.dataset.status;
        this.setData({
            status: status,
            page: 1,
            orderList: []
        });
        this.loadOrders();
    },

    loadOrders: function () {
        util.request('MobileOrderList', {
            status: this.data.status,
            page: this.data.page
        }).then(res => {
            // 格式化状态文本
            const list = res.list.map(item => {
                item.status_text = this.getStatusText(item.status);
                item.create_time = util.formatTime(new Date(item.create_time * 1000));
                return item;
            });

            this.setData({
                orderList: this.data.page === 1 ? list : this.data.orderList.concat(list)
            });
        });
    },

    getStatusText: function (status) {
        const map = {
            '0': '待支付',
            '1': '待接单',
            '2': '备餐中',
            '3': '配送中',
            '4': '已完成',
            '5': '已取消'
        };
        return map[status] || '未知';
    },

    goToDetail: function (e) {
        const id = e.currentTarget.dataset.id;
        wx.navigateTo({
            url: `/pages/order/detail?id=${id}`
        });
    }
})
