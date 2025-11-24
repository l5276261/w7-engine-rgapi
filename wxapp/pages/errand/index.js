// pages/errand/index.js
const util = require('../../utils/util.js');

Page({
    data: {
        status: '0',
        taskList: [],
        page: 1
    },

    onShow: function () {
        this.setData({ page: 1 });
        this.loadTasks();
    },

    switchTab: function (e) {
        const status = e.currentTarget.dataset.status;
        this.setData({
            status: status,
            page: 1,
            taskList: []
        });
        this.loadTasks();
    },

    loadTasks: function () {
        // 这里的API需要对应wxapp.php中的doMobileErrandList
        // 假设API参数为 status (0:待接单, 1:进行中, 2:已完成)
        // 注意：实际wxapp.php中可能需要调整以支持按状态筛选
        util.request('MobileErrandList', {
            status: this.data.status,
            page: this.data.page
        }).then(res => {
            const list = res.list.map(item => {
                item.create_time = util.formatTime(new Date(item.create_time * 1000));
                return item;
            });

            this.setData({
                taskList: this.data.page === 1 ? list : this.data.taskList.concat(list)
            });
        });
    },

    goToCreate: function () {
        wx.navigateTo({
            url: '/pages/errand/create'
        });
    },

    acceptTask: function (e) {
        const id = e.currentTarget.dataset.id;
        wx.showModal({
            title: '提示',
            content: '确定要接下这个任务吗？',
            success: (res) => {
                if (res.confirm) {
                    util.request('MobileErrandAccept', { id: id }, 'POST').then(res => {
                        wx.showToast({ title: '接单成功' });
                        this.onShow(); // 刷新列表
                    });
                }
            }
        });
    }
})
