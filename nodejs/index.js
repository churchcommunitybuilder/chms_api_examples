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
  accessToken: null,
  refreshToken: null,
  tokenExpiration: null,
}).write();

app.set('view engine', 'pug');

// helper to get current time as timestamp
const getNow = () => Math.floor((new Date()).getTime() / 1000);

// helper for api calls
const fetch = async (...args) => {
  console.log('API CALL', args[0], {
    ...args[1],
    body: args[1].body ? JSON.parse(args[1].body) : undefined
  });
  const response = await nodeFetch(...args);

  const data = await response.json()

  if (!response.ok) {
    console.log('API RESPONSE ERROR', args[0], data);
    throw data;
  }

  console.log('API RESPONSE OK', args[0], data);

  return data;
}

// helper for posting to ccb api
const postJson = (url, data, accessToken = '') => (
  fetch(url, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'Accept': 'application/vnd.ccbchurch.v2+json',
      'Authorization': `Bearer ${accessToken}`
    },
    body: JSON.stringify(data),
  })
)

// helper for getting from ccb api
const getJson = (url, accessToken = '') => (
  fetch(url, {
    method: 'GET',
    headers: {
      'Accept': 'application/vnd.ccbchurch.v2+json',
      'Authorization': `Bearer ${accessToken}`
    },
  })
)

// helper to store the token in our db
const storeTokenResult = async (token) => {
  const now = getNow();
  await db.set('accessToken', token.access_token).write();
  await db.set('refreshToken', token.refresh_token).write();
  await db.set('tokenExpiration', token.expires_in + now).write();
}

// helper to create a token from the refresh token
const refreshApiToken = async () => {
  const refreshToken = db.get('refreshToken').value();
  const data = await postJson('https://api.ccbchurch.com/oauth/token', {
    grant_type: 'refresh_token',
    client_id: clientId,
    client_secret: clientSecret,
    refresh_token: refreshToken,
  });

  // we successfully got an access token
  // now store it, the refresh token and
  // the expiration for use later
  await storeTokenResult(data);
}

// helper to create a new access token from the auth code
const createTokenFromAuthCode = async (code) => {
  const data = await postJson('https://api.ccbchurch.com/oauth/token', {
    grant_type: 'authorization_code',
    subdomain,
    client_id: clientId,
    client_secret: clientSecret,
    redirect_uri: 'http://localhost:8080/auth',
    code,
  });

  // we successfully got an access token
  // now store it, the refresh token and
  // the expiration for use later
  await storeTokenResult(data);
}

const buildAuthRedirectUrl = () => {
  const query = querystring.stringify({
    client_id: clientId,
    response_type: 'code',
    redirect_uri: 'http://localhost:8080/auth',
    subdomain,
  });
  return `https://oauth.ccbchurch.com/oauth/authorize?${query}`;
}

// index route
app.get('/', async (req, res) => {
  // if we dont have an access token then we should
  // start the oauth process with a redirect to chms oauth
  const accessToken = db.get('accessToken').value();

  if (!accessToken) {
    // if we dont have an access token
    // then redirect to ccb auth page so user
    // can authenticate with ccb api
    console.log('No access token, redirecting to auth')
    const redirectUrl = buildAuthRedirectUrl()
    console.log(redirectUrl)
    res.redirect(redirectUrl);
  } else {
    // if we do  have an access token then
    // make sure its not expired before we
    // attempt to use it
    const expiration = db.get('tokenExpiration').value();
    const now = getNow();
    if (now >= expiration) {
      console.log('token has expired, getting new accessToken');
      await refreshApiToken();
    }

    // Finally make a call to get a list of individuals
    // in the church
    const accessToken = db.get('accessToken').value();
    const individuals = await getJson('https://api.ccbchurch.com/individuals', accessToken);
    res.render('index', {
      individuals,
    });
  }
})

// this route is hit as a redirect
// back from the chms oauth process
// here we exchange the short term `code`
// for a access/refresh token pair
app.get('/auth', async (req, res) => {
  console.log('Redirect returned with code', req.query.code);
  console.log('Attempting to get access token');

  await createTokenFromAuthCode(req.query.code);

  console.log('token creation successful! redirecting to index')

  res.redirect('/');
})

// start the app
app.listen(port, () => {
  console.log(`App running on http://localhost:${port}`);
});
