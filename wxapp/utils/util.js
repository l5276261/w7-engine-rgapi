const app = getApp()

const formatTime = date => {
    const year = date.getFullYear()
    const month = date.getMonth() + 1
    const day = date.getDate()
    const hour = date.getHours()
    const minute = date.getMinutes()
    const second = date.getSeconds()

    return `${[year, month, day].map(formatNumber).join('/')} ${[hour, minute, second].map(formatNumber).join(':')}`
}

const formatNumber = n => {
    n = n.toString()
    return n[1] ? n : `0${n}`
}

// 封装请求方法
const request = (url, data = {}, method = 'GET') => {
    return new Promise((resolve, reject) => {
        // 获取全局配置的siteInfo
        const siteInfo = require('../siteinfo.js');

        // 构造完整的URL
        // 微擎小程序URL格式: https://domain/app/index.php?i=uniacid&c=entry&a=wxapp&do=action&m=module
        let fullUrl = `${siteInfo.siteroot}?i=${siteInfo.uniacid}&c=entry&a=wxapp&do=${url}&m=${siteInfo.acid}`;

        wx.request({
            url: fullUrl,
            data: data,
            method: method,
            header: {
                'content-type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            success: (res) => {
                if (res.data.errno === 0) {
                    resolve(res.data.data);
                } else {
                    wx.showToast({
                        title: res.data.message || '请求失败',
                        icon: 'none'
                    });
                    reject(res.data);
                }
            },
            fail: (err) => {
                wx.showToast({
                    title: '网络错误',
                    icon: 'none'
                });
                reject(err);
            }
        });
    });
}

module.exports = {
    formatTime,
    request
}
