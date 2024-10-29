//jQuery.datetimepicker.setLocale('en');


jQuery(function(){
jQuery( "#booking" ).validate({
  rules: {
    first_name: {
      required: true
    },
    last_name: {
      required: true
    },
    email: {
      required: true,
      email: true
    },
    tel1: {
      required: true
    },
    accept_term: {
      required: true
    },
    accept_term_adds: {
      required: true
    }
  }
});

});

jQuery(function($){
            $('#print').click(function(){
    		window.print();
         });
         
    $('#select_price_block').on('change', 'select.as-adult', function(){
        var age_id = parseInt($(this).data('age-id'));
        var $new_guests = parseInt($(this).val());
        var $max_guests = parseInt($(this).data('av-tickets'));
        var $before_guests = 0;
        var par_tr = $(this).parent().parent();
        
        $(par_tr).prevAll().each(function(){
            $before_guests = $before_guests + parseInt($(this).find('td select.as-adult').val());
        });
        
        var av_guests = $max_guests - $before_guests;
        // $('.cell-total-tickets').html($before_guests);
        
        if (av_guests < $new_guests){
            // set max select to av_guests
            $(this).find('option').filter(function() {
                //may want to use $.trim in here
                return $(this).val() == av_guests; 
            }).prop('selected', true);
            $new_guests = av_guests;
        }
        
        $('#pre_booking input[name="event_guests\['+age_id+'\]"]').val($new_guests);
        
        $(par_tr).nextAll().each(function(){
            // reset all after selections
            $(this).find('td select.as-adult option').filter(function() {
                //may want to use $.trim in here
                return $(this).val() == 0; 
            }).prop('selected', true);
        });
        
        update_total_price();
        update_total_guests();  
    });     
    
    $('#select_price_block').on('change', 'select.as-free', function(){
        var age_id = parseInt($(this).data('age-id'));
        var $new_guests = parseInt($(this).val());
        $('#pre_booking input[name="event_guests\['+age_id+'\]"]').val($new_guests);
        
        update_total_price();
        update_total_guests();
    });
    
function update_total_price(){
    var price = 0;
    $('#select_price_block').find('.select-tickets').each(function(){
        price = price + parseFloat($(this).data('price'))*parseInt($(this).val());
    });
    $('#select_price_block').find('.cell-total-amount span').html(price);
}

function update_total_guests(){
    var guests = 0;
    $('#select_price_block').find('.select-tickets').each(function(){
        guests = guests + parseInt($(this).val());
    });
    $('#select_price_block').find('.cell-total-tickets').html(guests);
}         
         
});

//////////////////////////////

