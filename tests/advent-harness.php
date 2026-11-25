<?php
// WordPress simulat minim pentru pluginul Calendar de Advent. Rulare: php advent-harness.php <cale plugin>
define( 'ABSPATH', __DIR__ . '/' );
$GLOBALS['opts'] = array();
$GLOBALS['can']  = false;
$GLOBALS['logged'] = false;
$GLOBALS['actions'] = array();
$GLOBALS['done'] = array();
$GLOBALS['cart'] = false;
$GLOBALS['routes'] = array();

// Ca în wp-includes/compat.php: WordPress are polyfill pentru mb_substr când lipsește mbstring.
if ( ! function_exists( 'mb_substr' ) ) {
	function mb_substr( $s, $start, $len = null ) { $c = preg_split( '//u', $s, -1, PREG_SPLIT_NO_EMPTY ); return implode( '', array_slice( $c, $start, $len ) ); }
	function mb_strlen( $s ) { return count( preg_split( '//u', $s, -1, PREG_SPLIT_NO_EMPTY ) ); }
}
class WooCommerce {}
class WP_REST_Response {
	public $data; public $headers = array();
	function __construct( $d ) { $this->data = $d; }
	function header( $k, $v ) { $this->headers[ $k ] = $v; }
}
class Req { public $p; function __construct( $p ) { $this->p = $p; } function get_param( $k ) { return $this->p[ $k ] ?? null; } }
class Redirect extends Exception {}

