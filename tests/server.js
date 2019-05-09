const path = require('path');
const express = require('express');
const ParseServer = require('parse-server').ParseServer;
const app = express();

const api = new ParseServer({
  appName: "MyTestApp",
  appId: "app-id-here",
  masterKey: "master-key-here",
  databaseURI: "mongodb://localhost/test",

  cloud: __dirname + "/cloud-code.js",
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

  emailAdapter: {
    module: "@parse/simple-mailgun-adapter",
    options: {
      apiKey: "not-a-real-api-key",
      domain: "example.com",
      fromAddress: "example@example.com"
    }
  },

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
    classNames: [
      "TestObject",
      "_User"
    ]
  }
});

// Serve the Parse API on the /parse URL prefix
app.use('/parse', api);

const port = 1337;
app.listen(port, function() {
  console.error('[ parse-server-test running on port ' + port + ' ]');
});
