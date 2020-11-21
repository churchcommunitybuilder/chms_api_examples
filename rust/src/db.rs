extern crate rustbreak;

use rustbreak::error::Result;
use rustbreak::{deser::Ron, FileDatabase};
use serde::{Deserialize, Serialize};
use std::collections::HashMap;
use std::path::Path;

type DBValue = String;
type DBReadResponse = Option<DBValue>;

#[derive(Deserialize, Serialize, Debug)]
pub struct TokenResponse {
  pub access_token: String,
  pub refresh_token: String,
  token_type: String,
  expires_in: u32,
  scope: String,
}

#[derive(Debug)]
pub struct TokenDatabase {
  db: FileDatabase<HashMap<String, DBValue>, Ron>,
}

fn build_token_database() -> Result<TokenDatabase> {
  let db = FileDatabase::<HashMap<String, DBValue>, Ron>::load_from_path_or_default(Path::new(
    "./db_data.ron",
  ))?;

  Ok(TokenDatabase { db })
}

impl Clone for TokenDatabase {
  fn clone(&self) -> TokenDatabase {
    build_token_database().unwrap()
  }
}

impl TokenDatabase {
  pub fn new() -> Result<TokenDatabase> {
    build_token_database()
  }

  fn read(&self, key: &str) -> DBReadResponse {
    match self.db.borrow_data() {
      Ok(data) => match data.get(key) {
        Some(data) => Some(data.to_string()),
        _ => None,
      },
      Err(_) => None,
    }
  }

  fn write(&self, key: &str, value: &String) -> Result<()> {
    let result = self.db.write(|db| {
      db.insert(key.into(), value.into());
    });

    if result.is_ok() {
      self.db.save()?;
    }

    result
  }

  pub fn get_access_token(&self) -> DBReadResponse {
    self.read("access_token")
  }

  pub fn get_refresh_token(&self) -> DBReadResponse {
    self.read("refresh_token")
  }

  pub fn handle_token_response(&self, tokens: &TokenResponse) -> Result<()> {
    self.write("access_token", &tokens.access_token)?;
    self.write("refresh_token", &tokens.refresh_token)?;

    Ok(())
  }
}
