// Zona "Arequipa": delivery + recojo en tienda. Idempotente: recrea la zona cada vez.
foreach ( WC_Shipping_Zones::get_zones() as $z ) {
	if ( 'Arequipa' === $z['zone_name'] ) {
		( new WC_Shipping_Zone( $z['id'] ) )->delete();
	}
}
$zona = new WC_Shipping_Zone();
$zona->set_zone_name( 'Arequipa' );
$zona->set_zone_order( 1 );
$zona->add_location( 'PE:ARE', 'state' );
$zona->save();

$delivery = $zona->add_shipping_method( 'flat_rate' );
update_option( 'woocommerce_flat_rate_' . $delivery . '_settings', array(
	'title'      => 'Delivery por inDrive, 12 a 24 horas (el costo de la carrera se coordina por WhatsApp)',
	'tax_status' => 'none',
	'cost'       => '0',
) );
$recojo = $zona->add_shipping_method( 'local_pickup' );
update_option( 'woocommerce_local_pickup_' . $recojo . '_settings', array(
	'title'      => 'Recojo en tienda (gratis)',
	'tax_status' => 'none',
	'cost'       => '0',
) );
echo "Zona Arequipa lista\n";
