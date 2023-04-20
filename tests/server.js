import express from 'express';
import { ParseServer } from 'parse-server';
import path from 'path';
import emailAdapter from './MockEmailAdapter.js';
const app = express();
const __dirname = path.resolve();

const server = new ParseServer({
  appName: "MyTestApp",
  appId: "app-id-here",
  masterKey: "master-key-here",
  restKey: "rest-api-key-here",
  databaseURI: "mongodb://localhost/test",
  cloud: __dirname + "/tests/cloud-code.js",
  publicServerURL: "http://localhost:1337/parse",
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
  fileUpload: {
    enableForPublic: true,
    enableForAnonymousUser: true,
    enableForAuthenticatedUser: true,
  },
});

await server.start();

// Serve the Parse API on the /parse URL prefix
app.use('/parse', server.app);

const port = 1337;
app.listen(port, function() {
  console.error('[ parse-server-test running on port ' + port + ' ]');
});
