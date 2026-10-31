=== SMuebleria ===
Contributors: uranodev
Tags: woocommerce, erp, inventario, sincronizacion, pedidos
Requires at least: 6.2
Tested up to: 6.8
Requires PHP: 8.1
Stable tag: 2.5.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Conecta WooCommerce con el ERP de sMuebleria: carga el catálogo, valida existencias y registra clientes, ventas y pagos.

== Description ==

SMuebleria mantiene una tienda WooCommerce conectada con el ERP de sMuebleria. El catálogo llega del ERP a la tienda, y los clientes, las ventas y los pagos van de la tienda al ERP.

= Catálogo =

* Descarga los productos del ERP a una cola de carga (tabla `queue_products`). Solo se reprocesan los productos nuevos o que cambiaron desde la última carga.
* Crea en WooCommerce los productos nuevos y actualiza los existentes, identificados por su `IdProducto` del ERP.
* Actualiza existencias, precio de lista, precio de oferta, peso, medidas, SKU e imágenes. Si el precio del ERP es menor al de lista, la tienda cobra ese precio y muestra el de lista tachado. La primera imagen queda como imagen destacada y las demás en la galería.
* Asigna a cada producto la categoría de su `IdCategoria`. Si la categoría no existe, la crea con el nombre que tiene en el ERP (si el ERP no responde, usa el número y lo corrige en la siguiente corrida). Si el ERP cambia el nombre, se actualiza; si alguien la renombró en WooCommerce, se respeta. Las categorías puestas a mano se conservan.
* Marca como agotados los productos descontinuados y los oculta del catálogo. Los demás productos quedan visibles en cada carga.

= Clientes y ventas =

* Registra en el ERP a cada cliente que se crea una cuenta en la tienda.
* Al confirmar un pedido, registra en el ERP sus direcciones de facturación y envío, y crea la venta.
* Las ventas pagadas con el método `cotzn-gateway` se registran como tipo de orden 2, y las demás como tipo 3.
* Propone como fecha de entrega 15 días después de la compra.
* Muestra el folio de venta del ERP en la página de confirmación del pedido.

= Pagos =

* Cuando un pedido se paga, registra el pago en el ERP junto con su monto y su número de transacción.
* Identifica la forma de pago: Stripe, Openpay, Conekta u Otra.

= Existencias en el checkout =

Si la tienda tiene activo el control de inventario, consulta las existencias en el ERP al finalizar la compra y rechaza el pedido cuando no alcanzan.

= Administración de pedidos =

Para administradores y editores, oculta en la pantalla del pedido el botón de reembolso, la acción de reenviar el correo de reembolso y el estado "Reembolsado". Un pedido que ya está reembolsado conserva su estado.

= Compatibilidad con WooCommerce =

* Compatible con el almacenamiento de pedidos de alto rendimiento (HPOS).
* No compatible con el checkout de bloques. La página de pago debe usar el shortcode `[woocommerce_checkout]`.

== Installation ==

1. Instala y activa WooCommerce.
2. Sube el plugin a `wp-content/plugins/smueblerias`. La carpeta debe llamarse así para recibir actualizaciones.
3. Activa el plugin en **Plugins**. Al activarlo se crea la tabla de la cola de carga.
4. Abre **SMuebleria Configuración** en el menú de WordPress.
5. Captura la clave, la empresa y el usuario del ERP, y haz clic en **Guardar**. Un mensaje indica si la conexión con el ERP funcionó.
6. Confirma que la página de pago use el shortcode `[woocommerce_checkout]`.
7. Programa las dos tareas de carga del catálogo. Ambas deben ejecutarse desde la carpeta del plugin:

`php cron_add_queue.php`

`php cron_process_queue.php`

La primera trae los productos del ERP a la cola, y la segunda los aplica a WooCommerce.

Las imágenes se comparan una por una. Se descarga la que es nueva o cambió en el ERP, se borra la que el ERP ya no incluye y las demás se conservan. Las imágenes que agregues a mano a un producto no se tocan. En la primera carga después de actualizar, las imágenes que tenían los productos se reemplazan una vez por las del ERP.

Para detectar imágenes que el ERP reemplazó con el mismo nombre de archivo, programa una vez al día o a la semana:

`php cron_process_queue.php imagenes`

También se puede lanzar por URL, un lote a la vez: `cron_process_queue.php?imagenes=1&desde=0&lote=100`. Al terminar cada lote indica la URL del siguiente. Revisa las imágenes de todos los productos de la cola y muestra cuántas descargó, borró, dejó igual o no pudo descargar.

== Frequently Asked Questions ==

= ¿Qué hace el botón Cargar Productos? =

Consulta el ERP y avisa si recibió productos, pero no los crea ni los actualiza. La carga la hacen las tareas `cron_add_queue.php` y `cron_process_queue.php`.

= ¿Qué pasa con la opción de control de inventario? =

Es el mismo ajuste que **Gestionar inventario** de WooCommerce. Cambiarla en una pantalla la cambia también en la otra.

= ¿Las opciones para deshabilitar la carga funcionan? =

