
jQuery(function($){
            $('#_booking_event').on('change', function(event){
               // event.preventDefault();
                $('#booking-cal-block').data('event', $(this).val());
                get_hidden_inputs();
                get_booking_calendar();
              //  get_amount_booking();
           });
           
           
function get_booking_calendar(){
        var event = $('#_booking_event').val();
        var event_date = '';
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
}

function get_hidden_inputs(){
    var event = $('#_booking_event').val();
    $.ajax({
		url : lst.ajax_url,
		type : 'POST',
		data : {
			action : 'get_hidden_inputs',
            event_id : event,
            // check
	        nonce : lst.nonce
		},
		success : function( msg ) {
		    $('#pre_booking').html(msg);
		  }
        });
}          
           

});

////////////////////////////////////

function alertObj(obj) { 
    var str = ""; 
    for(k in obj) { 
        str += k+": "+ obj[k]+"\r\n"; 
    } 
    alert(str); 
}

function get_amount_booking(){

                var event = jQuery("#_booking_event").val();
              //  event.preventDefault();
                var event_date = jQuery("#_booking_event_date").val();
                var event_guests = jQuery("#_booking_event_guests").val();

                var services = new Object();
                var services2 = jQuery("input[name='_booking_services[]']:checked").map(function(){
                     var key = jQuery(this).val();
                     services[key] = 1;
                     return key;
                  }).get();

              jQuery('#spin_amount').html('<span class="spin_f"><i class="fa fa-spinner fa-pulse fa-3x fa-fw"></i></span>');
                if (event != ''){
                 jQuery.ajax({
		url : lst.ajax_url,
		type : 'POST',
		data : {
			action : 'get_amount_booking',
            event_id : event,
            event_date : event_date,
            event_guests : event_guests,
            // check
	        nonce : lst.nonce
		},
		success : function( msg ) {
		   jQuery('#spin_amount').html('');
           try {
			var response = JSON.parse( msg );
		    } catch ( e ) {
			  return false;
		    }
           jQuery("#_booking_price").val(response.total);
           jQuery("#_booking_price_tax").val(response.total_tax);
           jQuery("#_booking_price_clear").val(response.total_clear);
		  }
        });
                } else
                jQuery('#spin_amount').html('');

}

////////////////////////

jQuery(function($){
    
    ////// lock edit post status
    $('.post-type-booking #misc-publishing-actions .misc-pub-post-status').html('');
    $('.post-type-booking #misc-publishing-actions .misc-pub-curtime').html('');
    $('.post-type-booking #preview-action').html('');
    $('.post-type-booking #save-action').html('');
    
    ////// lock publish button
    $('#booking_metabox select#_booking_event').each(function(){
        $('#publish.button').css('display', 'none');
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
        ////// unlock publish button
        var guests_adult = 0;
        $('#select_price_block').find('select.as-adult').each(function(){
            guests_adult = guests_adult + parseInt($(this).val());
        });
        if(guests_adult > 0) {
            $('#publish.button').css('display', 'block');
        } else {
            $('#publish.button').css('display', 'none');
        }    
          
    });
    
    ///////////////////////     
    
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
            $('#go_to_booking').remove();
		  }
        });
        
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
            with_links : 0,
            // check
	        nonce : lst.nonce
		},
		success : function( msg ) {
		    jQuery('#main-cal-body').html(msg);
		  }
        });     
}

//////////////////////////

jQuery(document).ready(function($){
    
    get_event_cal();
    
    $('.cmb2-id--event-age-empty').each(function(){
        $('#publish.button').css('display', 'none');
    });
    
    ////////////////////////
    
    $('#exclude_date').click(function(event){
                var date = $('#_event_date_ex').val();
                if (date != ''){
                   $('#excluded_dates').prepend('<div>'+ date + '<input type="hidden" name="_event_excluded_dates[]" value="'+date+'"></div>');  
                }
            });
            
    $('.del-ex-date').click(function(event){
               $(this).parent().remove();
    });
    
});

/////////////////////////////
////////////////////////

jQuery(function($){

            $('.booking_page_customers table.customers').on('click', '[id^="edit-notes-button"]', function(event){
            var user_id = $(this).attr('data-u');
            var td = $(this).parent();
            $(this).detach();
            var text = td.text();
            td.html('<textarea id="'+user_id+'" cols=17 rows=5>'+text+'</textarea><button id="save-notes-button'+user_id+'" data-u="'+user_id+'" type="button" title="Save">Save notes</button>');
           });

           $('.booking_page_customers table.customers').on('click', '[id^="save-notes-button"]', function(event){
              var user_id = $(this).attr('data-u');
              var text = $('textarea#'+user_id).val();
              var td = $(this).parent();
            //  $(this).detach();
              td.html('<span class="spin_f"><i class="fa fa-spinner fa-pulse fa-3x fa-fw"></i></span>');

              jQuery.ajax({
		url : lst.ajax_url,
		type : 'POST',
		data : {
			action : 'save_admin_notes',
            user_id : user_id,
            text : text,
            // check
	        nonce : lst.nonce
		},
		success : function( msg ) {
		   td.html(msg+'<button id="edit-notes-button'+user_id+'" data-u="'+user_id+'" type="button" title="Edit">Edit notes</button>');
		  }
        });

           });
   });
