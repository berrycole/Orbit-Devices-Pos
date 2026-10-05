'use strict';
document.querySelectorAll('[data-confirm]').forEach(form=>form.addEventListener('submit',event=>{if(!confirm(form.dataset.confirm))event.preventDefault();}));
const fileInput=document.querySelector('[data-image-input]');
if(fileInput)fileInput.addEventListener('change',()=>{const file=fileInput.files[0];if(file&&file.size<=2097152&&['image/png','image/jpeg','image/webp'].includes(file.type)){const img=document.querySelector('#image-preview');const url=URL.createObjectURL(file);img.onload=()=>URL.revokeObjectURL(url);img.src=url;}});
const product=document.querySelector('#product_id');
if(product){
 const quantity=document.querySelector('#quantity');const money=n=>new Intl.NumberFormat('en-PH',{style:'currency',currency:'PHP'}).format(n);
 function update(){const option=product.selectedOptions[0];const price=Number(option?.dataset.price||0);const stock=Number(option?.dataset.stock||0);const qty=Number(quantity.value);quantity.max=String(stock||1000000);document.querySelector('#unit-price').textContent=money(price);document.querySelector('#sale-total').textContent=money(price*(qty||0));document.querySelector('#quantity-summary').textContent=quantity.value||'0';document.querySelector('#stock-message').textContent=product.value?`${stock} units available in inventory.`:'Select from the collection to get started.';document.querySelector('#complete-sale').disabled=!product.value||!Number.isInteger(qty)||qty<1||qty>stock;document.querySelectorAll('[data-product]').forEach(card=>{const selected=card.dataset.product===product.value;card.classList.toggle('chosen',selected);card.setAttribute('aria-pressed',String(selected));});}
 document.querySelectorAll('[data-product]').forEach(card=>card.addEventListener('click',()=>{product.value=card.dataset.product;quantity.value='1';update();}));
 product.addEventListener('change',()=>{quantity.value='1';update();});quantity.addEventListener('input',update);
 document.querySelectorAll('[data-quantity]').forEach(button=>button.addEventListener('click',()=>{quantity.value=String(Math.min(Number(quantity.max),Math.max(1,(Number(quantity.value)||1)+Number(button.dataset.quantity))));update();}));
 document.querySelector('#device-search').addEventListener('input',event=>{let count=0;document.querySelectorAll('[data-product]').forEach(card=>{const show=card.dataset.name.toLowerCase().includes(event.target.value.toLowerCase());card.hidden=!show;if(show)count++;});document.querySelector('#no-devices').hidden=count>0;});
 document.querySelector('#sale-form').addEventListener('submit',()=>{document.querySelector('#complete-sale').disabled=true;document.querySelector('#complete-sale').textContent='Recording sale…';});update();
}
