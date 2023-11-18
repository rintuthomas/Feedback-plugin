(function($){

	"use strict";
	var post_id  = myAjax.post_id;
	var user_email = myAjax.user_email;
	$(document).ready(function(){

		// if(!isLoggedIn()){
		// 	delete_cookie("feedback_id_"+post_id);
		// }

		// var postID = $("#was_this_helpful").attr("data-post-id");
		if(getCookie("feedback_id_"+post_id) && getCookie("_feedback_username") == user_email){
			
			$("#was_this_helpful").hide();
		}
		//alert(getCookie("_feedback_username") +': '+ user_email);
		//alert(getCookie("feedback_id_"+post_id));
	});
	// function isLoggedIn(){
	// 	var loggedIn;
	// 	loggedIn = localStorage.getItem("wp_cg_logged_in");
	// 	return loggedIn;
	// }
	// Yes / No
	$("#wp_feedback_yes_no span").click(function(){

		// Getting value
		var value = parseInt($(this).attr("data-value"));
		var postID = $("#was_this_helpful").attr("data-post-id");
		// Cant send ajax
		// if(getCookie("feedback_id_"+postID)){
		// 	return false;
		// }
			console.log('clicked on was this helpful');
		//alert(myAjax.nonce_wp_feedback);
		$.post(myAjax.ajaxurl, {action: "wp_feedback_ajax", id: postID, val: value, nonce: myAjax.nonce_wp_feedback, url: window.location.href}).done(function(data){
			//setCookie("feedback_id_"+postID, "1");
			console.log(data);
			console.log('success after feedback submission');
		});

		// Disable and show a thank message
		setTimeout(function(){
			$("#was_this_helpful").addClass("wp_feedback_disabled");
		}, 20);

	});


	// Set Cookie
	function setCookie(name, value) {
		var expires = "";
		var date = new Date();
		date.setTime(date.getTime() + (24*60*60*1000));
		expires = "; expires=" + date.toUTCString();
		document.cookie = name + "=" + (value || "")  + expires + "; path=/";
	}


	// Get Cookie
	function getCookie(name) {
	    var nameEQ = name + "=";
	    var ca = document.cookie.split(';');
	    for(var i=0;i < ca.length;i++) {
	        var c = ca[i];
	        while (c.charAt(0)==' ') c = c.substring(1,c.length);
	        if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length,c.length);
	    }
	    return null;
	}
	// function delete_cookie(name) {
	// 	document.cookie = name +'=; Path=/; Expires=Thu, 01 Jan 1970 00:00:01 GMT;';
	//   }

})(jQuery);
