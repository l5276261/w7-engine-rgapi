// pages/post/index.js
const util = require('../../utils/util.js');

Page({
    data: {
        postList: [],
        page: 1,
        hasMore: true,
        loading: false
    },

    onShow: function () {
        this.setData({ page: 1, postList: [] });
        this.loadPosts();
    },

    onPullDownRefresh: function () {
        this.setData({
            page: 1,
            hasMore: true,
            postList: []
        });
        this.loadPosts().then(() => {
            wx.stopPullDownRefresh();
        });
    },

    onReachBottom: function () {
        if (this.data.hasMore && !this.data.loading) {
            this.setData({
                page: this.data.page + 1
            });
            this.loadPosts();
        }
    },

    loadPosts: function () {
        this.setData({ loading: true });
        return util.request('MobilePostList', {
            page: this.data.page
        }).then(res => {
            const list = res.list.map(item => {
                item.create_time = util.formatTime(new Date(item.create_time * 1000));
                // 假设images是JSON字符串，需要解析
                try {
                    if (typeof item.images === 'string') {
                        item.images = JSON.parse(item.images);
                    }
                } catch (e) {
                    item.images = [];
                }
                return item;
            });

            this.setData({
                postList: this.data.page === 1 ? list : this.data.postList.concat(list),
                hasMore: list.length >= 10,
                loading: false
            });
        }).catch(err => {
            this.setData({ loading: false });
        });
    },

    goToCreate: function () {
        wx.navigateTo({
            url: '/pages/post/create'
        });
    },

    goToDetail: function (e) {
        const id = e.currentTarget.dataset.id;
        wx.navigateTo({
            url: `/pages/post/detail?id=${id}`
        });
    },

    previewImage: function (e) {
        const current = e.currentTarget.dataset.current;
        const urls = e.currentTarget.dataset.urls;
        wx.previewImage({
            current: current,
            urls: urls
        });
    }
})
