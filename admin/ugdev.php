<?php

function ug_save_image($img,$fullpath){
	$img = str_replace(' ', '%20',$img);
	$ch = curl_init ($img);
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	$rawdata=curl_exec($ch);
	curl_close ($ch);
	if(file_exists($fullpath)){
		unlink($fullpath);
	}
	$fp = fopen($fullpath,'x');
	fwrite($fp, $rawdata);
	fclose($fp);
}

function ug_delete_attachments($post_id){
	$args = array('post_parent' => $post_id,
				'post_status' => 'inherit',
				'post_type'=> 'attachment',
				'post_mime_type' => 'image/jpeg,image/gif,image/jpg,image/png'
	);
	$old_attachs = new WP_Query($args);
	if ($old_attachs->have_posts()){
		while ( $old_attachs->have_posts() ) {
			$old_attachs->the_post();
			wp_delete_attachment(get_the_ID(), true);
		}
	}
	wp_reset_postdata($old_attachs);
}

function ug_image_featured($url_image, $post_id){
	//Change spaces by %20, in order to avoid error 400
	$url_image = str_replace(' ', '%20',$url_image);
	$attach = wc_rest_upload_image_from_url( $url_image ); // return file, url, type

	if (is_wp_error($attach)){
		error_log ('ERROR: ' . $attach->get_error_message());
		return;
	}
	// Prepare an array of post data for the attachment.
	$attachment = array(
		'guid'           => $attach['url'], 
		'post_mime_type' => $attach['type'],
		'post_title'     => preg_replace( '/\.[^.]+$/', '', basename( $attach['file'] ) ),
		'post_content'   => '',
		'post_status'    => 'inherit'
	);

	
	// Insert the attachment.
	$attach_id = wp_insert_attachment($attachment, $attach['file'], $post_id);

	// Make sure that this file is included, as wp_generate_attachment_metadata() depends on it.
	require_once( ABSPATH . 'wp-admin/includes/image.php' );
	// Generate the metadata for the attachment, and update the database record.
	$attach_data = wp_generate_attachment_metadata( $attach_id, $attach['file'] );
	wp_update_attachment_metadata( $attach_id, $attach_data );
	set_post_thumbnail ($post_id, $attach_id);
}

function ug_image_gallery($url_image, $post_id){
	//Change spaces by %20, in order to avoid error 400
	$url_image = str_replace(' ', '%20',$url_image);
	// Upload file into uploads/year/month
	$attach = wc_rest_upload_image_from_url( $url_image ); // return file, url, type

	if (is_a($attach, 'WP_Error')){return;}
	// Prepare an array of post data for the attachment.
	$attachment = array(
		'guid'           => $attach['url'], 
		'post_mime_type' => $attach['type'],
		'post_title'     => preg_replace( '/\.[^.]+$/', '', basename( $attach['file'] ) ),
		'post_content'   => '',
		'post_status'    => 'inherit'
	);

	// Insert the attachment.
	$attach_id = wp_insert_attachment($attachment, $attach['file'], $post_id);
	// Make sure that this file is included, as wp_generate_attachment_metadata() depends on it.
	require_once( ABSPATH . 'wp-admin/includes/image.php' );
	// Generate the metadata for the attachment, and update the database record.
	$attach_data = wp_generate_attachment_metadata( $attach_id, $attach['file'] );
	wp_update_attachment_metadata( $attach_id, $attach_data );
	$s = get_post_meta($post_id, "_product_image_gallery", true);
	if ($s===''){
		$s = $attach_id;
	}else{
		$s = $s.','.$attach_id;
	}
	update_post_meta($post_id,"_product_image_gallery",$s);
}

