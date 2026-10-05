<?php
namespace Tests;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use App\Models\{ProductModel,CustomerModel,UserModel,SaleModel};
use App\Libraries\SaleRecorder;

final class PosTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;
    protected $namespace='App';
    protected $refresh=true;
    private int $staffId;
    private int $productId;
    protected function setUp(): void
    {
        parent::setUp();
        $this->staffId=(int)(new UserModel())->insert(['username'=>'tester','full_name'=>'Test Staff','password'=>password_hash('TestPassword2026!',PASSWORD_DEFAULT),'created_at'=>date('Y-m-d H:i:s')]);
        $this->productId=(int)(new ProductModel())->insert(['name'=>'Test Phone','price'=>'1234.56','stock_quantity'=>3,'category'=>'Phones','created_at'=>date('Y-m-d H:i:s')]);
    }
    public function testSaleDeductsStockAndRecordsExactTotal(): void
    {
        $id=(new SaleRecorder($this->db))->record($this->productId,null,$this->staffId,2);
        $sale=(new SaleModel())->find($id);
        $this->assertSame(1,(int)(new ProductModel())->find($this->productId)['stock_quantity']);
        $this->assertSame('2469.12',number_format($sale['total_price'],2,'.',''));
        $this->assertSame($this->staffId,(int)$sale['sold_by']);
        $this->assertNull($sale['customer_id']);
    }
    public function testOversellingRollsBack(): void
    {
        try {(new SaleRecorder($this->db))->record($this->productId,null,$this->staffId,4);$this->fail('Overselling accepted');}catch(\DomainException $e){$this->assertStringContainsString('exceeds',$e->getMessage());}
        $this->assertSame(3,(int)(new ProductModel())->find($this->productId)['stock_quantity']);
        $this->assertSame(0,(new SaleModel())->countAllResults());
    }
    public function testInvalidCustomerRollsBackStock(): void
    {
        try {(new SaleRecorder($this->db))->record($this->productId,9999,$this->staffId,1);$this->fail('Invalid customer accepted');}catch(\DomainException $e){$this->assertStringContainsString('customer',$e->getMessage());}
        $this->assertSame(3,(int)(new ProductModel())->find($this->productId)['stock_quantity']);
    }
    public function testSecondSaleCannotUseAlreadySoldStock(): void
    {
        $recorder=new SaleRecorder($this->db);$recorder->record($this->productId,null,$this->staffId,3);
        $this->expectException(\DomainException::class);$recorder->record($this->productId,null,$this->staffId,1);
    }
    public function testArchivedProductsCannotBeSold(): void
    {
        (new ProductModel())->update($this->productId,['archived'=>1]);
        $this->expectException(\DomainException::class);(new SaleRecorder($this->db))->record($this->productId,null,$this->staffId,1);
    }
    public function testZeroQuantityIsRejected(): void
    {
        $this->expectException(\DomainException::class);(new SaleRecorder($this->db))->record($this->productId,null,$this->staffId,0);
    }
    public function testCustomerDeletionPreservesSale(): void
    {
        $customer=(int)(new CustomerModel())->insert(['full_name'=>'Test Customer','email'=>'test@example.com','created_at'=>date('Y-m-d H:i:s')]);
        $id=(new SaleRecorder($this->db))->record($this->productId,$customer,$this->staffId,1);
        (new CustomerModel())->delete($customer);
        $this->assertNull((new SaleModel())->find($id)['customer_id']);
    }
    public function testAllManagementPagesRequireAuthentication(): void
    {
        foreach(['/','/products','/products/new','/customers','/customers/new','/staff','/staff/new','/sales','/sales/new'] as $url) $this->get($url)->assertRedirectTo(site_url('login'));
    }
    public function testAuthenticatedPagesRender(): void
    {
        foreach(['/','/products','/customers','/staff','/sales','/sales/new','/products/new','/staff/new','/customers/new'] as $url) $this->withSession(['isLoggedIn'=>true,'user_id'=>$this->staffId,'full_name'=>'Test Staff'])->get($url)->assertOK();
    }
    private function securedPost(string $path, array $data=[]) { $security=service('security'); $data[$security->getTokenName()]=$security->getHash(); $this->session[$security->getTokenName()]=$security->getHash(); return $this->post($path,$data); }
    public function testCustomerCrudThroughController(): void
    {
        $session=['isLoggedIn'=>true,'user_id'=>$this->staffId,'full_name'=>'Test Staff'];
        $this->withSession($session)->securedPost('/customers',['full_name'=>'Jamie Example','email'=>'jamie@example.com','phone'=>'09171234567'])->assertRedirectTo(site_url('customers'));
        $customer=(new CustomerModel())->where('email','jamie@example.com')->first();
        $this->assertNotNull($customer);
        $this->withSession($session)->securedPost('/customers/'.$customer['id'],['full_name'=>'Jamie Updated','email'=>'jamie@example.com','phone'=>'09171234568']);
        $this->assertSame('Jamie Updated',(new CustomerModel())->find($customer['id'])['full_name']);
        $this->withSession($session)->securedPost('/customers/'.$customer['id'].'/delete');
        $this->assertNull((new CustomerModel())->find($customer['id']));
    }
    public function testInvalidProductCannotBeCreated(): void
    {
        $before=(new ProductModel())->countAllResults();
        $this->withSession(['isLoggedIn'=>true,'user_id'=>$this->staffId])->securedPost('/products',['name'=>'Invalid','price'=>'-5','stock_quantity'=>'-1','category'=>'Phones']);
        $this->assertSame($before,(new ProductModel())->countAllResults());
    }
    public function testStaffPasswordIsHashedAndBlankEditPreservesIt(): void
    {
        $session=['isLoggedIn'=>true,'user_id'=>$this->staffId];
        $this->withSession($session)->securedPost('/staff',['full_name'=>'New Staff','username'=>'newstaff','password'=>'LongTestPassword2026!']);
        $user=(new UserModel())->where('username','newstaff')->first();
        $this->assertNotNull($user);
        $this->assertTrue(password_verify('LongTestPassword2026!',$user['password']));
        $this->withSession($session)->securedPost('/staff/'.$user['id'],['full_name'=>'Updated Staff','username'=>'newstaff','password'=>'']);
        $this->assertSame($user['password'],(new UserModel())->find($user['id'])['password']);
        $this->withSession($session)->securedPost('/staff/'.$user['id'].'/delete');
        $this->assertNull((new UserModel())->find($user['id']));
    }
    public function testSelfDeletionIsBlocked(): void
    {
        $this->withSession(['isLoggedIn'=>true,'user_id'=>$this->staffId])->securedPost('/staff/'.$this->staffId.'/delete');
        $this->assertNotNull((new UserModel())->find($this->staffId));
    }
    public function testProductArchiveKeepsHistory(): void
    {
        $id=(new SaleRecorder($this->db))->record($this->productId,null,$this->staffId,1);
        $this->withSession(['isLoggedIn'=>true,'user_id'=>$this->staffId])->securedPost('/products/'.$this->productId.'/delete');
        $this->assertSame(1,(int)(new ProductModel())->find($this->productId)['archived']);
        $this->assertNotNull((new SaleModel())->find($id));
    }
    public function testImageIsReencodedAndAvatarResized(): void
    {
        $source=tempnam(sys_get_temp_dir(),'orbit');
        $image=imagecreatetruecolor(480,320);imagepng($image,$source);imagedestroy($image);
        $file=new TestUploadedFile($source,'test.png','image/png',filesize($source),UPLOAD_ERR_OK);
        $stored=(new \App\Libraries\ImageUpload())->store($file,true);
        $path=WRITEPATH.'uploads/'.basename($stored);
        try { $size=getimagesize($path);$this->assertSame(256,$size[0]);$this->assertSame(256,$size[1]);$this->assertSame('image/jpeg',$size['mime']); }
        finally { unlink($source);unlink($path); }
    }
    public function testNonImageUploadIsRejected(): void
    {
        $source=tempnam(sys_get_temp_dir(),'orbit');file_put_contents($source,'<?php echo "unsafe";');
        $file=new TestUploadedFile($source,'fake.jpg','image/jpeg',filesize($source),UPLOAD_ERR_OK);
        try { $this->expectException(\DomainException::class);(new \App\Libraries\ImageUpload())->store($file); } finally {unlink($source);}
    }
}

class TestUploadedFile extends \CodeIgniter\HTTP\Files\UploadedFile { public function isValid(): bool { return $this->getError()===UPLOAD_ERR_OK; } }
