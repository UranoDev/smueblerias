<?php
date_default_timezone_set('America/Mexico_City');
//este archivo irá en el directorio raiz de WP o se debe cambiar la ruta:
include_once "../../../wp-load.php";
include_once "../../../wp-admin/includes/plugin.php";
include_once "includes/smueblerias-log.php";
include_once "admin/ugdev.php";

//Los errores salen en pantalla solo con ?debug=1; siempre quedan en el log
ini_set('display_errors', ug_debug_enabled() ? 1 : 0);
ini_set('display_startup_errors', ug_debug_enabled() ? 1 : 0);
error_reporting(E_ALL);

// Main routine
global $nl;
(php_sapi_name() === 'cli')?$nl = "\n":$nl="<br>";
global $wpdb;

if (!ug_acquire_queue_lock()){
	echo "Ya hay otra carga de la cola en ejecución $nl";
	exit;
}

// Modo "imagenes" (php cron_process_queue.php imagenes): revisa las imágenes de todos los productos de la cola,
// no solo las de los pendientes. Detecta las imágenes que el ERP reemplazó con el mismo nombre.
$modo_imagenes = php_sapi_name() === 'cli' ? (isset($argv[1]) && $argv[1] === 'imagenes') : isset($_GET['imagenes']);
if ($modo_imagenes){
	if (ug_images_disabled()){
		echo "La carga de imágenes está deshabilitada en la configuración $nl";
		exit;
	}
	//Por URL se revisa un lote a la vez (?imagenes=1&desde=0&lote=100) para no pasar el tiempo límite del servidor
	$limit_sql = '';
	$total_cola = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}queue_products");
	if (php_sapi_name() !== 'cli'){
		$desde = isset($_GET['desde']) ? absint($_GET['desde']) : 0;
		$lote = isset($_GET['lote']) ? min(500, max(1, absint($_GET['lote']))) : 100;
		$limit_sql = " LIMIT $lote OFFSET $desde";
	}
	$rows = $wpdb->get_results("SELECT idProducto, rutaImagenes FROM {$wpdb->prefix}queue_products ORDER BY idProducto" . $limit_sql);
	$total = array('productos' => 0, 'sin producto en Woo' => 0, 'descargadas' => 0, 'borradas' => 0, 'sin cambios' => 0, 'fallidas' => 0);
	foreach ($rows as $row){
		$post_id = ug_find_product_id($row->idProducto);
		if (!$post_id){
			$total['sin producto en Woo']++;
			continue;
		}
		$total['productos']++;
		$result = ug_sync_product_images($post_id, explode(',', trim($row->rutaImagenes, ',')));
		foreach ($result as $k => $v){
			$total[$k] += $v;
		}
		if ($result['descargadas'] || $result['borradas'] || $result['fallidas']){
			echo "Producto {$row->idProducto}: {$result['descargadas']} descargadas, {$result['borradas']} borradas, {$result['fallidas']} fallidas $nl";
		}
	}
	if ($limit_sql !== '' && $desde + $lote < $total_cola){
		$siguiente = $desde + $lote;
		echo "Siguiente lote: ?imagenes=1&desde=$siguiente&lote=$lote $nl";
	}
	echo "Resumen imágenes: {$total['productos']} productos, {$total['descargadas']} descargadas, {$total['borradas']} borradas, {$total['sin cambios']} sin cambios, {$total['fallidas']} fallidas, {$total['sin producto en Woo']} sin producto en Woo $nl";
	exit;
}

//$table_name = $wpdb->prefix . "queue_products";
$sql = "SELECT * FROM {$wpdb->prefix}queue_products where procesado is null";
$a = $wpdb->get_results( $sql );
if (is_null($a)){
	echo "no hay registros para actualiziar $nl";
	exit;
}
$c = count($a);

