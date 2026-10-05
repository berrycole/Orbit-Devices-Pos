<?php
namespace App\Libraries;
use CodeIgniter\Database\BaseConnection;
class SaleRecorder
{
    public function __construct(private BaseConnection $db) {}
    public function record(int $productId, ?int $customerId, int $staffId, int $quantity): int
    {
        if ($quantity<1 || $quantity>1000000) throw new \DomainException('Enter a quantity between 1 and 1,000,000.');
        $this->db->transBegin();
        try {
            // The conditional UPDATE acquires a write lock before price is read.
            // Concurrent sales serialize here; insufficient stock never gets decremented.
            $this->db->table('products')->where('id',$productId)->where('archived',0)->where('stock_quantity >=',$quantity)->set('stock_quantity','stock_quantity - '.$quantity,false)->update();
            if ($this->db->affectedRows()!==1) throw new \DomainException('Sale rejected: the product is unavailable or the quantity exceeds available stock.');
            $product=$this->db->table('products')->where('id',$productId)->get()->getRowArray();
            if ($customerId && !$this->db->table('customers')->where('id',$customerId)->countAllResults()) throw new \DomainException('Choose an existing customer or walk-in.');
            if (!$this->db->table('users')->where('id',$staffId)->countAllResults()) throw new \DomainException('Your staff account is no longer available. Sign in again.');
            $cents=(int) round((float)$product['price']*100);
            $total=$cents*$quantity;
            if ($total>9999999999) throw new \DomainException('The sale total exceeds the supported amount. Record a smaller quantity.');
            $this->db->table('sales')->insert(['product_id'=>$productId,'customer_id'=>$customerId,'sold_by'=>$staffId,'quantity'=>$quantity,'total_price'=>number_format($total/100,2,'.',''),'created_at'=>date('Y-m-d H:i:s')]);
            $id=(int)$this->db->insertID();
            if (!$this->db->transStatus()) throw new \RuntimeException('Transaction failed.');
            $this->db->transCommit();
            return $id;
        } catch (\Throwable $e) { $this->db->transRollback(); throw $e; }
    }
}
