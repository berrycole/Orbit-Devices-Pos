<?php
namespace App\Controllers;
use App\Models\UserModel;
class Auth extends BaseController
{
    public function login() { return session('user_id') ? redirect()->to(site_url()) : view('login',['title'=>'Staff sign in']); }
    public function authenticate()
    {
        $key='login_'.hash('sha256',$this->request->getIPAddress());
        if (!service('throttler')->check($key,10,300)) return redirect()->back()->with('error','Too many attempts. Please try again in five minutes.');
        $username=trim((string)$this->request->getPost('username'));
        $password=(string)$this->request->getPost('password');
        $user=(new UserModel())->where('username',$username)->first();
        if (!$user || strlen($password)>72 || !password_verify($password,$user['password'])) return redirect()->back()->with('error','The username or password is incorrect.');
        session()->regenerate(true);
        session()->set(['isLoggedIn'=>true,'user_id'=>(int)$user['id'],'full_name'=>$user['full_name'],'username'=>$user['username']]);
        return redirect()->to(site_url());
    }
    public function logout() { session()->destroy(); return redirect()->to(site_url('login')); }
}
