(() => {
  'use strict';
  function icons(root=document){root.querySelectorAll('[data-icon]').forEach(el=>{el.innerHTML=window.moneyIcon(el.dataset.icon);el.removeAttribute('data-icon')})}
  icons();
  document.querySelectorAll('[data-auto-submit]').forEach(el=>el.addEventListener('change',()=>el.form.requestSubmit()));
  document.querySelectorAll('[data-confirm]').forEach(el=>el.addEventListener('click',event=>{if(!confirm(el.dataset.confirm))event.preventDefault()}));
  document.querySelectorAll('[data-dialog]').forEach(el=>el.addEventListener('click',()=>document.getElementById(el.dataset.dialog).showModal()));
  document.querySelectorAll('[data-close-dialog]').forEach(el=>el.addEventListener('click',()=>el.closest('dialog').close()));
  document.querySelectorAll('dialog[data-open-on-load]').forEach(el=>el.showModal());
  document.querySelectorAll('[data-dismiss]').forEach(el=>el.addEventListener('click',()=>el.closest('.notice').remove()));
  document.querySelectorAll('[data-back]').forEach(el=>el.addEventListener('click',()=>history.length>1?history.back():location.assign('index.php?r=dashboard')));
  document.querySelectorAll('[data-print]').forEach(el=>el.addEventListener('click',()=>window.print()));
  document.querySelectorAll('[data-toggle-password]').forEach(el=>el.addEventListener('click',()=>{const input=el.parentElement.querySelector('input'),show=input.type==='password';input.type=show?'text':'password';el.setAttribute('aria-label',show?'Hide password':'Show password');el.title=show?'Hide password':'Show password'}));
  const menu=document.querySelector('[data-menu-toggle]'),scrim=document.querySelector('[data-close-menu]');
  function closeMenu(){document.body.classList.remove('menu-open');if(scrim)scrim.hidden=true;if(menu)menu.setAttribute('aria-expanded','false')}
  menu?.addEventListener('click',()=>{const open=!document.body.classList.contains('menu-open');document.body.classList.toggle('menu-open',open);scrim.hidden=!open;menu.setAttribute('aria-expanded',String(open))});
  scrim?.addEventListener('click',closeMenu);
  document.addEventListener('keydown',event=>{if(event.key==='Escape')closeMenu()});
  document.querySelector('[data-public-menu]')?.addEventListener('click',event=>{const button=event.currentTarget,open=!button.closest('.site-nav').classList.contains('mobile-open');button.closest('.site-nav').classList.toggle('mobile-open',open);button.setAttribute('aria-expanded',String(open))});
  document.querySelectorAll('.nav-links a').forEach(el=>el.addEventListener('click',()=>{document.querySelector('.site-nav')?.classList.remove('mobile-open');document.querySelector('[data-public-menu]')?.setAttribute('aria-expanded','false')}));
  const status=document.querySelector('[data-payment-status]'),paidDate=document.querySelector('[data-paid-date]');
  function updatePaidDate(){if(paidDate){paidDate.hidden=status.value!=='paid';paidDate.querySelector('input').required=status.value==='paid'}}
  status?.addEventListener('change',updatePaidDate);if(status)updatePaidDate();
  const items=document.getElementById('invoice-items'),total=document.querySelector('[data-invoice-total]');
  function invoiceTotal(){if(!items)return;let amount=0;items.querySelectorAll('.invoice-item').forEach(row=>{const q=Math.round(Number(row.querySelector('[name="quantity[]"]').value)*100),p=Math.round(Number(row.querySelector('[name="unit_price[]"]').value)*100),rate=Math.round(Number(row.querySelector('[name="tax_rate[]"]').value)*100);const subtotal=Math.round(q*p/100);amount+=subtotal+Math.round(subtotal*rate/10000)});total.textContent=total.dataset.currency+' '+(amount/100).toLocaleString('en-GB',{minimumFractionDigits:2,maximumFractionDigits:2})}
  document.querySelector('[data-add-item]')?.addEventListener('click',()=>{if(items.children.length>=50)return;const row=items.firstElementChild.cloneNode(true);row.querySelectorAll('input').forEach(input=>input.value=input.name==='quantity[]'?'1':input.name==='tax_rate[]'?'21':'');items.append(row);row.querySelector('input').focus();invoiceTotal()});
  items?.addEventListener('click',event=>{const button=event.target.closest('[data-remove-item]');if(button&&items.children.length>1){button.closest('.invoice-item').remove();invoiceTotal()}});
  items?.addEventListener('input',invoiceTotal);invoiceTotal();
  document.querySelectorAll('[data-chart]').forEach(canvas=>{
    const points=JSON.parse(canvas.dataset.chart),container=canvas.parentElement,tip=container.querySelector('.chart-tooltip'),currency=canvas.dataset.currency;
    let geometry;
    const money=n=>currency+' '+(Number(n)/100).toLocaleString('en-GB',{minimumFractionDigits:2,maximumFractionDigits:2});
    function draw(){
      const width=container.clientWidth,height=container.clientHeight,dpr=window.devicePixelRatio||1;
      canvas.width=Math.round(width*dpr);canvas.height=Math.round(height*dpr);
      const ctx=canvas.getContext('2d');ctx.scale(dpr,dpr);ctx.clearRect(0,0,width,height);
      const left=42,right=12,top=18,bottom=29,plotW=width-left-right,plotH=height-top-bottom;
      const values=points.flatMap(p=>[Number(p.income),Number(p.expenses),Number(p.balance)]),lo=Math.min(0,...values),hi=Math.max(10000,...values),span=hi-lo;
      const max=hi+span*.12,min=lo<0?lo-span*.12:0;
      const y=n=>top+(max-n)/(max-min)*plotH,step=plotW/points.length;
      geometry={left,step};
      ctx.font='9px "Segoe UI",Arial';ctx.textBaseline='middle';ctx.fillStyle='#a1ad97';ctx.lineWidth=1;
      for(let i=0;i<=4;i++){const value=min+(max-min)*i/4,yy=y(value);ctx.fillText(Math.abs(value)>=100000?((value/100000).toFixed(1)+'k'):Math.round(value/100).toString(),0,yy);ctx.strokeStyle='#edf1e8';ctx.setLineDash([3,4]);ctx.beginPath();ctx.moveTo(left,yy);ctx.lineTo(width-right,yy);ctx.stroke()}
      ctx.setLineDash([]);ctx.strokeStyle='#dce5d6';ctx.beginPath();ctx.moveTo(left,y(0));ctx.lineTo(width-right,y(0));ctx.stroke();
      const bar=Math.min(15,step*.22);
      points.forEach((p,i)=>{const x=left+step*(i+.5);[['income','#85a86b',-bar-2],['expenses','#d3a18d',2]].forEach(([key,color,offset])=>{ctx.fillStyle=color;const value=Number(p[key]);if(value>0)ctx.fillRect(x+offset,y(value),bar,y(0)-y(value))});ctx.fillStyle='#9baa8e';ctx.textAlign='center';if(points.length<=6||width>580||i%2===0)ctx.fillText(p.label.slice(0,3),x,height-9)});
      ctx.strokeStyle='#769bb7';ctx.lineWidth=2;ctx.beginPath();points.forEach((p,i)=>{const x=left+step*(i+.5),yy=y(Number(p.balance));i?ctx.lineTo(x,yy):ctx.moveTo(x,yy)});ctx.stroke();points.forEach((p,i)=>{ctx.beginPath();ctx.arc(left+step*(i+.5),y(Number(p.balance)),3,0,Math.PI*2);ctx.fillStyle='#fff';ctx.fill();ctx.strokeStyle='#769bb7';ctx.lineWidth=1.5;ctx.stroke()});
    }
    canvas.addEventListener('pointermove',event=>{if(!geometry)return;const x=event.clientX-canvas.getBoundingClientRect().left,i=Math.max(0,Math.min(points.length-1,Math.floor((x-geometry.left)/geometry.step))),p=points[i];tip.replaceChildren();const heading=document.createElement('strong');heading.textContent=p.label;tip.append(heading);[['Income',p.income],['Expenses',p.expenses],['Balance',p.balance]].forEach(([label,n])=>{const row=document.createElement('div');row.textContent=label+': '+money(n);tip.append(row)});tip.hidden=false});
    canvas.addEventListener('pointerleave',()=>tip.hidden=true);
    new ResizeObserver(draw).observe(container);draw();
  });
})();
