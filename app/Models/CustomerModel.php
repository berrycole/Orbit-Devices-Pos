<?php
namespace App\Models;
class CustomerModel extends \CodeIgniter\Model
{
    protected $table='customers';
    protected $allowedFields=['full_name','email','phone','created_at'];
}
