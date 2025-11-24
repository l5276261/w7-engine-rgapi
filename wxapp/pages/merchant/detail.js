// pages/merchant/detail.js
const util = require('../../utils/util.js');

Page({
    data: {
        merchantId: 0,
        merchant: {},
        categories: [],
        foods: [],
        currentCategory: 0,
        toView: '',
        cart: {}, // {foodId: quantity}
        totalCount: 0,
        totalPrice: 0
    },

    onLoad: function (options) {
        this.setData({ merchantId: options.id });
        this.loadData();
    },

    loadData: function () {
        // 获取商家详情
        util.request('MobileMerchantDetail', { id: this.data.merchantId }).then(res => {
            this.setData({ merchant: res });
        });

        // 获取商品列表
        util.request('MobileFoodList', { merchant_id: this.data.merchantId }).then(res => {
            // 整理数据，res.list 包含商品，res.categories 包含分类
            // 假设API返回结构需要适配，这里简化处理
            // 实际开发中可能需要根据API返回结构调整
            this.setData({
                categories: res.categories || [],
                foods: res.list || [],
                currentCategory: res.categories[0]?.id
            });
        });
    },

    switchCategory: function (e) {
        const id = e.currentTarget.dataset.id;
        this.setData({
            currentCategory: id,
            toView: 'cat_' + id
        });
    },

    updateCart: function (e) {
        const { id, op } = e.currentTarget.dataset;
        let cart = this.data.cart;
        let quantity = cart[id] || 0;

        if (op === 'plus') {
            quantity++;
        } else {
            quantity--;
        }

        if (quantity <= 0) {
            delete cart[id];
        } else {
            cart[id] = quantity;
        }

        this.setData({ cart });
        this.calculateTotal();
    },

    calculateTotal: function () {
        let count = 0;
        let price = 0;
        const cart = this.data.cart;
        const foods = this.data.foods;

        for (let id in cart) {
            const food = foods.find(f => f.id == id);
            if (food) {
                count += cart[id];
                price += cart[id] * parseFloat(food.price);
            }
        }

        this.setData({
            totalCount: count,
            totalPrice: price.toFixed(2)
        });
    },

    goToCheckout: function () {
        if (this.data.totalCount === 0) return;

        // 将购物车数据存入本地存储，供结算页使用
        const cartItems = [];
        for (let id in this.data.cart) {
            const food = this.data.foods.find(f => f.id == id);
            if (food) {
                cartItems.push({
                    food_id: id,
                    name: food.name,
                    price: food.price,
                    quantity: this.data.cart[id],
                    image: food.image
                });
            }
        }

        wx.setStorageSync('cartItems', cartItems);
        wx.setStorageSync('merchantId', this.data.merchantId);

        wx.navigateTo({
            url: '/pages/order/create'
        });
    }
})
