<?php
date_default_timezone_set('America/Mexico_City');
//este archivo irá en el directorio raiz de WP o se debe cambiar la ruta:
include_once "../../../wp-load.php";
include_once "../../../wp-admin/includes/plugin.php";
include_once "admin/ugdev.php";

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Main routine
global $nl;
(php_sapi_name() === 'cli')?$nl = "\n":$nl="<br>";
global $wpdb;
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
$producto = new stdClass();
foreach ($a as $p) {
	//var_dump($p);
	$producto->IdProducto = $p->idProducto;
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
	 
	ug_create_product ($producto);
	echo "($i) $p->idProducto $nl";
	$i++;
	$sql = "update {$wpdb->prefix}queue_products set procesado = 1 where id={$p->id}";
	echo "Actualizando: $sql $nl";
	$a = $wpdb->get_results( $sql );
	if (is_null($a)){
		echo "no pude actualizar el registro {$p->id} $nl";
	}
}

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