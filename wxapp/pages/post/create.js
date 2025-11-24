// pages/post/create.js
const util = require('../../utils/util.js');
const siteInfo = require('../../siteinfo.js');

Page({
    data: {
        content: '',
        images: []
    },

    onContentInput(e) {
        this.setData({ content: e.detail.value });
    },

    chooseImage() {
        wx.chooseImage({
            count: 9 - this.data.images.length,
            success: (res) => {
                // 实际开发中需要上传图片到服务器
                // 这里简化处理，直接使用本地路径演示
                // 真实场景需调用 wx.uploadFile
                this.setData({
                    images: this.data.images.concat(res.tempFilePaths)
                });

                // 模拟上传逻辑
                /*
                const tempFilePaths = res.tempFilePaths;
                const uploadUrl = `${siteInfo.siteroot}?i=${siteInfo.uniacid}&c=entry&a=wxapp&do=upload&m=${siteInfo.acid}`;
                // 循环上传...
                */
            }
        });
    },

    deleteImage(e) {
        const index = e.currentTarget.dataset.index;
        const images = this.data.images;
        images.splice(index, 1);
        this.setData({ images });
    },

    submitPost() {
        if (!this.data.content && this.data.images.length === 0) {
            wx.showToast({ title: '请输入内容或上传图片', icon: 'none' });
            return;
        }

        // 假设图片已经上传并获取到了服务器路径
        // 这里直接发送
        const data = {
            content: this.data.content,
            images: JSON.stringify(this.data.images)
        };

        util.request('MobilePostCreate', data, 'POST').then(res => {
            wx.showToast({ title: '发布成功' });
            setTimeout(() => {
                wx.navigateBack();
            }, 1500);
        });
    }
})
