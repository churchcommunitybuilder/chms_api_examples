#[macro_use]
extern crate dotenv_codegen;
extern crate dotenv;

use reqwest::{Url, Error};
use serde::{Deserialize, Serialize};
use tide::utils::After;
use tide::{Body, Redirect, Request, Response, StatusCode};
use serde::de::DeserializeOwned;

struct Env {
  client_id: String,
  client_secret: String,
  subdomain: String,
}

#[derive(Deserialize, Serialize)]
struct TokenResponse {
  access_token: String,
  token_type: String,
  expires_in: u32,
  scope: String,
  refresh_token: String,
}

#[derive(Deserialize)]
struct AuthCodeParams {
  code: String,
}

#[derive(Deserialize, Serialize)]
struct AuthCodeBody {
  grant_type: String,
  subdomain: String,
  redirect_uri: String,
  client_id: String,
  client_secret: String,
  code: String,
}

const AUTH_CODE_URL: &str = "https://oauth.ccbchurch.com/oauth/authorize";
const TOKEN_URL: &str = "https://api.ccbchurch.com/oauth/token";
const REDIRECT_URL: &str = "http://localhost:3000/auth";

fn get_env() -> Env {
  Env {
    client_id: String::from(dotenv!("CLIENT_ID")),
    client_secret: String::from(dotenv!("CLIENT_SECRET")),
    subdomain: String::from(dotenv!("SUBDOMAIN")),
  }
}

fn post_json<T: Serialize, R: DeserializeOwned>(json_body: &T) -> Result<R, Error> {
  let json_response = reqwest::blocking::Client::new()
    .post(TOKEN_URL)
    .json(&json_body)
    .header("Content-Type", "application/json")
    .header("Accept", "application/vnd.ccbchurch.v2+json")
    .send()?
    .json::<R>()?;

    Ok(json_response)
}

async fn index(_: Request<()>) -> tide::Result {
  let env = get_env();
  let url = Url::parse_with_params(
    AUTH_CODE_URL,
    &[
      ("client_id", env.client_id),
      ("subdomain", env.subdomain),
      ("redirect_uri", REDIRECT_URL.into()),
      ("response_type", String::from("code")),
    ],
  )?;

  Ok(Redirect::new(url).into())
}

async fn handle_auth_code(req: Request<()>) -> tide::Result<Body> {
  let env = get_env();
  let params: AuthCodeParams = req.query()?;
  let body = AuthCodeBody {
    grant_type: String::from("authorization_code"),
    redirect_uri: REDIRECT_URL.into(),
    subdomain: env.subdomain.into(),
    client_id: env.client_id.into(),
    client_secret: env.client_secret.into(),
    code: params.code,
  };
  let token_response = post_json::<AuthCodeBody, TokenResponse>(&body)?;

  Ok(Body::from_json(&token_response)?)
}

#[async_std::main]
async fn main() -> tide::Result<()> {
  let mut app = tide::new();

  app.with(After(|mut res: Response| async {
    if let Some(err) = res.take_error() {
      let msg = format!("Error: {:?}", err);
      res.set_status(StatusCode::InternalServerError);
      res.set_body(msg);

    };

    Ok(res)
  }));

  app.at("/").get(index);
  app.at("/auth").get(handle_auth_code);
  app.listen("127.0.0.1:3000").await?;

  Ok(())
}
