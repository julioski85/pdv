<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Api extends MY_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->admin_model('sales_model');
   
    }


    public function sales_summary() {

    $start_date = $this->input->get('start_date');
    $end_date = $this->input->get('end_date');

    $summaryData = $this->getSalesSummaryData($start_date, $end_date);
    
    $this->output
        ->set_content_type('application/json')
        ->set_output(json_encode($summaryData));
}

private function getSalesSummaryData($start_date, $end_date) {
    $this->db->select('w.name AS warehouse_name, COUNT(s.id) as total_sales, SUM(s.grand_total) as total_revenue')
             ->from('sma_sales s')
             ->join('sma_warehouses w', 's.warehouse_id = w.id')
             ->group_start()
                 ->where('s.date >=', $start_date)
                 ->where('s.date <=', $end_date)
             ->group_end()
             ->group_by('w.id');

    $this->db->order_by('total_sales', 'DESC');

    return $this->db->get()->result_array();
}


public function sales() {

    $page = $this->input->get('page') ?: 1; // Obtiene la página de los parámetros GET, o usa 1 por defecto
    $page_size = $this->input->get('page_size') ?: 100; // Obtiene el tamaño de página de los parámetros GET, o usa 100 por defecto
    $start_date = $this->input->get('start_date');
    $end_date = $this->input->get('end_date');

    $offset = ($page - 1) * $page_size; // Calcula el offset para la consulta

    $salesData = $this->getMassSalesData($page_size, $offset, $start_date, $end_date);
    $totalSales = $this->getTotalSalesCount($start_date, $end_date);
    $totalPages = ceil($totalSales / $page_size);

    $this->output
        ->set_content_type('application/json')
        ->set_output(json_encode([
            'data' => $salesData,
            'totalRecords' => $totalSales, 
            'totalPages' => $totalPages,
            'currentPage' => (int)$page,
            'pageSize' => (int)$page_size
        ]));
}

private function getMassSalesData($page_size, $offset, $start_date = null, $end_date = null) {
    $this->db->select('w.name AS warehouse_name, s.date, s.id, s.grand_total')
             ->from('sma_sales s')
             ->join('sma_warehouses w', 's.warehouse_id = w.id');

    if ($start_date) {
        $this->db->where('s.date >=', $start_date);
    }

    if ($end_date) {
        $this->db->where('s.date <=', $end_date);
    }

    $this->db->order_by('s.date', 'DESC')
             ->limit($page_size, $offset);

    return $this->db->get()->result_array();
}

private function getTotalSalesCount($start_date = null, $end_date = null) {
    $this->db->from('sma_sales s')
             ->join('sma_warehouses w', 's.warehouse_id = w.id');

    if ($start_date) {
        $this->db->where('s.date >=', $start_date);
    }

    if ($end_date) {
        $this->db->where('s.date <=', $end_date);
    }

    return $this->db->count_all_results();
}

public function getProducts() {
    $productsData = $this->getAllProductData();

    $this->output
        ->set_content_type('application/json')
        ->set_output(json_encode($productsData));
}

private function getAllProductData() {
    $this->db->select('*') // Selecciona todas las columnas
             ->from('sma_products'); // Asume que tus productos están en 'sma_products'

    return $this->db->get()->result_array();
}




}