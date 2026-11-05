'use strict';
const test=require('node:test'),assert=require('node:assert/strict');
const {formatReceipt,formatDrawerPulse,wrap}=require('../lib/receipt-formatter');
const profile={paperColumns:42};
test('formats receipt data into ESC/POS bytes',()=>{const b=formatReceipt({company:'Example Store',userlog:'Cashier',docno:'CS-1001',items:[{description:'Milk 500ml',quantity:2,unitPrice:70,lineTotal:140}],totalVAT:0,total:140,cash:200,change:60,paymentMethod:'CASH'},profile);assert.ok(Buffer.isBuffer(b));assert.ok(b.includes(Buffer.from('CS-1001')));assert.ok(b.includes(Buffer.from('Milk 500ml')));});
test('rejects missing items and invalid quantities',()=>{assert.throws(()=>formatReceipt({docno:'CS-1',items:[]},profile),/at least one/);assert.throws(()=>formatReceipt({docno:'CS-2',items:[{description:'Item',quantity:-1,unitPrice:2,lineTotal:2}],total:2},profile),/quantity/);});
test('wraps descriptions to paper width',()=>assert.ok(wrap('A long product description for receipt printing',16).every(x=>x.length<=16)));
test('formats configured drawer pulse',()=>assert.deepEqual(Array.from(formatDrawerPulse({drawerPin:1,drawerOnTime:20,drawerOffTime:200})),[27,112,1,20,200]));
