// Importa el CSV de productos con el importador oficial de WooCommerce.
// Es idempotente: si el SKU ya existe, actualiza el producto en vez de duplicarlo.
wp_set_current_user( 1 ); // el importador necesita permisos de administrador para crear categorías
$archivo = '/var/www/html/productos_woocommerce.csv';
if ( ! file_exists( $archivo ) ) {
	WP_CLI::error( 'No encuentro el CSV. Ejecuta primero: npm run tienda:csv -- "ruta/al/Excel.xlsx"' );
}
require_once WC_ABSPATH . 'includes/import/class-wc-product-csv-importer.php';
// Columna del CSV (excel_a_csv.py) => campo de WooCommerce.
$mapeo = array(
	'SKU' => 'sku', 'Type' => 'type', 'Name' => 'name', 'Published' => 'published',
	'Visibility in catalog' => 'catalog_visibility', 'Short description' => 'short_description', 'Description' => 'description',
	'Tax status' => 'tax_status', 'Tax class' => 'tax_class', 'In stock?' => 'stock_status',
	'Stock' => 'stock_quantity', 'Regular price' => 'regular_price', 'Categories' => 'category_ids',
	'Images' => 'images',
);

/**
 * Recorre el CSV por tandas. $actualizar = false solo crea productos nuevos;
 * true solo actualiza los que ya existen (así lo hace WooCommerce).
 */
function denver_importar( $archivo, $mapeo, $actualizar ) {
	$total = array( 'imported' => 0, 'updated' => 0, 'skipped' => 0, 'failed' => array() );
	$pos   = 0;
	do {
		$importador = new WC_Product_CSV_Importer( $archivo, array(
			'parse'           => true,
			'update_existing' => $actualizar,
			'delimiter'       => ',',
			'mapping'         => $mapeo,
			'start_pos'       => $pos,
			'lines'           => 200,
		) );
		$r = $importador->import();
		$total['imported'] += count( $r['imported'] );
		$total['updated']  += count( $r['updated'] );
		$total['skipped']  += count( $r['skipped'] );
		$total['failed']    = array_merge( $total['failed'], $r['failed'] );
		$pos = $importador->get_file_position();
		echo '.';
	} while ( $importador->get_percent_complete() < 100 );
	echo "\n";
	return $total;
}

$nuevos    = denver_importar( $archivo, $mapeo, false );
$existian  = 0;
$otros_err = array();
foreach ( $nuevos['failed'] as $f ) {
	if ( is_wp_error( $f ) && 'woocommerce_product_importer_error' === $f->get_error_code() && false !== stripos( $f->get_error_message(), 'SKU' ) ) {
		$existian++;
	} else {
		$otros_err[] = $f;
	}
}
$existian += $nuevos["skipped"];
$actualizados = 0;
if ( $existian ) {
	echo "Actualizando $existian productos que ya existían...\n";
	$actualizados = denver_importar( $archivo, $mapeo, true )['updated'];
}
printf( "Nuevos: %d | Actualizados: %d | Con error: %d\n", $nuevos['imported'], $actualizados, count( $otros_err ) );
foreach ( array_slice( $otros_err, 0, 5 ) as $f ) {
	echo '  Error: ', is_wp_error( $f ) ? $f->get_error_message() : print_r( $f, true ), "\n";
}
