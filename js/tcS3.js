/*
* JS lib for tcS3
* Author: TC McCarthy
* Sept. 2, 2014
*/

(function($){
	$(document).ready(function(){

		//send file to S3
		$("a.push_single_to_S3").click(function(e){
			e.preventDefault();
			var row = $(this).closest("tr");
			row.animate({opacity: .25}, 600);

			$.getJSON($(this).attr("href"), function(result){
				row.animate({opacity: 1}, 600);
				if(typeof(result.success) != "undefined"){
					row.find(".notuploaded").removeClass("active");
					row.find(".uploaded").addClass("active");
				} else{
					row.find(".notuploaded").addClass("active");
					row.find(".uploaded").removeClass("active");
				}
			});
		});

		$("input#s3_sync").click(function(){
			var plugin_url = $(this).data("plugin-path"), progressBar = $(".progressbar").progressbar({
				value: 0
			}),ids, push_result;
			$.getJSON(plugin_url + "tcS3-ajax.php?action=get_attachment_ids", function(ids){
				if(ids != "null"){
					for(i = 0; i < ids.length; i++){
						percentage = (i / (ids.length - 1)) * 100;

						$.getJSON(plugin_url + "tcS3-ajax.php?action=push_single&postID="+ids[i], function(push_result){
							progressBar.progressbar( "value", percentage);
							$(".progressbar-label").html(percentage+"%");
						});
					}
				}
			});
		});
		
	});
})(jQuery);