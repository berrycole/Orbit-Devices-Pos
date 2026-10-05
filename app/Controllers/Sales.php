<?php
namespace App\Controllers;
use App\Models\{ProductModel,CustomerModel,SaleModel};
use App\Libraries\SaleRecorder;
class Sales extends BaseController
{
    public function index() { $model=(new SaleModel())->history(); return view('sales',['title'=>'Sales history','active'=>'sales','rows'=>$model->paginate(20),'pager'=>$model->pager]); }
    public function create() { return view('terminal',['title'=>'Sales terminal','active'=>'terminal','products'=>(new ProductModel())->where('archived',0)->orderBy('name')->findAll(),'customers'=>(new CustomerModel())->orderBy('full_name')->findAll()]); }
    public function store()
    {
        $data=$this->request->getPost(['product_id','customer_id','quantity']);
        if(!$this->validateData($data,['product_id'=>'required|is_natural_no_zero','customer_id'=>'permit_empty|is_natural_no_zero','quantity'=>'required|is_natural_no_zero|less_than_equal_to[1000000]'])) return redirect()->to(site_url('sales/new'))->with('error','Select a product and enter a positive whole-number quantity.');
        try { $id=(new SaleRecorder(db_connect()))->record((int)$data['product_id'],$data['customer_id']?(int)$data['customer_id']:null,(int)session('user_id'),(int)$data['quantity']); }
        catch (\DomainException $e) { return redirect()->to(site_url('sales/new'))->with('error',$e->getMessage()); }
        catch (\Throwable $e) { log_message('error','Sale failed: '.$e->getMessage()); return redirect()->to(site_url('sales/new'))->with('error','Sale could not be recorded. No stock was deducted. Please try again.'); }
        return redirect()->to(site_url('sales'))->with('success','Sale #'.str_pad((string)$id,5,'0',STR_PAD_LEFT).' recorded. Inventory updated.');
    }
}
