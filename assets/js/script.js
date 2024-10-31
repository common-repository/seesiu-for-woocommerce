jQuery(function($){	
	$('form.checkout').on('change', 'input[name=installment_fee]', function(){
		var $that = $(this),
			is_checked = 0,
			price = $that.parent().parent().parent().parent().attr('data-price'),
			data = { 'price' : price };
		
		if( $that.prop('checked') == true ){
			is_checked = 1;
		}
		
		data.is_checked = is_checked;
		
		Site.seesiu.update_price( data, function( response ){
			if( response.error ){
				alert('Something went wrong.');
			}else{
				$( 'body' ).trigger( 'update_checkout' );
			}
		});
	});
});
var Site = {
	ajax: function( action, data, callback ){
		jQuery.post(
			seesiu.ajaxurl,{
				action : 'gmAjax',
				gmAction : action,
				data: JSON.stringify(data)			
			},
			function(res){
				callback(eval('(' +res+ ')'));			
			}
		);
	},
	seesiu:{
		update_price: function( data, callback ){	
			Site.ajax( 'UpdatePrice', data, function( response ){
				callback( response );
			});
		},
	},
	input : {
		serializeToObject: function( serialize ){
			var fields = serialize.split("&"),
				object = {};
			for(var i in fields){
				var field = fields[i].split("=");
				field[0] = field[0].replace("%5B%5D","");
				if ( ! object[field[0]] ){
					object[field[0]] = decodeURIComponent(field[1]);
				}else{
					if ( object[field[0]].constructor === Array ){
						object[field[0]].push(field[1])
					}else{
						object[field[0]] = [object[field[0]],field[1] ];
					}
				}
				//alert(decodeURIComponent(field[1]));
			}
			return object;
		}
	},
	
	form : {		
		data: {
			use_parent:false,
		},
		trim: function(s){
			return s.replace(/^\s+|\s+$/, '');
		},		
		validateEmpty: function (field,msg) {
			var error = "",
				fld=document.getElementById(field);
			if (fld.value.length == 0 ) {
				error = msg;
				this.display_msg(field,msg);
			} else {
				this.clr_msg(field,msg);
			}
			return error;  
		},
		
		validatePhone: function (field,msg) {
			var error = "",
				fld=document.getElementById(field),
				matcher= /^\(?([0-9]{3})\)?[-. ]?([0-9]{3})[-. ]?([0-9]{4})$/;
			if (fld.value.length == 0 ) {
				this.clr_msg(field,msg);
			} else {
				if(!fld.value.match(matcher)) {
					error = msg;
					this.display_msg(field,msg);
				} else {
					this.clr_msg(field,msg);
				}
			}
			return error;  
		},
		
		validateEmail: function (field,msg) {
			var error="",
				fld = document.getElementById(field),
				tfld = this.trim(fld.value),
				emailFilter = /^[^@]+@[^@.]+\.[^@]*\w\w$/ ,
				illegalChars= /[\(\)\<\>\,\;\:\\\"\[\]]/ ;
			if (!emailFilter.test(tfld)) {              //test email for illegal characters
				error = msg;
				this.display_msg(field,error);
			} else if (fld.value.match(illegalChars)) {
				error = msg;
				this.display_msg(field,error);
			} else{       
				this.clr_msg(field,msg);
			}
			return error;
		},
		
		validate_num: function ( field, msg ) {
			var error = "",
				fld=document.getElementById(field),
				stripped = fld.value.replace(/[\(\)\.\-\ ]/g, '');
										
		   	if (isNaN(parseInt(stripped))){
				error = msg;
				this.display_msg(field,error);
			}else {        
				this.clr_msg(field,msg);
			}
			return error;
		},
		
		con_pass_check: function ( field, field1, msg){
			var error = "",
				fld=document.getElementById(field),
				fld1=document.getElementById(field1);			
		   	if (fld.value.length==0 || fld1.value.length==0 || fld.value!=fld1.value  ) {
				error = msg;
				this.display_msg(field,error);
			}else {       
				this.clr_msg(field,msg);
			}
			return error;
		},
		
		validate_fixedLength: function ( field, len, msg ) {
			var error = ""; 
			var fld = document.getElementById( field );
			var stripped = fld.value.replace(/[\(\)\.\-\ ]/g, '');    
			var len1 = parseInt( len );
			if (!( stripped.length == len1 ) ) {
				error = msg;
				this.display_msg( field, error );
			}else {
				this.clr_msg( field, msg );
			}
			return error;
		},
		
		is_checked: function(field,msg){
			var error = "";
			if(!jQuery('#'+field).is(":checked")){
				error = msg;
				this.display_msg(field,error);
			}else {
				this.clr_msg(field,msg);
			}
			return error;
		},
		
		display_msg: function (field,msg){
			var divname = field+"_error";
			if(this.data.use_parent){
				jQuery('#'+field).parent().addClass('error_input');
			}else{
				jQuery('#'+field).addClass('error_input');
			}
			jQuery('#'+divname).addClass('row_error').html(msg);
		},
				
		clr_msg: function (field,msg){
			var divname=field+"_error",
				div_text=jQuery('#'+divname).html();
			if( msg == div_text ){
				jQuery('#'+divname).removeClass('row_error').html('');
				if(this.data.use_parent){
					jQuery('#'+field).parent().removeClass('error_input');
				}else{
					jQuery('#'+field).removeClass('error_input');
				}
			}			
		}
	}
};