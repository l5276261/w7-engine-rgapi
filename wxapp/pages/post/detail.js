// pages/post/detail.js
const util = require('../../utils/util.js');

Page({
    data: {
        id: 0,
        post: {},
        comments: [],
        commentContent: ''
    },

    onLoad: function (options) {
        this.setData({ id: options.id });
        this.loadDetail();
        this.loadComments();
    },

    loadDetail: function () {
        // 假设API支持获取详情，或者从列表页传递
        // 这里简单模拟获取详情，实际应调用API
        // util.request('MobilePostDetail', { id: this.data.id }).then...
        // 暂时用模拟数据
        this.setData({
            post: {
                id: this.data.id,
                nickname: '测试用户',
                create_time: '2023-10-27 10:00',
                content: '这是一个测试帖子内容',
                images: []
            }
        });
    },

    loadComments: function () {
        util.request('MobileCommentList', { post_id: this.data.id }).then(res => {
            const list = res.list.map(item => {
                item.create_time = util.formatTime(new Date(item.create_time * 1000));
                return item;
            });
            this.setData({ comments: list });
        });
    },

    onCommentInput(e) {
        this.setData({ commentContent: e.detail.value });
    },

    submitComment: function () {
        if (!this.data.commentContent) {
            wx.showToast({ title: '请输入评论内容', icon: 'none' });
            return;
        }

        util.request('MobileCommentCreate', {
            post_id: this.data.id,
            content: this.data.commentContent
        }, 'POST').then(res => {
            wx.showToast({ title: '评论成功' });
            this.setData({ commentContent: '' });
            this.loadComments(); // 刷新评论列表
        });
    }
})
