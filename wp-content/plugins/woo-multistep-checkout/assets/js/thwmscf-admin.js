var thwmscf_admin = (function($){
	$(function(){
		$( ".thpladmin-colorpick" ).each(function() {     	
			var value = $(this).val();
			$( this ).parent().find( '.thpladmin-colorpickpreview' ).css({ backgroundColor: value });
		});

	    $('.thpladmin-colorpick').iris({
			change: function( event, ui ) {
				$( this ).parent().find( '.thpladmin-colorpickpreview' ).css({ backgroundColor: ui.color.toString() });
			},
			hide: true,
			border: true
		}).click( function() {
			$('.iris-picker').hide();
			$(this ).closest('td').find('.iris-picker').show(); 
		});

		$('body').click( function() {
			$('.iris-picker').hide();
		});

		$('.thpladmin-colorpick').click( function( event ) {
			event.stopPropagation();
		});
	});

	function back_to_cart(elm){
		var cart_text_settings = $('.back-to-cart-show');		
		if(elm.checked){
			cart_text_settings.show();;
		}else{
			cart_text_settings.hide();
		}
	}

	function display_login(elm){
		var cart_text_settings = $('.display-login-step');		
		if(elm.checked){
			cart_text_settings.show();;
		}else{
			cart_text_settings.hide();
		}
	}

	function shippingTitle(elm) {
		var shipping_title = $('.display-shipping-title')
		if(elm.checked){
			shipping_title.hide();
		}else{
			shipping_title.show();
		}
	}

	function orderreviewtitle(elm) {
		var order_review_title = $('.display-order-review-title')
		var order_details_titile = $('.display-order-details-title')
		var confirm_order_title = $('.display-confirm-order-title')
		if(elm.checked){
			order_review_title.hide();
			order_details_titile.show();
			confirm_order_title.show();
		}else{
			order_review_title.show();
			order_details_titile.hide();
			confirm_order_title.hide();
		}
	}

	function order_review_right(elm){
		var form = elm.closest('form');
		var host_class = $(form).find('#th-show-review-right');

		hide_field_using_wmsc_blur($(elm), host_class);
	}

	function hide_field_using_wmsc_blur(elm, host_class){
		if(elm.prop('checked') != true){
			remove_class_wmsc_blur(host_class)
		}else{
			add_class_wmsc_blur(host_class);
		}
	}

	function remove_class_wmsc_blur(host_class){
		host_class.removeClass('wmsc-blur');
	}

	function add_class_wmsc_blur(host_class){
		host_class.addClass('wmsc-blur');
	}

	function layout_change(elm){
		var layout = elm.value;
		var tab_position = $('.display-tab-position');
		if(layout == 'thwmscf_time_line_step'){   	
			var color = '#050505';
			var text_color_active = $('input[name="i_step_text_color_active"]');
			if(text_color_active.val() == '#ffffff'){
				text_color_active.parent().find( '.thpladmin-colorpickpreview' ).css({ backgroundColor: color });
				text_color_active.val(color);
			}
			tab_position.hide();
		}else if(layout == 'thwmscf_accordion_step'){
			tab_position.hide();
		}else{
			tab_position.show();
		}
	}

	return {
		back_to_cart : back_to_cart,
		display_login : display_login,
		shippingTitle : shippingTitle,
		layout_change : layout_change,
		orderreviewtitle : orderreviewtitle,
		order_review_right : order_review_right,
	}
}(window.jQuery, window, document))

function thwmscfBackToCart(elm){
	thwmscf_admin.back_to_cart(elm);
}
function thwmscfDisplayLogin(elm){
	thwmscf_admin.display_login(elm);
}
function thwmscfShippingTitle(elm){
	thwmscf_admin.shippingTitle(elm);
}
function thwmscfOrderReview(elm){
	// thwmscf_admin.orderreviewtitle(elm);
	thwmscf_admin.order_review_right(elm);
}
function thwmscLayoutChange(elm){
	thwmscf_admin.layout_change(elm);
}