function get_option( $k, $d = false ) { return array_key_exists( $k, $GLOBALS['opts'] ) ? $GLOBALS['opts'][ $k ] : $d; }
function update_option( $k, $v, $a = null ) { $GLOBALS['opts'][ $k ] = $v; return true; }
function wp_timezone_string() { return 'Europe/Bucharest'; }
function current_user_can( $c ) { return $GLOBALS['can']; }
function is_user_logged_in() { return $GLOBALS['logged']; }
function is_admin() { return false; }
function wp_doing_ajax() { return false; }
function is_cart() { return $GLOBALS['cart']; }
function is_checkout() { return false; }
function is_account_page() { return false; }
function plugins_url( $p, $f ) { return 'https://ex.ro/wp-content/plugins/sac/' . $p; }
function plugin_basename( $f ) { return 'sac/' . basename( $f ); }
function rest_url( $p ) { return 'https://ex.ro/wp-json/' . $p; }
function home_url( $p = '' ) { return 'https://ex.ro' . $p; }
function admin_url( $p = '' ) { return 'https://ex.ro/wp-admin/' . $p; }
function wp_json_encode( $v ) { return json_encode( $v, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ); }
function add_query_arg( $k, $v, $u ) { return $u . ( strpos( $u, '?' ) === false ? '?' : '&' ) . $k . '=' . rawurlencode( $v ); }
function wp_create_nonce( $a ) { return 'NONCE'; }
function sanitize_text_field( $s ) { return trim( preg_replace( '/\s+/', ' ', strip_tags( (string) $s ) ) ); }
function sanitize_textarea_field( $s ) { return trim( strip_tags( (string) $s ) ); }
function sanitize_hex_color( $c ) { return preg_match( '/^#([a-f0-9]{3}){1,2}$/i', $c ) ? $c : null; }
function esc_url_raw( $u ) { return preg_match( '#^https?://#', $u ) ? $u : ''; }
function esc_url( $u ) { return htmlspecialchars( $u, ENT_QUOTES ); }
function esc_attr( $s ) { return htmlspecialchars( (string) $s, ENT_QUOTES ); }
function esc_html( $s ) { return htmlspecialchars( (string) $s, ENT_QUOTES ); }
function esc_textarea( $s ) { return htmlspecialchars( (string) $s, ENT_QUOTES ); }
function wp_unslash( $v ) { return is_string( $v ) ? stripslashes( $v ) : $v; }
function add_action( $h, $cb, $p = 10, $a = 1 ) { $GLOBALS['actions'][ $h ][] = $cb; }
function add_filter( $h, $cb, $p = 10, $a = 1 ) { $GLOBALS['actions'][ $h ][] = $cb; }
function do_action( $h, ...$a ) { $GLOBALS['done'][] = array( $h, $a ); }
function register_rest_route( $ns, $r, $o ) { $GLOBALS['routes'][ $ns . $r ] = $o; }
function checked( $v, $c = true, $e = true ) { $r = ( (string) $v === (string) $c ) ? ' checked' : ''; if ( $e ) echo $r; return $r; }
function selected( $v, $c = true, $e = true ) { $r = ( (string) $v === (string) $c ) ? ' selected' : ''; if ( $e ) echo $r; return $r; }
function submit_button( $t ) { echo '<button>' . $t . '</button>'; }
function wp_nonce_field( $a ) { echo '<input type="hidden" name="_wpnonce" value="x">'; }
function check_admin_referer( $a ) { return true; }
function wp_safe_redirect( $u ) { throw new Redirect( $u ); }
function wp_die( $m ) { throw new Exception( 'die: ' . $m ); }
function add_submenu_page( ...$a ) { $GLOBALS['menu'] = $a; return 'woocommerce_page_ssadv'; }
function add_options_page( ...$a ) { $GLOBALS['menu'] = $a; }
function wc_get_coupon_id_by_code( $c ) { return 'ADVENT03' === $c ? 77 : 0; }
function absint( $v ) { return abs( (int) $v ); }
function wp_strip_all_tags( $s ) { return trim( strip_tags( (string) $s ) ); }
function wp_get_attachment_image_url( $id, $size ) { return 'https://ex.ro/img/' . $id . '-300x300.jpg'; }
function wc_price( $v ) { return '<span class="woocommerce-Price-amount amount"><bdi>' . number_format( $v, 2, '.', ',' ) . '&nbsp;<span class="woocommerce-Price-currencySymbol">lei</span></bdi></span>'; }
function wp_enqueue_script( $h ) { $GLOBALS['enq'][] = $h; }
function wp_enqueue_style( $h ) { $GLOBALS['enq'][] = $h; }
class FakeProduct {
	public $d;
	function __construct( $d ) { $this->d = $d; }
	function get_status() { return $this->d['status']; }
	function get_name() { return $this->d['name']; }
	function get_formatted_name() { return $this->d['name'] . ' (#' . $this->d['id'] . ')'; }
	function get_permalink() { return 'https://ex.ro/product/' . $this->d['id'] . '/'; }
	function get_image_id() { return $this->d['img']; }
	function get_price() { return $this->d['price']; }
	function get_regular_price() { return $this->d['reg']; }
}
$GLOBALS['products'] = array(
	79777  => new FakeProduct( array( 'id' => 79777, 'status' => 'publish', 'name' => 'Brad artificial Christmas Deluxe by Sersimo, Global nins, mix 2D+3D, 210cm', 'img' => 55, 'price' => '700', 'reg' => '700' ) ),
	217173 => new FakeProduct( array( 'id' => 217173, 'status' => 'publish', 'name' => 'Brad artificial Christmas Deluxe by Sersimo, Alaska, verde, mix 2D+3D, 210 cm', 'img' => 0, 'price' => '899', 'reg' => '1027' ) ),
	5      => new FakeProduct( array( 'id' => 5, 'status' => 'draft', 'name' => 'Ciornă', 'img' => 0, 'price' => '10', 'reg' => '10' ) ),
);
function wc_get_product( $id ) { return $GLOBALS['products'][ $id ] ?? false; }

$fail = 0; $n = 0;
function ok( $cond, $name ) { global $fail, $n; $n++; if ( ! $cond ) { $fail++; echo "FAIL: $name\n"; } else { echo "ok   $name\n"; } }

require $argv[1] . '/sersimo-advent-calendar.php';
$P = 'SSADV_Plugin';

// 1. Hook-uri
ok( isset( $GLOBALS['actions']['wp_footer'], $GLOBALS['actions']['rest_api_init'], $GLOBALS['actions']['admin_post_ssadv_save'] ), 'hook-uri înregistrate' );
$P::routes();
ok( isset( $GLOBALS['routes']['sersimo-advent/v1/zile'] ), 'ruta REST' );

