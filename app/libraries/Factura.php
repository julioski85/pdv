<?php

defined('BASEPATH') or exit('No direct script access allowed');


use Facturama\Client;
use function GuzzleHttp\json_encode;

class Factura
{
    private $facturama;

    public $ci;

    public function __construct()
    {
        $this->ci =& get_instance();
        $this->ci->config->load('factura');
        $api_key = $this->ci->config->item('facturama_api_key');  
        $api_secret = $this->ci->config->item('facturama_api_secret');
        $base_url = $this->ci->config->item('facturama_base_url');
        $this->facturama = new Client($api_key,$api_secret);
        $this->facturama->setApiUrl($base_url);
    }

    public function getPeriodicity() {
      return ["01" => 'Diario',
              "02" => 'Semanal',
              "03" => 'Quincenal',
              "04" => 'Mensual',
              "05" => 'Bimestral'];  
    }
    public function getMonths() {
      return ["01" => 'Enero',
              "02" => 'Febrero',
              "03" => 'Marzo',
              "04" => 'Abril',
              "05" => 'Mayo',
              "06" => 'Junio',
              "07" => 'Julio',
              "08" => 'Agosto',
              "09" => 'Septiembre',
              "10" => 'Octubre',
              "11" => 'Noviembre',
              "12" => 'Diciembre',
              "13" => 'Enero-Febrero',
              "14" => 'Marzo-Abril',
              "15" => 'Mayo-Junio',
              "16" => 'Julio-Agosto',
              "17" => 'Septiembre-Octubre',
              "18" => 'Noviembre-Diciembre'];  
    }
    public function getRegimenesFiscales(){
        $res = $this->facturama->get('catalogs/FiscalRegimens');
        $result = [];
        foreach($res as $resunit){
            log_message("error",json_encode($resunit));
            $result[$resunit->Value] = $resunit->Name;
        }
        return $result;
    }
    public function getUsoDeCfdi(){
        $res = $this->facturama->get('catalogs/CfdiUses');
        $result = [];
        foreach($res as $resunit){
            $result[$resunit->Value] = $resunit->Name;
        }
        return $result;
    }
    public function getMetodoDePago(){
        $res = $this->facturama->get('catalogs/PaymentMethods');
        $result = [];
        foreach($res as $resunit){
            $result[$resunit->Value] = $resunit->Name;
        }
        return $result;
    }
    public function getFormaDePago(){
        $res = $this->facturama->get('catalogs/PaymentForms');
        $result = [];
        foreach($res as $resunit){
            $result[$resunit->Value] = $resunit->Name;
        }
        return $result;
    }

