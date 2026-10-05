<?php
namespace App\Controllers;
use App\Models\{ProductModel,CustomerModel,UserModel};
use App\Libraries\ImageUpload;
class Catalog extends BaseController
{
    private function model(string $entity) { return match($entity) {'products'=>new ProductModel(),'customers'=>new CustomerModel(),'staff'=>new UserModel(),default=>throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound()}; }
    public function index(string $entity)
    {
        $model=$this->model($entity);
        $q=substr(trim((string)$this->request->getGet('q')),0,100);
        $category=(string)$this->request->getGet('category');
        if ($entity==='products') { $model->where('archived',0); if (in_array($category,['Phones','Tablets','Audio','Wearables','Laptops','Accessories'],true)) $model->where('category',$category); }
        if ($q!=='') $model->like($entity==='products'?'name':'full_name',$q);
        return view('catalog',['title'=>ucfirst($entity),'active'=>$entity,'entity'=>$entity,'rows'=>$model->orderBy('id','DESC')->paginate(12),'pager'=>$model->pager,'q'=>$q,'category'=>$category]);
    }
    public function form(string $entity, ?int $id=null)
    {
        $row=$id ? $this->model($entity)->find($id) : [];
        if ($id && !$row) throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        return view('form',['title'=>($id?'Edit ':'Add ').($entity==='staff'?'staff member':rtrim($entity,'s')),'active'=>$entity,'entity'=>$entity,'row'=>$row]);
    }
    public function save(string $entity, ?int $id=null)
    {
        $model=$this->model($entity); $row=$id?$model->find($id):null;
        if ($id && !$row) throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        $fields=match($entity) {'products'=>['name','price','stock_quantity','category'],'customers'=>['full_name','email','phone'],'staff'=>['full_name','username']};
        $data=[]; foreach($fields as $field) $data[$field]=trim((string)$this->request->getPost($field));
        $rules=match($entity) {
            'products'=>['name'=>'required|max_length[100]','price'=>'required|regex_match[/^\d{1,8}(\.\d{1,2})?$/]|greater_than[0]|less_than_equal_to[99999999.99]','stock_quantity'=>'required|is_natural|less_than_equal_to[1000000]','category'=>'required|in_list[Phones,Tablets,Audio,Wearables,Laptops,Accessories]'],
            'customers'=>['full_name'=>'required|max_length[100]','email'=>'required|valid_email|max_length[100]','phone'=>'permit_empty|max_length[20]|regex_match[/^[0-9+() -]+$/]'],
            'staff'=>['full_name'=>'required|max_length[100]','username'=>'required|alpha_dash|min_length[3]|max_length[50]'],
        };
        $errors=[];
        if (!$this->validateData($data,$rules)) $errors=$this->validator->getErrors();
        if ($entity==='staff') {
            $duplicate=(new UserModel())->where('username',$data['username']); if($id) $duplicate->where('id !=',$id);
            if($duplicate->first()) $errors['username']='That username is already taken.';
            $password=(string)$this->request->getPost('password');
            if (!$id || $password!=='') { if(strlen($password)<12 || strlen($password)>72) $errors['password']='Use a password of 12–72 bytes.'; else $data['password']=password_hash($password,PASSWORD_DEFAULT); }
        }
        if ($errors) return redirect()->back()->with('errors',$errors)->with('values',array_diff_key($data,['password'=>true]));
        $uploaded=null;
        try {
            if($entity!=='customers') { $field=$entity==='staff'?'avatar':'image'; $uploaded=(new ImageUpload())->store($this->request->getFile($field),$entity==='staff'); if($uploaded) $data[$field]=$uploaded; }
            if(!$id) $data['created_at']=date('Y-m-d H:i:s');
            if($id) $model->update($id,$data); else $model->insert($data);
        } catch (\DomainException $e) { return redirect()->back()->with('error',$e->getMessage())->with('values',array_diff_key($data,['password'=>true])); }
        catch (\Throwable $e) { if($uploaded) @unlink(WRITEPATH.'uploads/'.basename($uploaded)); log_message('error','Catalog save: '.$e->getMessage()); return redirect()->back()->with('error','The record could not be saved. Please try again.'); }
        if($entity==='staff' && $id==session('user_id')) session()->set(['full_name'=>$data['full_name'],'username'=>$data['username']]);
        return redirect()->to(site_url($entity))->with('success','Saved successfully.');
    }
    public function delete(string $entity,int $id)
    {
        $model=$this->model($entity);
        if(!$model->find($id)) throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        if($entity==='staff' && $id==session('user_id')) return redirect()->back()->with('error','You cannot delete the account you are signed in with.');
        if($entity==='staff' && db_connect()->table('sales')->where('sold_by',$id)->countAllResults()) return redirect()->back()->with('error','This staff member has recorded sales and must be retained for transaction history.');
        try { if($entity==='products') $model->update($id,['archived'=>1]); else $model->delete($id); }
        catch(\Throwable $e) { return redirect()->back()->with('error','This record is used by another transaction and cannot be removed.'); }
        return redirect()->to(site_url($entity))->with('success',$entity==='products'?'Product archived. Past sales are preserved.':'Record deleted.');
    }
}
