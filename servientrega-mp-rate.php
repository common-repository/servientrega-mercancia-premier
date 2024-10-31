<?php
/*
* Plugin Name: Servientrega Mercancía Premier 
* Description: Tarifas para el producto Mercancía Premier de Servientrega
* Version: 1.0.2
* Author: Servientrega
* Author URI: https://www.servientrega.com/
* Text Domain: woocommerce-extension
*/

/**
 * Check if WooCommerce is active
 */

error_reporting(E_ERROR | E_WARNING | E_PARSE);
if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
//WC_Your_Shipping_Method
	function servientrega_mp_rate_method_init() {
		if ( ! class_exists( 'Servientrega_MercanciaPremier' ) ) {
			class Servientrega_MercanciaPremier extends WC_Shipping_Method {
				/**
				 * Constructor for your shipping class
				 *
				 * @access public
				 * @return void
				 */
				public function __construct($instanceId = null) {
					$this->id                 = 'servientregamercanciapremier'; // Id for your shipping method. Should be uunique.
					$this->instance_id = absint($instanceId);
					$this->method_title       = __( 'Mercancía Premier - Servientrega' );  // Title shown in admin
					$this->method_description = __( 'Producto Mercancía Premier de Servientrega' ); // Description shown in admin
					$this->supports           = array(
														'settings',
														'shipping-zones',
														'instance-settings',
														'global-instance',
													);						
					$this->enabled            = "yes"; // This can be added as an setting but for this example its forced enabled
					$this->title              = "Mercancía Premier de Servientrega"; // This can be added as an setting but for this example its forced.
					

					$this->init();
				}

				/**
				 * Init your settings
				 *
				 * @access public
				 * @return void
				 */
				function init() {
					// Load the settings API
					$this->init_form_fields(); // This is part of the settings API. Override the method to add your own settings
					//$this->init_settings(); // This is part of the settings API. Loads settings you previously init.

					// Save settings in admin if you have any defined
					//add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
				}

				/**
				 * calculate_shipping function.
				 *
				 * @access public
				 * @param mixed $package
				 * @return void
				 */
				public function calculate_shipping( $package = array()) {
					
					$chosen_payment_method = WC()->session->get('chosen_payment_method');
					
					$country = $package['destination']['country'];
					$state_destination = $package['destination']['state'];
				
					
				 
					$city_destination  = $package['destination']['city'];
					$city_destination = clean_string_rate($city_destination);
					$city_destination = clean_city_rate($city_destination);
					
					//$txt = $country ;
					
					
					if($country !== 'CO' || empty($state_destination))
						return apply_filters( 'woocommerce_shipping_' . $this->id . '_is_available', false, $package, $this );

					$name_state_destination = name_destination_rate($country, $state_destination);
						
						
						

					if (empty($name_state_destination))
						return apply_filters( 'woocommerce_shipping_' . $this->id . '_is_available', false, $package, $this );

					$address_destine = "$city_destination-$name_state_destination";
						
						
						
					
						
					$cities = include dirname(__FILE__) . '/CiudadesDane.php';

					$cod_dane = array_search($address_destine, $cities);
						
						
						
						
					if(!$cod_dane)
						$cod_dane = array_search($address_destine, clean_cities_rate($cities));

					if(!$cod_dane)
						return apply_filters( 'woocommerce_shipping_' . $this->id . '_is_available', false, $package, $this );
					
					
						 
					$mediotransporte="1";
					
					
						 
					$aereo=  include dirname(__FILE__) . '/DaneAereo.php';
					    
					if(in_array($cod_dane,$aereo)) $mediotransporte="2";
					
										
					$arrLogisticaCobro=  include dirname(__FILE__) . '/LogisticaCobro.php';
					
					$nohabilitaLogisticaCobro=in_array($address_destine,$arrLogisticaCobro);
					
					
					
					
					
					if( $chosen_payment_method =="cod" )
						{
							if($nohabilitaLogisticaCobro) return;
							$User="UserCobro";
							$Password="PasswordCobro";
							$CodSer="CodSerCobro";
							$envioConCobro="true";
						}
						else
						{
							$User="User";
							$Password="Password";
							$CodSer="CodSer";
							$envioConCobro="false";						
						}
						
					
					
					$url='http://web.servientrega.com:8058/cotizadorcorporativo/api/autenticacion/login';
					$response = wp_remote_post( $url, array(
						"body"=>array("login"=> get_option($User),
										"password"=> get_option($Password),
										"codFacturacion"=> get_option($CodSer)
										)
					)
					);
					
					$arrheader=array();
				
					if ( ! is_wp_error( $response ) )
					{
						$body_response=json_decode($response['body']);
						$token= $body_response->token;		
						$arrheader=array('Authorization' => 'Bearer '.$token);
						
					}
					
					$valor_cotizacion=0;
					
					$i=1;
					
					
					
					foreach ( $package['contents'] as $item_id => $item ) {
						
						
						$product = $item['data'];
						
						
						$peso=3;
						
						if($product->get_weight()>3)$peso=$product->get_weight();
						
						
						
						$arrCotizarEnvio= array(
								"IdProducto"=>"2",
								"NumeroPiezas"=>"1",
								"ValorDeclarado"=>$product->get_price(),
								"IdDaneCiudadOrigen"=>get_option("Des_CiudadOrigen"),
								"IdDaneCiudadDestino"=>$cod_dane,
								"EnvioConCobro"=>$envioConCobro,
								"FormaPago"=>"2",
								"TiempoEntrega"=>"1",
								"MedioTransporte"=>$mediotransporte,
								"NumRecaudo"=>"123456",
								"Piezas"=>array( array("Peso"=>$peso,
												"Largo"=>$product->get_length(),
												"Ancho"=>$product->get_width(),
												"Alto"=>$product->get_height()))
							
						);
						
						$url2='http://web.servientrega.com:8058/cotizadorcorporativo/api/Cotizacion';
						
						
						
						
						
						$response2 = wp_remote_post( $url2,array("headers"=>$arrheader,'body'=>$arrCotizarEnvio) );
						
						if ( is_wp_error( $response2 ) )
						{
							return;
						}
						
						
						$body_response2=json_decode($response2['body']);
						
						
						
						$valor_item=$body_response2->ValorTotal;
						
						if($valor_item<=0) return;
						$valor_cotizacion+=$item['quantity']*$valor_item;
						$i++;
					} 
					
					
				
					$rate = [
							'id' => $this->id,
							'label' => $this->title,
							'cost' => $valor_cotizacion,
							'package' => $package,
						];

					return $this->add_rate( $rate );
				}
			}
		}
	}

	add_action( 'woocommerce_shipping_init', 'servientrega_mp_rate_method_init' );

	function add_servientrega_mp_rate_method( $methods ) {
		$methods['servientregamercanciapremier'] = 'Servientrega_MercanciaPremier';
		return $methods;
	}
	
	function name_destination_rate($country, $state_destination)
    {
        $countries_obj = new WC_Countries();
        $country_states_array = $countries_obj->get_states();
		
	

        $name_state_destination = '';

        if(!isset($country_states_array[$country][$state_destination]))
            return $name_state_destination;

        $name_state_destination = $country_states_array[$country][$state_destination];
        $name_state_destination = clean_string_rate($name_state_destination);
        return short_name_location_rate($name_state_destination);
    }
	
	function short_name_location_rate($name_location)
    {
        if ( 'Valle del Cauca' === $name_location )
            $name_location =  'Valle';
		
		if ('San Andres y Providencia'=== $name_location )
            $name_location =  'Archipielago de San Andres';
		
		if ('Archipielago de San Andres, Providencia y Santa Catalina'=== $name_location )
            $name_location =  'Archipielago de San Andres';
		
		if('Distrito Capital de Bogota'=== $name_location)
            $name_location ='Cundinamarca';
		
		
        return $name_location;
    }

    function clean_string_rate($string)
    {
        $not_permitted = array ("á","é","í","ó","ú","Á","É","Í",
            "Ó","Ú","ñ");
        $permitted = array ("a","e","i","o","u","A","E","I","O",
            "U","n");
        $text = str_replace($not_permitted, $permitted, $string);
        return $text;
    }

    function clean_city_rate($city)
    {
		if ($city === 'Bogota D.C') $city='Bogota' ;
		if ($city === 'San andres isla') $city='San Andres' ;
		if ($city === 'Inirida') $city='Puerto inirida' ;
		
        Return $city ; 
    }

    function clean_cities_rate($cities)
    {
        foreach ($cities as $key => $value){
            $cities[$key] = clean_string_rate($value);
        }

        return $cities;
    }
	
		

/*****************************Refresco por  cambio de forma de pago********************************/	
	add_action( 'woocommerce_review_order_before_payment', 'payment_methods_trigger_update_checkout' );
	function payment_methods_trigger_update_checkout(){
    // jQuery code
    ?>
    <script type="text/javascript">
        (function($){
            $( 'form.checkout' ).on( 'change blur', 'input[name^="payment_method"]', function() {
                setTimeout(function(){
                    $(document.body).trigger('update_checkout');
                }, 250 );
            });
        })(jQuery);
    </script>
    <?php
}
add_action( 'woocommerce_checkout_update_order_review', 'refresh_shipping_methods' );
function refresh_shipping_methods( $post_data ){
    
    $payment_method = 'cod';
    $bool           = true;

    if ( WC()->session->get('chosen_payment_method') === $payment_method )
        $bool = false;

    // Mandatory to make it work with shipping methods
    foreach ( WC()->cart->get_shipping_packages() as $package_key => $package ){
        WC()->session->set( 'shipping_for_package_' . $package_key, $bool );
    }
    WC()->cart->calculate_shipping();
}
/****************************************************************************************************/
	
	add_filter( 'woocommerce_shipping_methods', 'add_servientrega_mp_rate_method' );
}