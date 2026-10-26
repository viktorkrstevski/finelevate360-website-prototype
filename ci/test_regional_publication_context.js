'use strict';
const fs=require('node:fs');const vm=require('node:vm');const assert=require('node:assert/strict');
const code=fs.readFileSync(__dirname+'/../wordpress/plugins/finelevate360-directory/assets/js/regional-publication.js','utf8');
let tests=0;
function fixture(checked=true){let middleware,submit;const timers=[];const opt={checked,dataset:{postId:'2488',nonce:'fixture-nonce'}};const hash={value:'fixture-hash'};const form={addEventListener(event,cb,capture){assert.equal(event,'submit');assert.equal(capture,true);submit=cb;}};const apiFetch={use(cb){middleware=cb;}};vm.runInNewContext(code,{document:{getElementById(id){return id==='post'?form:opt;},querySelector(){return hash;}},window:{wp:{apiFetch}},setTimeout(cb){timers.push(cb);}});return{submit,run(options){let result;middleware(options,x=>{result=x;});return result;},cleanup(){timers.forEach(cb=>cb());}};}
const base={method:'POST',path:'rankmath/v1/updateMeta',data:{objectID:2488,objectType:'post',meta:{rank_math_seo_score:6,rank_math_description:'preserve'}}};
let f=fixture();assert.equal(f.run(base),base);tests++;f.submit();let marked=f.run(base);assert.equal(marked.data.finelevate360_publication.post_id,2488);tests++;assert.equal(marked.data.finelevate360_publication.expected_hash,'fixture-hash');tests++;assert.equal(marked.data.meta,base.data.meta);tests++;assert.equal(base.data.finelevate360_publication,undefined);tests++;assert.equal(f.run(base),base);tests++;f.submit();assert.equal(f.run(base),base);tests++;
f=fixture();f.submit();f.cleanup();assert.equal(f.run(base),base);tests++;
f=fixture(false);f.submit();assert.equal(f.run(base),base);tests++;
for(const changed of [{method:'GET'},{path:'rankmath/v1/updateSchemas'},{data:{...base.data,objectID:2487}},{data:{...base.data,objectType:'term'}}]){f=fixture();f.submit();let input={...base,...changed};assert.equal(f.run(input),input);tests++;f.cleanup();assert.equal(f.run(base),base);tests++;}
f=fixture();f.submit();assert.ok(f.run({...base,path:'/rankmath/v1/updateMeta'}).data.finelevate360_publication);tests++;
console.log(`Per-submit publication context: ${tests} assertions passed; simulated DOM/event/apiFetch boundary.`);
