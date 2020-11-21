use reqwest::Result;
use serde::de::DeserializeOwned;
use serde::Serialize;

pub fn make_api_url(url: &str) -> String {
  format!("https://api.ccbchurch.com/{}", url)
}

pub const AUTH_CODE_URL: &str = "https://oauth.ccbchurch.com/oauth/authorize";
pub const REDIRECT_URL: &str = "http://localhost:3000/auth";

pub fn get<R: DeserializeOwned>(url: &String, token: &String) -> Result<(R, reqwest::StatusCode)> {
  let response = reqwest::blocking::Client::new()
    .get(url)
    .header("Content-Type", "application/json")
    .header("Accept", "application/vnd.ccbchurch.v2+json")
    .header("Authorization", format!("Bearer {}", token))
    .send()?;

  let status = response.status();
  let json = response.json::<R>()?;

  Ok((json, status))
}

pub fn post<T: Serialize, R: DeserializeOwned>(url: &String, json_body: &T) -> Result<R> {
  let json_response = reqwest::blocking::Client::new()
    .post(url)
    .json(&json_body)
    .header("Content-Type", "application/json")
    .header("Accept", "application/vnd.ccbchurch.v2+json")
    .send()?
    .json::<R>()?;

  Ok(json_response)
}
