require('dotenv').config();
const querystring = require('querystring');
const nodeFetch = require('node-fetch');
const express = require('express');
const open = require('open');
const lowdb = require('lowdb')
const FileSync = require('lowdb/adapters/FileSync')

const port = process.env.PORT || 8080;
const dbFile = process.env.DB_FILE || 'db.json';
const clientId = process.env.CLIENT_ID;
const clientSecret = process.env.CLIENT_SECRET;
const subdomain = process.env.SUBDOMAIN;

const adapter = new FileSync(dbFile)
const db = lowdb(adapter)
const app = express();

// setup the simple datastore for the access/refresh tokens
db.defaults({
  token: {
    accessToken: null,
    refreshToken: null,
    expiration: null,
  },
}).write();

app.set('view engine', 'pug');

const fetch = (...args) => {
  console.log('API CALL', args[0], {
    ...args[1],
    body: args[1].body ? JSON.parse(args[1].body) : undefined
  });
  return nodeFetch(...args);
}

const postJson = (url, data) => (
  fetch(url, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'Accept': 'application/vnd.ccbchurch.v2+json',
    },
    body: JSON.stringify(data),
  })
)

// index route
app.get('/', async (req, res) => {
  // if we dont have an access token then we should
  // start the oauth process with a redirect to chms oauth
  const token = db.get('token');

  if (!token.accessToken) {
    console.log('No access token, redirecting to auth')
    const query = querystring.stringify({
      client_id: clientId,
      response_type: 'code',
      redirect_uri: 'http://localhost:8080/auth',
      subdomain,
    });
    const redirectUrl = `https://oauth.ccbchurch.com/oauth/authorize?${query}`;
    console.log(redirectUrl)
    res.redirect(redirectUrl);
  } else {
    console.log('Access token exists!')
    res.render('index');
  }
})

// this route is hit as a redirect
// back from the chms oauth process
// here we exchange the short term `code`
// for a access/refresh token pair
app.get('/auth', async (req, res) => {
  console.log('Redirect returned with code', req.query.code);
  console.log('Attempting to get access token');
  const result = await postJson('https://api.ccbchurch.com/oauth/token', {
    grant_type: 'authorization_code',
    subdomain,
    client_id: clientId,
    client_secret: clientSecret,
    redirect_uri: 'http://localhost:8080/auth',
    code: req.query.code,
  });

  console.log('RESPONSE', {
    url: result.url,
    status: result.status,
    statusText: result.statusText,
  });

  if (result.ok) {
    const data = await result.json();
    console.log('OK', data)
    await db.set('token', {
      accessToken: data.access_token,
      refreshToken: data.refresh_token,
    }).write();
    res.redirect('/');
  } else {
    const data = await result.json();
    console.log('ERROR', data)
    res.status(500);
    res.end();
  }
})

// start the app
app.listen(port, () => {
  console.log(`App running on http://localhost:${port}`);
});