// 2. Calculul zilei
$tz = new DateTimeZone( 'Europe/Bucharest' );
$d = function ( $s ) use ( $tz ) { return new DateTimeImmutable( $s, $tz ); };
ok( 0 === $P::day_for( $d( '2026-11-30 23:59:59' ), 2026 ), '30 nov = 0' );
ok( 1 === $P::day_for( $d( '2026-12-01 00:00:00' ), 2026 ), '1 dec = 1' );
ok( 24 === $P::day_for( $d( '2026-12-24 23:59' ), 2026 ), '24 dec = 24' );
ok( 25 === $P::day_for( $d( '2026-12-25 00:00' ), 2026 ), '25 dec = 25' );
ok( 25 === $P::day_for( $d( '2027-01-03' ), 2026 ), 'an următor = 25' );
ok( 0 === $P::day_for( $d( '2025-12-10' ), 2026 ), 'an anterior = 0' );
ok( 'Europe/Bucharest' === $P::tz()->getName(), 'fus orar' );

// 3. Curățare zi
$day = $P::sanitize_day( array( 'emoji' => '🎄', 'forma' => 'x', 'culoare' => 'red', 'mare' => '<b>-20%</b> foarte lung text', 'mic' => 'bradul Canada',
	'text' => "<script>alert(1)</script>Doar azi", 'cod' => ' advent 03 ', 'link' => 'javascript:alert(1)', 'buton' => 'Vezi' ) );
ok( 'glob' === $day['forma'], 'formă invalidă → glob' );
ok( '#c8102e' === $day['culoare'], 'culoare invalidă → implicit (roșu)' );
ok( false === strpos( $day['text'], '<script' ), 'text fără script' );
ok( 'ADVENT03' === $day['cod'], 'cod curățat și cu majuscule' );
ok( '' === $day['link'], 'link javascript: respins' );
ok( mb_strlen( $day['mare'] ) <= 14, 'text mare limitat la 14' );
ok( 'cadou' === $P::sanitize_day( array( 'forma' => 'cadou' ) )['forma'], 'formă cadou' );

// 4. Setări
$s = $P::sanitize_settings( array( 'activ' => '1', 'an' => '1999', 'auto' => '500', 'pozitie' => 'dreapta', 'cookie_bar' => '#cookie-law-info-bar<script>' ) );
ok( 1 === $s['activ'] && 2024 === $s['an'] && 120 === $s['auto'] && 'dreapta' === $s['pozitie'], 'setări limitate' );
ok( false === strpos( $s['cookie_bar'], '<' ), 'selector cookies curățat' );

// Date de test: 24 de zile completate
$days = array();
for ( $i = 1; $i <= 24; $i++ ) {
	$days[ $i ] = $P::sanitize_day( array( 'emoji' => '⭐', 'mare' => "-$i%", 'mic' => "zi $i", 'cod' => sprintf( 'ADVENT%02d', $i ), 'link' => 'https://ex.ro/p', 'buton' => 'Vezi' ) );
}
update_option( 'ssadv_days', $days );

// 5. REST
update_option( 'ssadv_settings', array( 'activ' => 0, 'an' => 2026 ) );
$r = $P::rest_days( new Req( array() ) );
ok( 0 === count( (array) $r->data['zile'] ), 'inactiv → nimic' );

update_option( 'ssadv_settings', array( 'activ' => 1, 'an' => 2026 ) ); // azi real = înainte de dec
$r = $P::rest_days( new Req( array( 'zi' => 5 ) ) );
ok( 0 === count( (array) $r->data['zile'] ) && 0 === $r->data['azi'], 'vizitator nu poate forța ?zi' );
ok( preg_match( '/max-age=(\d+)/', $r->headers['Cache-Control'], $m ) && $m[1] <= 300, 'cache public ≤ 300 s' );
ok( in_array( 'litespeed_control_set_ttl', array_column( $GLOBALS['done'], 0 ), true ), 'TTL trimis la LiteSpeed' );

$GLOBALS['can'] = true;
$r = $P::rest_days( new Req( array( 'zi' => 5 ) ) );
$z = (array) $r->data['zile'];
ok( 5 === $r->data['azi'], 'admin: previzualizare ziua 5' );
ok( isset( $z['5']['cod'] ) && 'ADVENT05' === $z['5']['cod'], 'ziua 5 completă' );
ok( isset( $z['3'] ) && array( 'emoji' => '⭐' ) === $z['3'], 'zilele trecute doar cu emoji (fără cod)' );
ok( ! isset( $z['6'] ), 'nicio zi viitoare' );
ok( 'no-store' === $r->headers['Cache-Control'], 'previzualizarea nu se cache-uiește' );
$GLOBALS['can'] = false;

