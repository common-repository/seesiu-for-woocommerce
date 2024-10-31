<?php
if( isset( $_POST['save_activation'] ) ){
	$key	= sanitize_text_field( $_POST['activation_key'] );
	$shop	= ( $_SERVER['HTTP_HOST'] == 'localhost' ) ? 'localhost.com' : $_SERVER['HTTP_HOST'];

	update_option( 'label_text', sanitize_text_field( $_POST['label_text'] ), true );
	
	if( sefw_is_valid_key( $key, $shop ) == 'success' ){
		update_option( 'activation_key', $key, true );
		$msg = '<span style="color:#093;">'.sefw_is_valid_key( $key, $shop ).'</span>';
	}else{
		$msg = '<span style="color:#F00;">'.sefw_is_valid_key( $key, $shop ).'</span>';
	}
}
$activation_key	= stripslashes( get_option( 'activation_key' ) );
$label_text		= stripslashes( get_option( 'label_text' ) );
?>
<div class="wrap nosubsub">
	<h2><?php esc_html_e( 'Settings', 'seesiu' ); ?></h2>
	<?php echo $msg; ?>
    <div id="col-container">
	<form action="" method="post" enctype="multipart/form-data">		 
		<table  border="0" cellspacing="0" cellpadding="0" class="wp-list-table widefat fixed users">
			<thead>
				 <th width="145"><span><b><?php esc_html_e( 'Plugin Activation', 'seesiu' ); ?></b></span></th><th></th>
			</thead>
			<tr>
				<td><b><?php esc_html_e( 'Activation Key:', 'seesiu' ); ?></b></td>
				<td><input name="activation_key" id="activation_key" value="<?php esc_attr_e( $activation_key ); ?>" type="text" style="width:500px;"></td>
			</tr>
            <tr>
				<td><b><?php esc_html_e( 'Label Text:', 'seesiu' ); ?></b></td>
				<td><input name="label_text" id="label_text" value="<?php esc_attr_e( $label_text ); ?>" type="text" style="width:500px;"></td>
			</tr>
			<tr>
				<td colspan="2" >
					<input type="submit" name="save_activation" id="save_activation" class="button" value="<?php esc_attr_e( 'Save' ); ?>">
				</td>
			</tr> 
		</table>
	</form>
	</div>
</div>