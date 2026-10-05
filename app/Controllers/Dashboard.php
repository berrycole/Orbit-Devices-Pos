<?php
namespace App\Controllers;
use App\Models\{ProductModel,CustomerModel,SaleModel};
class Dashboard extends BaseController
{
    public function index()
    {
        return view('dashboard',['title'=>'Overview','active'=>'dashboard','revenue'=>(new SaleModel())->selectSum('total_price')->first()['total_price'] ?? 0,'saleCount'=>(new SaleModel())->countAllResults(),'products'=>(new ProductModel())->where('archived',0)->countAllResults(),'customers'=>(new CustomerModel())->countAllResults(),'recent'=>(new SaleModel())->history()->findAll(5),'lowStock'=>(new ProductModel())->where('archived',0)->where('stock_quantity <=',5)->orderBy('stock_quantity')->findAll(5)]);
    }
}
