#[macro_use]
extern crate dotenv_codegen;
extern crate dotenv;

use reqwest::Url;
use serde::{Deserialize, Serialize};
use tide::utils::After;
use tide::{Body, Redirect, Request, Response, StatusCode};

mod utils;

#[derive(Deserialize, Serialize, Debug)]
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

static mut TOKENS: Option<TokenResponse> = None;
unsafe fn set_tokens(new_tokens: TokenResponse) {
  TOKENS = Some(new_tokens)
}

async fn index(_: Request<()>) -> tide::Result<Response> {
  let env = utils::env::get_env();
  let url = Url::parse_with_params(
    utils::api::AUTH_CODE_URL,
    &[
      ("client_id", env.client_id),
      ("subdomain", env.subdomain),
      ("redirect_uri", utils::api::REDIRECT_URL.into()),
      ("response_type", String::from("code")),
    ],
  )?;

  unsafe {
    match &TOKENS {
      Some(tk) => {
        let individuals_json =
          utils::api::get::<serde_json::Value>(&utils::api::make_api_url("individuals"), &tk.access_token)?;

        Ok(Response::from(Body::from_json(&individuals_json)?))
      }
      None => Ok(Redirect::new(url).into()),
    }
  }
}

async fn handle_auth_code(req: Request<()>) -> tide::Result {
  let env = utils::env::get_env();
  let params: AuthCodeParams = req.query()?;
  let body = AuthCodeBody {
    grant_type: String::from("authorization_code"),
    redirect_uri: utils::api::REDIRECT_URL.into(),
    subdomain: env.subdomain.into(),
    client_id: env.client_id.into(),
    client_secret: env.client_secret.into(),
    code: params.code,
  };

  let response = utils::api::post::<AuthCodeBody, TokenResponse>(
    &utils::api::make_api_url("oauth/token"),
    &body,
  )?;

  unsafe {
    set_tokens(response);
  }

  Ok(Redirect::new("/").into())
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
