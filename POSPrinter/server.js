'use strict';
const http=require('node:http'),fs=require('node:fs'),path=require('node:path'),crypto=require('node:crypto');
const {PrinterManager}=require('./lib/printer-manager');
const configPath=path.join(__dirname,'config.json');
if(!fs.existsSync(configPath)){console.error('Copy config.example.json to config.json and configure a printer.');process.exit(1);}
let config;try{config=JSON.parse(fs.readFileSync(configPath,'utf8'));}catch(e){console.error('Invalid config.json: '+e.message);process.exit(1);}
const host=config.serviceHost||'127.0.0.1',port=Number(config.servicePort||3000),token=String(config.apiToken||'');
const origins=Array.isArray(config.allowedOrigins)?config.allowedOrigins:[];
if(token.length<24||token==='REPLACE_WITH_A_LONG_RANDOM_SECRET'){console.error('Set apiToken to a random secret of at least 24 characters.');process.exit(1);}
if(!Number.isInteger(port)||port<1||port>65535){console.error('Invalid servicePort.');process.exit(1);}
const manager=new PrinterManager(config),maxBytes=Number(config.maxRequestBytes||1048576);
function reply(res,status,data,origin){const h={'Content-Type':'application/json; charset=utf-8','Cache-Control':'no-store','X-Content-Type-Options':'nosniff'};if(origin){h['Access-Control-Allow-Origin']=origin;h.Vary='Origin';}res.writeHead(status,h);res.end(JSON.stringify(data));}
function originFor(req){const o=req.headers.origin;if(!o)return '';if(origins.includes('*'))return o;return origins.includes(o)?o:null;}
function auth(req){const supplied=String(req.headers['x-print-token']||'');return supplied.length===token.length&&crypto.timingSafeEqual(Buffer.from(supplied),Buffer.from(token));}
function body(req){return new Promise((resolve,reject)=>{let n=0,parts=[];req.on('data',c=>{n+=c.length;if(n>maxBytes){reject(Object.assign(new Error('Request body too large.'),{statusCode:413}));req.destroy();}else parts.push(c);});req.on('end',()=>{try{resolve(JSON.parse(Buffer.concat(parts).toString('utf8')||'{}'));}catch(_){reject(Object.assign(new Error('Request body must be valid JSON.'),{statusCode:400}));}});req.on('error',reject);});}
const server=http.createServer(async(req,res)=>{
 const origin=originFor(req);if(origin===null)return reply(res,403,{success:false,message:'Browser origin is not allowed.'});
 if(req.method==='OPTIONS'){res.writeHead(204,{'Access-Control-Allow-Origin':origin||'null','Access-Control-Allow-Methods':'GET, POST, OPTIONS','Access-Control-Allow-Headers':'Content-Type, X-Print-Token','Access-Control-Max-Age':'600',Vary:'Origin'});return res.end();}
 const route=new URL(req.url,'http://localhost').pathname;
 if(req.method==='GET'&&route==='/health')return reply(res,200,{success:true,service:'POS Print Agent',version:'1.0.0',printers:manager.health()},origin);
 if(!auth(req))return reply(res,401,{success:false,message:'Invalid or missing print-agent token.'},origin);
 if(req.method==='GET'&&route==='/api/printers')return reply(res,200,{success:true,printers:manager.health()},origin);
 if(req.method!=='POST'||!['/print-receipt','/api/print','/open-drawer','/api/open-drawer','/api/test-print'].includes(route))return reply(res,404,{success:false,message:'Endpoint not found.'},origin);
 try{const data=await body(req);if(route==='/open-drawer'||route==='/api/open-drawer'){const result=await manager.openDrawer(data.printerId);return reply(res,200,{success:true,result},origin);}
 if(route==='/api/test-print'){const result=await manager.testPrint(data.printerId);return reply(res,200,{success:true,result},origin);}
 const result=await manager.printReceipt(data);return reply(res,200,{success:true,message:'Print job handed to adapter.',documentno:result.documentno,printerId:result.printerId,delivery:result.delivery,bytes:result.bytes,note:'Adapter acceptance does not confirm physical paper output.'},origin);
 }catch(e){console.error(new Date().toISOString(),e.message);return reply(res,e.statusCode||400,{success:false,message:e.message},origin);}
});
server.listen(port,host,()=>console.log('POS Print Agent listening on http://'+host+':'+port,'printers:',manager.health().map(p=>p.id).join(', ')||'none'));
function stop(){server.close(()=>process.exit(0));}process.on('SIGINT',stop);process.on('SIGTERM',stop);
