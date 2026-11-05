'use strict';
const test=require('node:test');
const assert=require('node:assert/strict');
const {PrinterManager}=require('../lib/printer-manager');

test('loads multiple configured printer profiles and default',()=>{
 const manager=new PrinterManager({defaultPrinterId:'front',printers:[
  {id:'front',adapter:'network-escpos',host:'10.0.0.10',port:9100},
  {id:'back',adapter:'system-queue',queue:'ReceiptQueue'}
 ]});
 const health=manager.health();
 assert.equal(health.length,2);
 assert.equal(health.find(p=>p.id==='front').isDefault,true);
 assert.equal(health.find(p=>p.id==='back').configured,true);
});

test('supports the original single-printer config during migration',()=>{
 const manager=new PrinterManager({printerHost:'192.168.1.25',printerPort:9100,paperColumns:42});
 assert.equal(manager.health()[0].id,'default');
 assert.equal(manager.health()[0].configured,true);
});

test('serializes jobs sent to the same printer',async()=>{
 const manager=new PrinterManager({printers:[{id:'front',adapter:'network-escpos',host:'127.0.0.1'}]});
 const order=[];
 const first=manager.enqueue('front',async()=>{order.push('first-start');await new Promise(r=>setTimeout(r,20));order.push('first-end');});
 const second=manager.enqueue('front',async()=>{order.push('second');});
 await Promise.all([first,second]);
 assert.deepEqual(order,['first-start','first-end','second']);
});

test('rejects an unknown adapter explicitly',()=>{
 const manager=new PrinterManager({printers:[{id:'x',adapter:'unknown-driver'}]});
 assert.throws(()=>manager.getAdapter(manager.getProfile('x')),/Unsupported printer adapter/);
});
