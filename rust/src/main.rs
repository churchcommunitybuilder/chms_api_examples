#[macro_use]
extern crate dotenv_codegen;
extern crate dotenv;

mod db;
mod utils;

use db::TokenDatabase;
use reqwest::Url;
use serde::{Deserialize, Serialize};
use std::future::Future;
use std::pin::Pin;
use tide::utils::After;
use tide::{Body, Next, Redirect, Request, Response, Result};

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

#[derive(Deserialize, Serialize)]
struct RefreshTokenBody {
  refresh_token: String,
  grant_type: String,
  client_id: String,
  client_secret: String,
}

fn perform_refresh(db: &TokenDatabase) -> Result<String> {
  let env = utils::env::get_env();
  let body = RefreshTokenBody {
    refresh_token: db.get_refresh_token().unwrap(),
    grant_type: String::from("refresh_token"),
    client_id: env.client_id,
    client_secret: env.client_secret,
  };

  let response = utils::api::post::<RefreshTokenBody, TokenResponse>(
    &utils::api::make_api_url("oauth/token"),
    &body,
  )?;

  db.set_tokens(&response.access_token, &response.refresh_token)?;

  Ok(response.access_token)
}

async fn api_passthrough_handler(request: Request<TokenDatabase>) -> tide::Result<Response> {
  let path = request.param("path")?;

  match request.ext() {
    None => Ok(Response::from("Token Missing")),
    Some(token) => {
      let url = &utils::api::make_api_url(path);

      let response = utils::api::get::<serde_json::Value>(url, token)?;
      let mut json = response.0;

      if response.1 == reqwest::StatusCode::UNAUTHORIZED {
        let refreshed_token = perform_refresh(request.state())?;
        json = utils::api::get::<serde_json::Value>(url, &refreshed_token)?.0;
      }

      return Ok(Response::from(Body::from_json(&json)?));
    }
  }
}

async fn auth_code_handler(req: Request<TokenDatabase>) -> tide::Result {
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

  req
    .state()
    .set_tokens(&response.access_token, &response.refresh_token)?;

  Ok(Redirect::new("/api/individuals").into())
}

fn access_token_middleware<'a>(
  mut request: Request<TokenDatabase>,
  next: Next<'a, TokenDatabase>,
) -> Pin<Box<dyn Future<Output = Result> + Send + 'a>> {
  Box::pin(async {
    if request.url().path() == "/auth" {
      return Ok(next.run(request).await);
    }

    match request.state().get_access_token() {
      Some(access_token) => {
        tide::log::trace!("access token found", { access_token: access_token });
        request.set_ext(access_token);

        return Ok(next.run(request).await);
      }
      None => {
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

        Ok(Redirect::new(url).into())
      }
    }
  })
}

#[async_std::main]
async fn main() -> tide::Result<()> {
  let mut app = tide::with_state(TokenDatabase::default());

  app.with(access_token_middleware);

  app.with(After(|mut res: Response| async {
    if let Some(err) = res.take_error() {
      let msg = format!("Error: {:?}", err);
      res.set_status(tide::StatusCode::InternalServerError);
      res.set_body(msg);
    };

    Ok(res)
  }));

  app.at("/auth").get(auth_code_handler);
  app.at("api/*path").get(api_passthrough_handler);
  app.listen("127.0.0.1:3000").await?;

  Ok(())
}