update_option( 'ssadv_settings', array( 'activ' => 1, 'an' => 2025 ) ); // pentru calendarul din 2025 suntem „după”
$r = $P::rest_days( new Req( array() ) );
$z = (array) $r->data['zile'];
ok( 25 === $r->data['azi'] && 24 === count( $z ), 'după încheiere: 24 de zile' );
$leak = false;
foreach ( $z as $x ) { if ( isset( $x['cod'] ) ) { $leak = true; } }
ok( ! $leak, 'după încheiere: niciun cod expus' );

// 6. Footer
update_option( 'ssadv_settings', array( 'activ' => 0, 'an' => 2026 ) );
ob_start(); $P::footer(); $o = ob_get_clean();
ok( '' === $o, 'inactiv → fără loader' );

update_option( 'ssadv_settings', array( 'activ' => 1, 'an' => 2026, 'auto' => 8, 'pozitie' => 'dreapta' ) );
ob_start(); $P::footer(); $o = ob_get_clean();
ok( false !== strpos( $o, 'window.SSADV_CFG=' ) && false !== strpos( $o, 'ssadv-fab' ), 'activ → loader inline' );
ok( false !== strpos( $o, 'data-no-optimize="1"' ), 'exclus de la optimizarea LiteSpeed' );
preg_match( '/window\.SSADV_CFG=(\{.*?\});/', $o, $m );
$cfg = json_decode( $m[1], true );
ok( 8000 === $cfg['auto'] && 'right' === $cfg['side'] && 2026 === $cfg['year'], 'config corectă' );
ok( ! isset( $cfg['debugDay'] ) && ! isset( $cfg['nonce'] ), 'vizitator: fără debugDay / nonce' );
ok( false === strpos( $o, 'ADVENT' ), 'niciun cod în HTML-ul paginii' );

$GLOBALS['cart'] = true;
ob_start(); $P::footer(); $o = ob_get_clean();
ok( '' === $o, 'ascuns în coș' );
$GLOBALS['cart'] = false;

$_GET['advent_zi'] = '7';
ob_start(); $P::footer(); $o = ob_get_clean();
preg_match( '/window\.SSADV_CFG=(\{.*?\});/', $o, $m ); $cfg = json_decode( $m[1], true );
ok( ! isset( $cfg['debugDay'] ), '?advent_zi ignorat pentru vizitatori' );
$GLOBALS['can'] = $GLOBALS['logged'] = true;
update_option( 'ssadv_settings', array( 'activ' => 0, 'an' => 2026 ) );
ob_start(); $P::footer(); $o = ob_get_clean();
preg_match( '/window\.SSADV_CFG=(\{.*?\});/', $o, $m ); $cfg = json_decode( $m[1], true );
ok( 7 === $cfg['debugDay'] && 'NONCE' === $cfg['nonce'] && false !== strpos( $cfg['data'], 'zi=7' ), 'admin: previzualizare chiar dacă e inactiv' );
unset( $_GET['advent_zi'] );

// 7. Pagina de admin
$GLOBALS['opts']['ssadv_days'][3]['cod'] = 'ADVENT03';
ob_start(); $P::page(); $o = ob_get_clean();
ok( 24 === substr_count( $o, '<details' ), 'admin: 24 de zile' );
ok( false !== strpos( $o, 'cupon găsit' ) && false !== strpos( $o, 'nu există cupon' ), 'admin: verificare cupoane' );
ok( false !== strpos( $o, 'advent_zi=' ), 'admin: linkuri de previzualizare' );
$P::menu();
ok( 'woocommerce' === $GLOBALS['menu'][0], 'meniu sub WooCommerce' );

