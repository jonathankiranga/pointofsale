'use strict';
const net=require('node:net');
class NetworkEscposAdapter{
 constructor(profile){this.profile=profile;}
 write(buffer){const host=String(this.profile.host||''),port=Number(this.profile.port||9100);if(!host||!Number.isInteger(port)||port<1||port>65535)return Promise.reject(new Error('Network printer profile requires a valid host and TCP port.'));return new Promise((resolve,reject)=>{const socket=new net.Socket();let settled=false,accepted=false;const finish=e=>{if(settled)return;settled=true;socket.destroy();if(e)reject(e);else resolve({delivery:'tcp_socket_write_accepted',host,port,bytes:buffer.length});};socket.setTimeout(Number(this.profile.timeoutMs||10000));socket.once('error',finish);socket.once('timeout',()=>finish(new Error('Timed out connecting to '+host+':'+port)));socket.connect(port,host,()=>socket.write(buffer,e=>{if(e)return finish(e);accepted=true;socket.end();}));socket.once('close',hadError=>{if(hadError)finish(new Error('Printer connection closed with an error.'));else if(accepted)finish();else finish(new Error('Connection closed before printer data was accepted.'));});});}
 print(b){return this.write(b);}openDrawer(b){return this.write(b);}
}
module.exports=NetworkEscposAdapter;
