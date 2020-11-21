#[macro_use]
extern crate dotenv_codegen;
extern crate dotenv;

mod db;
mod utils;

use db::{TokenDatabase, TokenResponse};
use reqwest::Url;
use serde::{Deserialize, Serialize};
use std::future::Future;
use std::pin::Pin;
use tide::http::url::ParseError;
use tide::utils::After;
use tide::{Body, Next, Redirect, Request, Response, Result};
use utils::{api, env};

#[derive(Deserialize)]
struct AuthCodeParams {
  code: String,
  state: String,
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

fn build_auth_code_redirect_url(return_url: String) -> std::result::Result<Url, ParseError> {
  let env = env::get_env();

  Url::parse_with_params(
    api::AUTH_CODE_URL,
    &[
      ("client_id", env.client_id),
      ("subdomain", env.subdomain),
      ("redirect_uri", api::REDIRECT_URL.into()),
      ("response_type", String::from("code")),
      ("state", return_url),
    ],
  )
}

fn build_auth_code_body(code: String) -> AuthCodeBody {
  let env = env::get_env();

  AuthCodeBody {
    grant_type: String::from("authorization_code"),
    redirect_uri: api::REDIRECT_URL.into(),
    subdomain: env.subdomain,
    client_id: env.client_id,
    client_secret: env.client_secret,
    code,
  }
}

fn build_refresh_token_body(refresh_token: String) -> RefreshTokenBody {
  let env = env::get_env();

  RefreshTokenBody {
    refresh_token,
    grant_type: String::from("refresh_token"),
    client_id: env.client_id,
    client_secret: env.client_secret,
  }
}

fn perform_refresh(db: &TokenDatabase) -> Result<String> {
  let body = build_refresh_token_body(db.get_refresh_token().unwrap());
  let response =
    api::post::<RefreshTokenBody, TokenResponse>(&api::make_api_url("oauth/token"), &body)?;

  db.handle_token_response(&response)?;
  println!("Tokens refreshed.");

  Ok(response.access_token)
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
        request.set_ext(access_token);

        Ok(next.run(request).await)
      }
      None => {
        println!("Missing auth tokens. Redirecting to auth url.");
        let url_path = request.url().path();

        Ok(Redirect::new(build_auth_code_redirect_url(url_path.into())?).into())
      }
    }
  })
}

async fn auth_code_handler(req: Request<TokenDatabase>) -> tide::Result {
  let params: AuthCodeParams = req.query()?;
  let body = build_auth_code_body(params.code);
  let response =
    api::post::<AuthCodeBody, TokenResponse>(&api::make_api_url("oauth/token"), &body)?;
  req.state().handle_token_response(&response)?;
  println!("Auth code successfully exchanged for tokens.");

  Ok(Redirect::new(params.state).into())
}

async fn api_passthrough_handler(request: Request<TokenDatabase>) -> tide::Result<Response> {
  let path = request.param("path")?;

  match request.ext() {
    None => Ok(Response::from("Token Missing")),
    Some(token) => {
      let url = &api::make_api_url(path);

      let response = api::get::<serde_json::Value>(url, token)?;
      let mut json = response.0;

      if response.1 == reqwest::StatusCode::UNAUTHORIZED {
        let refreshed_token = perform_refresh(request.state())?;
        json = api::get::<serde_json::Value>(url, &refreshed_token)?.0;
      }

      return Ok(Response::from(Body::from_json(&json)?));
    }
  }
}

#[async_std::main]
async fn main() -> tide::Result<()> {
  let mut app = tide::with_state(TokenDatabase::new()?);

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