function ug_create_product($producto){
	if (!(is_plugin_active('woocommerce/woocommerce.php')|| is_plugin_active_for_network('woocommerce/woocommerce.php'))){
		//echo "Woocommerce no está instalado y activado.";
		return;
	}
	error_log("Entrando a creat_product con:" . print_r($producto,true));
	$prod_is_new = false;
	$args = array( 'post_type' => 'product', 'posts_per_page' => 1, 'meta_key'=>'_IdProducto', 'meta_value' => $producto->IdProducto);
	$loop = new WP_Query( $args );
	if ($loop->have_posts()){
		$post = $loop->post;
		$post_id = $post->ID;
		error_log ("encontré un registrto ($post_id) para el producto ". $producto->IdProducto);
		wp_reset_postdata();
	}else {
		$post = array(
			'post_author' => get_current_user_id(),
			'post_content' => $producto->Descripcion,
			'post_status' => "publish",
			'post_title' => $producto->Nombre,
			'post_parent' => '',
			'post_type' => "product",
		);

		$prod_is_new = true;
		//Create post
		$post_id = wp_insert_post( $post, true );
		error_log("Se creó un registro $post_id");
	}
	
	if(!is_wp_error($post_id)){
		error_log("actualizando matadata...");
		//$attach_id = get_post_meta($product->parent_id, "_thumbnail_id", true);
		//add_post_meta($post_id, '_thumbnail_id', $attach_id);
	
		//wp_set_object_terms( $post_id, 'Races', 'product_cat' );
		wp_set_object_terms( $post_id, 'simple', 'product_type');
		
		if ($producto->Existensia > 0){
			update_post_meta( $post_id, '_stock_status', 'instock');
		}else{
			update_post_meta( $post_id, '_stock_status', 'outofstock');
		}
		update_post_meta( $post_id, '_stock', $producto->Existensia );
		if($producto->Descontinuado ==1){
			error_log('Item descontinuado: ' . $producto->Descontinuado);
			$my_prod = wc_get_product($post_id);
			$my_prod->set_catalog_visibility('hidden');
			update_post_meta( $post_id, '_stock_status', 'outofstock');
		}else{
			$my_prod = wc_get_product($post_id);
			$my_prod->set_catalog_visibility('visible');
		}
		
		update_post_meta ($post_id, '_IdProducto', $producto->IdProducto);
		if($prod_is_new){
			update_post_meta( $post_id, 'total_sales', '0');
		}


		$opt = get_option('smu_config_options');
		if (isset($opt['smu_disable_description'])){
			$my_prod->set_description($producto->Descripcion); //Set product description.	
			error_log("Descarga de Descripción deshabilitada");
		}else{
			error_log("Descarga de Descripción habilitada");
		}
		update_post_meta( $post_id, '_downloadable', 'no');
		update_post_meta( $post_id, '_virtual', 'no');
		
		update_post_meta( $post_id, '_price', $producto->PrecioLista);
		update_post_meta( $post_id, '_regular_price', $producto->PrecioLista);
		update_post_meta( $post_id, '_sale_price', $producto->Precio);
		update_post_meta( $post_id, '_purchase_note', "");
		update_post_meta( $post_id, '_featured', "no" );
		update_post_meta( $post_id, '_weight', $producto->Peso);
		update_post_meta( $post_id, '_length', $producto->Largo);
		update_post_meta( $post_id, '_width', $producto->Ancho);
		update_post_meta( $post_id, '_height', $producto->Alto);
		update_post_meta( $post_id, '_sku', $producto->IdProducto);
		//update_post_meta( $post_id, '_product_attributes', array());
		update_post_meta( $post_id, '_sale_price_dates_from', "" );
		update_post_meta( $post_id, '_sale_price_dates_to', "" );
		update_post_meta( $post_id, '_sold_individually', "" );
		//woocommerce_manage_stock is sync with plugin settings page
		if ( 'yes' === get_option( 'woocommerce_manage_stock' ) ) {
			update_post_meta( $post_id, '_manage_stock', "yes" );
		} else {
			update_post_meta( $post_id, '_manage_stock', "no" );
		}

		update_post_meta( $post_id, '_backorders', "no" );

		$opt = get_option('smu_config_options');
		if (isset($opt['smu_disable_images'])){
			$my_prod->set_description($producto->Descripcion); //Set product description.
			error_log("Descarga de Images deshabilitada");
		}else{
			update_post_meta( $post_id, '_product_image_gallery', '');
			$c = count($producto->RutaImagenes);
			error_log("$c images to be process...");
			if ($c>0){
				// Delete all attachments
				ug_delete_attachments($post_id);
				// The first image is added as featured image
				ug_image_featured($producto->RutaImagenes[0], $post_id);
				// Other images are added as gallery images
				if ($c>1){
					//delete all galery images
					delete_post_meta ($post_id, '_product_image_gallery');
					for ($i=1;$i<$c;$i++){
						error_log("ciclo $i adding ".$producto->RutaImagenes[$i]." <br>");
						ug_image_gallery($producto->RutaImagenes[$i], $post_id);
					}
				}
			}
			error_log("Descarga de Images habilitada");
		}
		

	}
}