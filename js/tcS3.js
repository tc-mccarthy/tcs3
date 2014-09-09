/*
 * JS lib for tcS3
 * Author: TC McCarthy
 * Sept. 2, 2014
 */

var ajaxOutput;

(function($) {
    $(document).ready(function() {

        //link to push a single file
        $("a.push_single_to_S3").click(function(e) {
            e.preventDefault();
            var row = $(this).closest("tr");
            row.animate({opacity: .25}, 600);
            $.ajax({
                url: ajaxurl,
                success: function(output) {
                    ajaxOutput = output;
                },
                error: function(a, b, c) {
                    console.log("Error: " + c);
                },
                complete: function() {
                    row.animate({opacity: 1}, 600);
                    if (typeof (ajaxOutput.success) != "undefined") {
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
        });

        //sync button on admin page
        $("input#s3_sync").click(function() {
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
        });

        //mark all items in site/network as synced
        $("#tcS3_mark_all_attached").click(function() {
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
        });
    });
})(jQuery);