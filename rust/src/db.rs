extern crate rustbreak;
use rustbreak::error::Result;
use rustbreak::{deser::Ron, FileDatabase};
use std::collections::HashMap;
use std::path::Path;

fn get_db() -> Result<FileDatabase<HashMap<String, String>, Ron>> {
  FileDatabase::<HashMap<String, String>, Ron>::load_from_path_or_default(Path::new(
    "./db_data.ron",
  ))
}

fn read(key: &str) -> Option<String> {
  let db = match get_db() {
    Ok(db) => db,
    Err(_) => return None,
  };

  let data = match db.borrow_data() {
    Ok(data) => data,
    Err(_) => return None,
  };

  let value = match data.get(key) {
    Some(data) => Some(data.to_string()),
    _ => None,
  };

  value
}

fn write(key: &str, value: &String) -> Result<()> {
  let db = get_db()?;

  match db.write(|db| {
    db.insert(String::from(key), value.into());
  }) {
    Ok(()) => {
      db.save()?;

      Ok(())
    }
    Err(e) => Err(e),
  }
}

#[derive(Clone, Default, Debug)]
pub struct TokenDatabase;

impl TokenDatabase {
  pub fn get_access_token(&self) -> Option<String> {
    read("access_token")
  }

  pub fn get_refresh_token(&self) -> Option<String> {
    read("refresh_token")
  }

  pub fn set_tokens(&self, access_token: &String, refresh_token: &String) -> Result<()> {
    write("access_token", access_token)?;
    write("refresh_token", refresh_token)?;

    Ok(())
  }
}
