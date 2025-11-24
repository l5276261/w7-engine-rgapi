// pages/user/schedule.js
const util = require('../../utils/util.js');

Page({
    data: {
        currentWeek: 1,
        weekDays: ['周一', '周二', '周三', '周四', '周五', '周六', '周日'],
        courseList: []
    },

    onLoad: function (options) {
        this.loadCourses();
    },

    loadCourses: function () {
        util.request('MobileCourseList', {}).then(res => {
            // 假设API返回的数据格式需要处理
            // 模拟数据
            const colors = ['#ff9c6e', '#ffc069', '#95de64', '#5cdbd3', '#69c0ff', '#b37feb', '#ff85c0'];

            const list = res.list.map((item, index) => {
                item.color = colors[index % colors.length];
                return item;
            });

            // 如果没有数据，添加一些模拟数据用于展示
            if (list.length === 0) {
                list.push(
                    { id: 1, name: '高等数学', location: 'A101', day: 1, start_section: 1, duration: 2, color: colors[0] },
                    { id: 2, name: '大学英语', location: 'B202', day: 2, start_section: 3, duration: 2, color: colors[1] },
                    { id: 3, name: '计算机基础', location: 'C303', day: 3, start_section: 5, duration: 2, color: colors[2] },
                    { id: 4, name: '体育', location: '操场', day: 4, start_section: 7, duration: 2, color: colors[3] }
                );
            }

            this.setData({ courseList: list });
        });
    },

    addCourse: function () {
        wx.showActionSheet({
            itemList: ['手动添加', '从分享码导入'],
            success: (res) => {
                if (res.tapIndex === 0) {
                    wx.showToast({ title: '暂未开放手动添加', icon: 'none' });
                } else if (res.tapIndex === 1) {
                    wx.showModal({
                        title: '导入课程',
                        editable: true,
                        placeholderText: '请输入分享码',
                        success: (res) => {
                            if (res.confirm && res.content) {
                                util.request('MobileCourseImport', { share_code: res.content }, 'POST').then(r => {
                                    wx.showToast({ title: '导入成功' });
                                    this.loadCourses();
                                });
                            }
                        }
                    });
                }
            }
        });
    },

    showCourseDetail: function (e) {
        const item = e.currentTarget.dataset.item;
        wx.showModal({
            title: item.name,
            content: `教室: ${item.location}\n时间: 周${item.day} 第${item.start_section}-${item.start_section + item.duration - 1}节`,
            showCancel: false
        });
    }
})
