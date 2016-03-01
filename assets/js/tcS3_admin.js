/*
 * JS lib for tcS3
 * Author: TC McCarthy
 * Sept. 2, 2014
 */
var tcS3_admin;

(function($) {

    tcS3_admin = {
        init: function(){
            var _this = this;

            $(_this.onReady.bind(_this));
        },

        onReady: function(){
            this.binds();
        },

        binds: function(){
            var _this = this;
            $("body").on("click", "a.push_single_to_S3", {context: _this}, _this.pushToS3);
            $("body").on("click", "input#s3_sync", {context: _this}, _this.s3_sync);
            $("body").on("click", "#tcS3_mark_all_attached", {context: _this}, _this.mark_all_attached);
        },

        pushToS3: function(e){
            e.preventDefault();
            var row = $(this).closest("tr"),
                _this = e.data.context;

            row.animate({opacity: .25}, 600);
            $.ajax({
                url: ajaxurl,
                success: function(output) {
                    _this.ajaxOutput = output;
                },
                error: function(a, b, c) {
                    console.log("Error: " + c);
                },
                complete: function() {
                    row.animate({opacity: 1}, 600);
                    if (typeof (_this.ajaxOutput.success) != "undefined") {
                        row.find(".notuploaded").removeClass("active");
                        row.find(".uploaded").addClass("active");
                    } else {
                        row.find(".notuploaded").addClass("active");
                        row.find(".uploaded").removeClass("active");
                    }
                },
                dataType: "json",
                type: "POST",
                data: {"postID": $(this).data("postid"), "action": "push_single"}
            });
        },

        s3_sync: function(e){
            var progressBar = $(".progressbar").progressbar({
                value: 0
            }), ids, push_result;

            $.ajax({
                url: ajaxurl,
                success: function(output) {
                    ids = output;
                },
                error: function(a, b, c) {
                    console.log("Error: " + c);
                },
                complete: function() {
                    for (i = 0; i < ids.length; i++) {
                        percentage = (i / (ids.length - 1)) * 100;
                        $.ajax({
                            async: false,
                            url: ajaxurl,
                            success: function(push_result) {
                                progressBar.progressbar("value", percentage);
                                $(".progressbar-label").html(percentage + "%");
                            },
                            error: function(a, b, c) {
                                console.log("Error: " + c);
                            },
                            data: {"postID": ids[i], "action": "push_single"},
                            dataType: "json",
                            type: "POST"
                        });
                    }
                },
                data: {"action": "get_attachment_ids", "full_sync": 1},
                dataType: "json",
                type: "POST"

            });
        },

        mark_all_attached: function(e){
            $.ajax({
                url: ajaxurl,
                success: function(output) {
                },
                error: function(a, b, c) {
                    console.log("Error: " + c);
                },
                complete: function() {
                },
                data: {"action": "mark_all_synced"},
                dataType: "json",
                type: "POST"
            });
        }
    };

    tcS3_admin.init();
})(jQuery);