Por ahora no. **Deshabilitar la carga de Descripción**, **de Imágenes** y **de Medidas** se guardan, pero la carga del catálogo no las aplica: sigue actualizando imágenes y medidas.

= ¿Se actualizan el nombre y la descripción de un producto que ya existe? =

No. Se toman del ERP solo cuando el producto se crea.

= ¿Qué pasa al desactivar el plugin? =

Se borra la tabla de la cola de carga. Los productos, los pedidos y la configuración se conservan. La tabla se crea de nuevo al activarlo.

= ¿Qué datos guarda el plugin en cada pedido? =

* `_erp_direccion_billing` y `_erp_direccion_shipping`: direcciones en el ERP.
* `_erp_venta_id` y `_erp_folio_venta`: venta en el ERP.
* `_erp_payment_id`: pago en el ERP.

== Changelog ==

= 2.5.1 =
* Compatibilidad con WooCommerce 11.2.1.
* Los logs y la salida de la carga del catálogo muestran solo los últimos 3 caracteres de la clave y el usuario del ERP. La empresa y la contraseña del cliente se ocultan completas.
* Los datos del ERP se guardan en el pedido y funcionan con HPOS. Los pedidos anteriores se siguen leyendo.
* La forma de pago enviada al ERP se lee del pedido y reconoce Stripe, Openpay y Conekta cuando su identificador empieza con ese nombre.
* Un pago ya registrado no se envía de nuevo al ERP.
* Corrección del error que impedía mostrar el aviso de existencias insuficientes en PHP 8.
* Corrección de los espacios en los apellidos enviados al ERP.
* Los productos descontinuados se ocultan del catálogo.
* La tienda cobra el precio especial del ERP cuando es menor al de lista.
* La carga del catálogo solo marca para reprocesar los productos que cambiaron en el ERP. La primera carga después de actualizar procesa todo el catálogo una vez.
* Los productos reciben la categoría del ERP, con el nombre que tiene en el ERP.
* Las imágenes se sincronizan una por una: solo se descarga la nueva o la que cambió, y se borra la que el ERP quitó. Las imágenes agregadas a mano se conservan.
* Modo `imagenes` para revisar las imágenes de todo el catálogo, por línea de comandos (`php cron_process_queue.php imagenes`) o por URL en lotes (`cron_process_queue.php?imagenes=1&desde=0&lote=100`).
* Los productos que el ERP deja de enviar se ocultan del catálogo, quedan agotados (no se pueden comprar) y se les anota la fecha, que se ve en el producto. Si el ERP los vuelve a enviar, se muestran de nuevo. Si el ERP manda menos de la mitad de los productos de la cola, no se oculta ninguno.
* Los scripts de carga muestran un resumen. El detalle y los errores en pantalla salen solo con `?debug=1` en la URL (o el argumento `debug` en la línea de comandos).
* Se quitaron del paquete `test.php`, `borrar.php` y los archivos `.zip`.
* La descripción de la cola admite hasta 6,400 caracteres (antes 2,500). Al actualizar, la tabla de la cola se ajusta sola una vez. Si una descripción pasa de ese largo se recorta, la carga lo avisa y el producto muestra el aviso en su caja "SMuebleria".
* Un producto de la cola se marca como procesado solo si quedó guardado en WooCommerce; si falla se reintenta en la siguiente carga y la salida lo reporta.
* Los productos nuevos reciben sus existencias desde la primera carga, y un producto en borrador ya no se duplica.
* La carga de la cola no corre si ya hay otra en ejecución.
* Las opciones "Deshabilitar la carga de Imágenes", "de Descripción" y "de Medidas" se respetan. Sin marcar la de descripción, el nombre y la descripción de los productos existentes se actualizan en cada carga.
* Si el ERP no responde o devuelve un error, la tienda ya no falla en el pago: se deja pasar la compra con las existencias de la tienda y el pedido recibe una nota cuando no se pudo enviar la dirección, la venta o el pago al ERP.
* Los pedidos sin calle en la dirección ya no crean una dirección vacía en el ERP.
* El cliente se da de alta en el ERP con su primer pedido, cuando ya se tiene su teléfono, y no al registrarse. Ya no se envía el teléfono falso. La contraseña de la tienda no se envía: el ERP recibe "sin_password".
* Los datos del cliente se codifican correctamente en la petición al ERP.
* La forma de pago de la venta se toma del pedido, no de la sesión de compra.
* El botón de reembolso también se oculta en la pantalla de pedidos de HPOS.
* Requiere PHP 8.1 o superior y está probado en PHP 8.1 y 8.4, sin avisos de funciones obsoletas.
* Declaración de compatibilidad con HPOS y de incompatibilidad con el checkout de bloques.
* Plantillas del botón de pago, de la confirmación del pedido y de productos agrupados actualizadas a WooCommerce 11.2.1.

== Upgrade Notice ==

= 2.5.1 =
Requiere WooCommerce 8.2 o superior. La página de pago debe usar el shortcode `[woocommerce_checkout]`.
