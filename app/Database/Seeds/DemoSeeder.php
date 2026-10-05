<?php
namespace App\Database\Seeds;
use CodeIgniter\Database\Seeder;
class DemoSeeder extends Seeder
{
    public function run()
    {
        if ($this->db->table('users')->countAllResults()) return;
        $password = (string) env('ORBIT_ADMIN_PASSWORD','');
        if (strlen($password)<12 || strlen($password)>72) throw new \RuntimeException('Set ORBIT_ADMIN_PASSWORD to a unique 12–72 byte password before seeding.');
        $now = date('Y-m-d H:i:s');
        $this->db->table('users')->insert(['username'=>'admin','full_name'=>'Store Administrator','password'=>password_hash($password,PASSWORD_DEFAULT),'created_at'=>$now]);
        foreach ([['iPhone 16 Pro',69990,18,'Phones','phone'],['iPhone 16',54990,24,'Phones','phone-blue'],['iPad Air 11-inch',42990,12,'Tablets','tablet'],['iPad mini',34990,8,'Tablets','tablet-purple'],['AirPods Pro 2',14990,30,'Audio','earbuds'],['AirPods Max',32990,5,'Audio','headphones'],['Apple Watch Series 10',26990,9,'Wearables','watch'],['MacBook Air 13-inch',64990,6,'Laptops','laptop'],['MagSafe Charger',2490,4,'Accessories','charger']] as [$name,$price,$stock,$category,$art]) {
            $this->db->table('products')->insert(['name'=>$name,'price'=>$price,'stock_quantity'=>$stock,'category'=>$category,'image'=>'assets/devices/'.$art.'.svg','created_at'=>$now]);
        }
        foreach ([['Alex Rivera','alex@example.com','09170000001'],['Jamie Santos','jamie@example.com','09170000002'],['Sam Cruz','sam@example.com','09170000003']] as [$name,$email,$phone]) $this->db->table('customers')->insert(['full_name'=>$name,'email'=>$email,'phone'=>$phone,'created_at'=>$now]);
    }
}
