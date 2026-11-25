<?php
/**
 * Plugin Name:       Sersimo – Calendar de Advent
 * Description:       Calendar de Advent în popup: 24 de uși, ușa zilei aprinsă, cupon în același popup la click. Pe pagină stă doar un loader de ~2 KB; restul se încarcă abia la deschidere.
 * Version:           1.0.0
 * Author:            Pruntech
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Text Domain:       sersimo-advent
 */

defined( 'ABSPATH' ) || exit;

final class SSADV_Plugin {

	const VER  = '1.0.0';
	const OPT  = 'ssadv_settings';
	const DAYS = 'ssadv_days';
	const REV  = 'ssadv_rev';
	const N    = 24;
	const NS   = 'sersimo-advent/v1';

	/** Câmpurile unei zile: cheie => [etichetă, tip, lungime maximă]. */
	public static function fields() {
		return array(
			'emoji'    => array( 'Emoji', 'text', 16 ),
			'forma'    => array( 'Formă cupon', 'select', 0 ),
			'culoare'  => array( 'Culoare', 'color', 0 ),
			'mare'     => array( 'Text mare (ex. -20%)', 'text', 14 ),
			'mic'      => array( 'Text mic (ex. bradul Canada)', 'text', 40 ),
			'eticheta' => array( 'Eticheta de sus (opțional)', 'text', 40 ),
			'text'     => array( 'Descriere', 'textarea', 220 ),
			'cod'      => array( 'Cod cupon', 'text', 40 ),
			'link'     => array( 'Link buton', 'url', 0 ),
			'buton'    => array( 'Text buton', 'text', 30 ),
		);
	}

	public static function defaults() {
		$now = new DateTimeImmutable( 'now', self::tz() );
		return array(
			'activ'            => 0,
			'an'               => (int) $now->format( 'Y' ),
			'auto'             => 8,
			'teaser'           => 0,
			'ascunde_checkout' => 1,
			'pozitie'          => 'stanga',
			'cookie_bar'       => '#cookie-law-info-bar',
		);
	}

