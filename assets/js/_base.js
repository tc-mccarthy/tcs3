var tcs3;

(function ($) {
	tcs3 = {
		ele: {},
		init: function () {

			$((e) => {
				this.onReady(e);
			});

			$("window").on({
				scroll: (e) => { this.onScroll(e); },
				resize: (e) => { this.onResize(e); },
				load: (e) => { this.onLoad(e); }
			});
		},

		onReady: function () {
			var _this = this;
			this.binds();
			console.log("READY!");
		},

		onLoad: function () {
			var _this = this;
		},

		onScroll: function () {
			var _this = this;
		},

		onResize: function () {
			var _this = this;
		},

		binds: function () {
			var _this = this;

			$("#tcs3_network_bucket_path, #tcs3_bucket_path").on("blur", function (e) {
				var ele = $(this),
					value = ele.val().replace(/^[\/]+/, "");

				console.log("Updating path");

				if (!value || value == "") {
					value = "/";
				}

				ele.val(value);
			});

			$("#tcs3_network_s3_url, #tcs3_s3_url").on("blur", function (e) {
				var ele = $(this),
					value = ele.val().replace(/[\/]+$/, "") + "/";

				console.log("Updating URL");

				ele.val(value);
			});
		}
	};

	tcs3.init();
})(jQuery);