echo "Número de registros leidos: $c $nl";
$i=0;
$errores=0;
foreach ($a as $p) {
	$producto = new stdClass();
	$producto->IdProducto = $p->idProducto;
	$producto->IdCategoria = $p->idCategoria;
	$producto->Descripcion = $p->descripcion;
	$producto->Nombre = $p->nombre;
	$producto->Existensia = $p->existencias;
	$producto->Descontinuado = $p->descontinuado;
	$producto->PrecioLista = $p->precioLista;
	$producto->Precio = $p->precio;
	$producto->Peso = $p->peso;
	$producto->Largo = $p->largo;
	$producto->Ancho = $p->ancho;
	$producto->Alto = $p->alto;
	$producto->RutaImagenes = explode(',', trim($p->rutaImagenes, ','));

	try {
		$ok = ug_create_product ($producto);
	} catch (Throwable $e){
		error_log("ERROR en el producto {$p->idProducto}: " . $e->getMessage());
		$ok = false;
	}
	if ($ok){
		ug_debug_echo("($i) $p->idProducto $nl");
		//Solo se marca procesado si el producto quedó guardado; si no, se reintenta en la siguiente corrida
		$done = $wpdb->query($wpdb->prepare("update {$wpdb->prefix}queue_products set procesado = 1 where id = %d", $p->id));
		if (false === $done){
			echo "no pude actualizar el registro {$p->id} $nl";
		}
	}else{
		$errores++;
		echo "($i) $p->idProducto ERROR: no se pudo guardar, se reintenta en la siguiente carga $nl";
	}
	$i++;
}
echo "Resumen: " . ($i - $errores) . " procesados, $errores con error $nl";
/**
 * fin de rutina principal
 */



function ug_get_product_by_sku( $sku ) {
	global $wpdb;
	$product_id = $wpdb->get_var( $wpdb->prepare( "SELECT post_id FROM $wpdb->postmeta WHERE meta_key='_sku' AND meta_value='%s' LIMIT 1", $sku ) );
	if ( $product_id ) return new WC_Product( $product_id );
	return null;
  }
/*  
function _descarta_ug_create_product ($info_producto){
	if (!(is_plugin_active('woocommerce/woocommerce.php')|| is_plugin_active_for_network('woocommerce/woocommerce.php'))){
		//echo "Woocommerce no está instalado y activado.";
		return;
	}

	//$args = array( 'post_type' => 'product', 'posts_per_page' => 1, 'meta_key'=>'_IdProducto', 'meta_value' => $info_producto->idProducto);

	//$loop = new WP_Query( $args );

	
	//seguro para solo procesar un producto
	if (($info_producto->idProducto == 1979663) or false){
		//var_dump($info_producto);
		//El producto existe?
		$wc_product = ug_get_product_by_sku ($info_producto->idProducto);
		if ($wc_product != null){
			//error_log ("El producto {$info_producto->idProducto} ya existe en la BD");
		}else { //el producto es nuevo
			$wc_product = new WC_Product ();
			error_log ("El producto {$info_producto->idProducto} es nuevo en la BD");
			$wc_product->set_sku("{$info_producto->idProducto}"); //can be blank in case you don't have sku, but You can't add duplicate sku's
		}
		$wc_product->set_name("{$info_producto->nombre}");
		$wc_product->set_status("publish");  // can be publish,draft or any wordpress post status
		
		$wc_product->set_description("{$info_producto->descripcion}");
		
		$wc_product->set_price($info_producto->precio); // set product price
		$wc_product->set_regular_price($info_producto->precioLista); // set product regular price
		
		$manage_stock = get_option("woocommerce_manage_stock");
		$wc_product->set_manage_stock($manage_stock === true?true:false); // true or false

		$wc_product->set_stock_quantity($info_producto->existencias);
		
		if ($info_producto->descontinuado == 1){
			$wc_product->set_stock_status('outofstock'); // in stock or out of stock value
			$wc_product->set_catalog_visibility('hidden'); // add the product visibility status
		}else {
			$wc_product->set_stock_status('instock'); // in stock or out of stock value
			$wc_product->set_catalog_visibility('visible'); // add the product visibility status
		}

		$wc_product->set_backorders('no');
		$wc_product->set_reviews_allowed(true);
		$wc_product->set_sold_individually(false);
		//$wc_product->set_category_ids(array(1,2,3)); // array of category ids, You can get category id from WooCommerce Product Category Section of Wordpress Admin

		// Galeria de imágenes
		// Delete all attachments
		ug_delete_attachments($wc_product->get_id());
		$ri = explode(',', trim($info_producto->rutaImagenes, ','));
		$c = count($ri);
		if ($c>0){
			// The first image is added as featured image 
			ug_image_featured($ri[0], $wc_product->get_id());
			// Other images are added as gallery images
			if ($c>1){
				//delete all galery images
				//delete_post_meta ($wc_product->get_id(), '_product_image_gallery');
				for ($i=1;$i<$c;$i++){
					error_log("ciclo $i adding ". $ri[$i]." <br>");
					ug_image_gallery($ri[$i], $wc_product->get_id());
				}
			}
		}
		$wc_product->save();
	}
}

 */