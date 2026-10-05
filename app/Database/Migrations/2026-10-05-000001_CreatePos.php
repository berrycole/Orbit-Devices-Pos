<?php
namespace App\Database\Migrations;
use CodeIgniter\Database\Migration;

class CreatePos extends Migration
{
    public function up()
    {
        $id = ['type'=>'INT','unsigned'=>true,'auto_increment'=>true];
        $date = ['type'=>'DATETIME'];
        $text = static fn($length) => ['type'=>'VARCHAR','constraint'=>$length];
        $this->forge->addField(['id'=>$id,'name'=>$text(100),'price'=>['type'=>'DECIMAL','constraint'=>'10,2'],'stock_quantity'=>['type'=>'INT','default'=>0],'image'=>$text(255)+['null'=>true],'category'=>$text(30),'archived'=>['type'=>'INT','default'=>0],'created_at'=>$date]);
        $this->forge->addKey('id',true); $this->forge->createTable('products',true);
        $this->forge->addField(['id'=>$id,'full_name'=>$text(100),'email'=>$text(100),'phone'=>$text(20)+['null'=>true],'created_at'=>$date]);
        $this->forge->addKey('id',true); $this->forge->createTable('customers',true);
        $this->forge->addField(['id'=>$id,'username'=>$text(50),'full_name'=>$text(100),'password'=>$text(255),'avatar'=>$text(255)+['null'=>true],'created_at'=>$date]);
        $this->forge->addKey('id',true); $this->forge->addUniqueKey('username'); $this->forge->createTable('users',true);
        $this->forge->addField(['id'=>$id,'product_id'=>['type'=>'INT','unsigned'=>true],'customer_id'=>['type'=>'INT','unsigned'=>true,'null'=>true],'sold_by'=>['type'=>'INT','unsigned'=>true],'quantity'=>['type'=>'INT'],'total_price'=>['type'=>'DECIMAL','constraint'=>'10,2'],'created_at'=>$date]);
        $this->forge->addKey('id',true);
        $this->forge->addForeignKey('product_id','products','id','CASCADE','RESTRICT');
        $this->forge->addForeignKey('customer_id','customers','id','CASCADE','SET NULL');
        $this->forge->addForeignKey('sold_by','users','id','CASCADE','RESTRICT');
        $this->forge->createTable('sales',true);
    }
    public function down()
    {
        foreach (['sales','customers','products','users'] as $table) $this->forge->dropTable($table,true);
    }
}