jQuery(function($){
         
    $('#booking-cal-block').on('click', '.nav-arrow', function(){
        var event = $('#booking-cal-block').data('event');
        var event_date = $(this).data('event-date');
        $('#select_price_block').html('');
        $('#av_times_block').html('');
        
        $('#booking-cal-block').html('<span class="spin_f center-block"><i class="fa fa-spinner fa-pulse fa-3x fa-fw"></i></span>');
        $.ajax({
		url : lst.ajax_url,
		type : 'POST',
		data : {
			action : 'booking_cal_update',
            event_id : event,
            event_date : event_date,
            // check
	        nonce : lst.nonce
		},
		success : function( msg ) {
		    $('#booking-cal-block').html(msg);
            $('.cal-cell-date.selected').removeClass('selected');
		  }
        });
        $('#pre_booking input[name="event_date"]').val('');
        $('#pre_booking input[name="event_time"]').val('');
        
        $.ajax({
		url : lst.ajax_url,
		type : 'POST',
		data : {
			action : 'booking_time_update',
            event_id : event,
            // check
	        nonce : lst.nonce
		},
		success : function( msg ) {
		    $('#av_times_block').html(msg);
		  }
        });
        
    });

    $('#booking-cal-block').on('click', '#booking-cal .cal-cell-date.cal-av', function(){
        var event_date = $(this).data('event-date');
        var event = $('#booking-cal-block').data('event');
        $('#pre_booking input[name="event_date"]').val(event_date);
        $('#pre_booking input[name="event_time"]').val('');
        $('.cal-cell-date.selected').removeClass('selected');
        $(this).addClass('selected');
        $('#select_price_block').html('');
        
        $('#av_times_block').html('<span class="spin_f center-block"><i class="fa fa-spinner fa-pulse fa-3x fa-fw"></i></span>');
        $.ajax({
		url : lst.ajax_url,
		type : 'POST',
		data : {
			action : 'booking_time_update',
            event_id : event,
            event_date : event_date,
            // check
	        nonce : lst.nonce
		},
		success : function( msg ) {
		    $('#av_times_block').html(msg);
		  }
        });
    });
    
    $('#av_times_block').on('click', 'input[name="event_time"]', function(){
        var event_time = $(this).val();
        var event = $('#booking-cal-block').data('event');
        var event_date = $('#pre_booking input[name="event_date"]').val();
        $('#pre_booking input[name="event_time"]').val(event_time);
        
        $('#select_price_block').html('<span class="spin_f center-block"><i class="fa fa-spinner fa-pulse fa-3x fa-fw"></i></span>');
        $.ajax({
		url : lst.ajax_url,
		type : 'POST',
		data : {
			action : 'booking_price_update',
            event_id : event,
            event_date : event_date,
            event_time : event_time,
            // check
	        nonce : lst.nonce
		},
		success : function( msg ) {
		    $('#select_price_block').html(msg);
		  }
        });
        
    });
    
    $('#select_price_block').on('click', '#go_to_booking', function(){
        var event_date = $('#pre_booking input[name="event_date"]').val();
        var event = $('#pre_booking input[name="event_id"]').val();
        var event_time = $('#pre_booking input[name="event_time"]').val();
        var guests = 0;
        
        $('#select_price_block').find('select.as-adult').each(function(){
            guests = guests + parseInt($(this).val());
        });
        
        if(guests > 0) $('#pre_booking').submit();
        
    });
    
    $('#main-cal-header').on('change', '#cal-select-month', get_event_cal);
    
    $('#main-cal-header').on('change', '#cal-select-event', function(){
       var event_id = $(this).val();
       if (event_id > 0){
          $('#main-cal-body').find('.cal-cell-inner').css('display', 'none');
          $('#main-cal-body').find('.cal-cell-inner[data-id="'+event_id+'"]').css('display', 'block');
       } else {
          $('#main-cal-body').find('.cal-cell-inner').css('display', 'block');
       } 
    });
    
    $('.booknow_event_btn').on('click', function(){
        var event = $(this).data('event');
        var url = $(this).data('url');
        document.location.href = url;
    });

});

///////////////////////

function get_event_cal(){
        var cur_month = jQuery('#cal-select-month').val();
        jQuery('#main-cal-body').html('<span class="spin_f center-block"><i class="fa fa-spinner fa-pulse fa-3x fa-fw"></i></span>');
        jQuery.ajax({
		url : lst.ajax_url,
		type : 'POST',
		data : {
			action : 'main_cal_update',
            cur_month : cur_month,
            // check
	        nonce : lst.nonce
		},
		success : function( msg ) {
		    jQuery('#main-cal-body').html(msg);
		  }
        });     
}

//////////////////////////////////////

jQuery(document).ready(function($){
    
    get_event_cal();
    
    $('.toggle_faq_box').on('click', function(){
        
        $(this).find('.ba_event_faq_box_collapse').each(function(){
           if ( $( this ).hasClass( "box_open" ) ){
            $(this).slideUp(400, 'linear');
            $(this).toggleClass('box_open');
           } else {
            $(this).slideDown(400, 'linear');
            $(this).toggleClass('box_open');  
           } 
        });
        
        $(this).find('.toggle_icon_box span').each(function(){
            if ( $( this ).hasClass( "chev_down" ) ){
                $( this ).addClass("chev_up");
                $( this ).removeClass("chev_down");
            } else {
                $( this ).addClass("chev_down");
                $( this ).removeClass("chev_up");
            }
        });
    });

    $('.event_slider_show').slick({
        slidesToShow: 1,
        slidesToScroll: 1,
        arrows: false,
        fade: true,
        asNavFor: '.event_slider_mini'
    });
    $('.event_slider_mini').slick({
        slidesToShow: 3,
        slidesToScroll: 1,
        asNavFor: '.event_slider_show',
        infinite: true,
        arrows: true,
        dots: false,
        centerMode: true,
        focusOnSelect: true,
        responsive: [
            {
                breakpoint: 603,
                settings: {
                    slidesToShow: 3,
                    slidesToScroll: 1,
                    infinite: true,
                    arrows: false
                }
            },
            {
                breakpoint: 525,
                settings: {
                    slidesToShow: 2,
                    slidesToScroll: 1,
                    infinite: true,
                    arrows: false
                }
            },
            {
                breakpoint: 470,
                settings: {
                    slidesToShow: 1,
                    slidesToScroll: 1,
                    infinite: false,
                    arrows: false,
                    centerMode: false
                }
            }
        ]
    });

});
