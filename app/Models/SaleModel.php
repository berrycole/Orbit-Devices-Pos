<?php
namespace App\Models;
class SaleModel extends \CodeIgniter\Model
{
    protected $table='sales';
    protected $allowedFields=['product_id','customer_id','sold_by','quantity','total_price','created_at'];
    public function history()
    {
        return $this->select('sales.*, products.name AS product_name, customers.full_name AS customer_name, users.full_name AS staff_name')
            ->join('products','products.id=sales.product_id')->join('customers','customers.id=sales.customer_id','left')->join('users','users.id=sales.sold_by')->orderBy('sales.id','DESC');
    }
}
