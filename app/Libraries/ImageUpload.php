<?php
namespace App\Libraries;
class ImageUpload
{
    public function store(?\CodeIgniter\HTTP\Files\UploadedFile $file, bool $avatar=false): ?string
    {
        if (!$file || $file->getError()===UPLOAD_ERR_NO_FILE) return null;
        if (!$file->isValid() || $file->getSize()>2097152 || !in_array($file->getMimeType(),['image/jpeg','image/png','image/webp'],true)) throw new \DomainException('Upload a valid JPG, PNG or WebP image up to 2 MB.');
        $size=@getimagesize($file->getTempName());
        if (!$size || $size[0]>6000 || $size[1]>6000 || $size[0]*$size[1]>16000000) throw new \DomainException('Image dimensions must be at most 6000 pixels per side and 16 megapixels.');
        $name=bin2hex(random_bytes(16)).'.jpg';
        $dir=WRITEPATH.'uploads/';
        if (!is_dir($dir)) mkdir($dir,0770,true);
        try {
            $image=service('image')->withFile($file->getTempName());
            if ($avatar) $image->fit(256,256,'center'); else $image->resize(1000,1000,true,'auto');
            $image->convert(IMAGETYPE_JPEG)->save($dir.$name,88);
        } catch (\Throwable $e) { throw new \DomainException('The image could not be prepared. Try a different image.'); }
        return 'media/'.$name;
    }
}
