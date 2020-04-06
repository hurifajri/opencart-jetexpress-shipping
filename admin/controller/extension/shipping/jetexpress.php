<?php
/*
 * JETexpress Shipping Extension for Opencart 3.0.*.*
 * by Moonlay Technologies
 */

class ControllerExtensionShippingJETexpress extends Controller {

	private $error = array();

	public function index() {

		// Load language file for this module
		$this->load->language('extension/shipping/jetexpress');

		// Set title from language file $_['heading_title'] string
		$this->document->setTitle($this->language->get('heading_title'));

		// Load settings model and/or other models if available
		$this->load->model('setting/setting');

		// Save settings if user has submitted admin form (ie if save button is clicked)
		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->model_setting_setting->editSetting('shipping_jetexpress', $this->request->post);

			$this->session->data['success'] = $this->language->get('text_success');

			$this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=shipping', true));
		}

		// This creates an error message. The error['warning'] variable is set by the call to function validate() in this controller (below)
		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		if (isset($this->error['key'])) {
			$data['error_key'] = $this->error['key'];
		} else {
			$data['error_key'] = '';
		}

		if (isset($this->error['origin'])) {
			$data['error_origin'] = $this->error['origin'];
		} else {
			$data['error_origin'] = '';
		}

		if (isset($this->error['origin_postcode'])) {
			$data['error_origin_postcode'] = $this->error['origin_postcode'];
		} else {
			$data['error_origin_postcode'] = '';
		}

		// Setup breadcrumb trail
		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_extension'),
			'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=shipping', true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/shipping/jetexpress', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['action'] = $this->url->link('extension/shipping/jetexpress', 'user_token=' . $this->session->data['user_token'], true);

		$data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=shipping', true);

		if (isset($this->request->post['shipping_jetexpress_key'])) {
			$data['shipping_jetexpress_key'] = $this->request->post['shipping_jetexpress_key'];
		} else {
			$data['shipping_jetexpress_key'] = $this->config->get('shipping_jetexpress_key');
		}

		if (isset($this->request->post['shipping_jetexpress_origin'])) {
			$data['shipping_jetexpress_origin'] = $this->request->post['shipping_jetexpress_origin'];
		} else {
			$data['shipping_jetexpress_origin'] = $this->config->get('shipping_jetexpress_origin');
		}

		if (isset($this->request->post['shipping_jetexpress_origin_postcode'])) {
			$data['shipping_jetexpress_origin_postcode'] = $this->request->post['shipping_jetexpress_origin_postcode'];
		} else {
			$data['shipping_jetexpress_origin_postcode'] = $this->config->get('shipping_jetexpress_origin_postcode');
		}

		if (isset($this->request->post['shipping_jetexpress_insurance'])) {
			$data['shipping_jetexpress_insurance'] = $this->request->post['shipping_jetexpress_insurance'];
		} else {
			$data['shipping_jetexpress_insurance'] = $this->config->get('shipping_jetexpress_insurance');
		}

		if (isset($this->request->post['shipping_jetexpress_tax_class_id'])) {
			$data['shipping_jetexpress_tax_class_id'] = $this->request->post['shipping_jetexpress_tax_class_id'];
		} else {
			$data['shipping_jetexpress_tax_class_id'] = $this->config->get('shipping_jetexpress_tax_class_id');
		}

		$this->load->model('localisation/tax_class');

		$data['tax_classes'] = $this->model_localisation_tax_class->getTaxClasses();

		if (isset($this->request->post['shipping_jetexpress_geo_zone_id'])) {
			$data['shipping_jetexpress_geo_zone_id'] = $this->request->post['shipping_jetexpress_geo_zone_id'];
		} else {
			$data['shipping_jetexpress_geo_zone_id'] = $this->config->get('shipping_jetexpress_geo_zone_id');
		}

		$this->load->model('localisation/geo_zone');

		$data['geo_zones'] = $this->model_localisation_geo_zone->getGeoZones();

		if (isset($this->request->post['shipping_jetexpress_status'])) {
			$data['shipping_jetexpress_status'] = $this->request->post['shipping_jetexpress_status'];
		} else {
			$data['shipping_jetexpress_status'] = $this->config->get('shipping_jetexpress_status');
		}

		if (isset($this->request->post['shipping_jetexpress_sort_order'])) {
			$data['shipping_jetexpress_sort_order'] = $this->request->post['shipping_jetexpress_sort_order'];
		} else {
			$data['shipping_jetexpress_sort_order'] = $this->config->get('shipping_jetexpress_sort_order');
		}

		// Choose which template file will be used to display this request
		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		// Send output
		$this->response->setOutput($this->load->view('extension/shipping/jetexpress', $data));
	}

	// Ensure that settings chosen by admin user are allowed/valid
	protected function validate() {
		if (!$this->user->hasPermission('modify', 'extension/shipping/jetexpress')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		if (empty($this->request->post['shipping_jetexpress_key'])) {
			$this->error['key'] = $this->language->get('error_key');
		}

		if (empty($this->request->post['shipping_jetexpress_origin'])) {
			$this->error['origin'] = $this->language->get('error_origin');
		}

		if (!preg_match('/^$|^[0-9]{5}$/', $this->request->post['shipping_jetexpress_origin_postcode'])) {
			$this->error['origin_postcode'] = $this->language->get('error_origin_postcode');
		}

		return !$this->error;
	}
}