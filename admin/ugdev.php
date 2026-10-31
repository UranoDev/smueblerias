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

// Id del producto de Woo que corresponde a un IdProducto del ERP, en cualquier estado salvo la papelera
function ug_find_product_id($id_producto){
	$ids = get_posts(array(
		'post_type' => 'product',
		'post_status' => 'any',
		'meta_key' => '_IdProducto',
		'meta_value' => $id_producto,
		'posts_per_page' => 1,
		'fields' => 'ids',
	));
	return $ids ? (int) $ids[0] : 0;
}

// Huella de una imagen remota: ETag, Last-Modified y tamaño, que el servidor da sin enviar la imagen.
// Devuelve false si no se pudo consultar y '' si el servidor no manda ninguno de esos datos.
function ug_image_fingerprint($url_image){
	$response = wp_remote_head(str_replace(' ', '%20', $url_image), array('timeout' => 20, 'redirection' => 3));
	if (is_wp_error($response) || 200 !== (int) wp_remote_retrieve_response_code($response)){
		return false;
	}
	$parts = array();
	foreach (array('etag', 'last-modified', 'content-length') as $header){
		$value = wp_remote_retrieve_header($response, $header);
		$parts[] = is_array($value) ? (string) end($value) : (string) $value;
	}
	return implode('', $parts) === '' ? '' : implode('|', $parts);
}

