<?php
/*
 * JETexpress Shipping Extension for Opencart 3.0.*.*
 * by Moonlay Technologies
 */

class ModelExtensionShippingJETexpress extends Model {
	function getQuote($address) {
		$this->load->language('extension/shipping/jetexpress');

		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "zone_to_geo_zone WHERE geo_zone_id = '" . (int)$this->config->get('shipping_jetexpress_geo_zone_id') . "' AND country_id = '" . (int)$address['country_id'] . "' AND (zone_id = '" . (int)$address['zone_id'] . "' OR zone_id = '0')");

		if (!$this->config->get('shipping_jetexpress_geo_zone_id')) {
			$status = true;
		} elseif ($query->num_rows) {
			$status = true;
		} else {
			$status = false;
		}

		$error = '';

		$client_key = $this->config->get('shipping_jetexpress_key');
		$origin = $this->config->get('shipping_jetexpress_origin');
		$origin_postcode = $this->config->get('shipping_jetexpress_origin_postcode');
		$destination = $address['city'];
		$destination_postcode = $address['postcode'];
		$is_insured = $this->config->get('shipping_jetexpress_insurance') == '1' ? true : false;
		$total_price = 0;
		$items = array();

		$quote_data = array();

		if ($status) {

			if ($address['iso_code_2'] == 'ID') {

				foreach ($this->cart->getProducts() as $key => $product) {

					$total_price += ($product['price']*$product['quantity']);

					$items[$key]['weight'] = (int)$product['weight'];
					$items[$key]['height'] = (int)$product['height'];
					$items[$key]['width']  = (int)$product['width'];
					$items[$key]['length'] = (int)$product['length'];
				}

				// body data
				$data = array(
    			'origin' => $origin,
					'OriginZipCode' => $origin_postcode,
					'destination' => $destination,
					'DestinationZipCode' => $destination_postcode,
					'isInsured' => $is_insured,
					'itemValue' => $this->currency->convert($total_price, 'IDR', $this->config->get('config_currency')),
					'items' => $items
				);

				$payload = json_encode($data);

				// echo '<script>';
  			// echo 'console.log('. json_encode( $data ) .')';
  			// echo '</script>';

				// prepare new cURL resource
				$curl = curl_init('http://api.jetexpress.co.id/v2/pricings');
				curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($curl, CURLINFO_HEADER_OUT, true);
				curl_setopt($curl, CURLOPT_POST, true);
				curl_setopt($curl, CURLOPT_POSTFIELDS, $payload);

				// set HTTP Header for POST request
				curl_setopt($curl, CURLOPT_HTTPHEADER, array(
    			'Content-Type: application/json',
    			'clientkey: ' . $client_key)
				);

				// submit the POST request
				$response = curl_exec($curl);

				// close cURL session handle
				curl_close($curl);

				// echo '<script>';
  			// echo 'console.log('. $response .')';
				// echo '</script>';

				if ($response) {
					$response_info = array();
					$response_parts = json_decode($response, true);

					$insurance_fee = $response_parts['insuranceFee'];
					$response_services = $response_parts['services'];

					// without insurance
					foreach ($response_services as $response_service) {
						$quote_data[$response_service['name']] = array(
						  'code'         => 'jetexpress.' .  $response_service['code'],
							'title'        => $response_service['name'],
							'cost'         => $this->currency->convert($response_service['totalFee'], 'IDR', $this->config->get('config_currency')),
							'tax_class_id' => $this->config->get('shipping_jetexpress_tax_class_id'),
							'text'         => $this->currency->format($this->tax->calculate($this->currency->convert($response_service['totalFee'], 'IDR', $this->session->data['currency']), $this->config->get('shipping_jetexpress_tax_class_id'), $this->config->get('config_tax')), $this->session->data['currency'], 1.0000000) . '<br /><span style="font-size: 1.1rem; font-weight: 600; color: dimgrey;">' . $response_service['estimatedDelivery'] . '</span>'
							);
					}

						// with insurance
					if($is_insured) {
						foreach ($response_services as $response_service) {
						$quote_data[$response_service['name'] . '_insuranced'] = array(
							'code'         => 'jetexpress.' .  $response_service['code'] . '_insuranced',
							'title'        => $response_service['name'],
							'cost'         => $this->currency->convert($response_service['totalFee'], 'IDR', $this->config->get('config_currency')) + $insurance_fee ,
							'tax_class_id' => $this->config->get('shipping_jetexpress_tax_class_id'),
							'text'         => $this->currency->format($this->tax->calculate($this->currency->convert($response_service['totalFee'] + $insurance_fee, 'IDR', $this->session->data['currency']), $this->config->get('shipping_jetexpress_tax_class_id'), $this->config->get('config_tax')), $this->session->data['currency'], 1.0000000) . ' <span style="font-size: 0.95rem; color: tomato; font-style: italic;">(inc. insurance)</span> <br /><span style="font-size: 1.1rem; font-weight: 600; color: dimgrey;">' . $response_service['estimatedDelivery'] . '</span>'
							);
						}
					}
				}
			}
		}

		$method_data = array();

		if($quote_data) {
			$method_data = array(
				'code'       => 'jetexpress',
				'title'      => "<span style='background-color:#CE1920; padding: 2px;
    margin-right: 5px;'><img src='http://www.jetexpress.co.id/Statics/images/logo.png' style='width: 30px;
    height: auto;' /></span>" . $this->language->get('text_title'),
				'quote'      => $quote_data,
				'sort_order' => $this->config->get('shipping_jetexpress_sort_order'),
				'error'      => $error
			);
		}

		return $method_data;
	}
}