    public function createCfdi($biller_id,$customer_id,$products,$forma_de_pago,$metodo_de_pago,$uso_de_cfdi){
        $this->ci->load->admin_model("companies_model");
        $issuer = $this->ci->companies_model->getCompanyByID($biller_id);
        $reciver = $this->ci->companies_model->getCompanyByID($customer_id); 
        $this->ci->load->admin_model("products_model"); 
        $items = [];
        foreach($products as $product){
            $product_detail= $this->ci->products_model->getProductById($product["product_id"]);
            $precio_total =$product["subtotal"];
            $porcentaje_impuesto = $this->ci->sma->formatDecimal(floatval($product["tax"]) / 100.00, 2);
            $precio_base = $precio_total / (1 + $porcentaje_impuesto);
            $precio_impuestos = $precio_total - $precio_base;
            $item_details = [
                'Quantity' => $product["quantity"],
                'ProductCode' => $product_detail->clave_sat,
                'UnitCode' => $product["product_unit_code"],
                'Description' => strip_tags($product_detail->product_details),
                'UnitPrice' => $this->ci->sma->formatDecimal($product["net_unit_price"],2),
                'Subtotal' => $this->ci->sma->formatDecimal($precio_base, 2) ,
                "TaxObject"=> "02",
                'Taxes' => [
                   [
                       'Total' =>  $this->ci->sma->formatDecimal($product["item_tax"]),
                       'Name' => 'IVA',
                       'Base' => $this->ci->sma->formatDecimal($precio_base, 2),
                       'Rate' => $porcentaje_impuesto,
                       'IsRetention' => "false",
                   ],
                ],
                'Total' => $this->ci->sma->formatDecimal($product["subtotal"],2),
            ];
            array_push($items,$item_details);
        } 
        $params = [
            "Issuer" =>
            [
                "Rfc"=> $issuer->rfc,
                "Name"=> $issuer->name,
                "FiscalRegime"=> $issuer->regimen_fiscal,
            ],
            "Receiver" =>
            [
                "Rfc"=> $reciver->rfc,
                "CfdiUse"=> $uso_de_cfdi,
                "Name"=> $reciver->name,
                "FiscalRegime"=> $reciver->regimen_fiscal,
                "TaxZipCode"=> $reciver->postal_code
            ],
            "CfdiType"=> "I",
            "NameId"=> "1",
            "Folio"=> uniqid(),
            "ExpeditionPlace"=> $issuer->postal_code,
            "PaymentForm"=> $forma_de_pago,
            "PaymentMethod"=> $metodo_de_pago,
            'Items' => $items,    
        ];
        try{
            $result = $this->facturama->post('api-lite/3/cfdis', $params);
            return $result->Id;
            ['status' => 1, 'id' => $result->Id];
        }catch(Exception $e){
            if ($e->getPrevious() !== null) {
                $msg = explode(";", $e->getPrevious()->getMessage());
            } else {
                $msg = array($e->getMessage());
            }
            return ['status' => 0, 'msg' => $msg];
        }
    }

    public function createCfdiForOldSale($sale_id,$forma_de_pago,$metodo_de_pago,$uso_de_cfdi){
        $this->ci->load->admin_model("sales_model");
        $sale = $this->ci->sales_model->getInvoiceByID($sale_id);
        $this->ci->load->admin_model("companies_model");
        $issuer = $this->ci->companies_model->getCompanyByID($sale->biller_id);
        $reciver = $this->ci->companies_model->getCompanyByID($sale->customer_id);
        $products = $this->ci->sales_model->getAllInvoiceItems($sale_id);

        if( !$sale->factura_id == "" ){
            return ['status' => 0, 'msg' => ["Ya esta facturado"]];
        }
        $this->ci->load->admin_model("products_model");
        $items = [];
        foreach($products as $product){
            $product_detail= $this->ci->products_model->getProductById($product->product_id);
            $precio_total = $this->ci->sma->formatDecimal($product->subtotal,2);
            $porcentaje_impuesto = $this->ci->sma->formatDecimal(floatval($product->tax) / 100.00, 2);
            $precio_base = $precio_total / (1 + $porcentaje_impuesto);
            $precio_impuestos = $precio_total - $precio_base;
            $item_details = [
                'Quantity' => $this->ci->sma->formatDecimal($product->quantity,2),
                'ProductCode' => $product_detail->clave_sat,
                'UnitCode' => $product->product_unit_code,
                'Description' => strip_tags($product_detail->product_details),
                'UnitPrice' => $this->ci->sma->formatDecimal($product->net_unit_price,2),
                'Subtotal' => $this->ci->sma->formatDecimal($precio_base, 2) , 
                "TaxObject"=> "02",
                'Taxes' => [
                   [
                       'Total' => $this->ci->sma->formatDecimal($precio_impuestos,2),
                       'Name' => 'IVA',
                       'Base' => $this->ci->sma->formatDecimal($precio_base, 2),
                       'Rate' => $porcentaje_impuesto,
                       'IsRetention' => "false",
                   ],
                ],
                'Total' => $this->ci->sma->formatDecimal($product->subtotal,2),
            ];
            array_push($items,$item_details);
        }
        $params = [
            "Issuer" =>
            [
                "Rfc"=> $issuer->rfc,
                "Name"=> $issuer->name,
                "FiscalRegime"=> $issuer->regimen_fiscal,
            ],
            "Receiver" =>
            [
                "Rfc"=> $reciver->rfc,
                "CfdiUse"=> $uso_de_cfdi,
                "Name"=> $reciver->name,
                "FiscalRegime"=> $reciver->regimen_fiscal,
                "TaxZipCode"=> $reciver->postal_code
            ],
            "CfdiType"=> "I",
            "NameId"=> "1",
            "Folio"=> uniqid(),
            "ExpeditionPlace"=> $issuer->postal_code,
            "PaymentForm"=> $forma_de_pago,
            "PaymentMethod"=> $metodo_de_pago,
            'Items' => $items,
        ];
        try{
            $result = $this->facturama->post('api-lite/3/cfdis', $params);
            $result->Id;
            $this->ci->sales_model->updateFactura($sale_id,$result->Id);
            return ['status' => 1, 'msg' => ''];
        }catch(Exception $e){
            if ($e->getPrevious() !== null) {
                $msg = explode(";", $e->getPrevious()->getMessage());
            } else {
                $msg = array($e->getMessage());
            }
            return ['status' => 0, 'msg' => $msg];
        }        
    }
 
