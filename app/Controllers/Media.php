<?php
namespace App\Controllers;
class Media extends BaseController
{
    public function show(string $name)
    {
        if (!preg_match('/^[a-f0-9]{32}\.(jpg|png|webp)$/D', $name)) throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        if (env('ORBIT_MEDIA_STORAGE') === 'database') {
            $media = db_connect()->table('media')->where('name', $name)->get()->getRowArray();
            if (!$media) throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
            return $this->response->setContentType($media['mime'])->setHeader('X-Content-Type-Options', 'nosniff')->setBody(base64_decode($media['data'], true));
        }
        if (!is_file(WRITEPATH.'uploads/'.$name)) throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        return $this->response->setContentType('image/jpeg')->setHeader('X-Content-Type-Options','nosniff')->setBody(file_get_contents(WRITEPATH.'uploads/'.$name));
    }
}
