<?php
namespace App\Controllers;
class Media extends BaseController
{
    public function show(string $name)
    {
        if(!preg_match('/^[a-f0-9]{32}\.jpg$/D',$name) || !is_file(WRITEPATH.'uploads/'.$name)) throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        return $this->response->setContentType('image/jpeg')->setHeader('X-Content-Type-Options','nosniff')->setBody(file_get_contents(WRITEPATH.'uploads/'.$name));
    }
}