    public function createCfdiGlobal($sale_ids,$periodicity,$months,$year,$biller_id,$payment_form){
        $this->ci->load->admin_model("sales_model");
        $this->ci->load->admin_model("products_model");
        $totalByProduct = array();
        foreach ($sale_ids as $sale_id) {
          $sale = $this->ci->sales_model->getInvoiceByID($sale_id);
          if($sale->factura_id!="")return ['status' => 0, 'msg' => ["Se selecciono una venta ya facturada"]];  
            $products = $this->ci->sales_model->getAllInvoiceItems($sale->id);
            foreach ($products as $product) {
            $productId = $product->product_id;
            $productQuantity = $product->quantity;
            if (isset($totalByProduct[$productId])) 
              $totalByProduct[$productId] += $productQuantity;
            else 
              $totalByProduct[$productId] = $productQuantity;
            }
        } 
        log_message("ERROR",json_encode($totalByProduct));
        $items = [];
        foreach ($totalByProduct as $productId => $quantity) {
          $product= $this->ci->products_model->getProductById($productId);
          $precio_total = $this->ci->sma->formatDecimal($product->price * $quantity,2);
          $tax_rate = $this->ci->site->getTaxRateByID($product->tax_rate)->rate;
          $porcentaje_impuesto = $this->ci->sma->formatDecimal(floatval($tax_rate) / 100.00, 2);
          $unit_price = $product->price / (1 + $porcentaje_impuesto);
          $precio_base = $precio_total / (1 + $porcentaje_impuesto);
          $precio_impuestos = $precio_total - $precio_base;
          $item_details = [
              'Quantity' => $this->ci->sma->formatDecimal($quantity,2),
              'ProductCode' => $product->clave_sat,
              'UnitCode' => $this->ci->site->getUnitByID($product->unit)->code,
              'Description' => strip_tags($product->product_details),
              'UnitPrice' => $this->ci->sma->formatDecimal($unit_price, 2),
              'Subtotal' => $this->ci->sma->formatDecimal($precio_base, 2) , 
              "TaxObject"=> "02",
              'Taxes' => [
                 [
                     'Total' => $this->ci->sma->formatDecimal($precio_impuestos,2),
                     'Name' => 'IVA',
                     'Base' => $this->ci->sma->formatDecimal($precio_base, 2),
                     'Rate' => $porcentaje_impuesto,
                     'IsRetention' => "false",
                 ],
              ],
              'Total' => $this->ci->sma->formatDecimal($precio_total,2),
          ];
          array_push($items,$item_details);
        }
        $this->ci->load->admin_model("companies_model");
        $issuer = $this->ci->companies_model->getCompanyByID($biller_id);
        $params = [
            "Issuer"=>
            [
                "Rfc"=> $issuer->rfc,
                "Name"=> $issuer->name,
                "FiscalRegime"=> $issuer->regimen_fiscal,
            ],
            "Receiver"=>
            [
             "Rfc" => "XAXX010101000",
		         "CfdiUse"=> "S01",
		         "Name"=> "PUBLICO EN GENERAL",
             "FiscalRegime"=> "616",
             "TaxZipCode" => $issuer->postal_code 
            ],
            "CfdiType"=> "I",
            "NameId"=> "1",
            "Folio"=> uniqid(),
            "ExpeditionPlace"=> $issuer->postal_code,
            "PaymentForm"=> $payment_form,
            "PaymentMethod"=> "PUE",
            'Items' => $items,
            "GlobalInformation"=> [
		          "Periodicity"=> $periodicity,
		          "Months"=> $months,
		          "Year"=> $year
            ]
        ];
        try{
            $result = $this->facturama->post('api-lite/3/cfdis', $params);
            $result->Id;
            foreach ($sale_ids as $sale_id)
              $this->ci->sales_model->updateFactura($sale_id,$result->Id);
            return ['status' => 1, 'msg' => ''];
        }catch(Exception $e){
            if ($e->getPrevious() !== null) {
                $msg = explode(";", $e->getPrevious()->getMessage());
            } else {
                $msg = array($e->getMessage());
            }
            return ['status' => 0, 'msg' => $msg];
        }
    }
    public function send_email($sale_id){
        $this->ci->load->admin_model("sales_model");
        $sale = $this->ci->sales_model->getInvoiceByID($sale_id);

        if( $sale->factura_id == "" ){
            return ['status' => 0, 'msg' => ["No esta facturado"]];
        }
        $this->ci->load->admin_model("companies_model");
        $reciver = $this->ci->companies_model->getCompanyByID($sale->customer_id);
        $body = [];
        $params = [
        'cfdiType' => 'issuedLite',
        'cfdiId' => $sale->factura_id,
        'email' => $reciver->email,
        ];
        try{
            $result = $this->facturama->post('cfdi', $body, $params);
           return ['status' => 1, 'msg' => ''];
        }catch(Exception $e){
           if ($e->getPrevious() !== null) {
                $msg = explode(";", $e->getPrevious()->getMessage());
            } else {
                $msg = array($e->getMessage());
            }
            return ['status' => 0, 'msg' => $msg];
 
        }        
    }
    
