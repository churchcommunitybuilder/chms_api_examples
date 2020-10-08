require('dotenv').config();
const nodeFetch = require('node-fetch');
const express = require('express');
const open = require('open');
const lowdb = require('lowdb')
const FileSync = require('lowdb/adapters/FileSync')

const port = process.env.PORT || 8080;
const dbFile = process.env.DB_FILE || 'db.json';
const clientId = process.env.CLIENT_ID;
const clientSecret = process.env.CLIENT_SECRET;

const adapter = new FileSync(dbFile)
const db = lowdb(adapter)
const app = express();

app.set('view engine', 'pug');

const fetch = (...args) => {
  console.log('API CALL', ...args);
  return nodeFetch(...args);
}

const postJson = (url, data) => (
  fetch(url, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json'
    },
    body: JSON.stringify(data),
  })
)

// index route
app.get('/', async (req, res) => {
  // if we dont have an access token then we should
  // start the oauth process with a redirect to chms oauth
  const token = await db.get('token');
  res.render('index');
})

// this route is hit as a redirect
// back from the chms oauth process
// here we exchange the short term `code`
// for a access/refresh token pair
app.get('/auth', async (req, res) => {
  const result = await postJson('https://api.ccbchurch.com/oauth/token', {
    code: req.params.code,
    client_id: clientId,
    client_secret: clientSecret,
    subdomain: 'stable',
    grant_type: 'authorization_code'
  });

  if (result.ok) {
    const data = await result.json();
    await db.set('token', {
      accessToken: data.access_token,
      refreshToken: data.refresh_token,
    }).write();
    res.redirect('/');
  } else {
    res.send(await result.text());
    res.status(500);
    res.end();
  }
})

// initialize the application
(async function() {
  // setup the simple datastore for the access/refresh tokens
  await db.defaults({
    token: {
      accessToken: null,
      refreshToken: null,
      expiration: null,
    },
  }).write();

  // start the app
  app.listen(port, () => {
    console.log(`App running on http://localhost:${port}`);
  });
})