// Baja una imagen del ERP, la agrega a la biblioteca de medios y guarda de dónde vino y su huella.
// Devuelve el id del adjunto, o 0 si no se pudo.
function ug_create_image_attachment($url_image, $post_id, $fingerprint){
	//Change spaces by %20, in order to avoid error 400
	$attach = wc_rest_upload_image_from_url(str_replace(' ', '%20', $url_image)); // return file, url, type
	if (is_wp_error($attach)){
		error_log('ERROR: ' . $attach->get_error_message());
		return 0;
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
	if (!$attach_id || is_wp_error($attach_id)){
		return 0;
	}
	// Make sure that this file is included, as wp_generate_attachment_metadata() depends on it.
	require_once( ABSPATH . 'wp-admin/includes/image.php' );
	// Generate the metadata for the attachment, and update the database record.
	wp_update_attachment_metadata($attach_id, wp_generate_attachment_metadata($attach_id, $attach['file']));
	update_post_meta($attach_id, '_smu_source_url', $url_image);
	update_post_meta($attach_id, '_smu_fingerprint', (string) $fingerprint);
	return (int) $attach_id;
}

// Deja las imágenes del producto igual a la lista del ERP: baja las nuevas o modificadas y borra las que ya no están.
// La primera imagen es la destacada y las demás la galería. Solo toca las imágenes que puso este plugin
// (las que traen _smu_source_url) y las agregadas a mano se conservan.
// Devuelve cuántas imágenes se descargaron, se borraron, no cambiaron o fallaron.
function ug_sync_product_images($post_id, $urls){
	$result = array('descargadas' => 0, 'borradas' => 0, 'sin cambios' => 0, 'fallidas' => 0);
	$urls = array_values(array_unique(array_filter(array_map('trim', (array) $urls))));
	if (empty($urls)){
		//El ERP no mandó imágenes: no se toca nada
		return $result;
	}

	//Imágenes que ya puso el plugin en este producto: url => id de adjunto
	$managed = array();
	$duplicates = array();
	$attachments = get_posts(array(
		'post_type' => 'attachment',
		'post_status' => 'inherit',
		'post_parent' => $post_id,
		'posts_per_page' => -1,
		'fields' => 'ids',
		'orderby' => 'ID',
		'order' => 'ASC',
		'meta_key' => '_smu_source_url',
	));
	foreach ($attachments as $id){
		$source = get_post_meta($id, '_smu_source_url', true);
		if (isset($managed[$source])){
			$duplicates[] = $id;
		}else{
			$managed[$source] = $id;
		}
	}

	$ids = array();       //adjuntos que debe tener el producto, en el orden del ERP
	$replaced = array();  //adjuntos que se reemplazaron por una versión nueva
	foreach ($urls as $url){
		$fingerprint = ug_image_fingerprint($url);
		if (isset($managed[$url])){
			$id = $managed[$url];
			$stored = (string) get_post_meta($id, '_smu_fingerprint', true);
			if (false === $fingerprint || '' === $fingerprint || $fingerprint === $stored){
				//Sin poder compararla, o igual a la que ya se tiene: se conserva
				$result['sin cambios']++;
				$ids[] = $id;
			}elseif ('' === $stored){
				//Se bajó cuando el servidor no daba huella: se anota la de ahora y se conserva
				update_post_meta($id, '_smu_fingerprint', $fingerprint);
				$result['sin cambios']++;
				$ids[] = $id;
			}else{
				//La imagen cambió en el ERP: se baja la nueva y la vieja se borra después
				$new_id = ug_create_image_attachment($url, $post_id, $fingerprint);
				if ($new_id){
					$ids[] = $new_id;
					$replaced[] = $id;
					$result['descargadas']++;
				}else{
					$ids[] = $id;
					$result['fallidas']++;
				}
			}
		}else{
			$new_id = ug_create_image_attachment($url, $post_id, false === $fingerprint ? '' : $fingerprint);
			if ($new_id){
				$ids[] = $new_id;
				$result['descargadas']++;
			}else{
				$result['fallidas']++;
			}
		}
	}

	if (empty($ids)){
		//Ninguna imagen quedó disponible: no se borra lo que había
		return $result;
	}

	set_post_thumbnail($post_id, $ids[0]);
	if (count($ids) > 1){
		update_post_meta($post_id, '_product_image_gallery', implode(',', array_slice($ids, 1)));
	}else{
		delete_post_meta($post_id, '_product_image_gallery');
	}

	//Se borran hasta que las nuevas ya están asignadas
	$obsolete = array_merge($duplicates, $replaced);
	foreach ($managed as $source => $id){
		if (!in_array($source, $urls, true)){
			$obsolete[] = $id;
		}
	}
	foreach ($obsolete as $id){
		wp_delete_attachment($id, true);
		$result['borradas']++;
	}

	//Las imágenes que puso una versión anterior del plugin no traen su origen: se reemplazan una vez
	if (!get_post_meta($post_id, '_smu_images_synced', true) && 0 === $result['fallidas']){
		$legacy = get_posts(array(
			'post_type' => 'attachment',
			'post_status' => 'inherit',
			'post_parent' => $post_id,
			'post_mime_type' => 'image',
			'posts_per_page' => -1,
			'fields' => 'ids',
			'meta_query' => array(array('key' => '_smu_source_url', 'compare' => 'NOT EXISTS')),
		));
		foreach ($legacy as $id){
			wp_delete_attachment($id, true);
			$result['borradas']++;
		}
		update_post_meta($post_id, '_smu_images_synced', 1);
	}
	return $result;
}
//Busca la categoría de Woo por el IdCategoria del ERP; si no existe la crea con el id como nombre
// Nombres de las categorías del ERP (id => nombre), de ObtenerCategoriasMenus. Se consulta una vez por corrida;
// si el ERP no responde queda vacío y las categorías se siguen nombrando con su id.
function ug_get_erp_category_names($refresh = false){
	static $names = null;
	if (null !== $names && !$refresh){
		return $names;
	}
	$names = array();
	$options = get_option('smu_config_options');
	if (!is_array($options) || empty($options['smu_url'])){
		return $names;
	}
	$service = 'ObtenerCategoriasMenus';
	$url = trailingslashit($options['smu_url']) . $service . '?claveServicio=' . $options['smu_clave_servicio'] .
		'&idEmpresa=' . $options['smu_empresa'] . '&idUsuario=' . $options['smu_usuario'];
	$response = wp_remote_get($url, array('timeout' => 30));
	if (is_wp_error($response) || 200 !== (int) wp_remote_retrieve_response_code($response)){
		error_log(ug_mask_erp_data("ERROR ERP $service: no se pudieron leer los nombres de categoría. url: $url"));
		return $names;
	}
	$list = json_decode(wp_remote_retrieve_body($response));
	if (!is_array($list)){
		error_log(ug_mask_erp_data("ERROR ERP $service: la respuesta no es una lista. url: $url"));
		return $names;
	}
	foreach ($list as $category){
		if (isset($category->ID, $category->Nombre) && '' !== trim((string) $category->Nombre)){
			$names[(string) $category->ID] = trim((string) $category->Nombre);
		}
	}
	return $names;
}

// Categoría de Woo que corresponde a un IdCategoria del ERP; la crea con el nombre del ERP si no existe.
// Una categoría que aún lleva su id como nombre, o el último nombre que puso el plugin, se renombra cuando el ERP
// cambia el nombre; si alguien la renombró a mano, se respeta.
function ug_get_erp_category($id_categoria){
	global $wpdb;
	$names = ug_get_erp_category_names();
	$name = $names[(string) $id_categoria] ?? (string) $id_categoria;
	$term_id = (int) $wpdb->get_var($wpdb->prepare("SELECT term_id FROM $wpdb->termmeta WHERE meta_key='_erp_id_categoria' AND meta_value=%s LIMIT 1", $id_categoria));
	if ($term_id){
		$term = get_term($term_id, 'product_cat');
		if (isset($names[(string) $id_categoria]) && $term && !is_wp_error($term) && $term->name !== $name){
			$last = (string) get_term_meta($term_id, '_erp_nombre_categoria', true);
			if ($term->name === (string) $id_categoria || ('' !== $last && $term->name === $last)){
				$args = array('name' => $name);
				if ($term->slug === sanitize_title((string) $id_categoria)){
					$args['slug'] = sanitize_title($name);
				}
				$updated = wp_update_term($term_id, 'product_cat', $args);
				if (is_wp_error($updated)){
					//El nombre o el slug ya los tiene otra categoría: se deja como está
					error_log("No pude renombrar la categoría $term_id a $name: " . $updated->get_error_message());
				}else{
					update_term_meta($term_id, '_erp_nombre_categoria', $name);
				}
			}
		}
		return $term_id;
	}
	$term = wp_insert_term($name, 'product_cat');
	if (is_wp_error($term)){
		$term_id = (int) $term->get_error_data('term_exists');
		if (!$term_id){
			error_log('ERROR categoria ' . $id_categoria . ': ' . $term->get_error_message());
			return 0;
		}
	}else{
		$term_id = (int) $term['term_id'];
	}
	update_term_meta($term_id, '_erp_id_categoria', $id_categoria);
	update_term_meta($term_id, '_erp_nombre_categoria', $name);
	return $term_id;
}
function ug_set_erp_category($my_prod, $id_categoria){
	if (empty($id_categoria)){
		return;
	}
	$term_id = ug_get_erp_category($id_categoria);
	if (!$term_id){
		return;
	}
	$default = (int) get_option('default_product_cat');
	$categories = array($term_id);
	foreach ($my_prod->get_category_ids() as $category){
		if ($category != $default && '' === get_term_meta($category, '_erp_id_categoria', true)){
			$categories[] = $category;
		}
	}
	$my_prod->set_category_ids($categories);
}

// Opción de la configuración "Deshabilitar la carga de Imágenes"
function ug_images_disabled(){
	return ug_option_is_yes('smu_disable_images');
}

// Una casilla de la configuración está marcada solo cuando vale 'yes' (desmarcada no se guarda o vale 'no')
function ug_option_is_yes($key){
	$opt = get_option('smu_config_options');
	return is_array($opt) && isset($opt[$key]) && 'yes' === $opt[$key];
}

// Candado para que no corran a la vez dos cargas de la cola. MySQL lo libera solo si el proceso termina.
// Devuelve false si otra carga ya lo tiene.
function ug_acquire_queue_lock(){
	global $wpdb;
	return '1' === (string) $wpdb->get_var("SELECT GET_LOCK('smu_queue_lock', 0)");
}

// Crea o actualiza el producto de Woo que corresponde al producto del ERP.
// Devuelve true si quedó guardado; false si no se pudo (el registro de la cola debe seguir pendiente).
function ug_create_product($producto){
	$post_id = ug_find_product_id($producto->IdProducto);
	if ($post_id){
		error_log ("encontré un registrto ($post_id) para el producto ". $producto->IdProducto);
	}else {
		$post = array(
			'post_author' => get_current_user_id(),
			'post_content' => $producto->Descripcion,
			'post_status' => "publish",
			'post_title' => $producto->Nombre,
			'post_parent' => '',
			'post_type' => "product",
		);
		//Create post
		$post_id = wp_insert_post( $post, true );
		if (is_wp_error($post_id)){
			error_log("ERROR al crear el producto {$producto->IdProducto}: " . $post_id->get_error_message());
			return false;
		}
		error_log("Se creó un registro $post_id");
	}

	$my_prod = wc_get_product($post_id);
	if (!$my_prod){
		error_log("ERROR no pude cargar el producto $post_id");
		return false;
	}
	error_log("actualizando matadata...");
	wp_set_object_terms( $post_id, 'simple', 'product_type');
	$my_prod = wc_get_product($post_id);

	$descontinuado = (1 == $producto->Descontinuado);
	if ($descontinuado){
		error_log('Item descontinuado: ' . $producto->Descontinuado);
	}
	$my_prod->set_catalog_visibility($descontinuado ? 'hidden' : 'visible');
	$my_prod->delete_meta_data('_smu_missing_since');
	ug_set_erp_category($my_prod, $producto->IdCategoria);
	$my_prod->set_regular_price($producto->PrecioLista);
	$my_prod->set_sale_price($producto->Precio > 0 && $producto->Precio < $producto->PrecioLista ? $producto->Precio : '');

	//Existencias: woocommerce_manage_stock is sync with plugin settings page.
	//Un descontinuado no lleva control de existencias para que Woo no lo vuelva a marcar disponible.
	if (!$descontinuado && 'yes' === get_option('woocommerce_manage_stock')){
		$my_prod->set_manage_stock(true);
		$my_prod->set_stock_quantity($producto->Existensia);
	}else{
		$my_prod->set_manage_stock(false);
		$my_prod->set_stock_status(!$descontinuado && $producto->Existensia > 0 ? 'instock' : 'outofstock');
	}
	$my_prod->set_backorders('no');

	$my_prod->set_downloadable(false);
	$my_prod->set_virtual(false);
	$my_prod->set_purchase_note('');
	$my_prod->set_featured(false);
	//Opción "Deshabilitar la carga de Medidas"
	if (!ug_option_is_yes('smu_disable_medidas')){
		$my_prod->set_weight($producto->Peso);
		$my_prod->set_length($producto->Largo);
		$my_prod->set_width($producto->Ancho);
		$my_prod->set_height($producto->Alto);
	}
	//Opción "Deshabilitar la carga de Descripción": con ella marcada el nombre y la descripción de un producto existente no se tocan
	if (!ug_option_is_yes('smu_disable_description')){
		$my_prod->set_name($producto->Nombre);
		$my_prod->set_description($producto->Descripcion);
		//La cola guarda hasta 6,400 caracteres: una descripción de ese largo llegó recortada
		if (mb_strlen((string) $producto->Descripcion) >= 6400){
			$my_prod->update_meta_data('_smu_description_truncated', current_time('Y-m-d'));
		}else{
			$my_prod->delete_meta_data('_smu_description_truncated');
		}
	}
	$my_prod->set_date_on_sale_from('');
	$my_prod->set_date_on_sale_to('');
	$my_prod->set_sold_individually(false);
	$my_prod->update_meta_data('_IdProducto', $producto->IdProducto);
	try {
		$my_prod->set_sku((string) $producto->IdProducto);
	} catch (WC_Data_Exception $e){
		error_log("ERROR SKU del producto {$producto->IdProducto}: " . $e->getMessage());
		return false;
	}
	$my_prod->save();

	if (ug_images_disabled()){
		return true;
	}
	$images = ug_sync_product_images($post_id, $producto->RutaImagenes);
	if ($images['fallidas']){
		error_log("Producto {$producto->IdProducto}: {$images['fallidas']} imágenes no se pudieron descargar; se reintentan con 'cron_process_queue.php imagenes'");
	}
	return true;
}