	public static function init() {
		add_action( 'wp_footer', array( __CLASS__, 'footer' ), 50 );
		add_action( 'rest_api_init', array( __CLASS__, 'routes' ) );
		add_action( 'admin_menu', array( __CLASS__, 'menu' ), 60 );
		add_action( 'admin_post_ssadv_save', array( __CLASS__, 'save' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( __CLASS__, 'action_links' ) );
	}

	/* ------------------------------------------------------------------ date */

	public static function settings() {
		$s = get_option( self::OPT, array() );
		return array_merge( self::defaults(), is_array( $s ) ? $s : array() );
	}

	public static function days() {
		$d = get_option( self::DAYS, array() );
		return is_array( $d ) ? $d : array();
	}

	/** Fusul orar al site-ului; dacă e un decalaj fix (+02:00), folosim Europe/Bucharest. */
	public static function tz() {
		$name = function_exists( 'wp_timezone_string' ) ? wp_timezone_string() : '';
		if ( ! $name || false === strpos( $name, '/' ) ) {
			$name = 'Europe/Bucharest';
		}
		try {
			return new DateTimeZone( $name );
		} catch ( Exception $e ) {
			return new DateTimeZone( 'Europe/Bucharest' );
		}
	}

	/** 0 = înainte de 1 decembrie, 1..24 = ziua, 25 = s-a terminat. */
	public static function day_for( DateTimeImmutable $now, $year ) {
		$y = (int) $now->format( 'Y' );
		$m = (int) $now->format( 'n' );
		$d = (int) $now->format( 'j' );
		if ( $y < $year || ( $y === $year && $m < 12 ) ) {
			return 0;
		}
		if ( $y > $year || $d > self::N ) {
			return 25;
		}
		return $d;
	}

	public static function cap() {
		return class_exists( 'WooCommerce' ) ? 'manage_woocommerce' : 'manage_options';
	}

	/** Previzualizare pentru admin: ?advent_zi=N simulează ziua N (doar pentru cine poate edita setările). */
	public static function preview_day() {
		if ( ! isset( $_GET['advent_zi'] ) || ! is_user_logged_in() || ! current_user_can( self::cap() ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			return null;
		}
		return max( 0, min( 25, (int) $_GET['advent_zi'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
	}

	/* -------------------------------------------------------------- frontend */

	public static function footer() {
		if ( is_admin() || wp_doing_ajax() ) {
			return;
		}
		$s       = self::settings();
		$preview = self::preview_day();
		if ( ! $s['activ'] && null === $preview ) {
			return;
		}
		if ( $s['ascunde_checkout'] && null === $preview && function_exists( 'is_cart' )
			&& ( is_cart() || is_checkout() || is_account_page() ) ) {
			return;
		}
		$loader = file_get_contents( __DIR__ . '/assets/advent-loader.js' ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		if ( ! $loader ) {
			return;
		}
		$cfg = array(
			'base'      => plugins_url( 'assets/', __FILE__ ),
			'data'      => rest_url( self::NS . '/zile' ),
			'year'      => (int) $s['an'],
			'tz'        => self::tz()->getName(),
			'auto'      => max( 0, (int) $s['auto'] ) * 1000,
			'teaser'    => (bool) $s['teaser'],
			'side'      => 'dreapta' === $s['pozitie'] ? 'right' : 'left',
			'cookieBar' => (string) $s['cookie_bar'],
			'v'         => self::VER . '.' . (int) get_option( self::REV, 0 ),
		);
		if ( null !== $preview ) {
			$cfg['debugDay'] = $preview;
			$cfg['teaser']   = true;
			$cfg['auto']     = 0;
			$cfg['nonce']    = wp_create_nonce( 'wp_rest' );
			$cfg['data']     = add_query_arg( 'zi', $preview, $cfg['data'] );
		}
		// data-no-optimize / data-cfasync: LiteSpeed și Cloudflare Rocket Loader să nu-l amâne sau combine.
		echo "\n<script id=\"ssadv-loader\" data-no-optimize=\"1\" data-cfasync=\"false\">window.SSADV_CFG="
			. wp_json_encode( $cfg ) . ";\n" . $loader . "</script>\n"; // phpcs:ignore WordPress.Security.EscapeOutput
	}

	/* ------------------------------------------------------------------ REST */

	public static function routes() {
		register_rest_route(
			self::NS,
			'/zile',
			array(
				'methods'             => 'GET',
				'permission_callback' => '__return_true',
				'callback'            => array( __CLASS__, 'rest_days' ),
				'args'                => array( 'zi' => array( 'type' => 'integer' ) ),
			)
		);
	}

	/**
	 * Trimite doar ce trebuie: ziua de azi complet, zilele trecute doar cu emoji, nimic din viitor.
	 * Astfel codurile din zilele următoare nu pot fi văzute dinainte.
	 */
	public static function rest_days( $req ) {
		$s       = self::settings();
		$now     = new DateTimeImmutable( 'now', self::tz() );
		$azi     = self::day_for( $now, (int) $s['an'] );
		$zi      = $req->get_param( 'zi' );
		$preview = null !== $zi && current_user_can( self::cap() );
		if ( $preview ) {
			$azi = max( 0, min( 25, (int) $zi ) );
		}
		$out = array();
		if ( $s['activ'] || $preview ) {
			$days = self::days();
			for ( $i = 1; $i <= min( $azi, self::N ); $i++ ) {
				if ( empty( $days[ $i ] ) || ! is_array( $days[ $i ] ) ) {
					continue;
				}
				$d = $days[ $i ];
				if ( $i < $azi ) {
					if ( ! empty( $d['emoji'] ) ) {
						$out[ (string) $i ] = array( 'emoji' => $d['emoji'] );
					}
					continue;
				}
				$pub = array();
				foreach ( array_keys( self::fields() ) as $k ) {
					if ( isset( $d[ $k ] ) && '' !== $d[ $k ] ) {
						$pub[ $k ] = $d[ $k ];
					}
				}
				$out[ (string) $i ] = $pub;
			}
		}
		$res = new WP_REST_Response( array( 'azi' => $azi, 'zile' => (object) $out ) );
		if ( $preview ) {
			$res->header( 'Cache-Control', 'no-store' );
		} else {
			// Cache scurt, care nu trece niciodată de miezul nopții (ora site-ului).
			$midnight = $now->setTime( 0, 0 )->modify( '+1 day' );
			$ttl      = max( 1, min( 300, $midnight->getTimestamp() - $now->getTimestamp() ) );
			$res->header( 'Cache-Control', 'public, max-age=' . $ttl );
			do_action( 'litespeed_control_set_ttl', $ttl );
		}
		return $res;
	}

	/* ----------------------------------------------------------------- admin */

	public static function action_links( $links ) {
		array_unshift( $links, '<a href="' . esc_url( self::admin_url() ) . '">Setări</a>' );
		return $links;
	}

	public static function admin_url() {
		return admin_url( ( class_exists( 'WooCommerce' ) ? 'admin.php' : 'options-general.php' ) . '?page=ssadv' );
	}

	public static function menu() {
		if ( class_exists( 'WooCommerce' ) ) {
			add_submenu_page( 'woocommerce', 'Calendar de Advent', 'Calendar Advent', self::cap(), 'ssadv', array( __CLASS__, 'page' ) );
		} else {
			add_options_page( 'Calendar de Advent', 'Calendar Advent', self::cap(), 'ssadv', array( __CLASS__, 'page' ) );
		}
	}

	public static function sanitize_settings( $in ) {
		$def = self::defaults();
		return array(
			'activ'            => empty( $in['activ'] ) ? 0 : 1,
			'an'               => max( 2024, min( 2100, (int) ( $in['an'] ?? $def['an'] ) ) ),
			'auto'             => max( 0, min( 120, (int) ( $in['auto'] ?? $def['auto'] ) ) ),
			'teaser'           => empty( $in['teaser'] ) ? 0 : 1,
			'ascunde_checkout' => empty( $in['ascunde_checkout'] ) ? 0 : 1,
			'pozitie'          => ( isset( $in['pozitie'] ) && 'dreapta' === $in['pozitie'] ) ? 'dreapta' : 'stanga',
			'cookie_bar'       => preg_replace( '/[^#.\w\s\-\[\]="\',>:]/u', '', sanitize_text_field( wp_unslash( $in['cookie_bar'] ?? '' ) ) ),
		);
	}

	public static function sanitize_day( $in ) {
		$out = array();
		foreach ( self::fields() as $k => $f ) {
			$v = isset( $in[ $k ] ) ? wp_unslash( $in[ $k ] ) : '';
			switch ( $f[1] ) {
				case 'select':
					$v = 'cadou' === $v ? 'cadou' : 'glob';
					break;
				case 'color':
					$v = sanitize_hex_color( $v );
					$v = $v ? $v : '#c31c72';
					break;
				case 'url':
					$v = esc_url_raw( trim( $v ) );
					break;
				case 'textarea':
					$v = sanitize_textarea_field( $v );
					break;
				default:
					$v = sanitize_text_field( $v );
			}
			if ( $f[2] > 0 && function_exists( 'mb_substr' ) ) {
				$v = mb_substr( $v, 0, $f[2] );
			}
			$out[ $k ] = $v;
		}
		$out['cod'] = strtoupper( str_replace( ' ', '', $out['cod'] ) );
		return $out;
	}

	public static function save() {
		if ( ! current_user_can( self::cap() ) ) {
			wp_die( 'Nu ai drepturi pentru asta.', 403 );
		}
		check_admin_referer( 'ssadv_save' );
		$s = self::sanitize_settings( isset( $_POST['s'] ) && is_array( $_POST['s'] ) ? $_POST['s'] : array() ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		$d = array();
		$raw = isset( $_POST['d'] ) && is_array( $_POST['d'] ) ? $_POST['d'] : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		for ( $i = 1; $i <= self::N; $i++ ) {
			$d[ $i ] = self::sanitize_day( isset( $raw[ $i ] ) && is_array( $raw[ $i ] ) ? $raw[ $i ] : array() );
		}
		update_option( self::OPT, $s, true );
		update_option( self::DAYS, $d, false );
		update_option( self::REV, (int) get_option( self::REV, 0 ) + 1, false );
		do_action( 'litespeed_purge_all' ); // ca vizitatorii să primească noile setări imediat
		wp_safe_redirect( add_query_arg( 'ssadv_ok', 1, self::admin_url() ) );
		exit;
	}

	/** Verificare informativă: există codul ca cupon WooCommerce? */
	public static function coupon_status( $code ) {
		if ( '' === $code || ! function_exists( 'wc_get_coupon_id_by_code' ) ) {
			return '';
		}
		return wc_get_coupon_id_by_code( $code )
			? '<span class="ssadv-ok">✓ cupon găsit în WooCommerce</span>'
			: '<span class="ssadv-warn">⚠ nu există cupon cu acest cod în WooCommerce</span>';
	}

	public static function page() {
		if ( ! current_user_can( self::cap() ) ) {
			return;
		}
		$s    = self::settings();
		$days = self::days();
		$azi  = self::day_for( new DateTimeImmutable( 'now', self::tz() ), (int) $s['an'] );
		$f    = self::fields();
		?>
		<div class="wrap ssadv-admin">
			<h1>🎄 Calendar de Advent</h1>
			<?php if ( isset( $_GET['ssadv_ok'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification ?>
				<div class="notice notice-success is-dismissible"><p>Salvat.</p></div>
			<?php endif; ?>
			<p>
				Azi, după ora site-ului (<?php echo esc_html( self::tz()->getName() ); ?>):
				<strong><?php echo esc_html( 0 === $azi ? 'calendarul nu a început' : ( 25 === $azi ? 'calendarul s-a încheiat' : 'ziua ' . $azi ) ); ?></strong>.
				Previzualizare (doar tu o vezi, chiar dacă calendarul e oprit):
				<a href="<?php echo esc_url( add_query_arg( 'advent_zi', max( 1, min( 24, $azi ) ), home_url( '/' ) ) . '#calendar-advent' ); ?>" target="_blank">deschide pe site ↗</a>
			</p>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="ssadv_save">
				<?php wp_nonce_field( 'ssadv_save' ); ?>
				<h2>General</h2>
				<table class="form-table" role="presentation">
					<tr><th>Activ pe site</th><td><label><input type="checkbox" name="s[activ]" value="1" <?php checked( $s['activ'] ); ?>> Arată calendarul vizitatorilor</label></td></tr>
					<tr><th>Anul</th><td><input type="number" name="s[an]" value="<?php echo esc_attr( $s['an'] ); ?>" min="2024" max="2100" class="small-text"> <span class="description">Ușile merg 1–24 decembrie din anul acesta.</span></td></tr>
					<tr><th>Deschidere automată</th><td><input type="number" name="s[auto]" value="<?php echo esc_attr( $s['auto'] ); ?>" min="0" max="120" class="small-text"> secunde după încărcare <span class="description">(0 = doar la click). O singură dată pe zi, doar dacă ușa zilei nu a fost deschisă, după ce s-a închis bannerul de cookies.</span></td></tr>
					<tr><th>Teaser</th><td><label><input type="checkbox" name="s[teaser]" value="1" <?php checked( $s['teaser'] ); ?>> Arată butonul și înainte de 1 decembrie (toate ușile încuiate, „mai sunt X zile”)</label></td></tr>
					<tr><th>Coș / checkout</th><td><label><input type="checkbox" name="s[ascunde_checkout]" value="1" <?php checked( $s['ascunde_checkout'] ); ?>> Nu arăta butonul în coș, checkout și contul meu</label></td></tr>
					<tr><th>Poziție buton</th><td><select name="s[pozitie]"><option value="stanga" <?php selected( $s['pozitie'], 'stanga' ); ?>>stânga-jos</option><option value="dreapta" <?php selected( $s['pozitie'], 'dreapta' ); ?>>dreapta-jos</option></select></td></tr>
					<tr><th>Banner cookies</th><td><input type="text" name="s[cookie_bar]" value="<?php echo esc_attr( $s['cookie_bar'] ); ?>" class="regular-text code"> <span class="description">Selectorul CSS al bannerului; deschiderea automată așteaptă să fie închis.</span></td></tr>
				</table>

				<h2>Cele 24 de zile</h2>
				<p class="description">Completați voi cupoanele în WooCommerce (reducere, produse, valabilitate doar în ziua respectivă). Aici puneți doar ce se afișează. Vizitatorii primesc conținutul unei zile abia în ziua ei.</p>
				<?php
				for ( $i = 1; $i <= self::N; $i++ ) :
					$d     = isset( $days[ $i ] ) && is_array( $days[ $i ] ) ? $days[ $i ] : array();
					$v     = function ( $k ) use ( $d ) {
						return isset( $d[ $k ] ) ? $d[ $k ] : '';
					};
					$done  = '' !== $v( 'mare' );
					$label = $done ? trim( $v( 'emoji' ) . ' ' . $v( 'mare' ) . ' ' . $v( 'mic' ) ) : 'necompletată';
					?>
					<details class="ssadv-day<?php echo $i === $azi ? ' is-today' : ''; ?>" <?php echo $i === $azi ? 'open' : ''; ?>>
						<summary>
							<b><?php echo (int) $i; ?> decembrie</b>
							<span class="<?php echo $done ? 'ssadv-ok' : 'ssadv-muted'; ?>"><?php echo esc_html( $label ); ?></span>
							<?php echo $i === $azi ? '<span class="ssadv-badge">azi</span>' : ''; ?>
						</summary>
						<div class="ssadv-grid">
							<?php
							foreach ( $f as $k => $meta ) :
								$name = 'd[' . $i . '][' . $k . ']';
								$val  = $v( $k );
								?>
								<label class="ssadv-f ssadv-f-<?php echo esc_attr( $k ); ?>"><span><?php echo esc_html( $meta[0] ); ?></span>
								<?php
								switch ( $meta[1] ) {
									case 'select':
										echo '<select name="' . esc_attr( $name ) . '"><option value="glob"' . selected( $val, 'glob', false ) . '>Glob</option><option value="cadou"' . selected( $val, 'cadou', false ) . '>Cadou</option></select>';
										break;
									case 'color':
										echo '<input type="color" name="' . esc_attr( $name ) . '" value="' . esc_attr( $val ? $val : '#c31c72' ) . '">';
										break;
									case 'textarea':
										echo '<textarea name="' . esc_attr( $name ) . '" rows="2" maxlength="' . (int) $meta[2] . '">' . esc_textarea( $val ) . '</textarea>';
										break;
									case 'url':
										echo '<input type="url" name="' . esc_attr( $name ) . '" value="' . esc_attr( $val ) . '" placeholder="https://sersimo.ro/...">';
										break;
									default:
										echo '<input type="text" name="' . esc_attr( $name ) . '" value="' . esc_attr( $val ) . '" maxlength="' . (int) $meta[2] . '">';
								}
								if ( 'cod' === $k ) {
									echo '<small>' . self::coupon_status( $val ) . '</small>'; // phpcs:ignore WordPress.Security.EscapeOutput
								}
								?>
								</label>
							<?php endforeach; ?>
						</div>
						<p><a href="<?php echo esc_url( add_query_arg( 'advent_zi', $i, home_url( '/' ) ) . '#calendar-advent' ); ?>" target="_blank">Previzualizează ziua <?php echo (int) $i; ?> pe site ↗</a> <span class="description">(salvează întâi)</span></p>
					</details>
				<?php endfor; ?>
				<?php submit_button( 'Salvează' ); ?>
			</form>
		</div>
		<style>
			.ssadv-day{background:#fff;border:1px solid #dcdcde;border-radius:6px;margin:0 0 6px;max-width:980px}
			.ssadv-day.is-today{border-color:#c31c72;box-shadow:0 0 0 1px #c31c72}
			.ssadv-day summary{cursor:pointer;padding:10px 14px;display:flex;gap:14px;align-items:center}
			.ssadv-day summary b{min-width:105px}
			.ssadv-day>p{margin:0;padding:0 14px 12px}
			.ssadv-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:10px 14px;padding:4px 14px 12px}
			.ssadv-f{display:flex;flex-direction:column;gap:3px}
			.ssadv-f>span{font-size:12px;color:#50575e}
			.ssadv-f input[type=text],.ssadv-f input[type=url],.ssadv-f select,.ssadv-f textarea{width:100%}
			.ssadv-f-text,.ssadv-f-link{grid-column:span 2}
			.ssadv-f input[type=color]{width:60px;height:32px;padding:2px}
			.ssadv-ok{color:#1a7f37}.ssadv-warn{color:#b45309}.ssadv-muted{color:#8c8f94}
			.ssadv-badge{background:#c31c72;color:#fff;border-radius:10px;padding:1px 8px;font-size:11px;text-transform:uppercase}
			@media(max-width:900px){.ssadv-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}
		</style>
		<?php
	}
}

SSADV_Plugin::init();
