"use strict";

var tcs3;

(function ($) {
	tcs3 = {
		ele: {},
		init: function init() {
			var _this2 = this;

			$(function (e) {
				_this2.onReady(e);
			});

			$("window").on({
				scroll: function scroll(e) {
					_this2.onScroll(e);
				},
				resize: function resize(e) {
					_this2.onResize(e);
				},
				load: function load(e) {
					_this2.onLoad(e);
				}
			});
		},

		onReady: function onReady() {
			var _this = this;
			this.binds();
			
		},

		onLoad: function onLoad() {
			var _this = this;
		},

		onScroll: function onScroll() {
			var _this = this;
		},

		onResize: function onResize() {
			var _this = this;
		},

		binds: function binds() {
			var _this = this;

			$("#tcs3_network_bucket_path, #tcs3_bucket_path").on("blur", function (e) {
				var ele = $(this),
				    value = ele.val().replace(/^[\/]+/, "");

				

				if (!value || value == "") {
					value = "/";
				}

				ele.val(value);
			});

			$("#tcs3_network_s3_url, #tcs3_s3_url").on("blur", function (e) {
				var ele = $(this),
				    value = ele.val().replace(/[\/]+$/, "") + "/";

				

				ele.val(value);
			});
		}
	};

	tcs3.init();
})(jQuery);
