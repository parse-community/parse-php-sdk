import express from 'express';
import { ParseServer } from 'parse-server';
import path from 'path';
import fs from 'fs';
import https from 'https';
import http from 'http';
import emailAdapter from './MockEmailAdapter.js';
const app = express();
const __dirname = path.resolve();

const parseServer = new ParseServer({
  appName: "MyTestApp",
  appId: "app-id-here",
  masterKey: "master-key-here",
  restKey: "rest-api-key-here",
  databaseURI: "mongodb://localhost/test",
  cloud: __dirname + "/tests/cloud-code.js",
  publicServerURL: "https://localhost:1337/parse",
  logsFolder: path.resolve(process.cwd(), 'logs'),
  verbose: true,
  silent: true,
  push: {
    android: {
      senderId: "blank-sender-id",
      apiKey: "not-a-real-api-key"
    }
  },
  emailAdapter: emailAdapter({
    apiKey: 'not-a-real-api-key',
    domain: 'example.com',
    fromAddress: 'example@example.com',
  }),
  auth: {
    twitter: {
      consumer_key: "not_a_real_consumer_key",
      consumer_secret: "not_a_real_consumer_secret"
    },
    facebook: {
      appIds: "not_a_real_facebook_app_id"
    }
  },
  liveQuery: {
    classNames: ["TestObject", "_User"],
  },
  fileUpload: {
    enableForPublic: true,
    enableForAnonymousUser: true,
    enableForAuthenticatedUser: true,
  },
});

await parseServer.start();

// Serve the Parse API on the /parse URL prefix
app.use('/parse', parseServer.app);

const port = 1337;
app.listen(port, function() {
  console.error('[ Parse Test Http Server running on port ' + port + ' ]');
});

const options = {
  port:       process.env.PORT       || 1338,
  server_key: process.env.SERVER_KEY || __dirname + '/tests/keys/localhost.key',
  server_crt: process.env.SERVER_CRT || __dirname + '/tests/keys/localhost.crt',
  server_fp:  process.env.SERVER_FP  || __dirname + '/tests/keys/localhost.fp',
  client_key: process.env.CLIENT_KEY || __dirname + '/tests/keys/client.key',
  client_crt: process.env.CLIENT_CRT || __dirname + '/tests/keys/client.crt',
  client_fp:  process.env.CLIENT_FP  || __dirname + '/tests/keys/client.fp',
  ca:         process.env.TLS_CA     || __dirname + '/tests/keys/parseca.crt'
}

// Load fingerprints
const clientFingerprints = [fs.readFileSync(options.server_fp).toString().replace('\n', '')];

// Configure server
const serverOptions = {
  key: fs.readFileSync(options.server_key),
  cert: fs.readFileSync(options.server_crt),
  ca: fs.readFileSync(options.ca),
  requestCert: true,
  rejectUnauthorized: true
}

function onRequest(req) { 
  console.log(new Date()+' '+ 
              req.connection.remoteAddress+' '+ 
              req.socket.getPeerCertificate().subject.CN+' '+ 
              req.method+' '+req.baseUrl);
}

// Create TLS enabled server
const server = https.createServer(serverOptions, app);
server.on('request', onRequest);

// Start Server
server.listen(options.port, function() {
  console.error('[ Parse Test Https Server running on port ' + options.port + ' ]');
});

// Create TLS request
var requestOptions = {
  hostname: 'localhost',
  port: options.port,
  path: '/parse/health',
  method: 'GET',
  key: fs.readFileSync(options.client_key),
  cert: fs.readFileSync(options.client_crt),
  ca: fs.readFileSync(options.ca),
  requestCert: true,
  rejectUnauthorized: true,
  maxCachedSessions: 0,
  headers: {
    'Content-Type': 'application/json',
    'X-Parse-Application-Id': 'app-id-here',
    'X-Parse-Master-Key': 'master-key-here',
    'X-Parse-REST-API-Key': 'rest-api-key-here',
  }
};

// Create agent (required for custom trust list)
requestOptions.agent = new https.Agent(requestOptions);

var req = https.request(requestOptions, (res) => {
  console.log('statusCode:', res.statusCode);
});
req.end();

// Pin server certs
req.on('socket', socket => {
  socket.on('secureConnect', () => {
    var fingerprint = socket.getPeerCertificate().fingerprint;
 
    // Check if certificate is valid
    if(socket.authorized === false){
      req.emit('error', new Error(socket.authorizationError));
      return req.destroy();
    }

    // Check if fingerprint matches
    if(clientFingerprints.indexOf(fingerprint) === -1){
      req.emit('error', new Error('Fingerprint does not match'));
      return req.destroy();
    }
  });
});

req.on('error', (e) => {
  console.error(e);
  process.exit(0);
});
