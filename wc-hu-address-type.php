<?php
/*
Plugin Name: WooCommerce Közterület Választó
Plugin URI: http://visztpeter.me
Author: Viszt Péter
Version: 1.0
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WC_HU_Address_Type_Selector {
	protected static $_instance = null;

	//Get main instance
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

  public function __construct() {

		//Validate address type on checkout
		add_action( 'woocommerce_after_checkout_validation', array( $this, 'address_type_validate' ), 10, 2);

		//Add new address type field to checkout
		add_filter( 'woocommerce_default_address_fields' , array( $this, 'adjust_address_field_layout' ) );

		//Display new address type format sitewide
		add_filter( 'woocommerce_my_account_my_address_formatted_address', array( $this, 'add_address_type'), 10, 3 );
		add_filter( 'woocommerce_localisation_address_formats', array( $this, 'display_address_type'), 10, 3 );
		add_action( 'woocommerce_admin_billing_fields', array( $this, 'display_address_type_in_admin' ) );
		add_action( 'woocommerce_admin_shipping_fields', array( $this, 'display_address_type_in_admin' ) );
		add_filter( 'woocommerce_formatted_address_replacements', array( $this, 'replace_address_type_in_address'), 10, 2);
		add_filter( 'woocommerce_order_formatted_billing_address', function( $address, $order ) {
			$address_type = $order->get_meta('_billing_address_type');
			$address['address_type'] = $address_type != '' ? $address_type : null;
			return $address;
		}, 10, 2 );

		add_filter( 'woocommerce_order_formatted_shipping_address', function( $address, $order ) {
			$address_type = $order->get_meta('_shipping_address_type');
			$address['address_type'] = $address_type != '' ? $address_type : null;
			return $address;
		}, 10, 2 );

		//Fix Billingo, Számlázz.hu and Barion(to add address_type)
		add_filter( 'woocommerce_barion_prepare_payment', array( $this, 'change_barion_billing_address'), 10, 2);
		add_filter( 'wc_billingo_plus_partner', array( $this, 'change_billingo_billing_address'), 10, 2);
		add_filter( 'wc_szamlazz_xml', array( $this, 'change_szamlazz_billing_address'), 10, 2);

	}

	//Validate address type on checkout
	public function address_type_validate($fields, $errors) {
		if($fields['billing_address_type'] && !in_array($fields['billing_address_type'], array_keys($this->get_address_types()))) {
			$errors->add( 'validation', esc_html__( 'Nem megfelelő közterület típus a számlázási adatoknál.', 'wc-hu-address-type') );
		}

		if($fields['shipping_address_type'] && !in_array($fields['shipping_address_type'], array_keys($this->get_address_types()))) {
			$errors->add( 'validation', esc_html__( 'Nem megfelelő közterület típus a szállítási adatoknál.', 'wc-hu-address-type') );
		}
	}

	//Add new address type field to checkout and rename existing address and address_2 fields
	public function adjust_address_field_layout($address_fields) {
		$address_fields['address_1']['class'][] = 'form-row-address-first';
		$address_fields['address_1']['label'] = __( 'Közterület', 'wc-hu-address-type' );
		$address_fields['address_1']['placeholder'] = __( 'Utcanév', 'wc-hu-address-type' );

		$address_fields['address_type']   = array(
			'label'        => __( 'Közterület jellege', 'wc-hu-address-type' ),
			'required'     => true,
			'class'        => array( 'form-row-address-middle' ),
			'priority'     => 71,
			'type' 				 => 'select',
			'options'			 => $this->get_address_types(),
			'default'			 => 'utca'
		);

		$address_fields['address_2']['class'][] = 'form-row-address-last';
		$address_fields['address_2']['placeholder'] = __( '', 'wc-hu-address-type' );
		$address_fields['address_2']['label'] = __( 'Házszám', 'wc-hu-address-type' );
		$address_fields['address_2']['label_class'] = array();
		$address_fields['address_2']['required'] = true;

		return $address_fields;
	}

	//Get a list of available valid address types
	public function get_address_types() {
		return array(
			'ut' => 'út',
			'utca' => 'utca',
			'akna' => 'akna',
			'akna-also' => 'akna-alsó',
			'akna-felso' => 'akna-felső',
			'alagut' => 'alagút',
			'alsorakpart' => 'alsórakpart',
			'arboretum' => 'arborétum',
			'autout' => 'autóút',
			'barakkepulet' => 'barakképület',
			'barlang' => 'barlang',
			'bejaro' => 'bejáró',
			'bekotout' => 'bekötőút',
			'banya' => 'bánya',
			'banyatelep' => 'bányatelep',
			'bastya' => 'bástya',
			'bastyaja' => 'bástyája',
			'csarda' => 'csárda',
			'csonakhazak' => 'csónakházak',
			'domb' => 'domb',
			'dulo' => 'dűlő',
			'dulok' => 'dűlők',
			'dulosor' => 'dűlősor',
			'duloterulet' => 'dűlőterület',
			'dulout' => 'dűlőút',
			'egyetemvaros' => 'egyetemváros',
			'egyeb' => 'egyéb',
			'elagazas' => 'elágazás',
			'emlekut' => 'emlékút',
			'erdeszhaz' => 'erdészház',
			'erdeszlak' => 'erdészlak',
			'erdo' => 'erdő',
			'erdosor' => 'erdősor',
			'fasor' => 'fasor',
			'fasora' => 'fasora',
			'felso' => 'felső',
			'fordulo' => 'forduló',
			'fomernokseg' => 'főmérnökség',
			'foter' => 'főtér',
			'fout' => 'főút',
			'fold' => 'föld',
			'gyar' => 'gyár',
			'gyartelep' => 'gyártelep',
			'gyarvaros' => 'gyárváros',
			'gyumolcsos' => 'gyümölcsös',
			'gat' => 'gát',
			'gatsor' => 'gátsor',
			'gatorhaz' => 'gátőrház',
			'hatarsor' => 'határsor',
			'hatarut' => 'határút',
			'hegy' => 'hegy',
			'hegyhat' => 'hegyhát',
			'hegyhat-dulo' => 'hegyhát dűlő',
			'hegyhat' => 'hegyhát',
			'koz' => 'köz',
			'hrsz' => 'hrsz',
			'hrsz2' => 'hrsz.',
			'haz' => 'ház',
			'hídfo' => 'hídfő',
			'iskola' => 'iskola',
			'jatszoter' => 'játszótér',
			'kapu' => 'kapu',
			'kastely' => 'kastély',
			'kert' => 'kert',
			'kertsor' => 'kertsor',
			'kerulet' => 'kerület',
			'kilato' => 'kilátó',
			'kioszk' => 'kioszk',
			'kocsiszín' => 'kocsiszín',
			'kolonia' => 'kolónia',
			'korzo' => 'korzó',
			'kulturpark' => 'kultúrpark',
			'kunyho' => 'kunyhó',
			'kor' => 'kör',
			'korter' => 'körtér',
			'korvasutsor' => 'körvasútsor',
			'korzet' => 'körzet',
			'korond' => 'körönd',
			'korut' => 'körút',
			'koz' => 'köz',
			'kut' => 'kút',
			'kultelek' => 'kültelek',
			'lakohaz' => 'lakóház',
			'lakokert' => 'lakókert',
			'lakonegyed' => 'lakónegyed',
			'lakopark' => 'lakópark',
			'lakotelep' => 'lakótelep',
			'lejto' => 'lejtő',
			'lejaro' => 'lejáró',
			'liget' => 'liget',
			'lepcso' => 'lépcső',
			'major' => 'major',
			'malom' => 'malom',
			'menedekhaz' => 'menedékház',
			'munkasszallo' => 'munkásszálló',
			'melyut' => 'mélyút',
			'muut' => 'műút',
			'oldal' => 'oldal',
			'orom' => 'orom',
			'park' => 'park',
			'parkja' => 'parkja',
			'parkolo' => 'parkoló',
			'part' => 'part',
			'pavilon' => 'pavilon',
			'piac' => 'piac',
			'piheno' => 'pihenő',
			'pince' => 'pince',
			'pincesor' => 'pincesor',
			'postafiok' => 'postafiók',
			'puszta' => 'puszta',
			'palya' => 'pálya',
			'palyaudvar' => 'pályaudvar',
			'rakpart' => 'rakpart',
			'repuloter' => 'repülőtér',
			'resz' => 'rész',
			'ret' => 'rét',
			'sarok' => 'sarok',
			'sor' => 'sor',
			'sora' => 'sora',
			'sportpalya' => 'sportpálya',
			'sporttelep' => 'sporttelep',
			'stadion' => 'stadion',
			'strandfurdo' => 'strandfürdő',
			'sugarut' => 'sugárút',
			'szer' => 'szer',
			'sziget' => 'sziget',
			'szivattyutelep' => 'szivattyútelep',
			'szallas' => 'szállás',
			'szallasok' => 'szállások',
			'szel' => 'szél',
			'szolo' => 'szőlő',
			'szolohegy' => 'szőlőhegy',
			'szolok' => 'szőlők',
			'sanc' => 'sánc',
			'savhaz' => 'sávház',
			'setany' => 'sétány',
			'tag' => 'tag',
			'tanya' => 'tanya',
			'tanyak' => 'tanyák',
			'telep' => 'telep',
			'temeto' => 'temető',
			'tere' => 'tere',
			'teto' => 'tető',
			'turistahaz' => 'turistaház',
			'teli-kikoto' => 'téli kikötő',
			'ter' => 'tér',
			'tomb' => 'tömb',
			'udvar' => 'udvar',
			'utak' => 'utak',
			'utca' => 'utca',
			'utcaja' => 'utcája',
			'vadaskert' => 'vadaskert',
			'vadaszhaz' => 'vadászház',
			'vasuti-megallo' => 'vasúti megálló',
			'vasuti-orhaz' => 'vasúti őrház',
			'vasutsor' => 'vasútsor',
			'vasutallomas' => 'vasútállomás',
			'vezetout' => 'vezetőút',
			'villasor' => 'villasor',
			'vagohíd' => 'vágóhíd',
			'var' => 'vár',
			'varkoz' => 'várköz',
			'varos' => 'város',
			'vízmu' => 'vízmű',
			'volgy' => 'völgy',
			'zsilip' => 'zsilip',
			'zug' => 'zug',
			'allat-es-nov-kert' => 'állat és növ.kert',
			'allomas' => 'állomás',
			'arnyek' => 'árnyék',
			'arok' => 'árok',
			'atjaro' => 'átjáró',
			'orhaz' => 'őrház',
			'orhazak' => 'őrházak',
			'orhazlak' => 'őrházlak',
			'ut' => 'út',
			'utja' => 'útja',
			'utorhaz' => 'útőrház',
			'udulo' => 'üdülő',
			'udulo-part' => 'üdülő-part',
			'udulo-sor' => 'üdülő-sor',
			'udulo-telep' => 'üdülő-telep',
		);

	}

	//Display address type in my account
	public function add_address_type( $args, $customer_id, $name ) {
		$address_types = $this->get_address_types();
		$address_type = get_user_meta( $customer_id, $name . '_address_type', true );

		if($address_type) {
			$args['address_type'] = $address_types[$address_type];
		} else {
			$args['address_type'] = '';
		}
		return $args;
	}

	//Format address type in formatted address
	public function display_address_type($formats) {
		foreach ( $formats as $key => &$format ) {
			$format = str_replace('{address_1}', '', $format);
			$format = str_replace('{address_2}', '{address_1} {address_type} {address_2}', $format);
		}
		return $formats;
	}

	//format address type in admin(editable)
	public function display_address_type_in_admin($billing_fields){
		$billing_fields['address_type'] = array(
			'label' => __( 'Közterület jellege', 'wc-szamlazz' ),
			'type' => 'select',
			'show' => false,
			'options' => $this->get_address_types()
		);
		return $billing_fields;
	}

	//Format address type in formatted address
	public function replace_address_type_in_address( $replacements, $args ){
		$replacements['{address_type}'] = '';
		$address_types = $this->get_address_types();
		if(isset($args['address_type']) && !empty($args['address_type'])) {
			$replacements['{address_type}'] = $address_types[$args['address_type']];
		}
		return $replacements;
	}

	//Helper function to get address for billing and payment extensions
	public function get_new_formatted_address($form = 'billing', $order) {
		$address_types = $this->get_address_types();
		$address_type = $order->get_meta('_'.$form.'_address_type');
		$address_label = $address_types[$address_type];
		$address = '';

		if($form == 'billing') {
			$address = $order->get_billing_address_1().' '.$address_label.' '.$order->get_billing_address_2();
		} else if($form == 'shipping') {
			$address = $order->get_shipping_address_1().' '.$address_label.' '.$order->get_shipping_address_2();
		}

		return $address;
	}

	//Fix Barion
	public function change_barion_billing_address($paymentRequest, $order) {
		$address_types = $this->get_address_types();

		//Change shipping address
		$paymentRequest->ShippingAddress->Street = $this->get_new_formatted_address('shipping', $order);
		$paymentRequest->ShippingAddress->Street2 = '';

		//Change billing address
		$paymentRequest->BillingAddress->Street = $this->get_new_formatted_address('billing', $order);
		$paymentRequest->BillingAddress->Street2 = '';

		return $paymentRequest;
	}

	//Fix Billingo
	public function change_billingo_billing_address($clientData, $order) {
		$clientData['address']['address'] = $this->get_new_formatted_address('billing', $order);
		return $clientData;
	}

	//Fix Számlázz.hu
	public function change_szamlazz_billing_address($szamla, $order) {
		$szamla->vevo->cim = $this->get_new_formatted_address('billing', $order);
		return $szamla;
	}

}

//Initialize
WC_HU_Address_Type_Selector::instance();
