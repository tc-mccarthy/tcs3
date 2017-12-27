var app = {
    ele: {},
    init: function() {
        var _this = this;
        $(_this.onReady.bind(_this));
        $(window).scroll(_this.onScroll.bind(_this));
        $(window).resize(_this.onResize.bind(_this));
        $(window).load(_this.onLoad.bind(_this));
    },

    onReady: function() {
        var _this = this;
        this.binds();
    },

    onLoad: function() {
        var _this = this;
    },

    onScroll: function() {
        var _this = this;
    },

    onResize: function() {
        var _this = this;
    },

    binds: function() {
        var _this = this;
    }
};

app.init();