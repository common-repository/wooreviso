/*jQuery(document).ready(function() {
    jQuery("li.wp-first-item").hide();
});*/

function sync_orders(sync_message) {
    var data = {
        action: 'sync_orders'
    };
    alert(sync_message);
    jQuery(".order_load").show();
    jQuery.ajax({
		url: ajaxurl,
		data: data,
		dataType: "json",
	}).always(function(data) {
		alert(data.status);
		//alert (JSON.stringify(data));
		jQuery("#result").html(data.msg);
		jQuery(".order_load").hide();
	});
}

function sync_contacts(sync_message) {
	if(jQuery("#sync_direction option:selected").attr("value") == 'wr'){
		var data = {
			action: 'sync_contacts'
		};
	}else{
		var data = {
			action: 'sync_contacts_rw'
		};
	}
    alert(sync_message);
    jQuery(".customer_load").show();
    jQuery.ajax({
		url: ajaxurl,
		data: data,
		dataType: "json",
	}).always(function(data) {
		alert(data.status);
		//alert (JSON.stringify(data));
		jQuery("#result").html(data.msg);
		jQuery(".customer_load").hide();
	});
}

function sync_products(sync_message) {
	if(jQuery("#sync_direction option:selected").attr("value") == 'wr'){
		var data = {
			 action: 'sync_products'
		};
	}else{
		var data = {
			 action: 'sync_products_rw'
		};
	}
    alert(sync_message);
	jQuery(".product_load").show();
	jQuery.ajax({
		url: ajaxurl,
		data: data,
		dataType: "json",
	}).always(function(data) {
		alert(data.status);
		//alert (JSON.stringify(data));
		jQuery("#result").html(data.msg);
		jQuery(".product_load").hide();
	});
}


function sync_shippings(sync_message) {
    var data = {
        action: 'sync_shippings'
    };
    alert(sync_message);
	jQuery(".shipping_load").show();
	jQuery.ajax({
		url: ajaxurl,
		data: data,
		dataType: "json",
	}).always(function(data) {
		//alert (JSON.stringify(data));
		alert(data.status);
		jQuery("#result").html(data.msg);
		jQuery(".shipping_load").hide();
	});
}

function sync_coupons(sync_message) {
    var data = {
        action: 'sync_coupons'
    };
    alert(sync_message);
	jQuery(".coupon_load").show();
	jQuery.ajax({
		url: ajaxurl,
		data: data,
		dataType: "json",
	}).always(function(data) {
		//alert (JSON.stringify(data));
		alert(data.status);
		jQuery("#result").html(data.msg);
		jQuery(".coupon_load").hide();
	});
}

function send_support_mail(form) {
    var data = jQuery('form#'+form).serialize();
    jQuery.post(ajaxurl, data, function(response) {
        if(response == "success"){
			alert("Message sent successfully!");
		}else{
			alert("Problem sending message, please try again later.");
		}
    });
}


function test_connection(){
	jQuery(".test_warning").hide(function(){
		jQuery(".test_load").show();
	});
	var data = {
        action: 'test_connection'
    };
    jQuery.post(ajaxurl, data, function(response) {
		jQuery(".test_load").hide(function(){
			jQuery(".test_warning").show();
			alert(response);
		});
    });
}