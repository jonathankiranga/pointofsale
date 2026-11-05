'use strict';
const path=require('node:path');
const {formatReceipt,formatDrawerPulse}=require('./receipt-formatter');
const NetworkAdapter=require('./adapters/network-escpos');
class PrinterManager{
 constructor(config){this.config=config||{};this.profiles=new Map();this.adapters=new Map();this.queues=new Map();const list=Array.isArray(this.config.printers)?this.config.printers:[];list.forEach(p=>{if(!p.id||!p.adapter)throw new Error('Every printer profile needs id and adapter.');if(this.profiles.has(p.id))throw new Error('Duplicate printer id: '+p.id);this.profiles.set(p.id,Object.assign({},p));});
 // Support old config.json files used by the first POS Print Agent version.
 if(!this.profiles.size&&this.config.printerHost)this.profiles.set('default',{id:'default',name:'Default printer',adapter:'network-escpos',host:this.config.printerHost,port:this.config.printerPort||9100,paperColumns:this.config.paperColumns||42,cut:true});
 this.defaultPrinterId=this.config.defaultPrinterId||(this.profiles.size?this.profiles.keys().next().value:null);}
 getProfile(id){const p=this.profiles.get(id||this.defaultPrinterId);if(!p)throw new Error('Printer profile not found. Configure defaultPrinterId or provide a valid printerId.');return p;}
 getAdapter(p){if(this.adapters.has(p.id))return this.adapters.get(p.id);let a;if(p.adapter==='network-escpos')a=new NetworkAdapter(p);else if(p.adapter==='system-queue')a=new(require('./adapters/system-queue'))(p);else if(p.adapter==='plugin'){const root=path.resolve(__dirname,'..','plugins'),file=path.resolve(root,String(p.module||''));if(!p.module||!file.startsWith(root+path.sep))throw new Error('Plugin module must be inside POSPrinter/plugins.');const Plugin=require(file);a=typeof Plugin.createAdapter==='function'?Plugin.createAdapter(p):new Plugin(p);}else throw new Error('Unsupported printer adapter: '+p.adapter);if(!a||typeof a.print!=='function')throw new Error('Adapter must implement print(buffer).');this.adapters.set(p.id,a);return a;}
 enqueue(id,fn){const prev=this.queues.get(id)||Promise.resolve(),current=prev.catch(()=>{}).then(fn);this.queues.set(id,current);return current.finally(()=>{if(this.queues.get(id)===current)this.queues.delete(id);});}
 async printReceipt(data){const p=this.getProfile(data.printerId),a=this.getAdapter(p),buffer=formatReceipt(data,p),r=await this.enqueue(p.id,()=>a.print(buffer));return{documentno:data.docno,printerId:p.id,delivery:r&&r.delivery||'adapter_accepted',bytes:buffer.length};}
 async openDrawer(id){const p=this.getProfile(id);if(p.cashDrawer===false)throw new Error('Cash drawer is disabled for this printer.');const a=this.getAdapter(p);if(typeof a.openDrawer!=='function')throw new Error('This adapter does not support cash drawer control.');return this.enqueue(p.id,()=>a.openDrawer(formatDrawerPulse(p)));}
 async testPrint(id){const p=this.getProfile(id),a=this.getAdapter(p),b=Buffer.from('\x1B\x40POS PRINT AGENT TEST\nPrinter: '+(p.name||p.id)+'\nTime: '+new Date().toISOString()+'\n\n\n\x1D\x56\x00');return this.enqueue(p.id,()=>a.print(b));}
 health(){return Array.from(this.profiles.values()).map(p=>({id:p.id,name:p.name||p.id,adapter:p.adapter,connection:p.adapter==='network-escpos'?'network':p.adapter==='system-queue'?'os-queue':'plugin',isDefault:p.id===this.defaultPrinterId,configured:p.adapter==='network-escpos'?!!(p.host&&Number(p.port||9100)):p.adapter==='system-queue'?!!p.queue:true}));}
}
module.exports={PrinterManager};