   public function cancel_bill($sale_id){
        
        log_message("ERROR","enter cancel bill");
        $this->ci->load->admin_model("sales_model");
        $sale = $this->ci->sales_model->getInvoiceByID($sale_id);
        if( $sale->factura_id == "" ){
            return ['status' => 0, 'msg' => ["No esta facturado"]];
        }
        $sales = $this->ci->sales_model->getInvoicesByFacturaId($sale->factura_id);
        try{
           $result = $this->facturama->delete('api-lite/cfdis/'.$sale->factura_id, ['motive'=>'02','uuidReplacement'=>'null']);
           foreach($sales as $s){
             
             log_message("ERROR","enter foreach");
             log_message("ERROR","SALE_ID:".$s->id);
             $this->ci->sales_model->updateFactura($s->id,"");
          }
           return ['status' => 1, 'msg' => ''];
        }catch(Exception $e){
           if ($e->getPrevious() !== null) {
                $msg = explode(";", $e->getPrevious()->getMessage());
            } else {
                $msg = array($e->getMessage());
            }
            return ['status' => 0, 'msg' => $msg];
 
        }        
    }

    public function get_cfdi($id){
        try{
            $result = $this->facturama->get('api-lite/cfdis/' .$id );
            return $result->Complement->TaxStamp;
        }
        catch(Exception $e){
            return false;
       }
    }
    public function new_csd($rfc,$password,$certificate,$key){
        $params = [
            'Rfc' => $rfc,
            'Certificate' => $certificate,
            'PrivateKey' => $key,
            'PrivateKeyPassword' => $password
        ];
        try{
            $this->facturama->post('api-lite/csds', $params );
             
            return true;
        }
        catch(Exception $e){
           if ($e->getPrevious() !== null) {
              $message =  $e->getPrevious()->getMessage();
               if($message == "Ya existe un CSD asociado a este RFC.") {
                return true;
        }
            }
            return false;
        }
    }

    public function delete_csd($rfc){

        try{
            $this->facturama->delete('api-lite/csds/' .$rfc );
            return true;
        }
        catch(Exception $e){
            return false;
        }
    }
}
