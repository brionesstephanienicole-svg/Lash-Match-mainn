(() => {
'use strict';
const message=document.querySelector('#recovery-message'),reset=document.body.dataset.mode==='reset';
let csrf='',busy=false,requestEmail='';
async function request(action,data){
 const response=await fetch('api.php?action='+action,{method:data?'POST':'GET',credentials:'same-origin',headers:data?{'Content-Type':'application/json','X-CSRF-Token':csrf}:{},body:data?JSON.stringify(data):undefined});
 let result;try{result=await response.json();}catch{throw Error('The password service is unavailable. Please try again.');}
 if(!response.ok){const error=Error(result.error||'Unable to complete the request.');error.retryAfter=result.retry_after||0;throw error;}return result;
}
async function session(){const result=await request('session');csrf=result.csrf;}
function passwordBytes(value){return new TextEncoder().encode(value).length;}
async function initializeLegacyReset(){
 const form=document.querySelector('form'),button=form.querySelector('button');let token=new URLSearchParams(location.hash.slice(1)).get('token')||'';
 history.replaceState(null,'',location.pathname);
 if(!/^[a-f0-9]{64}$/.test(token)){message.textContent='Open the confirmation link from your email, or request a new reset link.';return;}
 message.textContent='Checking your email confirmation link...';
 try{await session();await request('validate_password_reset',{token});form.hidden=false;message.textContent='Email confirmed. Choose your new password.';document.querySelector('#new-password').focus();}
 catch(e){message.textContent=e.message;}
 form.addEventListener('submit',async e=>{
  e.preventDefault();if(busy||!form.reportValidity())return;
  const password=form.elements.password.value;
  if(passwordBytes(password)<12||passwordBytes(password)>72){message.textContent='Use a password between 12 and 72 bytes.';return;}
  if(password!==form.elements.password_confirmation.value){message.textContent='The passwords do not match.';return;}
  busy=true;button.disabled=true;const label=button.textContent;button.textContent='Changing password...';message.textContent='';
  try{await session();const result=await request('reset_password',{token,password,password_confirmation:form.elements.password_confirmation.value});message.textContent=result.message;form.reset();form.hidden=true;document.querySelector('#success').hidden=false;}
  catch(e){message.textContent=e.message||'Unable to connect. Please try again.';}
  finally{busy=false;button.disabled=false;button.textContent=label;}
 });
}
function initializePinRequest(){
 const requestForm=document.querySelector('#request-form'),codeForm=document.querySelector('#code-form'),passwordForm=document.querySelector('#password-form');
 const sendButton=requestForm.querySelector('button'),verifyButton=codeForm.querySelector('button[type="submit"]'),resetButton=passwordForm.querySelector('button'),resendButton=document.querySelector('#resend-code');
 let token='',resendAt=0;
 function updateResend(){
  const seconds=Math.max(0,Math.ceil((resendAt-Date.now())/1000));
  resendButton.disabled=busy||seconds>0;
  resendButton.textContent=seconds>0?'Resend code in '+seconds+'s':'Resend code';
 }
 const timer=setInterval(updateResend,250);
 async function sendCode(){
  if(busy)return;
  if(Date.now()<resendAt){message.textContent='Please wait '+Math.ceil((resendAt-Date.now())/1000)+' seconds before requesting another code.';return;}
  busy=true;sendButton.disabled=true;sendButton.textContent='Sending PIN...';updateResend();message.textContent='';
  try{
   await session();const result=await request('request_password_reset',{email:requestEmail});
   resendAt=Date.now()+(result.retry_after||60)*1000;token='';codeForm.reset();requestForm.hidden=true;codeForm.hidden=false;
   message.textContent=result.message;codeForm.elements.pin.focus();
  }catch(error){
   if(error.retryAfter)resendAt=Date.now()+error.retryAfter*1000;
   message.textContent=error.message||'Unable to connect. Please try again.';
  }finally{busy=false;sendButton.disabled=false;sendButton.textContent='Send one-time PIN';updateResend();}
 }
 requestForm.addEventListener('submit',e=>{
  e.preventDefault();if(busy||!requestForm.reportValidity())return;
  requestEmail=requestForm.elements.email.value.trim();sendCode();
 });
 resendButton.addEventListener('click',sendCode);
 codeForm.addEventListener('submit',async e=>{
  e.preventDefault();if(busy||!codeForm.reportValidity())return;
  busy=true;verifyButton.disabled=true;verifyButton.textContent='Verifying PIN...';updateResend();message.textContent='';
  try{
   await session();const result=await request('verify_password_reset_pin',{email:requestEmail,pin:codeForm.elements.pin.value.trim()});
   token=result.token;codeForm.reset();codeForm.hidden=true;passwordForm.hidden=false;message.textContent=result.message;passwordForm.elements.password.focus();clearInterval(timer);
  }catch(error){message.textContent=error.message||'Unable to connect. Please try again.';}
  finally{busy=false;verifyButton.disabled=false;verifyButton.textContent='Verify PIN';updateResend();}
 });
 passwordForm.addEventListener('submit',async e=>{
  e.preventDefault();if(busy||!passwordForm.reportValidity())return;
  const password=passwordForm.elements.password.value;
  if(passwordBytes(password)<12||passwordBytes(password)>72){message.textContent='Use a password between 12 and 72 bytes.';return;}
  if(password!==passwordForm.elements.password_confirmation.value){message.textContent='The passwords do not match.';return;}
  busy=true;resetButton.disabled=true;resetButton.textContent='Changing password...';message.textContent='';
  try{
   await session();const result=await request('reset_password',{token,password,password_confirmation:passwordForm.elements.password_confirmation.value});
   token='';passwordForm.reset();passwordForm.hidden=true;document.querySelector('#success').hidden=false;message.textContent=result.message;
  }catch(error){message.textContent=error.message||'Unable to connect. Please try again.';}
  finally{busy=false;resetButton.disabled=false;resetButton.textContent='Change password';}
 });
}
if(reset)initializeLegacyReset();else initializePinRequest();
})();