// 7b. Produsul redus
$pd = $P::product_data( 79777 );
ok( 'Brad Global nins, mix 2D+3D, 210cm' === $pd['nume'], 'produs: nume scurtat' );
ok( '700.00 lei' === $pd['pret'] && ! isset( $pd['pret_vechi'] ), 'produs: preț fără reducere' );
ok( false !== strpos( $pd['img'], '55-300x300' ) && false !== strpos( $pd['link'], '/product/79777/' ), 'produs: poză și link' );
$pd = $P::product_data( 217173 );
ok( '899.00 lei' === $pd['pret'] && '1,027.00 lei' === $pd['pret_vechi'] && ! isset( $pd['img'] ), 'produs la reducere: preț vechi, fără poză' );
ok( null === $P::product_data( 5 ), 'produs nepublicat ignorat' );
ok( null === $P::product_data( 999 ), 'produs inexistent ignorat' );
ok( 79777 === $P::sanitize_day( array( 'produs' => '79777abc' ) )['produs'], 'produs: ID curățat' );
ok( 'Brad Canada' === $P::short_name( 'Brad Canada' ), 'nume fără prefix rămâne la fel' );

$dd = get_option( 'ssadv_days' );
$dd[5]['produs'] = 79777; $dd[5]['pret_cupon'] = '560.00 lei'; $dd[5]['mic'] = ''; $dd[4]['produs'] = 217173;
update_option( 'ssadv_days', $dd );
update_option( 'ssadv_settings', array( 'activ' => 1, 'an' => 2026 ) );
$GLOBALS['can'] = true;
$r = $P::rest_days( new Req( array( 'zi' => 5 ) ) );
$z = (array) $r->data['zile'];
ok( isset( $z['5']['produs']['nume'] ) && '560.00 lei' === $z['5']['pret_cupon'], 'REST: ziua de azi cu produs și preț cu cod' );
ok( array( 'emoji' => '⭐' ) === $z['4'], 'REST: ieri doar emoji (fără produs)' );
ok( ! is_int( $z['5']['produs'] ), 'REST: nu trimite ID-ul brut' );

ob_start(); $P::page(); $o = ob_get_clean();
ok( false !== strpos( $o, 'wc-product-search' ) && false !== strpos( $o, 'Global nins, mix 2D+3D, 210cm (#79777)</option>' ), 'admin: căutare produs cu produsul ales' );
ok( false !== strpos( $o, 'Brad Global nins' ), 'admin: rezumatul zilei arată produsul' );
$GLOBALS['enq'] = array();
$P::menu();
$P::admin_assets( 'altceva' );
ok( empty( $GLOBALS['enq'] ), 'admin: scripturi doar pe pagina noastră' );
$P::admin_assets( 'woocommerce_page_ssadv' );
ok( in_array( 'wc-enhanced-select', $GLOBALS['enq'], true ), 'admin: căutarea de produse încărcată pe pagina noastră' );

// 8. Salvare
$_POST = array( 's' => array( 'activ' => '1', 'an' => '2026', 'auto' => '5' ), 'd' => array( 2 => array( 'mare' => '-10%', 'cod' => 'abc', 'text' => 'Sersimo\\\'s' ) ) );
$rev = (int) get_option( 'ssadv_rev', 0 );
try { $P::save(); ok( false, 'salvare redirect' ); } catch ( Redirect $e ) { ok( false !== strpos( $e->getMessage(), 'ssadv_ok=1' ), 'salvare redirect' ); }
$sd = get_option( 'ssadv_days' );
ok( 24 === count( $sd ) && '-10%' === $sd[2]['mare'] && 'ABC' === $sd[2]['cod'], 'salvare zile' );
ok( "Sersimo's" === $sd[2]['text'], 'salvare: slash-uri eliminate' );
ok( $rev + 1 === get_option( 'ssadv_rev' ), 'salvare: versiune crescută (cache JS)' );
ok( in_array( 'litespeed_purge_all', array_column( $GLOBALS['done'], 0 ), true ), 'salvare: purge LiteSpeed' );
$GLOBALS['can'] = false;
try { $P::save(); ok( false, 'salvare fără drepturi' ); } catch ( Redirect $e ) { ok( false, 'salvare fără drepturi' ); } catch ( Exception $e ) { ok( 0 === strpos( $e->getMessage(), 'die' ), 'salvare fără drepturi refuzată' ); }

echo "\n$n teste, $fail eșuate\n";
exit( $fail ? 1 : 0 );
